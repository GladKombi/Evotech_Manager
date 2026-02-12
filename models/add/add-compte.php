<?php
include '../../connexion/connexion.php';

// // Vérifier l'autorisation
// if (!empty($_SESSION['user_id'])) {
//     header('Location: ../../login.php');
//     exit();
// }

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/comptes.php');
    exit();
}

$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('comptable', $userRoles)) {
    $_SESSION['error'] = "Vous n'avez pas la permission de créer un compte.";
    header('Location: ../../views/comptes.php');
    exit();
}

try {
    $nom = trim($_POST['nom'] ?? '');
    $type = trim($_POST['type'] ?? 'Caisse');
    $numero = trim($_POST['numero_compte'] ?? '');
    $devise = trim($_POST['devise'] ?? '$');
    $solde_initial = floatval($_POST['solde_initial'] ?? 0);
    $responsable_id = !empty($_POST['responsable_id']) && is_numeric($_POST['responsable_id']) ? intval($_POST['responsable_id']) : null;

    if ($nom === '') {
        $_SESSION['error'] = "Le nom du compte est requis.";
        header('Location: ../../views/comptes.php');
        exit();
    }

    $query = "INSERT INTO COMPTES (nom, type, numero_compte, devise, solde_initial, solde_actuel, responsable_id, statut)
              VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
    $stmt = $conn->prepare($query);
    $solde_actuel = $solde_initial;
    $stmt->execute([$nom, $type, $numero, $devise, $solde_initial, $solde_actuel, $responsable_id]);

    // // Journaliser
    // $user_id = $_SESSION['user_id'];
    // $lastId = $conn->lastInsertId();
    // $action = "Création compte: $nom (ID: $lastId)";
    // $log = $conn->prepare("INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    // $log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR'] ?? '']);

    $_SESSION['msg'] = "Compte créé avec succès.";
} catch (Exception $e) {
    error_log('Erreur ajout compte: ' . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la création du compte: " . $e->getMessage();
}

header('Location: ../../views/comptes.php');
exit();

?>
