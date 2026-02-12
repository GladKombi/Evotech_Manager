<?php
// Views/dashboard-agent.php
session_start();

// Vérifier l'autorisation - seulement pour agent
if (!isset($_SESSION['agent_id']) || $_SESSION['user_type'] !== 'agent') {
    header('Location: ../login.php');
    exit();
}

// Accès: Timesheet, tâches assignées, projets participés
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Agent - EvoTech</title>
    <?php require_once('style.php') ?>
    <style>
        .agent-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
                <!-- En-tête spécifique Agent -->
                <div class="agent-header rounded-3 p-4 mb-4">
                    <h4><i class="fas fa-user-tie me-2"></i>Dashboard Agent</h4>
                    <p class="mb-0">Timesheet & gestion des tâches</p>
                </div>
                
                <!-- Modules accessibles -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-clock me-2 text-danger"></i> Timesheet</h5>
                                <p>Saisie et gestion de mon temps de travail</p>
                                <a href="timesheet.php" class="btn btn-danger">Saisir mon temps</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-tasks me-2 text-primary"></i> Mes Tâches</h5>
                                <p>Tâches assignées et projets en cours</p>
                                <a href="mes-taches.php" class="btn btn-primary">Voir mes tâches</a>
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