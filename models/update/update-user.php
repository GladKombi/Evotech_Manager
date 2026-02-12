<?php
require_once('../../connexion/connexion.php');

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
    $user_id = intval($_POST['utilisateur_id'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $actif = isset($_POST['actif']) ? 1 : 0;
    $roles = $_POST['roles'] ?? [];
    
    // Validation
    if (empty($user_id) || empty($nom) || empty($prenom)) {
        $_SESSION['error'] = "Les données obligatoires sont manquantes";
        header("Location: ../../Views/utilisateurs.php");
        exit();
    }
    
    // Vérifier que l'utilisateur existe
    $verify = $conn->prepare("SELECT utilisateur_id FROM UTILISATEURS WHERE utilisateur_id = ? AND statut = 0");
    $verify->execute([$user_id]);
    
    if ($verify->rowCount() === 0) {
        $_SESSION['error'] = "Cet utilisateur n'existe pas";
        header("Location: ../../Views/utilisateurs.php");
        exit();
    }
    
    // Si email fourni, vérifier s'il est utilisé par un autre utilisateur
    if (!empty($email)) {
        $checkEmail = $conn->prepare("
            SELECT utilisateur_id FROM UTILISATEURS 
            WHERE email = ? AND utilisateur_id != ? AND statut = 0
        ");
        $checkEmail->execute([$email, $user_id]);
        
        if ($checkEmail->rowCount() > 0) {
            $_SESSION['error'] = "Cet email est déjà utilisé par un autre utilisateur";
            header("Location: ../../Views/utilisateurs.php");
            exit();
        }
    }
    
    // Récupérer l'utilisateur actuel pour la photo
    $getCurrentUser = $conn->prepare("SELECT profil FROM UTILISATEURS WHERE utilisateur_id = ?");
    $getCurrentUser->execute([$user_id]);
    $currentUser = $getCurrentUser->fetch(PDO::FETCH_ASSOC);
    $profil = $currentUser['profil'];
    
    // Gérer la photo de profil
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
        
        // Supprimer l'ancienne photo
        if (!empty($profil) && file_exists($uploadDir . $profil)) {
            unlink($uploadDir . $profil);
        }
        
        $ext = pathinfo($_FILES['profil']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . time() . '_' . uniqid() . '.' . $ext;
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['profil']['tmp_name'], $uploadPath)) {
            $profil = $filename;
        }
    }
    
    // Construire la requête de mise à jour
    if (!empty($mot_de_passe)) {
        if (strlen($mot_de_passe) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères";
            header("Location: ../../Views/utilisateurs.php");
            exit();
        }
        
        $password_hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);
        
        if (!empty($email)) {
            $update = $conn->prepare("
                UPDATE UTILISATEURS 
                SET nom = ?, prenom = ?, email = ?, mot_de_passe_hash = ?, profil = ?, actif = ?
                WHERE utilisateur_id = ?
            ");
            $update->execute([$nom, $prenom, $email, $password_hash, $profil, $actif, $user_id]);
        } else {
            $update = $conn->prepare("
                UPDATE UTILISATEURS 
                SET nom = ?, prenom = ?, mot_de_passe_hash = ?, profil = ?, actif = ?
                WHERE utilisateur_id = ?
            ");
            $update->execute([$nom, $prenom, $password_hash, $profil, $actif, $user_id]);
        }
    } else {
        // Sans changement de mot de passe
        if (!empty($email)) {
            $update = $conn->prepare("
                UPDATE UTILISATEURS 
                SET nom = ?, prenom = ?, email = ?, profil = ?, actif = ?
                WHERE utilisateur_id = ?
            ");
            $update->execute([$nom, $prenom, $email, $profil, $actif, $user_id]);
        } else {
            $update = $conn->prepare("
                UPDATE UTILISATEURS 
                SET nom = ?, prenom = ?, profil = ?, actif = ?
                WHERE utilisateur_id = ?
            ");
            $update->execute([$nom, $prenom, $profil, $actif, $user_id]);
        }
    }
    
    // Mettre à jour les rôles
    // Désactiver les anciennes attributions
    $disableOld = $conn->prepare("
        UPDATE ATTRIBUTIONS SET statut = 1 WHERE utilisateur_id = ?
    ");
    $disableOld->execute([$user_id]);
    
    // Ajouter les nouvelles attributions
    if (!empty($roles)) {
        $insertRole = $conn->prepare("
            INSERT INTO ATTRIBUTIONS (utilisateur_id, role_id, statut, date_attribution)
            VALUES (?, ?, 0, CURDATE())
        ");
        
        foreach ($roles as $role_id) {
            $role_id = intval($role_id);
            if ($role_id > 0) {
                // Vérifier si l'attribution existe déjà (désactivée)
                $check = $conn->prepare("
                    SELECT attribution_id FROM ATTRIBUTIONS 
                    WHERE utilisateur_id = ? AND role_id = ?
                ");
                $check->execute([$user_id, $role_id]);
                
                if ($check->rowCount() > 0) {
                    // Réactiver
                    $reactivate = $conn->prepare("
                        UPDATE ATTRIBUTIONS 
                        SET statut = 0, date_attribution = CURDATE()
                        WHERE utilisateur_id = ? AND role_id = ?
                    ");
                    $reactivate->execute([$user_id, $role_id]);
                } else {
                    // Créer une nouvelle
                    $insertRole->execute([$user_id, $role_id]);
                }
            }
        }
    }
    
    $_SESSION['msg'] = "Utilisateur mis à jour avec succès";
    
} catch (PDOException $e) {
    error_log("Erreur mise à jour utilisateur: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour";
}

header("Location: ../../Views/utilisateurs.php");
exit();
?>
