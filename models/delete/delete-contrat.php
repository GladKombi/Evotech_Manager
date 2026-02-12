<?php
session_start();
require_once '../../connexion/connexion.php';

// Vérifier permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('ChefDepartement', $userRoles)) {
    $_SESSION['error'] = "Permission refusée";
    header('Location: ../../views/contrats-com.php');
    exit();
}

// Vérifier CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF invalide";
    header('Location: ../../views/contrats-com.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    try {
        // Vérifier s'il y a des activités liées
        $query = "SELECT COUNT(*) as count FROM ACTIVITES_CONTRAT WHERE contrat_id = ? AND statut = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $_SESSION['error'] = "Impossible de supprimer : il y a des activités liées à ce contrat";
        } else {
            $query = "UPDATE CONTRATS_COM SET statut = 1 WHERE contrat_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$id]);
            
            $_SESSION['msg'] = "Contrat supprimé avec succès";
        }
    } catch (PDOException $e) {
        error_log("Erreur suppression contrat: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de la suppression";
    }
}

header('Location: ../../views/contrats-com.php');
exit();