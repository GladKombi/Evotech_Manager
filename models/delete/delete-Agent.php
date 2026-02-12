<?php
require_once('../../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['msg'] = "Token de sécurité invalide";
    header("Location: ../../views/agents.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Vérifier si l'agent est impliqué dans des projets
        $checkProjects = $conn->prepare("
            SELECT COUNT(*) as projects_count 
            FROM PARTICIPATION 
            WHERE agent_id = ? AND statut = 0
        ");
        $checkProjects->execute([$id]);
        $projectsCount = $checkProjects->fetch(PDO::FETCH_ASSOC);
        
        if ($projectsCount['projects_count'] > 0) {
            $_SESSION['msg'] = "Impossible de supprimer cet agent : il est impliqué dans " . $projectsCount['projects_count'] . " projet(s)";
            header("Location: ../../views/agents.php");
            exit();
        }
        
        // Vérifier si l'agent a des tâches assignées
        $checkTasks = $conn->prepare("
            SELECT COUNT(*) as tasks_count 
            FROM TACHES 
            WHERE assigne_a = ? AND statut = 0
        ");
        $checkTasks->execute([$id]);
        $tasksCount = $checkTasks->fetch(PDO::FETCH_ASSOC);
        
        if ($tasksCount['tasks_count'] > 0) {
            $_SESSION['msg'] = "Impossible de supprimer cet agent : il a " . $tasksCount['tasks_count'] . " tâche(s) assignée(s)";
            header("Location: ../../views/agents.php");
            exit();
        }
        
        // Vérifier si l'agent est responsable de contrats
        $checkContrats = $conn->prepare("
            SELECT COUNT(*) as contrats_count 
            FROM CONTRATS_COM 
            WHERE responsable_id = ? AND statut = 0
        ");
        $checkContrats->execute([$id]);
        $contratsCount = $checkContrats->fetch(PDO::FETCH_ASSOC);
        
        if ($contratsCount['contrats_count'] > 0) {
            $_SESSION['msg'] = "Impossible de supprimer cet agent : il est responsable de " . $contratsCount['contrats_count'] . " contrat(s)";
            header("Location: ../../views/agents.php");
            exit();
        }
        
        // Récupérer les fichiers à supprimer
        $getFiles = $conn->prepare("SELECT photo_profil, cv_path FROM AGENTS WHERE agent_id = ?");
        $getFiles->execute([$id]);
        $files = $getFiles->fetch(PDO::FETCH_ASSOC);
        
        // Soft delete (mise à jour du statut)
        $delete = $conn->prepare("UPDATE AGENTS SET statut = 1 WHERE agent_id = ?");
        
        if ($delete->execute([$id])) {
            // Supprimer les fichiers physiques
            if ($files['photo_profil'] && file_exists('../images/profil/agents/' . $files['photo_profil'])) {
                unlink('../images/profil/agents/' . $files['photo_profil']);
            }
            if ($files['cv_path'] && file_exists('../uploads/cv/' . $files['cv_path'])) {
                unlink('../uploads/cv/' . $files['cv_path']);
            }
            
            $_SESSION['msg'] = "Agent supprimé avec succès";
        } else {
            $_SESSION['msg'] = "Erreur lors de la suppression de l'agent";
        }
        
    } catch (PDOException $e) {
        error_log("Erreur suppression agent: " . $e->getMessage());
        $_SESSION['msg'] = "Une erreur est survenue lors de la suppression";
    }
    
    header("Location: ../../views/agents.php");
    exit();
} else {
    header("Location: ../../views/agents.php");
    exit();
}
?>
