<?php
session_start();
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/projets.php');
    exit();
}

// Récupérer les rôles de l'utilisateur
$userRoles = $_SESSION['roles'] ?? [];

// Vérifier les permissions
$canAccessRoles = in_array('DG', $userRoles) || in_array('Chef_projet', $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour modifier un projet.";
    header('Location: ../../views/projets.php');
    exit();
}

try {
    // Récupérer et valider les données
    $projet_id = intval($_POST['projet_id'] ?? 0);
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description_projet'] ?? '');
    $partenaire_id = !empty($_POST['partenaire_id']) ? intval($_POST['partenaire_id']) : null;
    $chef_projet_id = intval($_POST['chef_projet_id'] ?? 0);
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin_prevue = !empty($_POST['date_fin_prevue']) ? $_POST['date_fin_prevue'] : null;
    $budget_estime = !empty($_POST['budget_estime']) ? floatval($_POST['budget_estime']) : 0.00;
    $priorite = $_POST['priorite'] ?? 'Normale';
    $etat = $_POST['etat'] ?? 'En cours';
    
    if ($projet_id <= 0) {
        throw new Exception("ID de projet invalide.");
    }
    
    if (empty($titre)) {
        throw new Exception("Le titre du projet est requis.");
    }
    
    if ($chef_projet_id <= 0) {
        throw new Exception("Le chef de projet est requis.");
    }
    
    if (empty($date_debut)) {
        throw new Exception("La date de début est requise.");
    }
    
    // Vérifier l'existence du projet
    $stmt = $conn->prepare("SELECT projet_id, titre FROM PROJETS WHERE projet_id = ? AND statut = 0");
    $stmt->execute([$projet_id]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        throw new Exception("Le projet n'existe pas.");
    }
    
    // Mettre à jour le projet
    $query = "
        UPDATE PROJETS SET
            partenaire_id = :partenaire_id,
            titre = :titre,
            description_projet = :description,
            chef_projet_id = :chef_projet_id,
            date_debut = :date_debut,
            date_fin_prevue = :date_fin_prevue,
            budget_estime = :budget_estime,
            priorite = :priorite,
            etat = :etat,
            date_modification = NOW()
        WHERE projet_id = :projet_id AND statut = 0
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':partenaire_id' => $partenaire_id,
        ':titre' => $titre,
        ':description' => $description,
        ':chef_projet_id' => $chef_projet_id,
        ':date_debut' => $date_debut,
        ':date_fin_prevue' => $date_fin_prevue,
        ':budget_estime' => $budget_estime,
        ':priorite' => $priorite,
        ':etat' => $etat,
        ':projet_id' => $projet_id
    ]);
    
    // Journaliser l'action
    $user_id = $_SESSION['user_id'];
    $action = "Modification du projet: " . $existing['titre'] . " (ID: $projet_id)";
    $query_log = "INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)";
    $stmt_log = $conn->prepare($query_log);
    $stmt_log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR']]);
    
    $_SESSION['msg'] = "Projet modifié avec succès !";
    
} catch (Exception $e) {
    error_log("Erreur modification projet: " . $e->getMessage());
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}

// Rediriger vers la page des projets
header('Location: ../../views/projets.php');
exit();