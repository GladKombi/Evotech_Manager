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
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $signature = trim($_POST['signature'] ?? '');
    
    // Validation
    if (empty($user_id) || empty($nom) || empty($prenom)) {
        $_SESSION['error'] = "Les données obligatoires sont manquantes";
        header("Location: ../../Views/mon-profil.php");
        exit();
    }
    
    // Vérifier que l'utilisateur existe
    $verify = $conn->prepare("SELECT utilisateur_id FROM UTILISATEURS WHERE utilisateur_id = ? AND statut = 0");
    $verify->execute([$user_id]);
    
    if ($verify->rowCount() === 0) {
        $_SESSION['error'] = "Utilisateur non trouvé";
        header("Location: ../../Views/mon-profil.php");
        exit();
    }
    
    // Mettre à jour le profil
    $update = $conn->prepare("
        UPDATE UTILISATEURS 
        SET nom = ?, prenom = ?, signature = ?
        WHERE utilisateur_id = ?
    ");
    
    if ($update->execute([$nom, $prenom, $signature, $user_id])) {
        // Mettre à jour la session
        $_SESSION['nom'] = $nom;
        $_SESSION['prenom'] = $prenom;
        
        // Mettre à jour le téléphone si l'utilisateur est un agent
        if (!empty($telephone)) {
            // Essayer de mettre à jour le téléphone de l'agent associé
            $updateAgent = $conn->prepare("
                UPDATE AGENTS 
                SET telephone_professionnel = ?
                WHERE email_professionnel = (
                    SELECT email FROM UTILISATEURS WHERE utilisateur_id = ?
                )
            ");
            $updateAgent->execute([$telephone, $user_id]);
        }
        
        $_SESSION['msg'] = "Profil mis à jour avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour du profil";
    }
    
} catch (PDOException $e) {
    error_log("Erreur mise à jour profil: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour";
}

header("Location: ../../Views/mon-profil.php");
exit();
?>
