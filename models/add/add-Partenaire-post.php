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
    // Récupérer et valider les données
    $nom = htmlspecialchars(trim($_POST['nom']));
    $type = $_POST['type'];
    $telephone = !empty($_POST['telephone']) ? htmlspecialchars(trim($_POST['telephone'])) : null;
    $adresse = !empty($_POST['adresse']) ? htmlspecialchars(trim($_POST['adresse'])) : null;
    $email = !empty($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : null;
    $secteur_activite = !empty($_POST['secteur_activite']) ? htmlspecialchars(trim($_POST['secteur_activite'])) : null;
    
    // Validation
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
    
    // Vérifier si le partenaire existe déjà (nom ou email)
    $queryCheck = "SELECT COUNT(*) as count FROM PARTENAIRES WHERE (nom = ? OR email = ?) AND statut = 0";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->execute([$nom, $email]);
    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        $_SESSION['error'] = "Un partenaire avec ce nom ou cet email existe déjà";
        header('Location: ../../views/partenaires.php');
        exit();
    }
    
    // Insertion
    $query = "INSERT INTO PARTENAIRES (
        nom, type, telephone, adresse, email, secteur_activite
    ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $nom, $type, $telephone, $adresse, $email, $secteur_activite
    ]);
    
    $_SESSION['msg'] = "Partenaire ajouté avec succès";
    
} catch (PDOException $e) {
    error_log("Erreur ajout partenaire: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de l'ajout du partenaire";
} finally {
    header('Location: ../../views/partenaires.php');
    exit();
}