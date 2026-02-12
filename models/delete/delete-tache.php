<?php
include '../../connexion/connexion.php';

// Vérifier les permissions
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/taches.php');
    exit();
}

// Récupérer les rôles de l'utilisateur
$userRoles = $_SESSION['roles'] ?? [];

// Vérifier les permissions
function hasPermission($requiredRoles, $userRoles) {
    if (empty($requiredRoles)) return true;
    foreach ($requiredRoles as $role) {
        if (in_array($role, $userRoles)) return true;
    }
    return false;
}

if (!hasPermission(['DG', 'Chef_projet'], $userRoles)) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires.";
    header('Location: ../../views/taches.php');
    exit();
}

try {
    $tache_id = intval($_GET['id'] ?? 0);
    
    if ($tache_id <= 0) {
        throw new Exception("Identifiant de tâche invalide.");
    }
    
    // Vérifier que la tâche existe
    $stmt = $conn->prepare("SELECT tache_id, description_tache FROM TACHES WHERE tache_id = ? AND statut = 0");
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch();
    
    if (!$tache) {
        throw new Exception("La tâche n'existe pas ou a déjà été supprimée.");
    }
    
    // Vérifier si la tâche est terminée
    $stmt = $conn->prepare("SELECT etat FROM TACHES WHERE tache_id = ?");
    $stmt->execute([$tache_id]);
    $tache_etat = $stmt->fetchColumn();
    
    if ($tache_etat === 'Terminé') {
        throw new Exception("Impossible de supprimer une tâche terminée.");
    }
    
    // Vérifier s'il y a des timesheets associés
    $stmt = $conn->prepare("SELECT COUNT(*) FROM TIMESHEET WHERE tache_id = ? AND statut = 0");
    $stmt->execute([$tache_id]);
    $nb_timesheets = $stmt->fetchColumn();
    
    if ($nb_timesheets > 0) {
        // Marquer le timesheet comme supprimé aussi (soft delete)
        $stmt = $conn->prepare("UPDATE TIMESHEET SET statut = 1 WHERE tache_id = ?");
        $stmt->execute([$tache_id]);
    }
    
    // Soft delete de la tâche
    $sql = "UPDATE TACHES SET statut = 1 WHERE tache_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$tache_id]);
    
    $_SESSION['msg'] = "Tâche supprimée avec succès!";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la suppression de la tâche: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur technique est survenue. Veuillez réessayer.";
}

header('Location: ../../views/taches.php');
exit();
?>