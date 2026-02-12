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

$user_id = intval($_GET['id']);

try {
    // Récupérer les informations de l'utilisateur
    $query = "
        SELECT 
            u.utilisateur_id,
            u.nom,
            u.prenom,
            u.email,
            u.profil,
            u.actif,
            u.statut,
            u.date_creation,
            u.deleted_at,
            GROUP_CONCAT(r.fonction SEPARATOR ', ') as roles,
            GROUP_CONCAT(r.role_id SEPARATOR ',') as role_ids
        FROM UTILISATEURS u
        LEFT JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id AND a.statut = 0
        LEFT JOIN ROLES r ON a.role_id = r.role_id
        WHERE u.utilisateur_id = ? AND u.statut = 0
        GROUP BY u.utilisateur_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    }
    
} catch (Exception $e) {
    error_log("Erreur récupération utilisateur: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
