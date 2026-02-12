<?php
include '../../connexion/connexion.php';

// Vérifier les permissions
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
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
    // Récupérer et valider les données
    $tache_id = intval($_POST['tache_id'] ?? 0);
    $description_tache = trim($_POST['description_tache'] ?? '');
    $projet_id = intval($_POST['projet_id'] ?? 0);
    $assigne_a = !empty($_POST['assigne_a']) ? intval($_POST['assigne_a']) : null;
    $date_debut_prevue = !empty($_POST['date_debut_prevue']) ? $_POST['date_debut_prevue'] : null;
    $date_fin_prevue = !empty($_POST['date_fin_prevue']) ? $_POST['date_fin_prevue'] : null;
    $estimation_heures = !empty($_POST['estimation_heures']) ? floatval($_POST['estimation_heures']) : 0;
    $priorite = $_POST['priorite'] ?? 'Normale';
    $etat = $_POST['etat'] ?? 'À faire';
    
    // Validation
    if ($tache_id <= 0) {
        throw new Exception("Identifiant de tâche invalide.");
    }
    
    if (empty($description_tache)) {
        throw new Exception("La description de la tâche est requise.");
    }
    
    if ($projet_id <= 0) {
        throw new Exception("Un projet valide est requis.");
    }
    
    // Vérifier que la tâche existe
    $stmt = $conn->prepare("SELECT tache_id FROM TACHES WHERE tache_id = ? AND statut = 0");
    $stmt->execute([$tache_id]);
    if (!$stmt->fetch()) {
        throw new Exception("La tâche n'existe pas ou a été supprimée.");
    }
    
    // Vérifier que le projet existe
    $stmt = $conn->prepare("SELECT projet_id FROM PROJETS WHERE projet_id = ? AND statut = 0");
    $stmt->execute([$projet_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Le projet sélectionné n'existe pas ou a été supprimé.");
    }
    
    // Vérifier l'agent assigné si spécifié
    if ($assigne_a) {
        $stmt = $conn->prepare("SELECT agent_id FROM AGENTS WHERE agent_id = ? AND statut = 0 AND etat = 'Actif'");
        $stmt->execute([$assigne_a]);
        if (!$stmt->fetch()) {
            throw new Exception("L'agent sélectionné n'existe pas ou n'est pas actif.");
        }
    }
    
    // Vérifier les dates
    if ($date_debut_prevue && $date_fin_prevue && $date_debut_prevue > $date_fin_prevue) {
        throw new Exception("La date de début ne peut pas être postérieure à la date de fin.");
    }
    
    // Mise à jour de la tâche
    $sql = "UPDATE TACHES SET 
        projet_id = ?, 
        description_tache = ?, 
        assigne_a = ?, 
        date_debut_prevue = ?, 
        date_fin_prevue = ?, 
        priorite = ?, 
        etat = ?, 
        estimation_heures = ?
    WHERE tache_id = ? AND statut = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $projet_id,
        $description_tache,
        $assigne_a,
        $date_debut_prevue,
        $date_fin_prevue,
        $priorite,
        $etat,
        $estimation_heures,
        $tache_id
    ]);
    
    $_SESSION['msg'] = "Tâche mise à jour avec succès!";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la mise à jour de la tâche: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur technique est survenue. Veuillez réessayer.";
}

header('Location: ../../views/taches.php');
exit();
?>