<?php
// Views/dashboard-admin.php

// Inclure la connexion
require_once __DIR__ . '/../connexion/connexion.php';

// Vérifier l'autorisation - seulement pour DG et administrateurs
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'utilisateur') {
    header('Location: ../login.php');
    exit();
}

// Vérifier si l'utilisateur a le rôle DG
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles)) {
    // Rediriger vers le dashboard approprié selon le rôle
    if (in_array('ChefDepartement', $userRoles)) {
        header('Location: dashboard-chef.php');
    } elseif (in_array('Chef_projet', $userRoles)) {
        header('Location: dashboard-chef-projet.php');
    } elseif (in_array('comptable', $userRoles)) {
        header('Location: dashboard-comptable.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

// Récupérer les statistiques globales
$stats = getAdminStats($conn);

function getAdminStats($conn) {
    $stats = [];
    
    try {
        // 1. Totaux
        $query = "SELECT 
                    (SELECT COUNT(*) FROM UTILISATEURS WHERE statut = 0) as total_users,
                    (SELECT COUNT(*) FROM AGENTS WHERE statut = 0) as total_agents,
                    (SELECT COUNT(*) FROM PROJETS WHERE statut = 0) as total_projects,
                    (SELECT COUNT(*) FROM CONTRATS_COM WHERE statut = 0) as total_contracts,
                    (SELECT COUNT(*) FROM DEPARTEMENTS WHERE statut = 0) as total_departments";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['totals'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 2. Projets par état
        $query = "SELECT etat, COUNT(*) as count FROM PROJETS WHERE statut = 0 GROUP BY etat";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['projects_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Contrats par état
        $query = "SELECT etat, COUNT(*) as count FROM CONTRATS_COM WHERE statut = 0 GROUP BY etat";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['contracts_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 4. Agents par département
        $query = "SELECT d.nom, COUNT(a.agent_id) as count 
                  FROM DEPARTEMENTS d
                  LEFT JOIN AGENTS a ON d.departement_id = a.departement_id AND a.statut = 0
                  WHERE d.statut = 0
                  GROUP BY d.departement_id";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['agents_by_department'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. Dernières activités
        $query = "SELECT 'projet' as type, titre, date_creation FROM PROJETS WHERE statut = 0 ORDER BY date_creation DESC LIMIT 5";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['recent_projects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $query = "SELECT 'contrat' as type, reference, date_creation FROM CONTRATS_COM WHERE statut = 0 ORDER BY date_creation DESC LIMIT 5";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['recent_contracts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 6. Statistiques financières
        $query = "SELECT 
                    SUM(CASE WHEN type_mouvement = 'entrée' THEN montant ELSE 0 END) as total_entrees,
                    SUM(CASE WHEN type_mouvement = 'sortie' THEN montant ELSE 0 END) as total_sorties
                  FROM MOUVEMENT_COMPTE WHERE statut = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['financial'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erreur stats admin: " . $e->getMessage());
    }
    
    return $stats;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard DG - EvoTech Manager</title>
    
    <?php require_once('style.php') ?>
    
    <style>
        :root {
            --primary: #1e40af;
            --secondary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3a0ca3;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(30, 64, 175, 0.2);
        }
        
        .role-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .module-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .module-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .module-actions {
            margin-top: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-left: 5px solid;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .recent-activity {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        
        .activity-item:hover {
            background-color: #f8f9fa;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 15px;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-stat {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .quick-stat:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        /* Styles pour les boutons responsifs */
        .btn {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Texte responsif */
        h1, h2, h3, h4, h5, h6 {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        /* Conteneur fluid amélioré */
        .main-content.container-fluid {
            padding-left: 15px;
            padding-right: 15px;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Styles pour la sidebar rétractable sur mobile */
        @media (max-width: 992px) {
            #sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                width: 280px !important;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                box-shadow: none;
            }
            
            #sidebar.active {
                transform: translateX(0);
                box-shadow: 5px 0 25px rgba(0, 0, 0, 0.15);
            }
            
            /* Overlay pour fermer la sidebar */
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1049;
                display: none;
                opacity: 0;
                transition: opacity 0.3s ease-in-out;
            }
            
            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }
            
            /* Bouton de fermeture dans la sidebar */
            .sidebar-close {
                position: absolute;
                top: 15px;
                right: 15px;
                background: none;
                border: none;
                color: #666;
                font-size: 1.5rem;
                cursor: pointer;
                z-index: 1051;
                padding: 5px;
                line-height: 1;
            }
            
            .sidebar-close:hover {
                color: #333;
            }
            
            /* Bouton hamburger dans la navbar */
            .sidebar-toggle {
                background: none;
                border: none;
                color: #333;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 8px 12px;
                margin-right: 10px;
            }
            
            .sidebar-toggle:hover {
                color: #1e40af;
            }
            
            /* Désactiver le scroll du body quand sidebar est ouverte */
            body.sidebar-open {
                overflow: hidden;
            }
            
            /* Animation douce pour l'overlay */
            .sidebar-overlay {
                animation: fadeIn 0.3s ease-in-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            /* Empêcher le défilement horizontal */
            html, body {
                max-width: 100%;
                overflow-x: hidden;
            }
            
            /* Ajustement du contenu principal */
            .main-content {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        /* Pour desktop, cacher les boutons de toggle */
        @media (min-width: 993px) {
            .sidebar-toggle,
            .sidebar-close,
            .sidebar-overlay {
                display: none !important;
            }
            
            /* S'assurer que la sidebar est visible */
            #sidebar {
                transform: translateX(0) !important;
            }
        }

        /* Optimisations pour mobile */
        @media (hover: none) and (pointer: coarse) {
            .module-card:hover {
                transform: none;
            }
            
            .quick-stat:hover {
                transform: none;
            }
            
            .activity-item:hover {
                background-color: transparent;
            }
        }
        
        .mobile-device .module-card {
            cursor: default;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .module-card {
                padding: 20px;
            }
            
            .stat-number {
                font-size: 2.2rem;
            }
            
            .quick-stats {
                grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
                gap: 12px;
            }
        }
        
        @media (max-width: 992px) {
            .admin-header {
                padding: 20px;
                text-align: center;
            }
            
            .admin-header .row > div {
                margin-bottom: 15px;
            }
            
            .admin-header .text-md-end {
                text-align: center !important;
            }
            
            .module-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .quick-stats {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
            
            .quick-stat {
                padding: 12px;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .admin-header {
                padding: 15px;
                border-radius: 10px;
            }
            
            .admin-header .d-flex {
                flex-direction: column;
                text-align: center;
            }
            
            .admin-header .bg-white.rounded-circle {
                margin: 0 auto 15px;
            }
            
            .role-badge {
                display: block;
                width: fit-content;
                margin: 10px auto;
            }
            
            .role-badge.ms-3 {
                margin-left: auto !important;
                margin-right: auto;
                margin-top: 5px;
            }
            
            .module-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .module-actions .btn {
                font-size: 0.85rem;
                padding: 8px 5px;
            }
            
            .module-actions .btn i {
                margin-right: 3px;
            }
            
            .quick-stats {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
                gap: 10px;
            }
            
            .quick-stat {
                padding: 10px;
            }
            
            .stat-number {
                font-size: 1.6rem;
            }
            
            .activity-icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
                margin-right: 10px;
            }
            
            .activity-item {
                padding: 10px;
            }
            
            .alert .small {
                font-size: 0.8rem;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            
            .btn i {
                margin-right: 5px;
            }
            
            .main-content.container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .admin-header p {
                font-size: 0.9rem;
            }
            
            .module-card p {
                font-size: 0.9rem;
                line-height: 1.4;
            }
        }
        
        @media (max-width: 576px) {
            .admin-header h1 {
                font-size: 1.5rem;
            }
            
            .module-card h4 {
                font-size: 1.2rem;
            }
            
            .module-card p {
                font-size: 0.9rem;
            }
            
            .module-actions .row .col-6 {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .module-actions .col-6:last-child {
                margin-bottom: 0;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stat-number {
                font-size: 1.4rem;
            }
            
            .recent-activity h5 {
                font-size: 1.1rem;
            }
            
            .instructions h5 {
                font-size: 1.1rem;
            }
            
            .alert h6 {
                font-size: 0.95rem;
            }
            
            .btn {
                padding: 6px 10px;
                font-size: 0.85rem;
            }
            
            .btn i {
                margin-right: 3px;
            }
            
            body {
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 480px) {
            #sidebar {
                width: 250px !important;
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            .module-card {
                padding: 12px;
            }
            
            .module-actions .btn {
                font-size: 0.8rem;
            }
            
            .admin-header .btn {
                display: block;
                width: 100%;
                margin-bottom: 10px;
            }
            
            .admin-header .btn:last-child {
                margin-bottom: 0;
            }
        }
        
        /* Amélioration de l'accessibilité */
        .sidebar-toggle:focus,
        .sidebar-close:focus {
            outline: 2px solid #1e40af;
            outline-offset: 2px;
        }
        
        /* Transition pour les sous-menus */
        .submenu {
            transition: max-height 0.3s ease-in-out;
            overflow: hidden;
        }
        
        .sidebar-item.has-sub.active .submenu {
            max-height: 500px;
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
                <!-- En-tête DG -->
                <div class="admin-header">
                    <div class="row align-items-center">
                        <div class="col-md-8 mb-3 mb-md-0">
                            <div class="d-flex align-items-center mb-3 flex-column flex-md-row">
                                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-md-3 mb-3 mb-md-0" 
                                     style="width: 60px; height: 60px;">
                                    <i class="fas fa-crown text-primary fs-3"></i>
                                </div>
                                <div class="text-center text-md-start">
                                    <h1 class="h3 mb-1">
                                        <i class="fas fa-hand-wave me-2"></i>
                                        Bienvenue, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?>
                                    </h1>
                                    <p class="mb-0 opacity-90">
                                        <span class="role-badge d-inline-block mb-2 mb-md-0">
                                            <i class="fas fa-user-shield me-1"></i> Directeur Général
                                        </span>
                                        <span class="ms-md-3 d-block d-md-inline">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?= date('d F Y') ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <p class="mb-0 text-center text-md-start">
                                <i class="fas fa-star me-2"></i>
                                Accès complet à tous les modules du système
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-light me-2 mb-2 mb-md-0" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt me-1"></i> Actualiser
                            </button>
                            <a href="rapports.php" class="btn btn-outline-light">
                                <i class="fas fa-chart-bar me-1"></i> Rapports
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Statistiques rapides -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="quick-stats">
                            <div class="quick-stat">
                                <div class="text-primary mb-2">
                                    <i class="fas fa-users fs-4"></i>
                                </div>
                                <h4 class="stat-number text-primary"><?= $stats['totals']['total_users'] ?? 0 ?></h4>
                                <small class="text-muted">Utilisateurs</small>
                            </div>
                            <div class="quick-stat">
                                <div class="text-success mb-2">
                                    <i class="fas fa-user-tie fs-4"></i>
                                </div>
                                <h4 class="stat-number text-success"><?= $stats['totals']['total_agents'] ?? 0 ?></h4>
                                <small class="text-muted">Agents</small>
                            </div>
                            <div class="quick-stat">
                                <div class="text-info mb-2">
                                    <i class="fas fa-project-diagram fs-4"></i>
                                </div>
                                <h4 class="stat-number text-info"><?= $stats['totals']['total_projects'] ?? 0 ?></h4>
                                <small class="text-muted">Projets</small>
                            </div>
                            <div class="quick-stat">
                                <div class="text-warning mb-2">
                                    <i class="fas fa-file-contract fs-4"></i>
                                </div>
                                <h4 class="stat-number text-warning"><?= $stats['totals']['total_contracts'] ?? 0 ?></h4>
                                <small class="text-muted">Contrats</small>
                            </div>
                            <div class="quick-stat">
                                <div class="text-danger mb-2">
                                    <i class="fas fa-money-bill-wave fs-4"></i>
                                </div>
                                <h4 class="stat-number text-danger">
                                    <?= number_format(($stats['financial']['total_entrees'] ?? 0) - ($stats['financial']['total_sorties'] ?? 0), 0, ',', ' ') ?> $
                                </h4>
                                <small class="text-muted">Solde</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modules d'accès -->
                <div class="row g-3">
                    <!-- Module RH & Entités Fondamentales -->
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 mb-3">
                        <div class="module-card h-100">
                            <div class="module-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <h4 class="fw-bold mb-3">RH & Entités Fondamentales</h4>
                            <p class="text-muted mb-4">
                                Gestion complète des ressources humaines, utilisateurs, départements et rôles.
                            </p>
                            <div class="module-actions">
                                <div class="row g-2">
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="utilisateurs.php" class="btn btn-primary w-100">
                                            <i class="fas fa-users me-1"></i> Utilisateurs
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="agents.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-user-tie me-1"></i> Agents
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="departements.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-building me-1"></i> Départements
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="roles.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-user-tag me-1"></i> Rôles
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Module Projets -->
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 mb-3">
                        <div class="module-card h-100">
                            <div class="module-icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Gestion des Projets</h4>
                            <p class="text-muted mb-4">
                                Suivi des projets, tâches, équipes et livrables. Collaboration avec chefs de projet.
                            </p>
                            <div class="module-actions">
                                <div class="row g-2">
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="projets.php" class="btn btn-info w-100">
                                            <i class="fas fa-tasks me-1"></i> Projets
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="taches.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-list-check me-1"></i> Tâches
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="production.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-folder me-1"></i> Production
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="rapports-projets.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-chart-bar me-1"></i> Rapports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Module Finance & Caisse -->
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 mb-3">
                        <div class="module-card h-100">
                            <div class="module-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Finance & Caisse</h4>
                            <p class="text-muted mb-4">
                                Gestion financière, comptes, mouvements, budget projets. Collaboration avec comptable.
                            </p>
                            <div class="module-actions">
                                <div class="row g-2">
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="comptes.php" class="btn btn-success w-100">
                                            <i class="fas fa-wallet me-1"></i> Comptes
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="mouvements.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-exchange-alt me-1"></i> Mouvements
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="budgets.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-chart-pie me-1"></i> Budgets
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="rapports-financiers.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-file-invoice-dollar me-1"></i> Finances
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Module Contrats Communication -->
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 mb-3">
                        <div class="module-card h-100">
                            <div class="module-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Contrats Communication</h4>
                            <p class="text-muted mb-4">
                                Gestion des contrats de communication, activités, clients. Collaboration avec chefs de département.
                            </p>
                            <div class="module-actions">
                                <div class="row g-2">
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="contrats-com.php" class="btn btn-warning w-100">
                                            <i class="fas fa-handshake me-1"></i> Contrats
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="activites-contrat.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-calendar-check me-1"></i> Activités
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="partenaires.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-handshake-angle me-1"></i> Partenaires
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="suivi-contrats.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-chart-line me-1"></i> Suivi
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Module Temps (Timesheet) -->
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 mb-3">
                        <div class="module-card h-100">
                            <div class="module-icon bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Gestion du Temps</h4>
                            <p class="text-muted mb-4">
                                Supervision des timesheets, validation, suivi du temps par projet et agent.
                            </p>
                            <div class="module-actions">
                                <div class="row g-2">
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="timesheet.php" class="btn btn-danger w-100">
                                            <i class="fas fa-calendar-day me-1"></i> Timesheets
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="validation-timesheet.php" class="btn btn-outline-danger w-100">
                                            <i class="fas fa-check-circle me-1"></i> Validation
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="rapports-temps.php" class="btn btn-outline-danger w-100">
                                            <i class="fas fa-chart-bar me-1"></i> Rapports
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="parametres-temps.php" class="btn btn-outline-danger w-100">
                                            <i class="fas fa-cog me-1"></i> Paramètres
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Module Administration Système -->
                    <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 mb-3">
                        <div class="module-card h-100">
                            <div class="module-icon bg-secondary bg-opacity-10 text-secondary">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Administration Système</h4>
                            <p class="text-muted mb-4">
                                Paramètres système, logs, sauvegardes, maintenance et sécurité.
                            </p>
                            <div class="module-actions">
                                <div class="row g-2">
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="parametres.php" class="btn btn-secondary w-100">
                                            <i class="fas fa-sliders-h me-1"></i> Paramètres
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="logs.php" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-clipboard-list me-1"></i> Logs
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="sauvegardes.php" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-database me-1"></i> Sauvegardes
                                        </a>
                                    </div>
                                    <div class="col-6 col-sm-6 col-md-12 col-lg-6">
                                        <a href="securite.php" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-shield-alt me-1"></i> Sécurité
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Activités récentes -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="module-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-history me-2 text-primary"></i>
                                Projets récents
                            </h5>
                            <div class="recent-activity">
                                <?php if (!empty($stats['recent_projects'])): ?>
                                    <?php foreach ($stats['recent_projects'] as $project): ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon bg-primary bg-opacity-10 text-primary">
                                                    <i class="fas fa-project-diagram"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <strong><?= htmlspecialchars($project['titre']) ?></strong>
                                                        <small class="text-muted">
                                                            <?= date('d/m', strtotime($project['date_creation'])) ?>
                                                        </small>
                                                    </div>
                                                    <small class="text-muted">Nouveau projet créé</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">Aucun projet récent</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="module-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-history me-2 text-warning"></i>
                                Contrats récents
                            </h5>
                            <div class="recent-activity">
                                <?php if (!empty($stats['recent_contracts'])): ?>
                                    <?php foreach ($stats['recent_contracts'] as $contract): ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon bg-warning bg-opacity-10 text-warning">
                                                    <i class="fas fa-file-contract"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <strong><?= htmlspecialchars($contract['reference']) ?></strong>
                                                        <small class="text-muted">
                                                            <?= date('d/m', strtotime($contract['date_creation'])) ?>
                                                        </small>
                                                    </div>
                                                    <small class="text-muted">Nouveau contrat enregistré</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">Aucun contrat récent</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions et rappels -->
                <div class="row">
                    <div class="col-12">
                        <div class="module-card">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-lightbulb me-2 text-success"></i>
                                Instructions pour le DG
                            </h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-users me-2"></i> RH & Équipes</h6>
                                        <p class="small mb-0">• Valider les nouvelles embauches<br>• Réviser les structures départementales<br>• Attribuer les rôles et permissions</p>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-chart-line me-2"></i> Performance</h6>
                                        <p class="small mb-0">• Analyser les rapports financiers<br>• Évaluer l'avancement des projets<br>• Superviser la productivité des équipes</p>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="alert alert-success">
                                        <h6><i class="fas fa-shield-alt me-2"></i> Sécurité</h6>
                                        <p class="small mb-0">• Réviser les logs de sécurité<br>• Mettre à jour les politiques d'accès<br>• Planifier les audits de sécurité</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php require_once('footer.php') ?>
        </div>
    </div>

    <?php require_once('script.php') ?>
    
    <script>
        // Fonction pour rafraîchir le dashboard
        function refreshDashboard() {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Actualisation...';
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
        
        // Gestion de la sidebar rétractable
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebarClose = document.querySelector('.sidebar-close');
            const sidebarToggler = document.querySelector('.sidebar-toggler');
            const body = document.body;
            
            // Créer l'overlay
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            
            // Fonction pour ouvrir la sidebar
            function openSidebar() {
                sidebar.classList.add('active');
                overlay.classList.add('show');
                body.classList.add('sidebar-open');
            }
            
            // Fonction pour fermer la sidebar
            function closeSidebar() {
                sidebar.classList.remove('active');
                overlay.classList.remove('show');
                body.classList.remove('sidebar-open');
            }
            
            // Ouvrir la sidebar avec le bouton hamburger
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', openSidebar);
            }
            
            // Fermer la sidebar avec le bouton de fermeture
            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeSidebar);
            }
            
            // Fermer avec le toggler existant
            if (sidebarToggler) {
                sidebarToggler.addEventListener('click', closeSidebar);
            }
            
            // Fermer avec l'overlay
            overlay.addEventListener('click', closeSidebar);
            
            // Fermer avec la touche ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                    closeSidebar();
                }
            });
            
            // Fermer automatiquement la sidebar sur mobile lors du clic sur un lien
            if (window.innerWidth <= 992) {
                const sidebarLinks = sidebar.querySelectorAll('a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        // Ne pas fermer si c'est un sous-menu qui s'ouvre
                        if (!this.closest('.has-sub')) {
                            closeSidebar();
                        }
                    });
                });
            }
            
            // Gestion du redimensionnement de la fenêtre
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992 && sidebar.classList.contains('active')) {
                    closeSidebar();
                }
            });
            
            // Détection du mobile
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            
            if (isMobile) {
                // Optimisations pour mobile
                document.addEventListener('touchstart', function() {}, {passive: true});
                
                // Désactiver les hover sur mobile
                document.body.classList.add('mobile-device');
                
                // Empêcher le défilement du body quand on scroll dans la sidebar
                const sidebarContent = sidebar.querySelector('.sidebar-wrapper');
                if (sidebarContent) {
                    sidebarContent.addEventListener('touchstart', function(e) {
                        e.stopPropagation();
                    });
                }
            }
            
            // Raccourcis clavier
            document.addEventListener('keydown', function(e) {
                // Ctrl + R pour rafraîchir
                if (e.ctrlKey && e.key === 'r') {
                    e.preventDefault();
                    refreshDashboard();
                }
                
                // Alt + M pour accéder au menu
                if (e.altKey && e.key === 'm') {
                    if (window.innerWidth <= 992) {
                        if (sidebar.classList.contains('active')) {
                            closeSidebar();
                        } else {
                            openSidebar();
                        }
                    }
                }
            });
            
            // Notification de bienvenue
            setTimeout(() => {
                if (!sessionStorage.getItem('welcomeShown')) {
                    sessionStorage.setItem('welcomeShown', 'true');
                    console.log('%c👑 Dashboard DG - EvoTech Africa', 'color: #1e40af; font-size: 18px; font-weight: bold;');
                    console.log('%cAccès complet aux modules système activé', 'color: #10b981;');
                }
            }, 1000);
            
            // Ajustement pour les petites tailles d'écran
            function adjustLayout() {
                const width = window.innerWidth;
                
                if (width < 768) {
                    // Optimisations supplémentaires pour mobile
                    const moduleCards = document.querySelectorAll('.module-card');
                    moduleCards.forEach(card => {
                        card.style.cursor = 'default';
                    });
                    
                    // Ajuster le padding de la sidebar pour les petits écrans
                    if (sidebar) {
                        const sidebarLinks = sidebar.querySelectorAll('.sidebar-link');
                        sidebarLinks.forEach(link => {
                            link.style.padding = '8px 15px';
                        });
                    }
                }
                
                if (width < 576) {
                    // Ajustement des boutons pour très petits écrans
                    const buttons = document.querySelectorAll('.module-actions .btn');
                    buttons.forEach(btn => {
                        btn.style.padding = '5px 8px';
                        btn.style.fontSize = '0.8rem';
                    });
                    
                    // Réduire la taille de la sidebar
                    if (sidebar && width <= 480) {
                        sidebar.style.width = '250px';
                    }
                }
                
                if (width > 992) {
                    // S'assurer que la sidebar est visible sur desktop
                    if (sidebar) {
                        sidebar.classList.add('active');
                    }
                }
            }
            
            // Exécuter au chargement et lors du redimensionnement
            adjustLayout();
            window.addEventListener('resize', adjustLayout);
        });
    </script>
</body>
</html>