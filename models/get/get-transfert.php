<?php
require_once '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

// Récupérer l'ID du transfert
$transfert_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($transfert_id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID transfert invalide']);
    exit();
}

try {
    $query = "
        SELECT 
            t.*,
            cs.nom as compte_source_nom,
            cs.type as compte_source_type,
            cs.devise as compte_source_devise,
            cs.solde_actuel as compte_source_solde,
            cd.nom as compte_dest_nom,
            cd.type as compte_dest_type,
            cd.devise as compte_dest_devise,
            cd.solde_actuel as compte_dest_solde,
            u.nom as valide_par_nom,
            u.prenom as valide_par_prenom
        FROM TRANSFERTS_COMPTE t
        LEFT JOIN COMPTES cs ON t.compte_source_id = cs.compte_id
        LEFT JOIN COMPTES cd ON t.compte_destination_id = cd.compte_id
        LEFT JOIN UTILISATEURS u ON t.valide_par = u.utilisateur_id
        WHERE t.transfert_id = ? AND t.statut = 0
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$transfert_id]);
    $transfert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transfert) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Transfert non trouvé']);
        exit();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $transfert
    ]);
    
} catch (Exception $e) {
    error_log('Erreur récupération transfert: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}

exit();

?>
