<?php
require_once '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID agent invalide']);
    exit();
}

$agent_id = intval($_GET['id']);

try {
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
                a.departement_id
              FROM AGENTS a
              WHERE a.agent_id = ? AND a.statut = 0";

    $stmt = $conn->prepare($query);
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Agent non trouvé']);
        exit();
    }

    echo json_encode(['success' => true, 'agent' => $agent]);

} catch (PDOException $e) {
    error_log("Erreur fetch-agent: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>