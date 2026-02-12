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
if (!in_array('DG', $userRoles) && !in_array('ChefDepartement', $userRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    ob_end_clean();
    exit();
}

require_once('../../connexion/connexion.php');

$attribution_id = intval($_GET['id'] ?? 0);

if ($attribution_id <= 0) {
    $_SESSION['error'] = 'ID invalide.';
    ob_end_clean();
    header('Location: ../../Views/attributions.php');
    exit();
}

try {
    // Vérifier que l'attribution existe
    $stmtCheck = $conn->prepare("SELECT a.attribution_id, u.nom, u.prenom, r.fonction 
                                 FROM ATTRIBUTIONS a
                                 INNER JOIN UTILISATEURS u ON a.utilisateur_id = u.utilisateur_id
                                 INNER JOIN ROLES r ON a.role_id = r.role_id
                                 WHERE a.attribution_id = ? AND a.statut = 0");
    $stmtCheck->execute([$attribution_id]);
    $attribution = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$attribution) {
        $_SESSION['error'] = 'Attribution non trouvée ou déjà supprimée.';
        ob_end_clean();
        header('Location: ../../Views/attributions.php');
        exit();
    }

    // Soft delete: mettre le statut à 1
    $stmtDelete = $conn->prepare("UPDATE ATTRIBUTIONS SET statut = 1 WHERE attribution_id = ?");
    $stmtDelete->execute([$attribution_id]);

    $_SESSION['msg'] = "L'attribution de {$attribution['prenom']} {$attribution['nom']} au rôle {$attribution['fonction']} a été supprimée.";
    ob_end_clean();
    header('Location: ../../Views/attributions.php');
    exit();

} catch (PDOException $e) {
    error_log("Erreur suppression attribution: " . $e->getMessage());
    $_SESSION['error'] = 'Erreur lors de la suppression.';
    ob_end_clean();
    header('Location: ../../Views/attributions.php');
    exit();
}
?>