<?php
require_once '../../connexion/connexion.php';

header('Content-Type: application/json');

if (!isset($_GET['partenaire_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID partenaire manquant']);
    exit();
}

$partenaire_id = filter_input(INPUT_GET, 'partenaire_id', FILTER_VALIDATE_INT);

if (!$partenaire_id) {
    echo json_encode(['success' => false, 'message' => 'ID partenaire invalide']);
    exit();
}

try {
    $query = "
        SELECT 
            c.*,
            a.nom as responsable_nom,
            a.prenom as responsable_prenom
        FROM CONTRATS_COM c
        LEFT JOIN AGENTS a ON c.responsable_id = a.agent_id
        WHERE c.partenaire_id = ? 
        AND c.statut = 0
        ORDER BY c.date_creation DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$partenaire_id]);
    $contrats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'contrats' => $contrats]);
    
} catch (PDOException $e) {
    error_log("Erreur récupération contrats par partenaire: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}