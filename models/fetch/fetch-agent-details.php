<?php
require_once '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
$canAccess = in_array('DG', $userRoles) || in_array('ChefDepartement', $userRoles);

if (!$canAccess) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les permissions nécessaires']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID agent invalide']);
    exit();
}

$agent_id = intval($_GET['id']);

try {
    // Récupérer les détails de l'agent
    $query = "SELECT 
                a.agent_id,
                a.nom,
                a.prenom,
                a.date_naissance,
                a.telephone_personnel,
                a.telephone_professionnel,
                a.email_personnel,
                a.email_professionnel,
                a.situation_matrimoniale,
                a.nombre_enfants,
                a.urgence_nom,
                a.urgence_telephone,
                a.photo_profil,
                a.cv_path,
                a.etat,
                a.date_embauche,
                a.date_depart,
                a.date_creation,
                d.nom as departement_nom,
                d.code as departement_code
              FROM AGENTS a
              LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id AND d.statut = 0
              WHERE a.agent_id = ? AND a.statut = 0";

    $stmt = $conn->prepare($query);
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Agent non trouvé']);
        exit();
    }

    // Récupérer les projets auxquels l'agent participe
    $queryProjets = "
        SELECT 
            p.projet_id,
            p.reference,
            p.titre,
            p.etat
        FROM PARTICIPATION part
        JOIN PROJETS p ON part.projet_id = p.projet_id AND p.statut = 0
        WHERE part.agent_id = ? AND part.statut = 0
        ORDER BY p.reference
    ";
    
    $stmtProjets = $conn->prepare($queryProjets);
    $stmtProjets->execute([$agent_id]);
    $projets = $stmtProjets->fetchAll(PDO::FETCH_ASSOC);

    $agent['projets'] = $projets;
    $agent['nb_projets'] = count($projets);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'agent' => $agent
    ]);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des détails d'agent: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur lors de la récupération des données'
    ]);
}
?>
