<?php
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/participation.php');
    exit();
}

// Récupérer les rôles de l'utilisateur
$userRoles = $_SESSION['roles'] ?? [];

// Vérifier les permissions
$canAccessRoles = in_array('DG', $userRoles) || in_array('Chef_projet', $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour modifier les participations.";
    header('Location: ../../views/participation.php');
    exit();
}

try {
    // Récupérer les données
    $agent_id = intval($_POST['agent_id'] ?? 0);
    $nouveau_projet_id = !empty($_POST['nouveau_projet_id']) ? intval($_POST['nouveau_projet_id']) : null;
    
    if ($agent_id <= 0) {
        throw new Exception("Agent invalide.");
    }
    
    // Vérifier l'existence de l'agent avec statut = 0 (CORRECTION)
    $stmt = $conn->prepare("SELECT CONCAT(prenom, ' ', nom) as nom_complet FROM AGENTS WHERE agent_id = ? AND statut = 0");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch();
    
    if (!$agent) {
        throw new Exception("L'agent n'existe pas, est inactif ou a été supprimé.");
    }
    
    // Ajouter un nouveau projet si spécifié
    if ($nouveau_projet_id) {
        // Vérifier l'existence du projet avec statut = 0 (CORRECTION)
        $stmt = $conn->prepare("SELECT reference FROM PROJETS WHERE projet_id = ? AND statut = 0 AND etat IN ('En cours', 'Suspendu')");
        $stmt->execute([$nouveau_projet_id]);
        $projet = $stmt->fetch();
        
        if (!$projet) {
            throw new Exception("Le projet sélectionné n'existe pas, est terminé/annulé ou a été supprimé.");
        }
        
        // Vérifier si l'agent est déjà assigné à ce projet (avec statut = 0)
        $stmt = $conn->prepare("SELECT participation_id FROM PARTICIPATION WHERE agent_id = ? AND projet_id = ? AND statut = 0");
        $stmt->execute([$agent_id, $nouveau_projet_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            throw new Exception("Cet agent est déjà assigné à ce projet.");
        }
        
        // Insérer la nouvelle participation
        $query = "INSERT INTO PARTICIPATION (agent_id, projet_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$agent_id, $nouveau_projet_id]);
    }
    
    // Journaliser l'action
    $user_id = $_SESSION['user_id'];
    $action = "Mise à jour participations pour l'agent: {$agent['nom_complet']}";
    $query_log = "INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)";
    $stmt_log = $conn->prepare($query_log);
    $stmt_log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR']]);
    
    $_SESSION['msg'] = "Participations mises à jour avec succès !";
    
} catch (Exception $e) {
    error_log("Erreur mise à jour participations: " . $e->getMessage());
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}

// Rediriger vers la page des participations
header('Location: ../../views/participation.php');
exit();