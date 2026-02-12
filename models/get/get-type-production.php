<?php
include '../../connexion/connexion.php';
header('Content-Type: application/json');

// Vérifier les permissions
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

// Récupérer les rôles de l'utilisateur
$userRoles = $_SESSION['roles'] ?? [];

// Vérifier les permissions
function hasPermission($requiredRoles, $userRoles) {
    if (empty($requiredRoles)) return true;
    foreach ($requiredRoles as $role) {
        if (in_array($role, $userRoles)) return true;
    }
    return false;
}

if (!hasPermission(['DG', 'Chef_projet'], $userRoles)) {
    echo json_encode(['success' => false, 'error' => 'Permission refusée']);
    exit();
}

try {
    $type_prod_id = intval($_GET['id'] ?? 0);
    
    if ($type_prod_id <= 0) {
        throw new Exception("Identifiant invalide");
    }
    
    // Récupérer les données du type avec le nombre de productions
    $query = "
        SELECT 
            tp.*,
            COUNT(p.production_id) as nb_productions
        FROM TYPES_PRODUCTION tp
        LEFT JOIN PRODUCTION p ON tp.type_prod_id = p.type_prod_id AND p.statut = 0
        WHERE tp.type_prod_id = ? AND tp.statut = 0
        GROUP BY tp.type_prod_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$type_prod_id]);
    $type = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$type) {
        throw new Exception("Type de production non trouvé");
    }
    
    echo json_encode(['success' => true, 'type' => $type]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données']);
}
?>