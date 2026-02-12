<?php
// ../models/update/change-statut-activite.php
session_start();
require_once '../../connexion/connexion.php';

header('Content-Type: application/json');

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
    exit();
}

// Vérifier les permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['roles'][0] ?? '', ['DG', 'ChefDepartement'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $activite_id = $_POST['activite_id'];
    $nouveau_statut = $_POST['statut_activite'];
    
    // Vérifier que le statut est valide
    $statuts_valides = ['Planifiée', 'Réalisée', 'Annulée'];
    if (!in_array($nouveau_statut, $statuts_valides)) {
        echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        exit();
    }
    
    // Mettre à jour le statut
    $query = "UPDATE ACTIVITES_CONTRAT SET statut_activite = ? WHERE activite_id = ? AND statut = 0";
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([$nouveau_statut, $activite_id]);
    
    if ($success && $stmt->rowCount() > 0) {
        // Log
        $query_log = "INSERT INTO logs_systeme (utilisateur_id, action, module, details) VALUES (?, ?, ?, ?)";
        $stmt_log = $conn->prepare($query_log);
        $stmt_log->execute([
            $_SESSION['user_id'],
            'UPDATE',
            'ACTIVITES_CONTRAT',
            "Changement de statut de l'activité ID: $activite_id vers '$nouveau_statut'"
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Statut modifié avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucune modification effectuée']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?>