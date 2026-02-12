<?php
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit();
}

$projet_id = intval($_GET['id']);

try {
    $query = "
        SELECT 
            p.*,
            pa.nom as partenaire_nom
        FROM PROJETS p
        LEFT JOIN PARTENAIRES pa ON p.partenaire_id = pa.partenaire_id
        WHERE p.projet_id = ? AND p.statut = 0
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$projet_id]);
    $projet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($projet) {
        echo json_encode(['success' => true, 'projet' => $projet]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Projet non trouvé']);
    }
    
} catch (Exception $e) {
    error_log("Erreur récupération projet: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}