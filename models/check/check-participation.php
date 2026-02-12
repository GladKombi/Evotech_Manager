<?php
include '../../connexion/connexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['exists' => false, 'error' => 'Non authentifié']);
    exit();
}

$projet_id = $_GET['projet_id'] ?? 0;
$agent_id = $_GET['agent_id'] ?? 0;

if (!$projet_id || !$agent_id) {
    echo json_encode(['exists' => false, 'error' => 'Paramètres manquants']);
    exit();
}

try {
    // Vérifier si la participation existe déjà (statut = 0 pour les actives seulement)
    $query = "SELECT COUNT(*) as count 
              FROM PARTICIPATION 
              WHERE projet_id = ? 
              AND agent_id = ? 
              AND statut = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$projet_id, $agent_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['exists' => $result['count'] > 0]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification de la participation: " . $e->getMessage());
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}
?>