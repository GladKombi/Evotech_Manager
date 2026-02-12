<?php
require_once '../../connexion/connexion.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit();
}

try {
    $query = "SELECT * FROM CONTRATS_COM WHERE contrat_id = ? AND statut = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $contrat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contrat) {
        echo json_encode(['success' => true, 'contrat' => $contrat]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contrat non trouvé']);
    }
} catch (PDOException $e) {
    error_log("Erreur récupération contrat: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}