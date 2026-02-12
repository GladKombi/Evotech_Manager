<?php
require_once '../../connexion/connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/transferts.php');
    exit();
}

// Vérifier CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF invalide";
    header('Location: ../../views/transferts.php');
    exit();
}

// Vérifier permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('comptable', $userRoles)) {
    $_SESSION['error'] = "Vous n'avez pas la permission d'effectuer un transfert.";
    header('Location: ../../views/transferts.php');
    exit();
}

try {
    $compte_source_id = filter_input(INPUT_POST, 'compte_source_id', FILTER_VALIDATE_INT);
    $compte_destination_id = filter_input(INPUT_POST, 'compte_destination_id', FILTER_VALIDATE_INT);
    $montant = floatval($_POST['montant'] ?? 0);
    $motif = htmlspecialchars(trim($_POST['motif'] ?? ''));
    $reference_transfert = !empty($_POST['reference_transfert']) ? htmlspecialchars(trim($_POST['reference_transfert'])) : null;
    $date_transfert = date('Y-m-d H:i:s');
    
    // Validation
    if (!$compte_source_id) {
        $_SESSION['error'] = "Compte source invalide";
        header('Location: ../../views/transferts.php');
        exit();
    }
    
    if (!$compte_destination_id) {
        $_SESSION['error'] = "Compte destination invalide";
        header('Location: ../../views/transferts.php');
        exit();
    }
    
    if ($compte_source_id === $compte_destination_id) {
        $_SESSION['error'] = "Les comptes source et destination doivent être différents";
        header('Location: ../../views/transferts.php');
        exit();
    }
    
    if ($montant <= 0) {
        $_SESSION['error'] = "Le montant doit être positif";
        header('Location: ../../views/transferts.php');
        exit();
    }
    
    if (empty($motif)) {
        $_SESSION['error'] = "Le motif est requis";
        header('Location: ../../views/transferts.php');
        exit();
    }
    
    // Vérifier que les deux comptes existent et sont actifs
    $querySource = "SELECT nom, solde_actuel, devise FROM COMPTES WHERE compte_id = ? AND statut = 0";
    $stmtSource = $conn->prepare($querySource);
    $stmtSource->execute([$compte_source_id]);
    $compteSource = $stmtSource->fetch(PDO::FETCH_ASSOC);
    
    if (!$compteSource) {
        $_SESSION['error'] = "Le compte source n'existe pas ou a été supprimé";
        header('Location: ../../views/transferts.php');
        exit();
    }
    
    // Vérifier le solde du compte source
    if ($compteSource['solde_actuel'] < $montant) {
        $_SESSION['error'] = "Solde insuffisant dans le compte source (" . $compteSource['devise'] . number_format($compteSource['solde_actuel'], 2, ',', ' ') . ")";
        header('Location: ../../views/transferts.php');
        exit();
    }
    
    $queryDest = "SELECT nom, solde_actuel FROM COMPTES WHERE compte_id = ? AND statut = 0";
    $stmtDest = $conn->prepare($queryDest);
    $stmtDest->execute([$compte_destination_id]);
    $compteDest = $stmtDest->fetch(PDO::FETCH_ASSOC);
    
    if (!$compteDest) {
        $_SESSION['error'] = "Le compte destination n'existe pas ou a été supprimé";
        header('Location: ../../views/transferts.php');
        exit();
    }
    
    // Commencer une transaction
    $conn->beginTransaction();
    
    try {
        // 1. Créer le mouvement de SORTIE sur le compte source
        $querySortie = "INSERT INTO MOUVEMENT_COMPTE 
                       (compte_id, type_mouvement, montant, motif, cree_par, date_mouvement, statut)
                       VALUES (?, 'sortie', ?, ?, ?, ?, 0)";
        $stmtSortie = $conn->prepare($querySortie);
        $stmtSortie->execute([$compte_source_id, $montant, "Transfert vers " . $compteDest['nom'], $_SESSION['user_id'], $date_transfert]);
        $mouvement_sortie_id = $conn->lastInsertId();
        
        // 2. Créer le mouvement d'ENTRÉE sur le compte destination
        $queryEntree = "INSERT INTO MOUVEMENT_COMPTE 
                       (compte_id, type_mouvement, montant, motif, cree_par, date_mouvement, statut)
                       VALUES (?, 'entrée', ?, ?, ?, ?, 0)";
        $stmtEntree = $conn->prepare($queryEntree);
        $stmtEntree->execute([$compte_destination_id, $montant, "Transfert depuis " . $compteSource['nom'], $_SESSION['user_id'], $date_transfert]);
        $mouvement_entree_id = $conn->lastInsertId();
        
        // 3. Créer l'enregistrement de TRANSFERT
        $queryTransfert = "INSERT INTO TRANSFERTS_COMPTE 
                          (compte_source_id, compte_destination_id, montant, motif, reference_transfert, 
                           mouvement_sortie_id, mouvement_entree_id, date_transfert, valide_par, statut)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmtTransfert = $conn->prepare($queryTransfert);
        $stmtTransfert->execute([
            $compte_source_id, 
            $compte_destination_id, 
            $montant, 
            $motif, 
            $reference_transfert,
            $mouvement_sortie_id, 
            $mouvement_entree_id, 
            $date_transfert,
            $_SESSION['user_id']
        ]);
        $transfert_id = $conn->lastInsertId();
        
        // 4. Mettre à jour les soldes des comptes
        $newSoldeSource = $compteSource['solde_actuel'] - $montant;
        $updateSource = $conn->prepare("UPDATE COMPTES SET solde_actuel = ? WHERE compte_id = ?");
        $updateSource->execute([$newSoldeSource, $compte_source_id]);
        
        $newSoldeDest = $compteDest['solde_actuel'] + $montant;
        $updateDest = $conn->prepare("UPDATE COMPTES SET solde_actuel = ? WHERE compte_id = ?");
        $updateDest->execute([$newSoldeDest, $compte_destination_id]);
        
        // 5. Journaliser (désactivé pour l'instant)
        // $user_id = $_SESSION['user_id'];
        // $action = "Transfert: " . $compteSource['nom'] . " => " . $compteDest['nom'] . " - " . number_format($montant, 2) . " (ID: $transfert_id)";
        // $log = $conn->prepare("INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        // $log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        // Commit la transaction
        $conn->commit();
        
        $_SESSION['msg'] = "Transfert effectué avec succès.";
    } catch (Exception $e) {
        // Rollback en cas d'erreur
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Erreur transfert: ' . $e->getMessage());
    $_SESSION['error'] = "Erreur lors du transfert: " . $e->getMessage();
}

header('Location: ../../views/transferts.php');
exit();

?>
