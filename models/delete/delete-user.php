<?php
session_start();
require_once('../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['msg'] = "Token de sécurité invalide";
    header("Location: ../views/utilisateurs.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Vérifier si l'utilisateur est le dernier administrateur
        $checkAdmin = $conn->prepare("
            SELECT COUNT(*) as admin_count 
            FROM UTILISATEURS u
            INNER JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id AND a.statut = 0
            INNER JOIN ROLES r ON a.role_id = r.role_id AND r.statut = 0
            WHERE u.statut = 0 AND r.fonction = 'DG'
        ");
        $checkAdmin->execute();
        $adminCount = $checkAdmin->fetch(PDO::FETCH_ASSOC);
        
        // Vérifier si cet utilisateur est DG
        $checkUserIsDG = $conn->prepare("
            SELECT COUNT(*) as is_dg 
            FROM ATTRIBUTIONS a
            INNER JOIN ROLES r ON a.role_id = r.role_id
            WHERE a.utilisateur_id = ? AND a.statut = 0 AND r.fonction = 'DG'
        ");
        $checkUserIsDG->execute([$id]);
        $userIsDG = $checkUserIsDG->fetch(PDO::FETCH_ASSOC);
        
        // Empêcher la suppression du dernier DG
        if ($userIsDG['is_dg'] > 0 && $adminCount['admin_count'] <= 1) {
            $_SESSION['msg'] = "Impossible de supprimer le dernier administrateur DG";
            header("Location: ../views/utilisateurs.php");
            exit();
        }
        
        // Soft delete (mise à jour du statut et deleted_at)
        $delete = $conn->prepare("
            UPDATE UTILISATEURS 
            SET statut = 1, deleted_at = NOW() 
            WHERE utilisateur_id = ?
        ");
        
        if ($delete->execute([$id])) {
            // Désactiver aussi toutes ses attributions
            $disableAttributions = $conn->prepare("
                UPDATE ATTRIBUTIONS 
                SET statut = 1 
                WHERE utilisateur_id = ?
            ");
            $disableAttributions->execute([$id]);
            
            $_SESSION['msg'] = "Utilisateur supprimé avec succès";
        } else {
            $_SESSION['msg'] = "Erreur lors de la suppression de l'utilisateur";
        }
        
    } catch (PDOException $e) {
        error_log("Erreur suppression utilisateur: " . $e->getMessage());
        $_SESSION['msg'] = "Une erreur est survenue lors de la suppression";
    }
    
    header("Location: ../views/utilisateurs.php");
    exit();
} else {
    header("Location: ../views/utilisateurs.php");
    exit();
}
?>