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
$canAccess = in_array('DG', $userRoles) || in_array('ChefDepartement', $userRoles);

if (!$canAccess) {
    http_response_code(403);
    $response = json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    ob_end_clean();
    echo $response;
    exit();
}

require_once('../../connexion/connexion.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'ID manquant']);
    ob_end_clean();
    echo $response;
    exit();
}

$attribution_id = intval($_GET['id']);

if ($attribution_id <= 0) {
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'ID invalide']);
    ob_end_clean();
    echo $response;
    exit();
}

try {
    // Récupérer l'attribution avec détails
    $sql = "SELECT 
                a.attribution_id,
                a.utilisateur_id,
                a.role_id,
                a.date_attribution,
                a.date_creation,
                u.nom as user_nom,
                u.prenom as user_prenom,
                u.email as user_email,
                u.profil as user_profil,
                r.fonction as role_fonction
            FROM ATTRIBUTIONS a
            INNER JOIN UTILISATEURS u ON a.utilisateur_id = u.utilisateur_id AND u.statut = 0
            INNER JOIN ROLES r ON a.role_id = r.role_id AND r.statut = 0
            WHERE a.attribution_id = ? AND a.statut = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$attribution_id]);
    $attribution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attribution) {
        http_response_code(404);
        $response = json_encode(['success' => false, 'message' => 'Attribution non trouvée']);
        ob_end_clean();
        echo $response;
        exit();
    }
    
    // Formater la réponse
    $response = json_encode([
        'success' => true,
        'attribution' => [
            'attribution_id' => $attribution['attribution_id'],
            'utilisateur_id' => $attribution['utilisateur_id'],
            'role_id' => $attribution['role_id'],
            'user_nom' => htmlspecialchars($attribution['user_nom'], ENT_QUOTES, 'UTF-8'),
            'user_prenom' => htmlspecialchars($attribution['user_prenom'], ENT_QUOTES, 'UTF-8'),
            'user_email' => htmlspecialchars($attribution['user_email'], ENT_QUOTES, 'UTF-8'),
            'role_fonction' => htmlspecialchars($attribution['role_fonction'], ENT_QUOTES, 'UTF-8'),
            'date_attribution' => $attribution['date_attribution'],
            'date_creation' => $attribution['date_creation']
        ]
    ], JSON_UNESCAPED_UNICODE);
    
    ob_end_clean();
    echo $response;
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur fetch attribution: " . $e->getMessage());
    http_response_code(500);
    $response = json_encode(['success' => false, 'message' => 'Erreur base de données']);
    ob_end_clean();
    echo $response;
    exit();
}
?>
