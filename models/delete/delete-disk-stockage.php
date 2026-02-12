<?php
include '../connexion/connexion.php';

// Vérifier CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF invalide";
    header('Location: ../../Views/disk-stockage.php');
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('Chef_projet', $userRoles)) {
    $_SESSION['error'] = "Permission refusée";
    header('Location: ../../Views/disk-stockage.php');
    exit();
}

try {
    $disk_id = intval($_GET['id']);

    // Vérifier si le disque contient des productions
    $checkQuery = "SELECT COUNT(*) FROM PRODUCTION WHERE disk_id = ? AND statut = 0";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$disk_id]);
    $hasProductions = $checkStmt->fetchColumn() > 0;

    if ($hasProductions) {
        throw new Exception("Impossible de supprimer un disque contenant des productions");
    }

    // Soft delete (mettre statut à 1)
    $query = "UPDATE DISK_STOCKAGE SET statut = 1 WHERE disk_id = ? AND statut = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$disk_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['msg'] = "Disque supprimé avec succès";
    } else {
        $_SESSION['error'] = "Disque non trouvé ou déjà supprimé";
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

// Régénérer le token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: ../../Views/disk-stockage.php');
exit();
?>