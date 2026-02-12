<?php
include '../../connexion/connexion.php';

// Vérifier les permissions
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
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
    $type_prod_id = intval($_GET['id'] ?? 0);
    
    if ($type_prod_id <= 0) {
        throw new Exception("Identifiant de type invalide.");
    }
    
    // Récupérer les informations du type
    $stmt = $conn->prepare("SELECT type_prod_id, nom FROM TYPES_PRODUCTION WHERE type_prod_id = ? AND statut = 0");
    $stmt->execute([$type_prod_id]);
    $type = $stmt->fetch();
    
    if (!$type) {
        throw new Exception("Le type de production n'existe pas ou a déjà été supprimé.");
    }
    
    // Vérifier s'il y a des productions utilisant ce type
    $stmt = $conn->prepare("SELECT COUNT(*) FROM PRODUCTION WHERE type_prod_id = ? AND statut = 0");
    $stmt->execute([$type_prod_id]);
    $nb_productions = $stmt->fetchColumn();
    
    if ($nb_productions > 0) {
        throw new Exception("Impossible de supprimer ce type car il est utilisé par $nb_productions production(s).");
    }
    
    // Soft delete du type
    $sql = "UPDATE TYPES_PRODUCTION SET statut = 1 WHERE type_prod_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$type_prod_id]);
    
    $_SESSION['msg'] = "Type de production supprimé avec succès!";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la suppression du type: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur technique est survenue. Veuillez réessayer.";
}

header('Location: ../../views/types-production.php');
exit();
?>