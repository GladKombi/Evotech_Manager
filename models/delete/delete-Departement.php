<?php
session_start();
require_once('../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide";
    header("Location: ../../Views/departements.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Vérifier si le département est utilisé par des agents
        $checkAgents = $conn->prepare("
            SELECT COUNT(*) as agents_count 
            FROM AGENTS 
            WHERE departement_id = ? AND statut = 0
        ");
        $checkAgents->execute([$id]);
        $agentsCount = $checkAgents->fetch(PDO::FETCH_ASSOC);
        
        if ($agentsCount['agents_count'] > 0) {
            $_SESSION['error'] = "Impossible de supprimer ce département : il est encore utilisé par " . $agentsCount['agents_count'] . " agent(s)";
            header("Location: ../../Views/departements.php");
            exit();
        }
        
        // Vérifier si le département est utilisé par des types de production
        $checkProduction = $conn->prepare("
            SELECT COUNT(*) as production_count 
            FROM TYPES_PRODUCTION 
            WHERE departement_id = ? AND statut = 0
        ");
        $checkProduction->execute([$id]);
        $productionCount = $checkProduction->fetch(PDO::FETCH_ASSOC);
        
        if ($productionCount['production_count'] > 0) {
            $_SESSION['error'] = "Impossible de supprimer ce département : il est encore utilisé par " . $productionCount['production_count'] . " type(s) de production";
            header("Location: ../../Views/departements.php");
            exit();
        }
        
        // Soft delete (mise à jour du statut)
        $delete = $conn->prepare("UPDATE DEPARTEMENTS SET statut = 1 WHERE departement_id = ?");
        
        if ($delete->execute([$id])) {
            $_SESSION['msg'] = "Département supprimé avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression du département";
        }
        
    } catch (PDOException $e) {
        error_log("Erreur suppression département: " . $e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de la suppression";
    }
    
    header("Location: ../../Views/departements.php");
    exit();
} else {
    header("Location: ../views/departements.php");
    exit();
}
?>