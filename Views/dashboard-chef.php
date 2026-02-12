<?php
// Views/dashboard-chef.php
session_start();

// Vérifier l'autorisation - seulement pour ChefDepartement
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'utilisateur') {
    header('Location: ../login.php');
    exit();
}

$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('ChefDepartement', $userRoles)) {
    header('Location: dashboard.php');
    exit();
}

// Accès: Contrats Communication + Supervision équipe
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Chef Département - EvoTech</title>
    <?php require_once('style.php') ?>
    <style>
        .chef-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
                <!-- En-tête spécifique Chef Département -->
                <div class="chef-header rounded-3 p-4 mb-4">
                    <h4><i class="fas fa-user-tie me-2"></i>Dashboard Chef Département</h4>
                    <p class="mb-0">Gestion des contrats communication & supervision d'équipe</p>
                </div>
                
                <!-- Modules accessibles -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-file-contract me-2 text-warning"></i> Contrats Communication</h5>
                                <p>Gestion des contrats clients, activités et suivi</p>
                                <a href="contrats-com.php" class="btn btn-warning">Accéder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-users me-2 text-primary"></i> Mon Équipe</h5>
                                <p>Supervision des agents du département</p>
                                <a href="agents.php?departement=<?= $_SESSION['departement_id'] ?? '' ?>" class="btn btn-primary">Voir équipe</a>
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