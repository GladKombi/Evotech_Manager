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

// Récupérer l'ID du rôle
$role_id = intval($_GET['id'] ?? 0);

if ($role_id <= 0) {
    $_SESSION['error'] = 'ID de rôle invalide.';
    ob_end_clean();
    header('Location: ../../Views/roles.php');
    exit();
}

try {
    // Vérifier que le rôle existe et est actif
    $stmtCheck = $conn->prepare("SELECT fonction FROM ROLES WHERE role_id = ? AND statut = 0");
    $stmtCheck->execute([$role_id]);
    $role = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        $_SESSION['error'] = 'Rôle non trouvé ou déjà supprimé.';
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();
    }

    // Vérifier qu'aucun utilisateur n'a ce rôle
    $stmtCheckUsers = $conn->prepare("SELECT COUNT(*) as count FROM ATTRIBUTIONS WHERE role_id = ? AND statut = 0");
    $stmtCheckUsers->execute([$role_id]);
    $result = $stmtCheckUsers->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        $_SESSION['error'] = "Impossible de supprimer ce rôle. Des utilisateurs l'utilisent encore.";
        ob_end_clean();
        header('Location: ../../Views/roles.php');
        exit();
    }

    // Soft delete: mettre le statut à 1
    $stmtDelete = $conn->prepare("UPDATE ROLES SET statut = 1 WHERE role_id = ?");
    $stmtDelete->execute([$role_id]);

    $_SESSION['msg'] = "Le rôle '{$role['fonction']}' a été supprimé avec succès.";
    ob_end_clean();
    header('Location: ../../Views/roles.php');
    exit();

} catch (PDOException $e) {
    error_log("Erreur suppression rôle: " . $e->getMessage());
    $_SESSION['error'] = 'Erreur lors de la suppression du rôle.';
    ob_end_clean();
    header('Location: ../../Views/roles.php');
    exit();
}
?>
