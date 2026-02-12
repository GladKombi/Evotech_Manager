<?php
// ../models/get/get-activite.php
require_once '../../connexion/connexion.php';

header('Content-Type: application/json');

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Récupérer les rôles de l'utilisateur
$userRoles = $_SESSION['roles'] ?? [];

// Vérifier les permissions (DG, ChefDepartement)
$canAccess = false;
foreach ($userRoles as $role) {
    if (in_array($role, ['DG', 'ChefDepartement'])) {
        $canAccess = true;
        break;
    }
}

if (!$canAccess) {
    echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    exit();
}

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit();
}

$activiteId = intval($_GET['id']);

try {
    $query = "
        SELECT * FROM ACTIVITES_CONTRAT 
        WHERE activite_id = ? AND statut = 0
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$activiteId]);
    $activite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activite) {
        // Formater les dates pour l'affichage
        if ($activite['date_activite']) {
            $activite['date_activite'] = date('Y-m-d', strtotime($activite['date_activite']));
        }
        
        echo json_encode(['success' => true, 'activite' => $activite]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Activité non trouvée']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur get-activite: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?>