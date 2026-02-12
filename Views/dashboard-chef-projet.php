<?php
// Views/dashboard-chef-projet.php
session_start();

// Vérifier l'autorisation - seulement pour Chef_projet
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'utilisateur') {
    header('Location: ../login.php');
    exit();
}

$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('Chef_projet', $userRoles)) {
    header('Location: dashboard.php');
    exit();
}

// Accès: Projets, tâches, production, validation timesheet
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Chef Projet - EvoTech</title>
    <?php require_once('style.php') ?>
    <style>
        .chef-projet-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div id="app">
        <?php
        require_once('Active.php');
        $ActiveDashboard = 1;
        require_once('aside.php');
        ?>
        
        <div id="main">
            <?php require_once('navbar.php') ?>
            
            <div class="main-content container-fluid">
                <!-- En-tête spécifique Chef Projet -->
                <div class="chef-projet-header rounded-3 p-4 mb-4">
                    <h4><i class="fas fa-tasks me-2"></i>Dashboard Chef Projet</h4>
                    <p class="mb-0">Gestion des projets, tâches & production</p>
                </div>
                
                <!-- Modules accessibles -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-project-diagram me-2 text-info"></i> Projets</h5>
                                <p>Gestion et suivi des projets</p>
                                <a href="projets.php" class="btn btn-info">Accéder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-list-check me-2 text-success"></i> Validation Timesheet</h5>
                                <p>Validation des feuilles de temps des agents</p>
                                <a href="validation-timesheet.php" class="btn btn-success">Valider</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require_once('footer.php') ?>
        </div>
    </div>
    
    <?php require_once('script.php') ?>
</body>
</html>