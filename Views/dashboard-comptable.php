<?php
// Views/dashboard-comptable.php
session_start();

// Vérifier l'autorisation - seulement pour comptable
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'utilisateur') {
    header('Location: ../login.php');
    exit();
}

$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('comptable', $userRoles)) {
    header('Location: dashboard.php');
    exit();
}

// Accès: Finance, comptes, mouvements, budgets
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Comptable - EvoTech</title>
    <?php require_once('style.php') ?>
    <style>
        .comptable-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
                <!-- En-tête spécifique Comptable -->
                <div class="comptable-header rounded-3 p-4 mb-4">
                    <h4><i class="fas fa-calculator me-2"></i>Dashboard Comptable</h4>
                    <p class="mb-0">Gestion financière & comptable</p>
                </div>
                
                <!-- Modules accessibles -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-wallet me-2 text-success"></i> Comptes & Mouvements</h5>
                                <p>Gestion des comptes et transactions</p>
                                <a href="comptes.php" class="btn btn-success">Accéder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-chart-pie me-2 text-info"></i> Budgets & Rapports</h5>
                                <p>Suivi budgétaire et rapports financiers</p>
                                <a href="budgets.php" class="btn btn-info">Consulter</a>
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