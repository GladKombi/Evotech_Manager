<?php
// Démarrer buffering
ob_start();

// Headers JSON
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

session_start();

// Nettoyer les buffers antérieurs
while (ob_get_level() > 1) {
    ob_end_clean();
}
// Charger la connexion
require_once('../../connexion/connexion.php');

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    $response = json_encode(['success' => false, 'message' => 'Non autorisé']);
    ob_end_clean();
    echo $response;
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
$canSuspend = in_array('DG', $userRoles) || in_array('ChefDepartement', $userRoles);

if (!$canSuspend) {
    http_response_code(403);
    $response = json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    ob_end_clean();
    echo $response;
    exit();
}

// Vérifier l'ID
if (!isset($_POST['departement_id']) || empty($_POST['departement_id'])) {
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'ID du département manquant']);
    ob_end_clean();
    echo $response;
    exit();
}

$departement_id = intval($_POST['departement_id']);

if ($departement_id <= 0) {
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'ID invalide']);
    ob_end_clean();
    echo $response;
    exit();
}

try {
    // Vérifier que le département existe et est actif
    $stmtCheck = $conn->prepare("SELECT departement_id, nom FROM DEPARTEMENTS WHERE departement_id = ? AND statut = 0");
    $stmtCheck->execute([$departement_id]);
    $departement = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$departement) {
        http_response_code(404);
        $response = json_encode(['success' => false, 'message' => 'Département non trouvé ou déjà suspendu']);
        ob_end_clean();
        echo $response;
        exit();
    }
    
    // Mettre à jour le statut à 2 (suspendu)
    $stmtUpdate = $conn->prepare("UPDATE DEPARTEMENTS SET statut = 2 WHERE departement_id = ?");
    $stmtUpdate->execute([$departement_id]);
    
    // Vérifier que la mise à jour a fonctionné
    if ($stmtUpdate->rowCount() > 0) {
        $_SESSION['msg'] = "Le département '" . htmlspecialchars($departement['nom']) . "' a été suspendu avec succès.";
        
        $response = json_encode([
            'success' => true,
            'message' => 'Département suspendu avec succès',
            'departement_nom' => $departement['nom']
        ], JSON_UNESCAPED_UNICODE);
        
        ob_end_clean();
        echo $response;
        exit();
    } else {
        http_response_code(500);
        $response = json_encode(['success' => false, 'message' => 'Erreur lors de la suspension']);
        ob_end_clean();
        echo $response;
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Erreur suspend departement: " . $e->getMessage());
    http_response_code(500);
    $response = json_encode(['success' => false, 'message' => 'Erreur base de données']);
    ob_end_clean();
    echo $response;
    exit();
} catch (Exception $e) {
    error_log("Erreur générale suspend: " . $e->getMessage());
    http_response_code(500);
    $response = json_encode(['success' => false, 'message' => 'Erreur serveur']);
    ob_end_clean();
    echo $response;
    exit();
}
?>
