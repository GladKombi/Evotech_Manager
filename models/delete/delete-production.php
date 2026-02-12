<?php
include '../connexion.php';

// Vérifier le token CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token_production']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../production.php');
    exit();
}

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour effectuer cette action.";
    header('Location: ../../login.php');
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('Chef_projet', $userRoles)) {
    $_SESSION['error'] = "Accès non autorisé.";
    header('Location: ../../production.php');
    exit();
}

// Vérifier que l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de production manquant.";
    header('Location: ../../production.php');
    exit();
}

try {
    $conn->beginTransaction();
    
    $production_id = intval($_GET['id']);
    
    // Récupérer le chemin du fichier et les infos du projet avant suppression
    $query = "SELECT p.*, pr.chef_projet_id 
              FROM PRODUCTION p
              LEFT JOIN PROJETS pr ON p.projet_id = pr.projet_id
              WHERE p.production_id = ? AND p.statut = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$production_id]);
    $production = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$production) {
        $_SESSION['error'] = "Production non trouvée.";
        header('Location: ../../production.php');
        exit();
    }
    
    // Vérifier les permissions
    if (in_array('Chef_projet', $userRoles) && !in_array('DG', $userRoles)) {
        if ($production['chef_projet_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "Vous n'êtes pas autorisé à supprimer cette production.";
            header('Location: ../../production.php');
            exit();
        }
    }
    
    // Supprimer le fichier physique
    $file_path = $production['chemin_stockage'];
    $file_deleted = true;
    
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            error_log("Impossible de supprimer le fichier: " . $file_path);
            $file_deleted = false;
        }
    }
    
    // Mettre à jour le statut dans la base (soft delete)
    $query = "UPDATE PRODUCTION SET statut = 1 WHERE production_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$production_id]);
    
    $conn->commit();
    
    if ($file_deleted) {
        $_SESSION['msg'] = "Production supprimée avec succès!";
    } else {
        $_SESSION['msg'] = "Production marquée comme supprimée, mais le fichier n'a pas pu être supprimé du serveur.";
    }
    
    header('Location: ../../production.php');
    exit();
    
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Erreur PDO suppression production: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression de la production en base de données.";
    header('Location: ../../production.php');
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Erreur suppression production: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression de la production.";
    header('Location: ../../production.php');
    exit();
}
?>