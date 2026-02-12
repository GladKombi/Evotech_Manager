<?php
require_once '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/mouvements.php');
    exit();
}

$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('comptable', $userRoles)) {
    $_SESSION['error'] = "Vous n'avez pas la permission de supprimer ce mouvement.";
    header('Location: ../../views/mouvements.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de mouvement invalide.";
    header('Location: ../../views/mouvements.php');
    exit();
}

$mouvement_id = intval($_GET['id']);

try {
    // Récupérer le mouvement
    $stmt = $conn->prepare("SELECT * FROM MOUVEMENT_COMPTE WHERE mouvement_id = ? AND statut = 0");
    $stmt->execute([$mouvement_id]);
    $mouvement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mouvement) {
        $_SESSION['error'] = "Le mouvement n'existe pas.";
        header('Location: ../../views/mouvements.php');
        exit();
    }

    // Récupérer le compte pour inverser l'opération
    $stmtCompte = $conn->prepare("SELECT nom, solde_actuel FROM COMPTES WHERE compte_id = ?");
    $stmtCompte->execute([$mouvement['compte_id']]);
    $compte = $stmtCompte->fetch(PDO::FETCH_ASSOC);

    // Inverser l'opération sur le solde
    if ($mouvement['type_mouvement'] === 'entrée') {
        $newSolde = $compte['solde_actuel'] - $mouvement['montant'];
    } else {
        $newSolde = $compte['solde_actuel'] + $mouvement['montant'];
    }

    // Mettre à jour le solde du compte
    $updateCompte = $conn->prepare("UPDATE COMPTES SET solde_actuel = ? WHERE compte_id = ?");
    $updateCompte->execute([$newSolde, $mouvement['compte_id']]);

    // Soft delete du mouvement
    $update = $conn->prepare("UPDATE MOUVEMENT_COMPTE SET statut = 1 WHERE mouvement_id = ?");
    $update->execute([$mouvement_id]);

    // Journaliser
    $user_id = $_SESSION['user_id'];
    $action = "Suppression mouvement ({$mouvement['type_mouvement']}): " . $compte['nom'] . " - " . number_format($mouvement['montant'], 2) . " (ID: $mouvement_id)";
    $log = $conn->prepare("INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR'] ?? '']);

    $_SESSION['msg'] = "Mouvement supprimé avec succès.";
} catch (Exception $e) {
    error_log('Erreur suppression mouvement: ' . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression du mouvement: " . $e->getMessage();
}

header('Location: ../../views/mouvements.php');
exit();

?>
