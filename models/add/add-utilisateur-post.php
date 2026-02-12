<?php
session_start();
require_once('../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['msg'] = "Token de sécurité invalide";
    header("Location: ../views/utilisateurs.php");
    exit();
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        // Ajouter un nouvel utilisateur
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        // Validation
        if ($password !== $confirm_password) {
            $_SESSION['msg'] = "Les mots de passe ne correspondent pas";
            header("Location: ../views/utilisateurs.php");
            exit();
        }
        
        if (strlen($password) < 6) {
            $_SESSION['msg'] = "Le mot de passe doit contenir au moins 6 caractères";
            header("Location: ../views/utilisateurs.php");
            exit();
        }
        
        // Vérifier si l'email existe déjà
        $check = $conn->prepare("SELECT utilisateur_id FROM UTILISATEURS WHERE email = ? AND statut = 0");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            $_SESSION['msg'] = "Cet email est déjà utilisé";
            header("Location: ../views/utilisateurs.php");
            exit();
        }
        
        // Hacher le mot de passe
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insérer le nouvel utilisateur
        $insert = $conn->prepare("
            INSERT INTO UTILISATEURS (nom, prenom, email, mot_de_passe_hash, actif) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($insert->execute([$nom, $prenom, $email, $password_hash, $actif])) {
            $_SESSION['msg'] = "Utilisateur créé avec succès";
        } else {
            $_SESSION['msg'] = "Erreur lors de la création de l'utilisateur";
        }
    }
    elseif ($action === 'edit') {
        // Modifier un utilisateur existant
        $user_id = intval($_POST['user_id']);
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        // Vérifier que l'utilisateur existe
        $verify = $conn->prepare("SELECT utilisateur_id FROM UTILISATEURS WHERE utilisateur_id = ? AND statut = 0");
        $verify->execute([$user_id]);
        
        if ($verify->rowCount() === 0) {
            $_SESSION['msg'] = "Cet utilisateur n'existe pas";
            header("Location: ../views/utilisateurs.php");
            exit();
        }
        
        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        $checkEmail = $conn->prepare("
            SELECT utilisateur_id 
            FROM UTILISATEURS 
            WHERE email = ? AND utilisateur_id != ? AND statut = 0
        ");
        $checkEmail->execute([$email, $user_id]);
        
        if ($checkEmail->rowCount() > 0) {
            $_SESSION['msg'] = "Cet email est déjà utilisé par un autre utilisateur";
            header("Location: ../views/utilisateurs.php");
            exit();
        }
        
        // Mise à jour de base
        $update = $conn->prepare("
            UPDATE UTILISATEURS 
            SET nom = ?, prenom = ?, email = ?, actif = ? 
            WHERE utilisateur_id = ?
        ");
        
        // Vérifier si un nouveau mot de passe est fourni
        if (!empty($_POST['password'])) {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($password !== $confirm_password) {
                $_SESSION['msg'] = "Les mots de passe ne correspondent pas";
                header("Location: ../views/utilisateurs.php");
                exit();
            }
            
            if (strlen($password) < 6) {
                $_SESSION['msg'] = "Le mot de passe doit contenir au moins 6 caractères";
                header("Location: ../views/utilisateurs.php");
                exit();
            }
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $update = $conn->prepare("
                UPDATE UTILISATEURS 
                SET nom = ?, prenom = ?, email = ?, actif = ?, mot_de_passe_hash = ? 
                WHERE utilisateur_id = ?
            ");
            
            if ($update->execute([$nom, $prenom, $email, $actif, $password_hash, $user_id])) {
                $_SESSION['msg'] = "Utilisateur mis à jour avec succès";
            } else {
                $_SESSION['msg'] = "Erreur lors de la mise à jour de l'utilisateur";
            }
        } else {
            if ($update->execute([$nom, $prenom, $email, $actif, $user_id])) {
                $_SESSION['msg'] = "Utilisateur mis à jour avec succès";
            } else {
                $_SESSION['msg'] = "Erreur lors de la mise à jour de l'utilisateur";
            }
        }
    }
    elseif ($action === 'update_roles') {
        // Mettre à jour les rôles d'un utilisateur
        $user_id = intval($_POST['user_id']);
        $roles = $_POST['roles'] ?? [];
        
        // Vérifier que l'utilisateur existe
        $verify = $conn->prepare("SELECT utilisateur_id FROM UTILISATEURS WHERE utilisateur_id = ? AND statut = 0");
        $verify->execute([$user_id]);
        
        if ($verify->rowCount() === 0) {
            $_SESSION['msg'] = "Cet utilisateur n'existe pas";
            header("Location: ../views/utilisateurs.php");
            exit();
        }
        
        // Désactiver les anciennes attributions
        $disableOld = $conn->prepare("
            UPDATE ATTRIBUTIONS 
            SET statut = 1 
            WHERE utilisateur_id = ?
        ");
        $disableOld->execute([$user_id]);
        
        // Ajouter les nouvelles attributions
        if (!empty($roles)) {
            foreach ($roles as $role_id) {
                $role_id = intval($role_id);
                if ($role_id > 0) {
                    // Vérifier si l'attribution existe déjà (statut = 1)
                    $check = $conn->prepare("
                        SELECT attribution_id 
                        FROM ATTRIBUTIONS 
                        WHERE utilisateur_id = ? AND role_id = ?
                    ");
                    $check->execute([$user_id, $role_id]);
                    
                    if ($check->rowCount() > 0) {
                        // Réactiver l'attribution existante
                        $reactivate = $conn->prepare("
                            UPDATE ATTRIBUTIONS 
                            SET statut = 0, date_attribution = CURDATE() 
                            WHERE utilisateur_id = ? AND role_id = ?
                        ");
                        $reactivate->execute([$user_id, $role_id]);
                    } else {
                        // Créer une nouvelle attribution
                        $insert = $conn->prepare("
                            INSERT INTO ATTRIBUTIONS (utilisateur_id, role_id) 
                            VALUES (?, ?)
                        ");
                        $insert->execute([$user_id, $role_id]);
                    }
                }
            }
        }
        
        $_SESSION['msg'] = "Rôles mis à jour avec succès";
    }
    
    header("Location: ../views/utilisateurs.php");
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur utilisateur: " . $e->getMessage());
    $_SESSION['msg'] = "Une erreur est survenue";
    header("Location: ../views/utilisateurs.php");
    exit();
}
?>