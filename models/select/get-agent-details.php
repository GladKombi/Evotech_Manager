<?php
// Se connecter à la BD
include '../../connexion/connexion.php';

header('Content-Type: application/json');



// Vérifier si agent_id est passé
if (!isset($_GET['agent_id']) || !is_numeric($_GET['agent_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID d\'agent invalide']);
    exit();
}

$agent_id = intval($_GET['agent_id']);

try {
    // Récupérer les données détaillées de l'agent avec département
    $query = "SELECT 
                a.*,
                d.nom as departement_nom,
                d.code as departement_code
              FROM AGENTS a
              LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id AND d.statut = 0
              WHERE a.agent_id = ? AND a.statut = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($agent) {
        // Formater les dates pour l'affichage
        if (!empty($agent['date_creation'])) {
            $agent['date_creation'] = date('d/m/Y', strtotime($agent['date_creation']));
        }
        if (!empty($agent['date_embauche'])) {
            $agent['date_embauche'] = date('d/m/Y', strtotime($agent['date_embauche']));
        }
        if (!empty($agent['date_depart'])) {
            $agent['date_depart'] = date('d/m/Y', strtotime($agent['date_depart']));
        }
        if (!empty($agent['date_naissance'])) {
            $agent['date_naissance'] = date('d/m/Y', strtotime($agent['date_naissance']));
        }
        
        echo json_encode([
            'success' => true,
            'agent' => $agent
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Agent non trouvé']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur get-agent-details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>