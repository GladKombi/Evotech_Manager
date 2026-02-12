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
$canModify = in_array('DG', $userRoles) || in_array('ChefDepartement', $userRoles);

if (!$canModify) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    ob_end_clean();
    exit();
}

require_once('../../connexion/connexion.php');

// Déterminer l'action
$action = $_POST['action'] ?? 'add';

if ($action === 'add') {
    // Ajouter une nouvelle attribution
    if (!isset($_POST['utilisateur_id']) || !isset($_POST['role_id'])) {
        $_SESSION['error'] = 'Données manquantes.';
        ob_end_clean();
        header('Location: ../../Views/attributions.php');
        exit();
    }

    $utilisateur_id = intval($_POST['utilisateur_id']);
    $role_id = intval($_POST['role_id']);
    $date_attribution = $_POST['date_attribution'] ?? date('Y-m-d');

    if ($utilisateur_id <= 0 || $role_id <= 0) {
        $_SESSION['error'] = 'IDs invalides.';
        ob_end_clean();
        header('Location: ../../Views/attributions.php');
        exit();
    }

    try {
        // Vérifier que l'utilisateur existe et est actif
        $stmtCheckUser = $conn->prepare("SELECT utilisateur_id, nom, prenom FROM UTILISATEURS WHERE utilisateur_id = ? AND statut = 0");
        $stmtCheckUser->execute([$utilisateur_id]);
        $user = $stmtCheckUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = 'Utilisateur non trouvé.';
            ob_end_clean();
            header('Location: ../../Views/attributions.php');
            exit();
        }

        // Vérifier que le rôle existe et est actif
        $stmtCheckRole = $conn->prepare("SELECT role_id, fonction FROM ROLES WHERE role_id = ? AND statut = 0");
        $stmtCheckRole->execute([$role_id]);
        $role = $stmtCheckRole->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            $_SESSION['error'] = 'Rôle non trouvé.';
            ob_end_clean();
            header('Location: ../../Views/attributions.php');
            exit();
        }

        // Vérifier que cette attribution n'existe pas déjà
        $stmtCheckDuplicate = $conn->prepare("SELECT attribution_id FROM ATTRIBUTIONS WHERE utilisateur_id = ? AND role_id = ? AND statut = 0");
        $stmtCheckDuplicate->execute([$utilisateur_id, $role_id]);

        if ($stmtCheckDuplicate->fetch()) {
            $_SESSION['error'] = "Cet utilisateur a déjà ce rôle.";
            ob_end_clean();
            header('Location: ../../Views/attributions.php');
            exit();
        }

        // Insérer l'attribution
        $stmtInsert = $conn->prepare("INSERT INTO ATTRIBUTIONS (utilisateur_id, role_id, date_attribution, date_creation, statut) 
                                      VALUES (?, ?, ?, NOW(), 0)");
        $stmtInsert->execute([$utilisateur_id, $role_id, $date_attribution]);

        $_SESSION['msg'] = "L'attribution a été créée avec succès.";
        ob_end_clean();
        header('Location: ../../Views/attributions.php');
        exit();

    } catch (PDOException $e) {
        error_log("Erreur ajout attribution: " . $e->getMessage());
        $_SESSION['error'] = 'Erreur lors de la création de l\'attribution.';
        ob_end_clean();
        header('Location: ../../Views/attributions.php');
        exit();
    }

} elseif ($action === 'edit') {
    // Modifier une attribution
    if (!isset($_POST['attribution_id']) || !isset($_POST['role_id'])) {
        $_SESSION['error'] = 'Données invalides.';
        ob_end_clean();
        header('Location: ../../Views/attributions.php');
        exit();
    }

    $attribution_id = intval($_POST['attribution_id']);
    $role_id = intval($_POST['role_id']);
    $date_attribution = $_POST['date_attribution'] ?? date('Y-m-d');

    if ($attribution_id <= 0 || $role_id <= 0) {
        $_SESSION['error'] = 'IDs invalides.';
        ob_end_clean();
        header('Location: ../../Views/attributions.php');
        exit();
    }

    try {
        // Vérifier que l'attribution existe
        $stmtCheck = $conn->prepare("SELECT utilisateur_id FROM ATTRIBUTIONS WHERE attribution_id = ? AND statut = 0");
        $stmtCheck->execute([$attribution_id]);
        $attribution = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$attribution) {
            $_SESSION['error'] = 'Attribution non trouvée.';
            ob_end_clean();
            header('Location: ../../Views/attributions.php');
            exit();
        }

        // Vérifier que le rôle existe
        $stmtCheckRole = $conn->prepare("SELECT role_id FROM ROLES WHERE role_id = ? AND statut = 0");
        $stmtCheckRole->execute([$role_id]);

        if (!$stmtCheckRole->fetch()) {
            $_SESSION['error'] = 'Rôle non trouvé.';
            ob_end_clean();
            header('Location: ../../Views/attributions.php');
            exit();
        }

        // Vérifier qu'aucune autre attribution n'existe avec ce rôle pour cet utilisateur
        $stmtCheckDuplicate = $conn->prepare("SELECT attribution_id FROM ATTRIBUTIONS 
                                              WHERE utilisateur_id = ? AND role_id = ? AND attribution_id != ? AND statut = 0");
        $stmtCheckDuplicate->execute([$attribution['utilisateur_id'], $role_id, $attribution_id]);

        if ($stmtCheckDuplicate->fetch()) {
            $_SESSION['error'] = "Cet utilisateur a déjà ce rôle.";
            ob_end_clean();
            header('Location: ../../Views/attributions.php');
            exit();
        }

        // Mettre à jour l'attribution
        $stmtUpdate = $conn->prepare("UPDATE ATTRIBUTIONS SET role_id = ?, date_attribution = ? WHERE attribution_id = ?");
        $stmtUpdate->execute([$role_id, $date_attribution, $attribution_id]);

        $_SESSION['msg'] = "L'attribution a été mise à jour avec succès.";
        ob_end_clean();
        header('Location: ../../Views/attributions.php');
        exit();

    } catch (PDOException $e) {
        error_log("Erreur modification attribution: " . $e->getMessage());
        $_SESSION['error'] = 'Erreur lors de la modification de l\'attribution.';
        ob_end_clean();
        header('Location: ../../Views/attributions.php');
        exit();
    }
}

ob_end_clean();
header('Location: ../../Views/attributions.php');
exit();
?>
