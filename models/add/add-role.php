<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

while (ob_get_level() > 1) {
    ob_end_clean();
}

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    ob_end_clean();
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    ob_end_clean();
    exit();
}

require_once('../../connexion/connexion.php');

// Déterminer l'action
$action = $_POST['action'] ?? 'add';

if ($action === 'add') {
    // Ajouter un nouveau rôle
    if (!isset($_POST['role_name']) || empty($_POST['role_name'])) {
        $_SESSION['error'] = 'Le nom du rôle est obligatoire.';
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();
    }

    $role_name = trim($_POST['role_name']);

    // Validation
    if (strlen($role_name) < 2 || strlen($role_name) > 50) {
        $_SESSION['error'] = 'Le nom du rôle doit avoir entre 2 et 50 caractères.';
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();
    }

    try {
        // Vérifier que le rôle n'existe pas déjà
        $stmtCheck = $conn->prepare("SELECT role_id FROM ROLES WHERE fonction = ? AND statut IN (0, 2)");
        $stmtCheck->execute([$role_name]);
        
        if ($stmtCheck->fetch()) {
            $_SESSION['error'] = "Le rôle '{$role_name}' existe déjà.";
            ob_end_clean();
            header('Location: ../../Views/roles.php');
            exit();
        }

        // Insérer le nouveau rôle
        $stmtInsert = $conn->prepare("INSERT INTO ROLES (fonction, statut) VALUES (?, 0)");
        $stmtInsert->execute([$role_name]);

        $_SESSION['msg'] = "Le rôle '{$role_name}' a été créé avec succès.";
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();

    } catch (PDOException $e) {
        error_log("Erreur ajout rôle: " . $e->getMessage());
        $_SESSION['error'] = 'Erreur lors de la création du rôle.';
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();
    }

} elseif ($action === 'edit') {
    // Modifier un rôle
    if (!isset($_POST['role_id']) || !isset($_POST['role_name'])) {
        $_SESSION['error'] = 'Données invalides.';
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();
    }

    $role_id = intval($_POST['role_id']);
    $role_name = trim($_POST['role_name']);

    // Validation
    if ($role_id <= 0) {
        $_SESSION['error'] = 'ID de rôle invalide.';
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();
    }

    if (strlen($role_name) < 2 || strlen($role_name) > 50) {
        $_SESSION['error'] = 'Le nom du rôle doit avoir entre 2 et 50 caractères.';
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();
    }

    try {
        // Vérifier que le rôle existe
        $stmtCheck = $conn->prepare("SELECT fonction FROM ROLES WHERE role_id = ? AND statut = 0");
        $stmtCheck->execute([$role_id]);
        $currentRole = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$currentRole) {
            $_SESSION['error'] = 'Rôle non trouvé.';
            ob_end_clean();
            header('Location: ../../Views/roles.php');
            exit();
        }

        // Si le nom a changé, vérifier l'unicité
        if ($currentRole['fonction'] !== $role_name) {
            $stmtCheckDuplicate = $conn->prepare("SELECT role_id FROM ROLES WHERE fonction = ? AND role_id != ? AND statut IN (0, 2)");
            $stmtCheckDuplicate->execute([$role_name, $role_id]);

            if ($stmtCheckDuplicate->fetch()) {
                $_SESSION['error'] = "Le rôle '{$role_name}' existe déjà.";
                ob_end_clean();
                header('Location: ../../Views/roles.php');
                exit();
            }
        }

        // Mettre à jour le rôle
        $stmtUpdate = $conn->prepare("UPDATE ROLES SET fonction = ? WHERE role_id = ?");
        $stmtUpdate->execute([$role_name, $role_id]);

        $_SESSION['msg'] = "Le rôle a été mis à jour avec succès.";
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();

    } catch (PDOException $e) {
        error_log("Erreur modification rôle: " . $e->getMessage());
        $_SESSION['error'] = 'Erreur lors de la modification du rôle.';
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();
    }
}

ob_end_clean();
header('Location: ../../Views/roles.php');
exit();
?>
