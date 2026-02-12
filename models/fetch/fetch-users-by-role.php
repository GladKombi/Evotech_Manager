<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

while (ob_get_level() > 1) {
    ob_end_clean();
}

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    $response = json_encode(['success' => false, 'message' => 'Non autorisé']);
    ob_end_clean();
    echo $response;
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles)) {
    http_response_code(403);
    $response = json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    ob_end_clean();
    echo $response;
    exit();
}

require_once('../../connexion/connexion.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'ID du rôle manquant']);
    ob_end_clean();
    echo $response;
    exit();
}

$role_id = intval($_GET['id']);

if ($role_id <= 0) {
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'ID invalide']);
    ob_end_clean();
    echo $response;
    exit();
}

try {
    // Récupérer le rôle
    $stmtRole = $conn->prepare("SELECT fonction FROM ROLES WHERE role_id = ? AND statut = 0");
    $stmtRole->execute([$role_id]);
    $role = $stmtRole->fetch(PDO::FETCH_ASSOC);
    
    if (!$role) {
        http_response_code(404);
        $response = json_encode(['success' => false, 'message' => 'Rôle non trouvé']);
        ob_end_clean();
        echo $response;
        exit();
    }
    
    // Récupérer les utilisateurs avec ce rôle
    $stmtUsers = $conn->prepare("
        SELECT DISTINCT
            u.utilisateur_id,
            u.nom,
            u.prenom,
            u.email,
            u.actif,
            u.statut
        FROM UTILISATEURS u
        INNER JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id
        WHERE a.role_id = ? AND a.statut = 0
        ORDER BY u.nom, u.prenom
    ");
    $stmtUsers->execute([$role_id]);
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    
    $response = json_encode([
        'success' => true,
        'role' => htmlspecialchars($role['fonction'], ENT_QUOTES, 'UTF-8'),
        'users' => array_map(function($user) {
            return [
                'utilisateur_id' => $user['utilisateur_id'],
                'nom' => htmlspecialchars($user['nom'], ENT_QUOTES, 'UTF-8'),
                'prenom' => htmlspecialchars($user['prenom'], ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'),
                'actif' => $user['actif'],
                'statut' => $user['statut']
            ];
        }, $users)
    ], JSON_UNESCAPED_UNICODE);
    
    ob_end_clean();
    echo $response;
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur fetch users by role: " . $e->getMessage());
    http_response_code(500);
    $response = json_encode(['success' => false, 'message' => 'Erreur base de données']);
    ob_end_clean();
    echo $response;
    exit();
}
?>
