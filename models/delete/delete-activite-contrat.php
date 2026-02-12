<?php
// ../models/delete/delete-activite-contrat.php
session_start();
require_once '../../connexion/connexion.php';

// Vérifier CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide";
    header('Location: ../../views/activites-contrat.php');
    exit();
}

// Vérifier les permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['roles'][0] ?? '', ['DG', 'ChefDepartement'])) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires";
    header('Location: ../../views/activites-contrat.php');
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID manquant";
    header('Location: ../../views/activites-contrat.php');
    exit();
}

try {
    $activite_id = intval($_GET['id']);
    
    // Récupérer le contrat_id avant suppression pour la redirection
    $query_ref = "SELECT contrat_id FROM ACTIVITES_CONTRAT WHERE activite_id = ?";
    $stmt_ref = $conn->prepare($query_ref);
    $stmt_ref->execute([$activite_id]);
    $result = $stmt_ref->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        $_SESSION['error'] = "Activité non trouvée";
        header('Location: ../../views/activites-contrat.php');
        exit();
    }
    
    $contrat_id = $result['contrat_id'];
    
    // Soft delete (mise à jour du statut)
    $query = "UPDATE ACTIVITES_CONTRAT SET statut = 1 WHERE activite_id = ?";
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([$activite_id]);
    
    if ($success) {
        // Log
        $query_log = "INSERT INTO logs_systeme (utilisateur_id, action, module, details) VALUES (?, ?, ?, ?)";
        $stmt_log = $conn->prepare($query_log);
        $stmt_log->execute([
            $_SESSION['user_id'],
            'DELETE',
            'ACTIVITES_CONTRAT',
            "Suppression de l'activité ID: $activite_id"
        ]);
        
        $_SESSION['msg'] = "Activité supprimée avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression";
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la suppression: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
}

header('Location: ../../views/activites-contrat.php' . (isset($contrat_id) ? '?contrat_id=' . $contrat_id : ''));
exit();
?>