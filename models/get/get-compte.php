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

$compte_id = intval($_GET['id']);

try {
    $query = "
        SELECT c.*, u.nom as responsable_nom, u.prenom as responsable_prenom
        FROM COMPTES c
        LEFT JOIN UTILISATEURS u ON c.responsable_id = u.utilisateur_id
        WHERE c.compte_id = ? AND c.statut = 0
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([$compte_id]);
    $compte = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($compte) {
        echo json_encode(['success' => true, 'compte' => $compte]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Compte non trouvé']);
    }
} catch (Exception $e) {
    error_log('Erreur get-compte: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

?>
