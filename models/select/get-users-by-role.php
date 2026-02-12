<?php
session_start();
header('Content-Type: application/json');

// Se connecter à la BD
include '../../connexion/connexion.php';

// Vérifier si role_id est passé
if (!isset($_GET['role_id']) || !is_numeric($_GET['role_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de rôle invalide']);
    exit();
}

$role_id = intval($_GET['role_id']);

try {
    // Récupérer les utilisateurs ayant ce rôle
    $query = "SELECT 
                u.utilisateur_id,
                u.nom,
                u.prenom,
                u.email,
                u.profil,
                DATE_FORMAT(a.date_attribution, '%d/%m/%Y') as date_attribution
              FROM UTILISATEURS u
              INNER JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id
              WHERE a.role_id = ?
              AND u.statut = 0
              AND u.actif = 1
              AND a.statut = 0
              ORDER BY u.nom, u.prenom";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$role_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur get-users-by-role: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?>