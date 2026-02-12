<?php
include '../../connexion/connexion.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
    header('Location: ../../login.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/participation.php');
    exit();
}

// Récupérer les données du formulaire
$projet_id = $_POST['projet_id'] ?? 0;
$agent_id = $_POST['agent_id'] ?? 0;

// Validation
if (empty($projet_id) || empty($agent_id)) {
    $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
    header('Location: ../../views/participation.php');
    exit();
}

try {
    // Vérifier si la participation existe déjà
    $checkQuery = "SELECT participation_id FROM PARTICIPATION 
                   WHERE projet_id = ? AND agent_id = ? AND statut = 0";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$projet_id, $agent_id]);
    
    if ($checkStmt->fetch()) {
        $_SESSION['error'] = "Cette participation existe déjà. Cet agent est déjà assigné à ce projet.";
        header('Location: ../../views/participation.php');
        exit();
    }
    
    // Vérifier si le projet existe et est actif
    $projetQuery = "SELECT projet_id, titre, etat FROM PROJETS WHERE projet_id = ? AND statut = 0";
    $projetStmt = $conn->prepare($projetQuery);
    $projetStmt->execute([$projet_id]);
    $projet = $projetStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$projet) {
        $_SESSION['error'] = "Le projet sélectionné n'existe pas ou a été supprimé.";
        header('Location: ../../views/participation.php');
        exit();
    }
    
    // Vérifier si le projet est terminé ou annulé
    if (in_array($projet['etat'], ['Terminé', 'Annulé'])) {
        $_SESSION['error'] = "Impossible d'ajouter une participation à un projet " . strtolower($projet['etat']) . ".";
        header('Location: ../../views/participation.php');
        exit();
    }
    
    // Vérifier si l'agent existe et est actif
    $agentQuery = "SELECT agent_id, nom, prenom FROM AGENTS WHERE agent_id = ? AND statut = 0";
    $agentStmt = $conn->prepare($agentQuery);
    $agentStmt->execute([$agent_id]);
    $agent = $agentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agent) {
        $_SESSION['error'] = "L'agent sélectionné n'existe pas ou a été supprimé.";
        header('Location: ../../views/participation.php');
        exit();
    }
    
    // Vérifier si l'agent est actif
    $agentEtatQuery = "SELECT etat FROM AGENTS WHERE agent_id = ?";
    $agentEtatStmt = $conn->prepare($agentEtatQuery);
    $agentEtatStmt->execute([$agent_id]);
    $agentEtat = $agentEtatStmt->fetchColumn();
    
    if ($agentEtat !== 'Actif') {
        $_SESSION['error'] = "L'agent sélectionné n'est pas actif. État: " . $agentEtat;
        header('Location: ../../views/participation.php');
        exit();
    }
    
    // Insérer la nouvelle participation
    $insertQuery = "INSERT INTO PARTICIPATION (projet_id, agent_id, date_creation) VALUES (?, ?, NOW())";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->execute([$projet_id, $agent_id]);
    
    // Récupérer les infos pour le log
    $projetInfo = $projet['titre'];
    $agentInfo = $agent['nom'] . ' ' . $agent['prenom'];
    
    // // Journaliser l'action
    // $user_id = $_SESSION['user_id'];
    // $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // // Vérifier si la table logs_actions existe
    // $checkLogsTable = "SHOW TABLES LIKE 'logs_actions'";
    // $logsTableExists = $conn->query($checkLogsTable)->fetch();
    
    // if ($logsTableExists) {
    //     $logQuery = "INSERT INTO logs_actions (user_id, action, details, ip_address, date_creation) 
    //                  VALUES (?, 'ADD_PARTICIPATION', ?, ?, NOW())";
    //     $logStmt = $conn->prepare($logQuery);
    //     $logDetails = "Ajout participation - Projet: $projetInfo, Agent: $agentInfo";
    //     $logStmt->execute([$user_id, $logDetails, $ip_address]);
    // }
    
    $_SESSION['msg'] = "Participation ajoutée avec succès. L'agent " . $agentInfo . " a été assigné au projet " . $projetInfo . ".";
    header('Location: ../../views/participation.php');
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur lors de l'ajout de la participation: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de l'ajout de la participation: " . $e->getMessage();
    header('Location: ../../views/participation.php');
    exit();
}
?>