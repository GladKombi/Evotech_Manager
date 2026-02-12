<?php
// ../models/update/update-activite-contrat.php
require_once '../../connexion/connexion.php';

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
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

try {
    $activite_id = $_POST['activite_id'];
    $contrat_id = $_POST['contrat_id'];
    $date_activite = $_POST['date_activite'];
    $description_activite = $_POST['description_activite'];
    $details = $_POST['details'] ?: null;
    $lien_url = $_POST['lien_url'] ?: null;
    $cout = $_POST['cout'] ?: 0;
    $statut_activite = $_POST['statut_activite'];

    // Validation
    if (empty($activite_id) || empty($contrat_id) || empty($date_activite) || empty($description_activite)) {
        throw new Exception("Tous les champs obligatoires doivent être remplis");
    }

    // Mettre à jour
    $query = "UPDATE ACTIVITES_CONTRAT SET 
                date_activite = ?, 
                description_activite = ?, 
                details = ?, 
                lien_url = ?, 
                cout = ?, 
                statut_activite = ?,
                date_creation = CURRENT_TIMESTAMP
              WHERE activite_id = ? AND contrat_id = ? AND statut = 0";
    
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([
        $date_activite, $description_activite, $details,
        $lien_url, $cout, $statut_activite,
        $activite_id, $contrat_id
    ]);

    if ($success) {
        $_SESSION['msg'] = "Activité mise à jour avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour de l'activité";
    }

    // if ($success && $stmt->rowCount() > 0) {
    //     // Log
    //     $query_log = "INSERT INTO logs_systeme (utilisateur_id, action, module, details) VALUES (?, ?, ?, ?)";
    //     $stmt_log = $conn->prepare($query_log);
    //     $stmt_log->execute([
    //         $_SESSION['user_id'],
    //         'UPDATE',
    //         'ACTIVITES_CONTRAT',
    //         "Modification de l'activité ID: $activite_id"
    //     ]);
        
    //     $_SESSION['msg'] = "Activité mise à jour avec succès";
    // } else {
    //     $_SESSION['error'] = "Aucune modification effectuée ou activité non trouvée";
    // }
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la mise à jour: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../../views/activites-contrat.php' . (isset($contrat_id) ? '?contrat_id=' . $contrat_id : ''));
exit();
?>