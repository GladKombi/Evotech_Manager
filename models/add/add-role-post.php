<?php
session_start();
require_once('../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['msg'] = "Token de sécurité invalide";
    header("Location: ../views/roles.php");
    exit();
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        // Ajouter un nouveau rôle
        $roleName = trim($_POST['role_name']);
        
        // Validation
        if (empty($roleName)) {
            $_SESSION['msg'] = "Le nom du rôle est obligatoire";
            header("Location: ../views/roles.php");
            exit();
        }
        
        if (strpos($roleName, ' ') !== false) {
            $_SESSION['msg'] = "Le nom du rôle ne doit pas contenir d'espaces";
            header("Location: ../views/roles.php");
            exit();
        }
        
        // Vérifier si le rôle existe déjà
        $check = $conn->prepare("SELECT role_id FROM ROLES WHERE fonction = ? AND statut = 0");
        $check->execute([$roleName]);
        
        if ($check->rowCount() > 0) {
            $_SESSION['msg'] = "Ce rôle existe déjà";
            header("Location: ../views/roles.php");
            exit();
        }
        
        // Rôles système par défaut (ne peuvent être modifiés)
        $systemRoles = ['DG', 'ChefDepartement', 'Chef_projet', 'comptable', 'actionnaire', 'agent'];
        
        // Insérer le nouveau rôle
        $insert = $conn->prepare("INSERT INTO ROLES (fonction) VALUES (?)");
        
        if ($insert->execute([$roleName])) {
            $_SESSION['msg'] = "Rôle créé avec succès";
        } else {
            $_SESSION['msg'] = "Erreur lors de la création du rôle";
        }
    }
    elseif ($action === 'edit') {
        // Modifier un rôle existant
        $roleId = intval($_POST['role_id']);
        $roleName = trim($_POST['role_name']);
        
        // Validation
        if (empty($roleName)) {
            $_SESSION['msg'] = "Le nom du rôle est obligatoire";
            header("Location: ../views/roles.php");
            exit();
        }
        
        if (strpos($roleName, ' ') !== false) {
            $_SESSION['msg'] = "Le nom du rôle ne doit pas contenir d'espaces";
            header("Location: ../views/roles.php");
            exit();
        }
        
        // Vérifier que le rôle existe
        $verify = $conn->prepare("SELECT fonction FROM ROLES WHERE role_id = ? AND statut = 0");
        $verify->execute([$roleId]);
        $currentRole = $verify->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentRole) {
            $_SESSION['msg'] = "Ce rôle n'existe pas";
            header("Location: ../views/roles.php");
            exit();
        }
        
        // Vérifier si c'est un rôle système
        $systemRoles = ['DG', 'ChefDepartement', 'Chef_projet', 'comptable', 'actionnaire', 'agent'];
        $isSystem = in_array($currentRole['fonction'], $systemRoles);
        
        // Pour les rôles système, on ne peut que changer le nom (sauf si c'est un rôle système par défaut)
        if ($isSystem) {
            // Si le nom a changé, vérifier que le nouveau nom n'est pas un rôle système par défaut
            if ($roleName !== $currentRole['fonction'] && in_array($roleName, $systemRoles)) {
                $_SESSION['msg'] = "Ce nom est réservé pour un rôle système par défaut";
                header("Location: ../views/roles.php");
                exit();
            }
        }
        
        // Vérifier si le nouveau nom existe déjà pour un autre rôle
        $checkName = $conn->prepare("
            SELECT role_id 
            FROM ROLES 
            WHERE fonction = ? AND role_id != ? AND statut = 0
        ");
        $checkName->execute([$roleName, $roleId]);
        
        if ($checkName->rowCount() > 0) {
            $_SESSION['msg'] = "Ce nom de rôle est déjà utilisé";
            header("Location: ../views/roles.php");
            exit();
        }
        
        // Mettre à jour le rôle
        $update = $conn->prepare("UPDATE ROLES SET fonction = ? WHERE role_id = ?");
        
        if ($update->execute([$roleName, $roleId])) {
            $_SESSION['msg'] = "Rôle mis à jour avec succès";
        } else {
            $_SESSION['msg'] = "Erreur lors de la mise à jour du rôle";
        }
    }
    
    header("Location: ../views/roles.php");
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur rôle: " . $e->getMessage());
    $_SESSION['msg'] = "Une erreur est survenue";
    header("Location: ../views/roles.php");
    exit();
}
?>