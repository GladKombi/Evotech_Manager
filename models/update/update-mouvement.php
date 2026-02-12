<?php
require_once '../../connexion/connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/mouvements.php');
    exit();
}

// Vérifier CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF invalide";
    header('Location: ../../views/mouvements.php');
    exit();
}

// Vérifier permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('comptable', $userRoles)) {
    $_SESSION['error'] = "Vous n'avez pas la permission de modifier ce mouvement.";
    header('Location: ../../views/mouvements.php');
    exit();
}

try {
    $mouvement_id = filter_input(INPUT_POST, 'mouvement_id', FILTER_VALIDATE_INT);
    $compte_id = filter_input(INPUT_POST, 'compte_id', FILTER_VALIDATE_INT);
    $type_mouvement = trim($_POST['type_mouvement'] ?? '');
    $montant = floatval($_POST['montant'] ?? 0);
    $motif = htmlspecialchars(trim($_POST['motif'] ?? ''));
    $projet_id = !empty($_POST['projet_id']) && is_numeric($_POST['projet_id']) ? intval($_POST['projet_id']) : null;
    $contrat_id = !empty($_POST['contrat_id']) && is_numeric($_POST['contrat_id']) ? intval($_POST['contrat_id']) : null;
    $reference_piece = !empty($_POST['reference_piece']) ? htmlspecialchars(trim($_POST['reference_piece'])) : null;
    $date_mouvement = !empty($_POST['date_mouvement']) ? $_POST['date_mouvement'] : date('Y-m-d H:i:s');
    
    // Validation
    if (!$mouvement_id) {
        $_SESSION['error'] = "ID mouvement invalide";
        header('Location: ../../views/mouvements.php');
        exit();
    }
    
    if (!$compte_id) {
        $_SESSION['error'] = "ID compte invalide";
        header('Location: ../../views/mouvements.php');
        exit();
    }
    
    if (empty($type_mouvement) || !in_array($type_mouvement, ['entrée', 'sortie'])) {
        $_SESSION['error'] = "Type de mouvement invalide";
        header('Location: ../../views/mouvements.php');
        exit();
    }
    
    if ($montant <= 0) {
        $_SESSION['error'] = "Le montant doit être positif";
        header('Location: ../../views/mouvements.php');
        exit();
    }
    
    if (empty($motif)) {
        $_SESSION['error'] = "Le motif est requis";
        header('Location: ../../views/mouvements.php');
        exit();
    }
    
    // Récupérer le mouvement existant
    $queryMouvement = "SELECT * FROM MOUVEMENT_COMPTE WHERE mouvement_id = ? AND statut = 0";
    $stmtMouvement = $conn->prepare($queryMouvement);
    $stmtMouvement->execute([$mouvement_id]);
    $ancienMouvement = $stmtMouvement->fetch(PDO::FETCH_ASSOC);
    
    if (!$ancienMouvement) {
        $_SESSION['error'] = "Le mouvement n'existe pas ou a été supprimé";
        header('Location: ../../views/mouvements.php');
        exit();
    }
    
    // Vérifier que le compte existe et est actif
    $queryCheck = "SELECT nom, solde_actuel FROM COMPTES WHERE compte_id = ? AND statut = 0";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->execute([$compte_id]);
    $compte = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$compte) {
        $_SESSION['error'] = "Le compte n'existe pas ou a été supprimé";
        header('Location: ../../views/mouvements.php');
        exit();
    }
    
    // Calculer le nouveau solde
    $soldeActuel = $compte['solde_actuel'];
    
    // Inverser l'ancien mouvement
    if ($ancienMouvement['type_mouvement'] === 'entrée') {
        $soldeActuel -= $ancienMouvement['montant'];
    } else {
        $soldeActuel += $ancienMouvement['montant'];
    }
    
    // Appliquer le nouveau mouvement
    if ($type_mouvement === 'entrée') {
        $newSolde = $soldeActuel + $montant;
    } else {
        $newSolde = $soldeActuel - $montant;
    }
    
    // Mettre à jour le mouvement
    $query = "UPDATE MOUVEMENT_COMPTE 
              SET compte_id = ?, type_mouvement = ?, montant = ?, motif = ?, projet_id = ?, contrat_id = ?, reference_piece = ?, date_mouvement = ?
              WHERE mouvement_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$compte_id, $type_mouvement, $montant, $motif, $projet_id, $contrat_id, $reference_piece, $date_mouvement, $mouvement_id]);
    
    // Mettre à jour le solde du compte
    $updateQuery = "UPDATE COMPTES SET solde_actuel = ? WHERE compte_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([$newSolde, $compte_id]);
    
    // Journaliser
    $user_id = $_SESSION['user_id'];
    $ancienDetail = $ancienMouvement['type_mouvement'] . ' ' . number_format($ancienMouvement['montant'], 2);
    $nouveauDetail = $type_mouvement . ' ' . number_format($montant, 2);
    $action = "Modification mouvement: $ancienDetail => $nouveauDetail (ID: $mouvement_id)";
    $log = $conn->prepare("INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $_SESSION['msg'] = "Mouvement modifié avec succès.";
} catch (Exception $e) {
    error_log('Erreur modification mouvement: ' . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la modification du mouvement: " . $e->getMessage();
}

header('Location: ../../views/mouvements.php');
exit();

?>
