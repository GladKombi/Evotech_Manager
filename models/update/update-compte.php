<?php
require_once '../../connexion/connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/comptes.php');
    exit();
}

// Vérifier CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF invalide";
    header('Location: ../../views/comptes.php');
    exit();
}

// Vérifier permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('comptable', $userRoles)) {
    $_SESSION['error'] = "Vous n'avez pas la permission de modifier ce compte.";
    header('Location: ../../views/comptes.php');
    exit();
}

try {
    $compte_id = filter_input(INPUT_POST, 'compte_id', FILTER_VALIDATE_INT);
    $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $type = trim($_POST['type'] ?? 'Caisse');
    $numero = htmlspecialchars(trim($_POST['numero_compte'] ?? ''));
    $devise = trim($_POST['devise'] ?? '$');
    $solde_initial = floatval($_POST['solde_initial'] ?? 0);
    $responsable_id = !empty($_POST['responsable_id']) && is_numeric($_POST['responsable_id']) ? intval($_POST['responsable_id']) : null;
    
    // Validation
    if (!$compte_id) {
        $_SESSION['error'] = "ID compte invalide";
        header('Location: ../../views/comptes.php');
        exit();
    }
    
    if (empty($nom)) {
        $_SESSION['error'] = "Le nom du compte est requis";
        header('Location: ../../views/comptes.php');
        exit();
    }
    
    // Vérifier si le compte existe et est actif
    $queryCheck = "SELECT nom FROM COMPTES WHERE compte_id = ? AND statut = 0";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->execute([$compte_id]);
    $compte = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$compte) {
        $_SESSION['error'] = "Le compte n'existe pas ou a été supprimé";
        header('Location: ../../views/comptes.php');
        exit();
    }
    
    // Vérifier si un autre compte a déjà ce nom
    $queryCheckDuplicate = "SELECT COUNT(*) as count FROM COMPTES 
                            WHERE nom = ? 
                            AND compte_id != ? 
                            AND statut = 0";
    $stmtCheckDuplicate = $conn->prepare($queryCheckDuplicate);
    $stmtCheckDuplicate->execute([$nom, $compte_id]);
    $resultDuplicate = $stmtCheckDuplicate->fetch(PDO::FETCH_ASSOC);
    
    if ($resultDuplicate['count'] > 0) {
        $_SESSION['error'] = "Un autre compte porte déjà ce nom";
        header('Location: ../../views/comptes.php');
        exit();
    }
    
    // Mettre à jour le compte
    $query = "UPDATE COMPTES 
              SET nom = ?, type = ?, numero_compte = ?, devise = ?, solde_initial = ?, responsable_id = ?
              WHERE compte_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$nom, $type, $numero, $devise, $solde_initial, $responsable_id, $compte_id]);
    
    // // Journaliser
    // $user_id = $_SESSION['user_id'];
    // $action = "Modification compte: " . $compte['nom'] . " => $nom (ID: $compte_id)";
    // $log = $conn->prepare("INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    // $log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $_SESSION['msg'] = "Compte modifié avec succès.";
} catch (Exception $e) {
    error_log('Erreur modification compte: ' . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la modification du compte: " . $e->getMessage();
}

header('Location: ../../views/comptes.php');
exit();

?>
