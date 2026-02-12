<?php
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if (!isset($_GET['agent_id']) || !is_numeric($_GET['agent_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID agent invalide']);
    exit();
}

$agent_id = intval($_GET['agent_id']);

try {
    // Récupérer les informations de l'agent avec statut = 0 (CORRECTION)
    $query = "
        SELECT 
            a.agent_id,
            CONCAT(a.prenom, ' ', a.nom) as nom_complet,
            a.photo_profil,
            d.nom as departement_nom,
            COUNT(DISTINCT part.projet_id) as nb_projets,
            UPPER(CONCAT(SUBSTRING(a.prenom, 1, 1), SUBSTRING(a.nom, 1, 1))) as initials
        FROM AGENTS a
        LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id AND d.statut = 0
        LEFT JOIN PARTICIPATION part ON a.agent_id = part.agent_id AND part.statut = 0
        WHERE a.agent_id = ? AND a.statut = 0  -- CORRECTION: ajouté statut = 0
        GROUP BY a.agent_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agent) {
        echo json_encode(['success' => false, 'message' => 'Agent non trouvé ou supprimé']);
        exit();
    }
    
    // Récupérer les projets de l'agent avec statut = 0 (CORRECTION)
    $queryProjets = "
        SELECT 
            p.projet_id,
            p.reference,
            p.titre,
            p.etat
        FROM PARTICIPATION part
        JOIN PROJETS p ON part.projet_id = p.projet_id AND p.statut = 0  -- CORRECTION: ajouté statut = 0
        WHERE part.agent_id = ? AND part.statut = 0
        ORDER BY p.reference
    ";
    
    $stmtProjets = $conn->prepare($queryProjets);
    $stmtProjets->execute([$agent_id]);
    $projets = $stmtProjets->fetchAll(PDO::FETCH_ASSOC);
    
    $agent['projets'] = $projets;
    
    echo json_encode(['success' => true, 'agent' => $agent]);
    
} catch (Exception $e) {
    error_log("Erreur récupération participations agent: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}