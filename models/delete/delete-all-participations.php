<?php
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/participation.php');
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles)) {
    $_SESSION['error'] = "Seul le DG peut retirer un agent de tous ses projets.";
    header('Location: ../../views/participation.php');
    exit();
}

if (!isset($_GET['agent_id']) || !is_numeric($_GET['agent_id'])) {
    $_SESSION['error'] = "ID agent invalide.";
    header('Location: ../../views/participation.php');
    exit();
}

$agent_id = intval($_GET['agent_id']);

try {
    // Récupérer les infos de l'agent
    $stmt = $conn->prepare("SELECT nom, prenom FROM AGENTS WHERE agent_id = ? AND statut = 0");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch();
    
    if (!$agent) {
        $_SESSION['error'] = "Agent non trouvé.";
        header('Location: ../../views/participation.php');
        exit();
    }
    
    // Compter le nombre de participations avant suppression
    $stmt = $conn->prepare("SELECT COUNT(*) as nb_participations FROM PARTICIPATION WHERE agent_id = ? AND statut = 0");
    $stmt->execute([$agent_id]);
    $count = $stmt->fetch();
    
    // Marquer toutes les participations comme supprimées
    $query = "UPDATE PARTICIPATION SET statut = 1 WHERE agent_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$agent_id]);
    
    // Journaliser l'action
    $user_id = $_SESSION['user_id'];
    $action = "Retrait complet de {$agent['prenom']} {$agent['nom']} de tous ses projets ({$count['nb_participations']} participations)";
    $query_log = "INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)";
    $stmt_log = $conn->prepare($query_log);
    $stmt_log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR']]);
    
    $_SESSION['msg'] = "Agent retiré de tous ses projets avec succès !";
    
} catch (Exception $e) {
    error_log("Erreur suppression complète participations: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
}

// Rediriger vers la page des participations
header('Location: ../../views/participation.php');
exit();