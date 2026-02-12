<?php
require_once '../../connexion/connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/partenaires.php');
    exit();
}

// Vérifier CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF invalide";
    header('Location: ../../views/partenaires.php');
    exit();
}

// Vérifier permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('ChefDepartement', $userRoles) && !in_array('Chef_projet', $userRoles)) {
    $_SESSION['error'] = "Permission refusée";
    header('Location: ../../views/partenaires.php');
    exit();
}

try {
    $partenaire_id = filter_input(INPUT_POST, 'partenaire_id', FILTER_VALIDATE_INT);
    $nom = htmlspecialchars(trim($_POST['nom']));
    $type = $_POST['type'];
    $telephone = !empty($_POST['telephone']) ? htmlspecialchars(trim($_POST['telephone'])) : null;
    $adresse = !empty($_POST['adresse']) ? htmlspecialchars(trim($_POST['adresse'])) : null;
    $email = !empty($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : null;
    $secteur_activite = !empty($_POST['secteur_activite']) ? htmlspecialchars(trim($_POST['secteur_activite'])) : null;
    
    // Validation
    if (!$partenaire_id) {
        $_SESSION['error'] = "ID partenaire invalide";
        header('Location: ../../views/partenaires.php');
        exit();
    }
    
    if (empty($nom)) {
        $_SESSION['error'] = "Le nom du partenaire est requis";
        header('Location: ../../views/partenaires.php');
        exit();
    }
    
    if ($email === false) {
        $_SESSION['error'] = "Adresse email invalide";
        header('Location: ../../views/partenaires.php');
        exit();
    }
    
    // Vérifier si un autre partenaire a déjà ce nom ou email
    $queryCheck = "SELECT COUNT(*) as count FROM PARTENAIRES 
                   WHERE (nom = ? OR email = ?) 
                   AND partenaire_id != ? 
                   AND statut = 0";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->execute([$nom, $email, $partenaire_id]);
    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        $_SESSION['error'] = "Un autre partenaire avec ce nom ou cet email existe déjà";
        header('Location: ../../views/partenaires.php');
        exit();
    }
    
    // Mise à jour
    $query = "UPDATE PARTENAIRES SET 
        nom = ?, type = ?, telephone = ?, adresse = ?, 
        email = ?, secteur_activite = ?
        WHERE partenaire_id = ? AND statut = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $nom, $type, $telephone, $adresse, 
        $email, $secteur_activite, $partenaire_id
    ]);
    
    $_SESSION['msg'] = "Partenaire mis à jour avec succès";
    
} catch (PDOException $e) {
    error_log("Erreur mise à jour partenaire: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la mise à jour du partenaire";
} finally {
    header('Location: ../../views/partenaires.php');
    exit();
}