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
    $query = "SELECT * FROM PARTENAIRES WHERE partenaire_id = ? AND statut = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $partenaire = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($partenaire) {
        echo json_encode(['success' => true, 'partenaire' => $partenaire]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Partenaire non trouvé']);
    }
} catch (PDOException $e) {
    error_log("Erreur récupération partenaire: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}