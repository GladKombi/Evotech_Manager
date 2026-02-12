<?php
require_once '../../connexion/connexion.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit();
}

try {
    $query = "
        SELECT 
            c.*,
            p.nom as partenaire_nom,
            p.type as partenaire_type,
            a.nom as responsable_nom,
            a.prenom as responsable_prenom,
            (SELECT COUNT(*) FROM ACTIVITES_CONTRAT WHERE contrat_id = c.contrat_id AND statut = 0) as nb_activites,
            (SELECT COUNT(*) FROM ACTIVITES_CONTRAT WHERE contrat_id = c.contrat_id AND statut_activite != 'Terminé' AND statut = 0) as nb_activites_non_terminees
        FROM CONTRATS_COM c
        LEFT JOIN PARTENAIRES p ON c.partenaire_id = p.partenaire_id
        LEFT JOIN AGENTS a ON c.responsable_id = a.agent_id
        WHERE c.contrat_id = ? AND c.statut = 0
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $contrat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contrat) {
        // Formater les dates pour l'affichage
        $contrat['date_debut_formatted'] = date('d/m/Y', strtotime($contrat['date_debut']));
        if ($contrat['date_fin']) {
            $contrat['date_fin_formatted'] = date('d/m/Y', strtotime($contrat['date_fin']));
        }
        
        echo json_encode(['success' => true, 'contrat' => $contrat]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contrat non trouvé']);
    }
} catch (PDOException $e) {
    error_log("Erreur récupération contrat complet: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}