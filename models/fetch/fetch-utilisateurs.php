<?php
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $query = "
        SELECT 
            u.utilisateur_id,
            u.nom,
            u.prenom,
            u.email,
            u.profil,
            u.actif,
            u.date_creation,
            u.deleted_at,
            GROUP_CONCAT(r.fonction SEPARATOR ', ') as roles,
            GROUP_CONCAT(r.role_id SEPARATOR ',') as role_ids
        FROM UTILISATEURS u
        LEFT JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id AND a.statut = 0
        LEFT JOIN ROLES r ON a.role_id = r.role_id AND r.statut = 0
        WHERE u.statut = 0
        GROUP BY u.utilisateur_id
        ORDER BY u.date_creation DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'utilisateurs' => $utilisateurs]);
    
} catch (Exception $e) {
    error_log("Erreur récupération utilisateurs: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
