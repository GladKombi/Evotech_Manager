<?php
session_start();
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/comptes.php');
    exit();
}

$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('comptable', $userRoles)) {
    $_SESSION['error'] = "Vous n'avez pas la permission de supprimer ce compte.";
    header('Location: ../../views/comptes.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de compte invalide.";
    header('Location: ../../views/comptes.php');
    exit();
}

$compte_id = intval($_GET['id']);

try {
    // Récupérer le compte
    $stmt = $conn->prepare("SELECT nom FROM COMPTES WHERE compte_id = ? AND statut = 0");
    $stmt->execute([$compte_id]);
    $compte = $stmt->fetch();

    if (!$compte) {
        $_SESSION['error'] = "Le compte n'existe pas.";
        header('Location: ../../views/comptes.php');
        exit();
    }

    // Soft delete
    $update = $conn->prepare("UPDATE COMPTES SET statut = 1 WHERE compte_id = ?");
    $update->execute([$compte_id]);

    // Journaliser
    $user_id = $_SESSION['user_id'];
    $action = "Suppression compte: " . $compte['nom'] . " (ID: $compte_id)";
    $log = $conn->prepare("INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR'] ?? '']);

    $_SESSION['msg'] = "Compte supprimé avec succès.";
} catch (Exception $e) {
    error_log('Erreur suppression compte: ' . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression du compte.";
}

header('Location: ../../views/comptes.php');
exit();

?>
