<?php
include '../../connexion/connexion.php';

// Vérifier les permissions
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/types-production.php');
    exit();
}

// Récupérer les rôles de l'utilisateur
$userRoles = $_SESSION['roles'] ?? [];

// Vérifier les permissions
function hasPermission($requiredRoles, $userRoles) {
    if (empty($requiredRoles)) return true;
    foreach ($requiredRoles as $role) {
        if (in_array($role, $userRoles)) return true;
    }
    return false;
}

if (!hasPermission(['DG', 'Chef_projet'], $userRoles)) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires.";
    header('Location: ../../views/types-production.php');
    exit();
}

try {
    // Récupérer et valider les données
    $nom = trim($_POST['nom'] ?? '');
    $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
    $departement_id = !empty($_POST['departement_id']) ? intval($_POST['departement_id']) : null;
    $taille_max_mo = intval($_POST['taille_max_mo'] ?? 100);
    $extension_autorisee = !empty($_POST['extension_autorisee']) ? trim($_POST['extension_autorisee']) : null;
    
    // Validation
    if (empty($nom)) {
        throw new Exception("Le nom du type est requis.");
    }
    
    if ($taille_max_mo < 1 || $taille_max_mo > 10000) {
        throw new Exception("La taille maximale doit être comprise entre 1 et 10000 MB.");
    }
    
    // Vérifier si le type existe déjà
    $stmt = $conn->prepare("SELECT type_prod_id FROM TYPES_PRODUCTION WHERE nom = ? AND statut = 0");
    $stmt->execute([$nom]);
    if ($stmt->fetch()) {
        throw new Exception("Un type de production avec ce nom existe déjà.");
    }
    
    // Vérifier le département si spécifié
    if ($departement_id) {
        $stmt = $conn->prepare("SELECT departement_id FROM DEPARTEMENTS WHERE departement_id = ? AND statut = 0");
        $stmt->execute([$departement_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Le département sélectionné n'existe pas ou a été supprimé.");
        }
    }
    
    // Valider les extensions
    if ($extension_autorisee) {
        $extensions = array_map('trim', explode(',', $extension_autorisee));
        foreach ($extensions as $ext) {
            if (!preg_match('/^[a-zA-Z0-9]{1,10}$/', $ext)) {
                throw new Exception("Extension invalide: " . htmlspecialchars($ext));
            }
        }
        $extension_autorisee = implode(',', $extensions);
    }
    
    // Insertion du type
    $sql = "INSERT INTO TYPES_PRODUCTION (
        nom, 
        description, 
        departement_id, 
        taille_max_mo, 
        extension_autorisee,
        date_creation
    ) VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $nom,
        $description,
        $departement_id,
        $taille_max_mo,
        $extension_autorisee
    ]);
    
    $_SESSION['msg'] = "Type de production créé avec succès!";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
} catch (PDOException $e) {
    error_log("Erreur PDO lors de l'ajout du type: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur technique est survenue. Veuillez réessayer.";
}

header('Location: ../../views/types-production.php');
exit();
?>