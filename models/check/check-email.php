<?php
// models/check/check-email.php
session_start();
require_once __DIR__ . '/../../connexion/connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Méthode non autorisée');
}

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['exists' => false]);
    exit;
}

$email = $_POST['email'] ?? '';
$userId = $_POST['user_id'] ?? null;

if (empty($email)) {
    echo json_encode(['exists' => false]);
    exit;
}

// Fonction pour vérifier si l'email existe
function checkEmailExists($email, $excludeId = null) {
    global $conn;
    
    if ($excludeId) {
        $query = "SELECT COUNT(*) as count FROM UTILISATEURS WHERE email = :email AND utilisateur_id != :exclude_id AND statut = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute([':email' => $email, ':exclude_id' => $excludeId]);
    } else {
        $query = "SELECT COUNT(*) as count FROM UTILISATEURS WHERE email = :email AND statut = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute([':email' => $email]);
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['count'] > 0 : false;
}

$exists = checkEmailExists($email, $userId);
echo json_encode(['exists' => $exists]);
?>