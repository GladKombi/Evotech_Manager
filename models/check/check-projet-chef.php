<?php
session_start();
include '../connexion/connexion.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['is_chef' => false, 'error' => 'Non authentifié']);
    exit();
}

// Récupérer l'ID du projet
$projet_id = filter_input(INPUT_GET, 'projet_id', FILTER_VALIDATE_INT);

if (!$projet_id) {
    header('Content-Type: application/json');
    echo json_encode(['is_chef' => false, 'error' => 'ID de projet invalide']);
    exit();
}

try {
    // Vérifier si l'utilisateur est chef de ce projet
    $query = "SELECT COUNT(*) as count FROM PROJETS 
              WHERE projet_id = ? AND chef_projet_id = ? AND statut = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$projet_id, $_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $is_chef = $result['count'] > 0;
    
    header('Content-Type: application/json');
    echo json_encode(['is_chef' => $is_chef]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['is_chef' => false, 'error' => 'Erreur serveur']);
}
?>