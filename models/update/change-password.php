<?php
session_start();
require_once('../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide";
    header("Location: ../../Views/mon-profil.php");
    exit();
}

try {
    $user_id = intval($_POST['user_id'] ?? 0);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires";
        header("Location: ../../Views/mon-profil.php");
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Les nouveaux mots de passe ne correspondent pas";
        header("Location: ../../Views/mon-profil.php");
        exit();
    }
    
    if (strlen($new_password) < 8) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères";
        header("Location: ../../Views/mon-profil.php");
        exit();
    }
    
    // Vérifier la force du mot de passe
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $new_password)) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre";
        header("Location: ../../Views/mon-profil.php");
        exit();
    }
    
    // Récupérer l'utilisateur
    $getUser = $conn->prepare("
        SELECT mot_de_passe_hash FROM UTILISATEURS 
        WHERE utilisateur_id = ? AND statut = 0
    ");
    $getUser->execute([$user_id]);
    $user = $getUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "Utilisateur non trouvé";
        header("Location: ../../Views/mon-profil.php");
        exit();
    }
    
    // Vérifier le mot de passe actuel
    if (!password_verify($current_password, $user['mot_de_passe_hash'])) {
        $_SESSION['error'] = "Le mot de passe actuel est incorrect";
        header("Location: ../../Views/mon-profil.php");
        exit();
    }
    
    // Hacher le nouveau mot de passe
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    
    // Mettre à jour le mot de passe
    $update = $conn->prepare("
        UPDATE UTILISATEURS 
        SET mot_de_passe_hash = ?, last_password_change = NOW()
        WHERE utilisateur_id = ?
    ");
    
    if ($update->execute([$password_hash, $user_id])) {
        $_SESSION['msg'] = "Mot de passe mis à jour avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour du mot de passe";
    }
    
} catch (PDOException $e) {
    error_log("Erreur changement mot de passe: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue";
}

header("Location: ../../Views/mon-profil.php");
exit();
?>
