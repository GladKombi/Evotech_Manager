<?php
session_start();
require_once('../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide";
    header("Location: ../../Views/utilisateurs.php");
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
$hasPermission = in_array('DG', $userRoles) || in_array('ChefDepartement', $userRoles);

if (!$hasPermission) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires";
    header("Location: ../../Views/utilisateurs.php");
    exit();
}

try {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $actif = isset($_POST['actif']) ? 1 : 0;
    $roles = $_POST['roles'] ?? [];
    
    // Validation
    if (empty($nom) || empty($prenom)) {
        $_SESSION['error'] = "Le nom et le prénom sont obligatoires";
        header("Location: ../../Views/utilisateurs.php");
        exit();
    }
    
    if (empty($mot_de_passe) || strlen($mot_de_passe) < 8) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères";
        header("Location: ../../Views/utilisateurs.php");
        exit();
    }
    
    // Générer un email si non fourni
    if (empty($email)) {
        $email = strtolower(substr($prenom, 0, 1) . $nom) . '_' . time() . '@evotech.local';
    }
    
    // Vérifier si l'email existe déjà
    $checkEmail = $conn->prepare("SELECT utilisateur_id FROM UTILISATEURS WHERE email = ? AND statut = 0");
    $checkEmail->execute([$email]);
    
    if ($checkEmail->rowCount() > 0) {
        $_SESSION['error'] = "Cet email est déjà utilisé";
        header("Location: ../../Views/utilisateurs.php");
        exit();
    }
    
    // Hacher le mot de passe
    $password_hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);
    
    // Gérer la photo de profil
    $profil = null;
    if (isset($_FILES['profil']) && $_FILES['profil']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['profil']['type'], $allowedTypes)) {
            $_SESSION['error'] = "Format d'image non autorisé";
            header("Location: ../../Views/utilisateurs.php");
            exit();
        }
        
        if ($_FILES['profil']['size'] > $maxSize) {
            $_SESSION['error'] = "L'image est trop grande (max 2MB)";
            header("Location: ../../Views/utilisateurs.php");
            exit();
        }
        
        $uploadDir = '../../images/profil/utilisateurs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = pathinfo($_FILES['profil']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . time() . '_' . uniqid() . '.' . $ext;
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['profil']['tmp_name'], $uploadPath)) {
            $profil = $filename;
        }
    }
    
    // Insérer l'utilisateur
    $insert = $conn->prepare("
        INSERT INTO UTILISATEURS (nom, prenom, email, mot_de_passe_hash, profil, actif, date_creation)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if ($insert->execute([$nom, $prenom, $email, $password_hash, $profil, $actif])) {
        $user_id = $conn->lastInsertId();
        
        // Ajouter les rôles
        if (!empty($roles)) {
            $insertRole = $conn->prepare("
                INSERT INTO ATTRIBUTIONS (utilisateur_id, role_id, date_attribution)
                VALUES (?, ?, CURDATE())
            ");
            
            foreach ($roles as $role_id) {
                $role_id = intval($role_id);
                if ($role_id > 0) {
                    $insertRole->execute([$user_id, $role_id]);
                }
            }
        }
        
        $_SESSION['msg'] = "Utilisateur créé avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la création de l'utilisateur";
    }
    
} catch (PDOException $e) {
    error_log("Erreur insertion utilisateur: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement";
}

header("Location: ../../Views/utilisateurs.php");
exit();
?>
