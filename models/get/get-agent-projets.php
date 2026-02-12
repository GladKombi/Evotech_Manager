<?php
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if (!isset($_GET['agent_id']) || !is_numeric($_GET['agent_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID agent invalide']);
    exit();
}

$agent_id = intval($_GET['agent_id']);

try {
    $query = "
        SELECT 
            p.reference,
            p.titre,
            p.etat
        FROM PARTICIPATION part
        JOIN PROJETS p ON part.projet_id = p.projet_id AND p.statut = 0
        WHERE part.agent_id = ? AND part.statut = 0
        ORDER BY p.reference
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$agent_id]);
    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'projets' => $projets]);
    
} catch (Exception $e) {
    error_log("Erreur récupération projets agent: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}