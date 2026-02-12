<?php
include '../../connexion/connexion.php';

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
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
    // Récupérer et valider les données
    $disk_id = intval($_POST['disk_id']);
    $matricule = trim($_POST['matricule']);
    $capacite_go = intval($_POST['capacite_go']);
    $etat = $_POST['etat'];
    $emplacement_physique = trim($_POST['emplacement_physique'] ?? '');
    $date_acquisition = !empty($_POST['date_acquisition']) ? $_POST['date_acquisition'] : null;

    // Vérifier les données
    if (empty($matricule) || $capacite_go <= 0 || $disk_id <= 0) {
        throw new Exception("Données invalides");
    }

    // Vérifier si le matricule existe déjà (pour un autre disque)
    $checkQuery = "SELECT COUNT(*) FROM DISK_STOCKAGE WHERE matricule = ? AND disk_id != ? AND statut = 0";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$matricule, $disk_id]);
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception("Un autre disque avec ce matricule existe déjà");
    }

    // Mettre à jour le disque
    $query = "UPDATE DISK_STOCKAGE SET 
              matricule = ?, 
              capacite_go = ?, 
              etat = ?, 
              emplacement_physique = ?, 
              date_acquisition = ?
              WHERE disk_id = ? AND statut = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$matricule, $capacite_go, $etat, $emplacement_physique, $date_acquisition, $disk_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['msg'] = "Disque mis à jour avec succès";
    } else {
        $_SESSION['error'] = "Disque non trouvé ou déjà supprimé";
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}

// Régénérer le token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: ../../Views/disk-stockage.php');
exit();
?>