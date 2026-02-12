<?php
include '../../connexion/connexion.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
    header('Location: ../../login.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/participation.php');
    exit();
}

$participation_id = $_GET['id'] ?? 0;

if (empty($participation_id)) {
    $_SESSION['error'] = "ID de participation manquant.";
    header('Location: ../../views/participation.php');
    exit();
}

try {
    // Vérifier si la participation existe et récupérer les informations
    $checkQuery = "SELECT 
                        p.participation_id,
                        p.agent_id,
                        p.projet_id,
                        pr.reference,
                        pr.titre as projet_titre,
                        CONCAT(a.nom, ' ', a.prenom) as agent_nom,
                        a.email_professionnel
                   FROM PARTICIPATION p
                   LEFT JOIN PROJETS pr ON p.projet_id = pr.projet_id AND pr.statut = 0
                   LEFT JOIN AGENTS a ON p.agent_id = a.agent_id AND a.statut = 0
                   WHERE p.participation_id = ? AND p.statut = 0";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$participation_id]);
    $participation = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participation) {
        $_SESSION['error'] = "Participation non trouvée ou déjà retirée.";
        header('Location: ../../views/participation.php');
        exit();
    }
    
    // Vérifier si l'agent a des tâches actives sur ce projet
    $tachesQuery = "SELECT COUNT(*) as nb_taches 
                    FROM TACHES 
                    WHERE projet_id = ? 
                    AND assigne_a = ? 
                    AND etat NOT IN ('Terminé', 'Bloqué')
                    AND statut = 0";
    $tachesStmt = $conn->prepare($tachesQuery);
    $tachesStmt->execute([$participation['projet_id'], $participation['agent_id']]);
    $nb_taches_actives = $tachesStmt->fetchColumn();
    
    // Vérifier si l'agent a des timesheets non validés sur ce projet
    $timesheetsQuery = "SELECT COUNT(*) as nb_timesheets
                        FROM TIMESHEET ts
                        INNER JOIN TACHES t ON ts.tache_id = t.tache_id
                        WHERE t.projet_id = ?
                        AND ts.agent_id = ?
                        AND ts.etat IN ('Brouillon', 'Soumise')
                        AND ts.statut = 0";
    $timesheetsStmt = $conn->prepare($timesheetsQuery);
    $timesheetsStmt->execute([$participation['projet_id'], $participation['agent_id']]);
    $nb_timesheets_non_valides = $timesheetsStmt->fetchColumn();
    
    $warnings = [];
    if ($nb_taches_actives > 0) {
        $warnings[] = "L'agent a $nb_taches_actives tâche(s) active(s) sur ce projet.";
    }
    if ($nb_timesheets_non_valides > 0) {
        $warnings[] = "L'agent a $nb_timesheets_non_valides timesheet(s) non validé(s) sur ce projet.";
    }
    
    // Marquer comme supprimé (soft delete)
    $deleteQuery = "UPDATE PARTICIPATION SET statut = 1 WHERE participation_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->execute([$participation_id]);
    
    // Journaliser l'action
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $agentInfo = $participation['agent_nom'];
    $projetInfo = $participation['projet_titre'] . ' (' . $participation['reference'] . ')';
    
    // // Vérifier si la table logs_actions existe
    // $checkLogsTable = "SHOW TABLES LIKE 'logs_actions'";
    // $logsTableExists = $conn->query($checkLogsTable)->fetch();
    
    // if ($logsTableExists) {
    //     $logQuery = "INSERT INTO logs_actions (user_id, action, details, ip_address, date_creation) 
    //                  VALUES (?, 'DELETE_PARTICIPATION', ?, ?, NOW())";
    //     $logStmt = $conn->prepare($logQuery);
    //     $logDetails = "Retrait participation - Projet: $projetInfo, Agent: $agentInfo";
    //     $logStmt->execute([$user_id, $logDetails, $ip_address]);
    // }
    
    // Préparer le message de succès
    $successMsg = "Participation retirée avec succès. L'agent " . $agentInfo . " a été retiré du projet " . $projetInfo . ".";
    
    // Ajouter des avertissements si nécessaire
    if (!empty($warnings)) {
        $warningMsg = implode(' ', $warnings);
        $_SESSION['warning'] = $successMsg . " Note: " . $warningMsg;
    } else {
        $_SESSION['msg'] = $successMsg;
    }
    
    header('Location: ../../views/participation.php');
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur lors de la suppression de la participation: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors du retrait de la participation: " . $e->getMessage();
    header('Location: ../../views/participation.php');
    exit();
}
?>