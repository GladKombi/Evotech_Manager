<?php
// Démarrer buffering avant toute sortie
ob_start();

// Headers JSON d'abord
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Démarrer la session
session_start();

// Nettoyer les buffers antérieurs
while (ob_get_level() > 1) {
    ob_end_clean();
}

// Charger connexion
require_once('../../connexion/connexion.php');

// Vérifier ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'ID manquant']);
    ob_end_clean();
    echo $response;
    exit();
}

$departement_id = intval($_GET['id']);

if ($departement_id <= 0) {
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'ID invalide']);
    ob_end_clean();
    echo $response;
    exit();
}

try {
    $sql = "SELECT 
                d.departement_id,
                d.code,
                d.nom,
                d.description,
                d.responsable_id,
                d.date_creation,
                u.nom as responsable_nom,
                u.prenom as responsable_prenom,
                u.email as responsable_email
            FROM DEPARTEMENTS d
            LEFT JOIN UTILISATEURS u ON d.responsable_id = u.utilisateur_id
            WHERE d.departement_id = ? AND d.statut = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$departement_id]);
    $departement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$departement) {
        http_response_code(404);
        $response = json_encode(['success' => false, 'message' => 'Département non trouvé']);
        ob_end_clean();
        echo $response;
        exit();
    }
    
    // Compter les agents
    $stmtAgents = $conn->prepare("SELECT COUNT(*) as nombre_agents FROM AGENTS WHERE departement_id = ? AND statut = 0");
    $stmtAgents->execute([$departement_id]);
    $agentCount = $stmtAgents->fetch(PDO::FETCH_ASSOC);
    $departement['nombre_agents'] = $agentCount['nombre_agents'] ?? 0;
    
    // Construire le nom du responsable
    if (!empty($departement['responsable_nom']) && !empty($departement['responsable_prenom'])) {
        $departement['responsable_nom'] = trim($departement['responsable_nom'] . ' ' . $departement['responsable_prenom']);
    } elseif (!empty($departement['responsable_nom'])) {
        $departement['responsable_nom'] = trim($departement['responsable_nom']);
    } else {
        $departement['responsable_nom'] = null;
    }
    
    // Réponse
    $response = json_encode([
        'success' => true,
        'departement' => $departement
    ], JSON_UNESCAPED_UNICODE);
    
    ob_end_clean();
    echo $response;
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur fetch departement: " . $e->getMessage());
    http_response_code(500);
    $response = json_encode(['success' => false, 'message' => 'Erreur base de données']);
    ob_end_clean();
    echo $response;
    exit();
} catch (Exception $e) {
    error_log("Erreur générale: " . $e->getMessage());
    http_response_code(500);
    $response = json_encode(['success' => false, 'message' => 'Erreur serveur']);
    ob_end_clean();
    echo $response;
    exit();
}
?>
