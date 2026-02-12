<?php
require_once '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

// Récupérer l'ID du mouvement
$mouvement_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($mouvement_id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID mouvement invalide']);
    exit();
}

try {
    $query = "
        SELECT 
            m.*,
            c.nom as compte_nom,
            c.type as compte_type,
            c.devise as compte_devise,
            p.reference as projet_reference,
            p.titre as projet_titre,
            cc.reference as contrat_reference,
            cc.type_contrat as contrat_type
        FROM MOUVEMENT_COMPTE m
        LEFT JOIN COMPTES c ON m.compte_id = c.compte_id
        LEFT JOIN PROJETS p ON m.projet_id = p.projet_id
        LEFT JOIN CONTRATS_COM cc ON m.contrat_id = cc.contrat_id
        WHERE m.mouvement_id = ? AND m.statut = 0
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$mouvement_id]);
    $mouvement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mouvement) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Mouvement non trouvé']);
        exit();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $mouvement
    ]);
    
} catch (Exception $e) {
    error_log('Erreur récupération mouvement: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}

exit();

?>
