<?php
session_start();
include '../connexion/connexion.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

// Vérifier les permissions - DG, Chef de projet ou Chef de département
$userId = $_SESSION['user_id'];
$userRoles = $_SESSION['roles'] ?? [];
$isDG = in_array('DG', $userRoles);
$isChefProjet = in_array('Chef_projet', $userRoles);
$isChefDepartement = in_array('ChefDepartement', $userRoles);

if (!$isDG && !$isChefProjet && !$isChefDepartement) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Permission refusée']);
    exit();
}

// Récupérer les filtres
$projet_id = filter_input(INPUT_GET, 'projet_id', FILTER_VALIDATE_INT);
$agent_id = filter_input(INPUT_GET, 'agent_id', FILTER_VALIDATE_INT);
$etat = filter_input(INPUT_GET, 'etat', FILTER_SANITIZE_STRING);

try {
    // Construire la requête selon les permissions
    $query = "
        SELECT 
            t.tache_id,
            t.projet_id,
            t.description_tache,
            t.assigne_a,
            t.date_debut_prevue,
            t.date_fin_prevue,
            t.priorite,
            t.etat,
            t.estimation_heures,
            t.date_creation,
            pr.reference as projet_reference,
            pr.titre as projet_titre,
            pr.chef_projet_id,
            CONCAT(a.nom, ' ', a.prenom) as agent_nom,
            a.email_professionnel,
            d.nom as departement_nom,
            d.departement_id
        FROM TACHES t
        LEFT JOIN PROJETS pr ON t.projet_id = pr.projet_id AND pr.statut = 0
        LEFT JOIN AGENTS a ON t.assigne_a = a.agent_id AND a.statut = 0
        LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id AND d.statut = 0
        WHERE t.statut = 0
    ";
    
    $params = [];
    $conditions = [];
    
    // Ajouter les conditions de permission
    if ($isDG) {
        // Le DG voit toutes les tâches - pas de condition supplémentaire
    } elseif ($isChefProjet) {
        // Chef de projet ne voit que les tâches de ses projets
        $conditions[] = "pr.chef_projet_id = ?";
        $params[] = $userId;
    } elseif ($isChefDepartement) {
        // Chef de département ne voit que les tâches des agents de son département
        // Récupérer l'ID du département du chef
        $queryDept = "SELECT departement_id FROM DEPARTEMENTS WHERE responsable_id = ? AND statut = 0";
        $stmtDept = $conn->prepare($queryDept);
        $stmtDept->execute([$userId]);
        $dept = $stmtDept->fetch(PDO::FETCH_ASSOC);
        
        if ($dept) {
            $conditions[] = "d.departement_id = ?";
            $params[] = $dept['departement_id'];
        } else {
            // Si le chef n'a pas de département, il ne voit aucune tâche
            $conditions[] = "1 = 0";
        }
    }
    
    // Ajouter les filtres supplémentaires
    if ($projet_id) {
        $conditions[] = "t.projet_id = ?";
        $params[] = $projet_id;
    }
    
    if ($agent_id) {
        $conditions[] = "t.assigne_a = ?";
        $params[] = $agent_id;
    }
    
    if ($etat) {
        $conditions[] = "t.etat = ?";
        $params[] = $etat;
    }
    
    // Combiner toutes les conditions
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
    
    // Ajouter l'ordre
    $query .= " ORDER BY 
        CASE t.priorite 
            WHEN 'Urgent' THEN 1
            WHEN 'Haute' THEN 2
            WHEN 'Normale' THEN 3
            WHEN 'Basse' THEN 4
        END,
        t.date_debut_prevue,
        t.date_creation DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $taches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['taches' => $taches]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des tâches: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur serveur']);
}
?>