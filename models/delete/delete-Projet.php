<?php
session_start();
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/projets.php');
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles)) {
    $_SESSION['error'] = "Seul le DG peut supprimer des projets.";
    header('Location: ../../views/projets.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de projet invalide.";
    header('Location: ../../views/projets.php');
    exit();
}

$projet_id = intval($_GET['id']);

try {
    // Récupérer les infos du projet avant suppression
    $stmt = $conn->prepare("SELECT titre, etat FROM PROJETS WHERE projet_id = ? AND statut = 0");
    $stmt->execute([$projet_id]);
    $projet = $stmt->fetch();
    
    if (!$projet) {
        $_SESSION['error'] = "Le projet n'existe pas.";
        header('Location: ../../views/projets.php');
        exit();
    }
    
    // Vérifier que le projet n'est pas terminé
    if ($projet['etat'] === 'Terminé') {
        $_SESSION['error'] = "Impossible de supprimer un projet terminé.";
        header('Location: ../../views/projets.php');
        exit();
    }
    
    // Marquer le projet comme supprimé (soft delete)
    $query = "UPDATE PROJETS SET statut = 1 WHERE projet_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$projet_id]);
    
    // Marquer aussi les tâches, participations et productions associées
    $queries = [
        "UPDATE TACHES SET statut = 1 WHERE projet_id = ?",
        "UPDATE PARTICIPATION SET statut = 1 WHERE projet_id = ?",
        "UPDATE PRODUCTION SET statut = 1 WHERE projet_id = ?"
    ];
    
    foreach ($queries as $q) {
        $stmt = $conn->prepare($q);
        $stmt->execute([$projet_id]);
    }
    
    // Journaliser l'action
    $user_id = $_SESSION['user_id'];
    $action = "Suppression du projet: " . $projet['titre'] . " (ID: $projet_id)";
    $query_log = "INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)";
    $stmt_log = $conn->prepare($query_log);
    $stmt_log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR']]);
    
    $_SESSION['msg'] = "Projet supprimé avec succès !";
    
} catch (Exception $e) {
    error_log("Erreur suppression projet: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
}

// Rediriger vers la page des projets
header('Location: ../../views/projets.php');
exit();