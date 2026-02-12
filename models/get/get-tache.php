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
    $tache_id = intval($_GET['id'] ?? 0);
    
    if ($tache_id <= 0) {
        throw new Exception("Identifiant invalide");
    }
    
    // Récupérer les données de la tâche avec les heures validées
    $query = "
        SELECT 
            t.*,
            COALESCE(SUM(CASE WHEN ts.etat = 'Validée' THEN ts.worked_heures ELSE 0 END), 0) as heures_validees,
            ROUND(
                CASE WHEN t.estimation_heures > 0 
                THEN (COALESCE(SUM(CASE WHEN ts.etat = 'Validée' THEN ts.worked_heures ELSE 0 END), 0) / t.estimation_heures) * 100 
                ELSE 0 END
            ) as pourcentage
        FROM TACHES t
        LEFT JOIN TIMESHEET ts ON t.tache_id = ts.tache_id AND ts.statut = 0
        WHERE t.tache_id = ? AND t.statut = 0
        GROUP BY t.tache_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tache) {
        throw new Exception("Tâche non trouvée");
    }
    
    echo json_encode(['success' => true, 'tache' => $tache]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données']);
}
?>