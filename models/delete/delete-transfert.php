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
    header('Location: ../../views/transferts.php');
    exit();
}

$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('comptable', $userRoles)) {
    $_SESSION['error'] = "Vous n'avez pas la permission de supprimer ce transfert.";
    header('Location: ../../views/transferts.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de transfert invalide.";
    header('Location: ../../views/transferts.php');
    exit();
}

$transfert_id = intval($_GET['id']);

try {
    // Récupérer le transfert
    $queryTransfert = "SELECT * FROM TRANSFERTS_COMPTE WHERE transfert_id = ? AND statut = 0";
    $stmtTransfert = $conn->prepare($queryTransfert);
    $stmtTransfert->execute([$transfert_id]);
    $transfert = $stmtTransfert->fetch(PDO::FETCH_ASSOC);

    if (!$transfert) {
        $_SESSION['error'] = "Le transfert n'existe pas.";
        header('Location: ../../views/transferts.php');
        exit();
    }

    // Récupérer les informations des comptes
    $queryComptes = "SELECT c1.nom as nom_source, c1.solde_actuel as solde_source,
                           c2.nom as nom_dest, c2.solde_actuel as solde_dest
                    FROM COMPTES c1 
                    JOIN COMPTES c2 ON c2.compte_id = ?
                    WHERE c1.compte_id = ?";
    $stmtComptes = $conn->prepare($queryComptes);
    $stmtComptes->execute([$transfert['compte_destination_id'], $transfert['compte_source_id']]);
    $comptes = $stmtComptes->fetch(PDO::FETCH_ASSOC);

    // Commencer une transaction
    $conn->beginTransaction();

    try {
        // 1. Supprimer les deux mouvements liés (soft delete)
        $updateMouvements = $conn->prepare("UPDATE MOUVEMENT_COMPTE SET statut = 1 WHERE mouvement_id IN (?, ?)");
        $updateMouvements->execute([$transfert['mouvement_sortie_id'], $transfert['mouvement_entree_id']]);

        // 2. Inverser les soldes des comptes
        $newSoldeSource = $comptes['solde_source'] + $transfert['montant'];
        $updateSource = $conn->prepare("UPDATE COMPTES SET solde_actuel = ? WHERE compte_id = ?");
        $updateSource->execute([$newSoldeSource, $transfert['compte_source_id']]);

        $newSoldeDest = $comptes['solde_dest'] - $transfert['montant'];
        $updateDest = $conn->prepare("UPDATE COMPTES SET solde_actuel = ? WHERE compte_id = ?");
        $updateDest->execute([$newSoldeDest, $transfert['compte_destination_id']]);

        // 3. Soft delete du transfert
        $updateTransfert = $conn->prepare("UPDATE TRANSFERTS_COMPTE SET statut = 1 WHERE transfert_id = ?");
        $updateTransfert->execute([$transfert_id]);

        // 4. Journaliser (désactivé pour l'instant)
        // $user_id = $_SESSION['user_id'];
        // $action = "Suppression transfert: " . $comptes['nom_source'] . " => " . $comptes['nom_dest'] . " - " . number_format($transfert['montant'], 2) . " (ID: $transfert_id)";
        // $log = $conn->prepare("INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        // $log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR'] ?? '']);

        // Commit la transaction
        $conn->commit();

        $_SESSION['msg'] = "Transfert supprimé avec succès.";
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Erreur suppression transfert: ' . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression du transfert: " . $e->getMessage();
}

header('Location: ../../views/transferts.php');
exit();

?>
