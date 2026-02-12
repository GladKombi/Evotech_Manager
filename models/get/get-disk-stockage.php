<?php
header('Content-Type: application/json');
include '../connexion/connexion.php';

// Vérifier l'autorisation
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('Chef_projet', $userRoles) && !in_array('ChefDepartement', $userRoles)) {
    echo json_encode(['success' => false, 'message' => 'Permission refusée']);
    exit();
}

try {
    $disk_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $query = "
        SELECT 
            ds.*,
            COUNT(p.production_id) as nb_productions
        FROM DISK_STOCKAGE ds
        LEFT JOIN PRODUCTION p ON ds.disk_id = p.disk_id AND p.statut = 0
        WHERE ds.disk_id = ? AND ds.statut = 0
        GROUP BY ds.disk_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$disk_id]);
    $disk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($disk) {
        echo json_encode(['success' => true, 'disk' => $disk]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Disque non trouvé']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>