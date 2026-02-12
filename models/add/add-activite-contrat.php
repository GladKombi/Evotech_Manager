<?php
// ../models/add/add-activite-contrat.php
require_once '../../connexion/connexion.php';

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide";
    header('Location: ../../views/activites-contrat.php' . (isset($_POST['contrat_id']) ? '?contrat_id=' . $_POST['contrat_id'] : ''));
    exit();
}


// Vérifier permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('ChefDepartement', $userRoles)) {
    $_SESSION['error'] = "Permission refusée";
    header('Location: ../../views/contrats-com.php');
    exit();
}

try {
    // Récupérer les données
    $contrat_id = $_POST['contrat_id'];
    $date_activite = $_POST['date_activite'];
    $description_activite = $_POST['description_activite'];
    $details = $_POST['details'] ?: null;
    $lien_url = $_POST['lien_url'] ?: null;
    $cout = $_POST['cout'] ?: 0;
    $statut_activite = $_POST['statut_activite'];

    // Validation des données
    if (empty($contrat_id) || empty($date_activite) || empty($description_activite)) {
        throw new Exception("Tous les champs obligatoires doivent être remplis");
    }

    // Vérifier que le contrat existe et est actif
    $queryCheck = "SELECT reference, etat FROM CONTRATS_COM WHERE contrat_id = ? AND statut = 0";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->execute([$contrat_id]);
    $contrat = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$contrat) {
        throw new Exception("Le contrat spécifié n'existe pas ou a été supprimé");
    }
    
    if ($contrat['etat'] == 'Terminé' || $contrat['etat'] == 'Résilié') {
        throw new Exception("Impossible d'ajouter des activités à un contrat terminé ou résilié");
    }

    // Insérer l'activité
    $query = "INSERT INTO ACTIVITES_CONTRAT (
                contrat_id, date_activite, description_activite, 
                details, lien_url, cout, statut_activite
              ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $contrat_id, $date_activite, $description_activite,
        $details, $lien_url, $cout, $statut_activite
    ]);

    // Récupérer l'ID de l'activité créée
    $activite_id = $conn->lastInsertId();

    // // Enregistrer dans l'historique
    // $query_log = "INSERT INTO logs_systeme (utilisateur_id, action, module, details) VALUES (?, ?, ?, ?)";
    // $stmt_log = $conn->prepare($query_log);
    // $stmt_log->execute([
    //     $_SESSION['user_id'],
    //     'CREATE',
    //     'ACTIVITES_CONTRAT',
    //     "Création d'activité pour contrat {$contrat['reference']} (Activité ID: $activite_id)"
    // ]);

    $_SESSION['msg'] = "Activité ajoutée avec succès";
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de l'ajout de l'activité: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de l'ajout de l'activité : " . $e->getMessage();
} catch (Exception $e) {
    error_log("Erreur lors de l'ajout de l'activité: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

// Redirection
header('Location: ../../views/activites-contrat.php' . (isset($contrat_id) ? '?contrat_id=' . $contrat_id : ''));
exit();
?>