<?php
require_once '../../connexion/connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérifier CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
    exit();
}

// Vérifier permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('ChefDepartement', $userRoles)) {
    echo json_encode(['success' => false, 'message' => 'Permission refusée']);
    exit();
}

try {
    $contrat_id = filter_input(INPUT_POST, 'contrat_id', FILTER_VALIDATE_INT);
    $etat = $_POST['etat'];
    
    if (!$contrat_id || !in_array($etat, ['Actif', 'En attente', 'Terminé', 'Résilié'])) {
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
        exit();
    }
    
    // Si on termine un contrat, vérifier s'il y a des activités non terminées
    if ($etat === 'Terminé') {
        $query = "SELECT COUNT(*) as count FROM ACTIVITES_CONTRAT 
                 WHERE contrat_id = ? AND statut_activite != 'Terminé' AND statut = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute([$contrat_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Impossible de terminer le contrat : il y a des activités non terminées']);
            exit();
        }
    }
    
    $query = "UPDATE CONTRATS_COM SET etat = ? WHERE contrat_id = ? AND statut = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$etat, $contrat_id]);
    
    echo json_encode(['success' => true, 'message' => 'État modifié avec succès']);
    
} catch (PDOException $e) {
    error_log("Erreur changement état contrat: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}