<?php
# Se connecter à la BD
include '../connexion/connexion.php';

# Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Récupérer les rôles de l'utilisateur
$userRoles = $_SESSION['roles'] ?? [];
$userType = $_SESSION['user_type'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

// Fonction pour vérifier les permissions
function hasPermission($requiredRoles, $userRoles)
{
    if (empty($requiredRoles)) return true;
    foreach ($requiredRoles as $role) {
        if (in_array($role, $userRoles)) return true;
    }
    return false;
}

// Permissions pour cette page
$canAccessRoles = hasPermission(['DG', 'comptable'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveComptes = 1;

# Récupérer les comptes avec jointures
try {
    $query = "
        SELECT 
            c.*,
            u.nom as responsable_nom,
            u.prenom as responsable_prenom
        FROM COMPTES c
        LEFT JOIN UTILISATEURS u ON c.responsable_id = u.utilisateur_id
        WHERE c.statut = 0
        ORDER BY c.type, c.nom
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les utilisateurs pour le formulaire (responsables)
    $queryUtilisateurs = "
        SELECT utilisateur_id, nom, prenom 
        FROM UTILISATEURS 
        WHERE statut = 0 AND actif = 1 
        ORDER BY nom, prenom
    ";
    $stmtUtilisateurs = $conn->prepare($queryUtilisateurs);
    $stmtUtilisateurs->execute();
    $utilisateurs = $stmtUtilisateurs->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques globales
    $queryStats = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN type = 'Banque' THEN 1 ELSE 0 END) as banques,
            SUM(CASE WHEN type = 'Caisse' THEN 1 ELSE 0 END) as caisses,
            SUM(solde_actuel) as total_solde,
            AVG(solde_actuel) as moyenne_solde
        FROM COMPTES 
        WHERE statut = 0
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Statistiques par type de compte
    $queryStatsParType = "
        SELECT 
            type,
            COUNT(*) as nombre,
            SUM(solde_actuel) as total_solde,
            AVG(solde_actuel) as moyenne_solde,
            MIN(solde_actuel) as solde_min,
            MAX(solde_actuel) as solde_max
        FROM COMPTES 
        WHERE statut = 0
        GROUP BY type
    ";
    $stmtStatsParType = $conn->prepare($queryStatsParType);
    $stmtStatsParType->execute();
    $statsParType = $stmtStatsParType->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des comptes: " . $e->getMessage());
    $comptes = [];
    $utilisateurs = [];
    $stats = ['total' => 0, 'banques' => 0, 'caisses' => 0, 'total_solde' => 0, 'moyenne_solde' => 0];
    $statsParType = [];
}

# Messages de session
$success_msg = $_SESSION['msg'] ?? null;
$error_msg = $_SESSION['error'] ?? null;
if (empty($error_msg) && !empty($success_msg)) {
    $lower = mb_strtolower($success_msg, 'UTF-8');
    if (strpos($lower, 'erreur') !== false || strpos($lower, 'echec') !== false || strpos($lower, 'impossible') !== false || strpos($lower, 'token') !== false || strpos($lower, "n'existe") !== false || strpos($lower, 'déjà') !== false) {
        $error_msg = $success_msg;
        $success_msg = null;
    }
}
unset($_SESSION['msg'], $_SESSION['error']);

# Générer un token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Comptes - Evotech Africa</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-blue: #0d6efd;
            --primary-blue-dark: #0b5ed7;
            --primary-blue-light: #3d8bfd;
            --secondary-blue: #6ea8fe;
            --light-blue-bg: #f0f8ff;
            --success-green: #198754;
            --warning-yellow: #ffc107;
            --info-blue: #0dcaf0;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding-left: 280px;
            /* Space for sidebar */
        }

        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }
        }

        /* Sidebar Styles - Identique aux autres pages */
        #sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: white;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        @media (max-width: 992px) {
            #sidebar {
                transform: translateX(-100%);
            }

            #sidebar.active {
                transform: translateX(0);
            }
        }

        .sidebar-wrapper {
            height: 100%;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: white;
        }

        .sidebar-header img {
            max-width: 70px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .user-profile {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .avatar-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
        }

        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 20px;
        }

        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }

        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu li {
            list-style: none;
        }

        .menu a {
            text-decoration: none !important;
        }

        .sidebar-title {
            color: #6c757d;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 600;
            padding: 20px 25px 10px;
            margin-top: 10px;
        }

        .sidebar-item {
            margin: 0;
        }

        .sidebar-link {
            color: #495057;
            padding: 14px 25px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-size: 0.95rem;
        }

        .sidebar-link:hover {
            background: linear-gradient(90deg, #f0f7ff 0%, #ffffff 100%);
            color: #1e40af;
            border-left-color: #3b82f6;
            transform: translateX(5px);
        }

        .sidebar-item.active .sidebar-link {
            background: linear-gradient(90deg, #e6f0ff 0%, #f8f9fa 100%);
            color: #1e40af;
            border-left-color: #1e40af;
            font-weight: 600;
            box-shadow: inset 2px 0 10px rgba(30, 64, 175, 0.1);
        }

        .sidebar-link i {
            width: 24px;
            margin-right: 12px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar-item.has-sub .sidebar-link::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: auto;
            font-size: 0.8rem;
            color: #adb5bd;
            transition: transform 0.3s ease;
        }

        .sidebar-item.has-sub.active .sidebar-link::after {
            transform: rotate(180deg);
            color: #1e40af;
        }

        .submenu {
            background: linear-gradient(180deg, #f8f9fa 0%, #f1f3f5 100%);
            border-left: 3px solid #dee2e6;
            margin-left: 25px;
            padding: 8px 0;
            border-radius: 0 0 8px 0;
            display: none;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sidebar-item.has-sub.active .submenu {
            display: block;
        }

        .submenu a {
            color: #6c757d;
            text-decoration: none;
            display: block;
            padding: 10px 25px 10px 20px;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            border-left: 2px solid transparent;
            position: relative;
        }

        .submenu a:hover {
            color: #1e40af;
            background: rgba(59, 130, 246, 0.1);
            border-left-color: #3b82f6;
            padding-left: 25px;
        }

        .submenu li.active a {
            color: #1e40af;
            background: rgba(59, 130, 246, 0.15);
            border-left-color: #1e40af;
            font-weight: 500;
        }

        .sidebar-toggler {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #6c757d;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1001;
        }

        /* Main content styles */
        .main-content-wrapper {
            min-height: 100vh;
            padding: 20px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            border-bottom: none;
        }

        .btn-primary {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .btn-primary:hover {
            background-color: var(--primary-blue-dark);
            border-color: var(--primary-blue-dark);
        }

        .stat-card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .search-box {
            position: relative;
            max-width: 400px;
        }

        .search-box .form-control {
            padding-left: 2.5rem;
            border-radius: 25px;
        }

        .search-box .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        @media (min-width: 993px) {
            .overlay {
                display: none !important;
            }
        }

        /* Mobile hamburger menu */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 5px;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        @media (max-width: 992px) {
            .mobile-menu-btn {
                display: flex;
            }
        }

        /* Styles spécifiques aux comptes */
        .solde-positif {
            color: var(--success-green) !important;
            font-weight: bold;
        }
        
        .solde-negatif {
            color: #dc3545 !important;
            font-weight: bold;
        }
        
        .solde-zero {
            color: #6c757d !important;
        }
        
        .badge-banque {
            background: linear-gradient(135deg, #0d6efd, #6ea8fe);
            color: white;
        }
        
        .badge-caisse {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
        }
        
        .type-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .type-icon-banque {
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary-blue);
        }
        
        .type-icon-caisse {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success-green);
        }
        
        .users-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .user-item {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s;
        }

        .user-item:hover {
            background-color: #f8f9fa;
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            display: none;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .filter-info {
            background-color: #e8f4fd;
            border: 1px solid #b6d4fe;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 20px;
            display: none;
        }

        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .account-card {
            border-left: 4px solid;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .account-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .account-card-banque {
            border-left-color: var(--primary-blue);
        }
        
        .account-card-caisse {
            border-left-color: var(--success-green);
        }
    </style>
</head>

<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div id="sidebar" class='active'>
        <div class="sidebar-wrapper active">
            <div class="sidebar-header text-center">
                <img src="../images/logo.jpg" alt="Logo EvoTech Africa" srcset="" style="max-height: 50px;">
                <a href="dashboard.php">
                    <h4 class="text-primary">EvoTech Africa</h4>
                </a>
                <small class="text-muted d-block">Gestion Intégrée</small>
            </div>

            <!-- Profil utilisateur -->
            <div class="user-profile text-center py-3 border-bottom">
                <div class="avatar mb-2">
                    <?php
                    $initials = strtoupper(
                        substr($_SESSION['prenom'] ?? '', 0, 1) .
                            substr($_SESSION['nom'] ?? '', 0, 1)
                    );
                    ?>
                    <div class="avatar-circle">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                </div>
                <div>
                    <strong class="d-block">
                        <?= htmlspecialchars(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')) ?>
                    </strong>
                    <small class="text-muted d-block">
                        <?php
                        $primaryRole = !empty($userRoles) ? $userRoles[0] : 'Utilisateur';
                        echo htmlspecialchars($primaryRole);
                        ?>
                    </small>
                </div>
            </div>

            <div class="sidebar-menu">
                <ul class="menu">
                    <!-- Tableau de bord principal -->
                    <li class='sidebar-title'>Navigation</li>

                    <li class="sidebar-item">
                        <?php
                        $dashboardUrl = 'dashboard.php';
                        if (in_array('DG', $userRoles)) {
                            $dashboardUrl = 'dashboard-admin.php';
                        } elseif (in_array('ChefDepartement', $userRoles)) {
                            $dashboardUrl = 'dashboard-chef.php';
                        } elseif (in_array('Chef_projet', $userRoles)) {
                            $dashboardUrl = 'dashboard-projet.php';
                        } elseif (in_array('comptable', $userRoles)) {
                            $dashboardUrl = 'dashboard-comptable.php';
                        } elseif (in_array('actionnaire', $userRoles)) {
                            $dashboardUrl = 'dashboard-actionnaire.php';
                        } elseif (in_array('agent', $userRoles)) {
                            $dashboardUrl = 'dashboard-agent.php';
                        }
                        ?>
                        <a href="<?= $dashboardUrl ?>" class='sidebar-link'>
                            <i class="fas fa-tachometer-alt text-primary"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>

                    <!-- Module RH & Entités Fondamentales -->
                    <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                        <li class='sidebar-title'>RH & Entités</li>

                        <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                            <li class="sidebar-item">
                                <a href="utilisateurs.php" class='sidebar-link'>
                                    <i class="fas fa-users-cog text-info"></i>
                                    <span>Utilisateurs</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="sidebar-item">
                            <a href="agents.php" class='sidebar-link'>
                                <i class="fas fa-user-tie text-success"></i>
                                <span>Agents</span>
                            </a>
                        </li>

                        <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                            <li class="sidebar-item">
                                <a href="departements.php" class='sidebar-link'>
                                    <i class="fas fa-building text-warning"></i>
                                    <span>Départements</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (hasPermission(['DG'], $userRoles)): ?>
                            <li class="sidebar-item">
                                <a href="roles.php" class='sidebar-link'>
                                    <i class="fas fa-user-tag text-danger"></i>
                                    <span>Rôles & Permissions</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                            <li class="sidebar-item">
                                <a href="attributions.php" class='sidebar-link'>
                                    <i class="fas fa-user-check text-secondary"></i>
                                    <span>Attributions</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Module Projets -->
                    <?php if (hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles)): ?>
                        <li class='sidebar-title'>Gestion des Projets</li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="fas fa-project-diagram text-info"></i>
                                <span>Projets & Production</span>
                            </a>
                            <ul class="submenu">
                                <?php if (hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles)): ?>
                                    <li>
                                        <a href="projets.php">Projets</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles)): ?>
                                    <li>
                                        <a href="taches.php">Tâches</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles)): ?>
                                    <li>
                                        <a href="partenaires.php">Partenaires</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                    <li>
                                        <a href="participation.php">Participation</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles)): ?>
                                    <li>
                                        <a href="production.php">Production</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                    <li>
                                        <a href="types-production.php">Types de production</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                    <li>
                                        <a href="rapports-projets.php">Rapports projets</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Module Temps (Timesheet) -->
                    <?php if (hasPermission(['DG', 'Chef_projet', 'agent'], $userRoles)): ?>
                        <li class='sidebar-title'>Gestion du Temps</li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="fas fa-clock text-warning"></i>
                                <span>Timesheets</span>
                            </a>
                            <ul class="submenu">
                                <?php if (hasPermission(['DG', 'Chef_projet', 'agent'], $userRoles)): ?>
                                    <li>
                                        <a href="timesheet.php">Mes timesheets</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                    <li>
                                        <a href="validation-timesheet.php">Validation</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                    <li>
                                        <a href="rapports-temps.php">Rapports temps</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Module Contrats Communication -->
                    <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                        <li class='sidebar-title'>Communication</li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="fas fa-file-contract text-success"></i>
                                <span>Contrats Communication</span>
                            </a>
                            <ul class="submenu">
                                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                    <li>
                                        <a href="contrats-com.php">Contrats</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                    <li>
                                        <a href="activites-contrat.php">Activités</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                    <li>
                                        <a href="suivi-contrats.php">Suivi contrats</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Module Finance & Caisse -->
                    <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                        <li class='sidebar-title'>Finance & Caisse</li>

                        <li class="sidebar-item has-sub active">
                            <a href="#" class='sidebar-link'>
                                <i class="fas fa-money-bill-wave text-success"></i>
                                <span>Gestion Financière</span>
                            </a>
                            <ul class="submenu">
                                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                    <li class="active">
                                        <a href="comptes.php">Comptes</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                    <li>
                                        <a href="mouvements.php">Mouvements</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                    <li>
                                        <a href="budgets.php">Budgets</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'comptable', 'actionnaire'], $userRoles)): ?>
                                    <li>
                                        <a href="rapports-financiers.php">Rapports financiers</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                    <li>
                                        <a href="cloture.php">Clôture de caisse</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Module Administration Système -->
                    <?php if (hasPermission(['DG'], $userRoles)): ?>
                        <li class='sidebar-title'>Administration</li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="fas fa-cogs text-secondary"></i>
                                <span>Système</span>
                            </a>
                            <ul class="submenu">
                                <?php if (hasPermission(['DG'], $userRoles)): ?>
                                    <li>
                                        <a href="parametres.php">Paramètres</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG'], $userRoles)): ?>
                                    <li>
                                        <a href="logs.php">Logs système</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG'], $userRoles)): ?>
                                    <li>
                                        <a href="securite.php">Sécurité</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG'], $userRoles)): ?>
                                    <li>
                                        <a href="sauvegardes.php">Sauvegardes</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>

                        <?php if (hasPermission(['DG', 'ChefDepartement', 'Chef_projet'], $userRoles)): ?>
                            <li class="sidebar-item">
                                <a href="statistiques.php" class='sidebar-link'>
                                    <i class="fas fa-chart-bar text-info"></i>
                                    <span>Statistiques</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Profil utilisateur -->
                    <li class='sidebar-title'>Compte</li>
                    <li class="sidebar-item">
                        <a href="mon-profil.php" class='sidebar-link'>
                            <i class="fas fa-user-circle text-primary"></i>
                            <span>Mon profil</span>
                        </a>
                    </li>

                    <li class="sidebar-item">
                        <a href="../logout.php" class='sidebar-link text-danger'>
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </a>
                    </li>
                </ul>
            </div>
            <button class="sidebar-toggler btn x">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Overlay pour mobile -->
    <div class="overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content-wrapper">
        <!-- Header -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-4 mb-3 border-bottom">
            <h1 class="h3 fw-bold text-primary">
                <i class="fas fa-university me-2"></i>
                Gestion des Comptes
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un compte..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompteModal">
                        <i class="bi bi-plus-circle me-1"></i> Nouveau Compte
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages d'alerte -->
        <?php if (isset($success_msg) && !empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg) && !empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="row mb-4 g-3">
            <div class="col-md-3">
                <div class="card stat-card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Comptes</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $stats['total'] ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-university"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Comptes Banque</h6>
                                <h2 class="card-title text-info mb-0">
                                    <?= $stats['banques'] ?>
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="fas fa-landmark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Caisses</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $stats['caisses'] ?>
                                </h2>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-cash-register"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Solde Total</h6>
                                <h2 class="card-title text-warning mb-0" id="filteredCount">
                                    $<?= number_format($stats['total_solde'] ?? 0, 2, ',', ' ') ?>
                                </h2>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vue par cartes -->
        <div class="row mb-4 g-3">
            <?php foreach ($comptes as $compte): 
                $solde = $compte['solde_actuel'];
                $soldeClass = $solde > 0 ? 'solde-positif' : ($solde < 0 ? 'solde-negatif' : 'solde-zero');
                $typeClass = $compte['type'] == 'Banque' ? 'account-card-banque' : 'account-card-caisse';
                $badgeClass = $compte['type'] == 'Banque' ? 'badge-banque' : 'badge-caisse';
                $typeIconClass = $compte['type'] == 'Banque' ? 'type-icon-banque' : 'type-icon-caisse';
                $typeIcon = $compte['type'] == 'Banque' ? 'fas fa-landmark' : 'fas fa-cash-register';
            ?>
            <div class="col-md-4 col-lg-3">
                <div class="card account-card <?= $typeClass ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="type-icon <?= $typeIconClass ?>">
                                <i class="<?= $typeIcon ?>"></i>
                            </div>
                            <div>
                                <h6 class="card-title mb-0"><?= htmlspecialchars($compte['nom']) ?></h6>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($compte['type']) ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">N° de compte</small>
                            <p class="mb-0 fw-semibold"><?= $compte['numero_compte'] ? htmlspecialchars($compte['numero_compte']) : 'Non spécifié' ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Solde actuel</small>
                            <h4 class="<?= $soldeClass ?> mb-0">
                                <?= $compte['devise'] ?><?= number_format($solde, 2, ',', ' ') ?>
                            </h4>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Responsable</small>
                            <p class="mb-0">
                                <?= $compte['responsable_nom'] 
                                    ? htmlspecialchars($compte['responsable_prenom'] . ' ' . $compte['responsable_nom'])
                                    : 'Non assigné' ?>
                            </p>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" 
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editCompteModal"
                                    onclick="loadEditData(<?= $compte['compte_id'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-info"
                                    onclick="window.location.href='mouvements.php?compte_id=<?= $compte['compte_id'] ?>'">
                                <i class="bi bi-eye"></i> Mouvements
                            </button>
                            <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteModal"
                                    onclick="setDeleteId(<?= $compte['compte_id'] ?>, '<?= htmlspecialchars(addslashes($compte['nom'])) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Information de filtrage -->
        <div class="filter-info" id="filterInfo">
            <i class="bi bi-funnel me-2"></i>
            <span id="filterText">Affichage des résultats filtrés</span>
            <button type="button" class="btn btn-sm btn-outline-primary ms-3" id="clearFilter">
                <i class="bi bi-x-circle me-1"></i> Effacer le filtre
            </button>
        </div>

        <!-- Filtres -->
        <div class="filter-section">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Type de compte</label>
                    <select class="form-select" id="filterType">
                        <option value="">Tous les types</option>
                        <option value="Banque">Banque</option>
                        <option value="Caisse">Caisse</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Devise</label>
                    <select class="form-select" id="filterDevise">
                        <option value="">Toutes les devises</option>
                        <option value="$">$ USD</option>
                        <option value="€">€ EUR</option>
                        <option value="FCFA">FCFA</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Solde minimum</label>
                    <input type="number" class="form-control" id="filterSoldeMin" placeholder="0" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Solde maximum</label>
                    <input type="number" class="form-control" id="filterSoldeMax" placeholder="1000000" step="0.01">
                </div>
            </div>
        </div>

        <!-- Table des comptes -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Liste détaillée des comptes
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($comptes) ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun compte ne correspond à vos critères</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(1)">
                                        Nom <i class="bi bi-arrow-down-up ms-1"></i>
                                    </button>
                                </th>
                                <th>Type</th>
                                <th>N° Compte</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(4)">
                                        Solde <i class="bi bi-arrow-down-up ms-1"></i>
                                    </button>
                                </th>
                                <th>Devise</th>
                                <th>Responsable</th>
                                <th>Création</th>
                                <th width="140" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="comptesTable">
                            <?php
                            $n = 0;
                            foreach ($comptes as $compte):
                                $n++;
                                $solde = $compte['solde_actuel'];
                                $soldeClass = $solde > 0 ? 'solde-positif' : ($solde < 0 ? 'solde-negatif' : 'solde-zero');
                                $badgeClass = $compte['type'] == 'Banque' ? 'badge-banque' : 'badge-caisse';
                                $responsable = $compte['responsable_nom'] 
                                    ? htmlspecialchars($compte['responsable_prenom'] . ' ' . $compte['responsable_nom'])
                                    : 'Non assigné';
                                $dateCreation = date('d/m/Y', strtotime($compte['date_creation']));
                            ?>
                            <tr class="table-row" 
                                data-nom="<?= htmlspecialchars(strtolower($compte['nom'])) ?>"
                                data-type="<?= htmlspecialchars($compte['type']) ?>"
                                data-devise="<?= htmlspecialchars($compte['devise']) ?>"
                                data-solde="<?= $solde ?>"
                                data-numero="<?= htmlspecialchars(strtolower($compte['numero_compte'] ?? '')) ?>">
                                <td class="text-center"><?= $n ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($compte['nom']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($compte['type']) ?>
                                    </span>
                                </td>
                                <td><?= $compte['numero_compte'] ? htmlspecialchars($compte['numero_compte']) : '-' ?></td>
                                <td class="fw-bold <?= $soldeClass ?>">
                                    <?= $compte['devise'] ?><?= number_format($solde, 2, ',', ' ') ?>
                                </td>
                                <td><?= $compte['devise'] ?></td>
                                <td><?= $responsable ?></td>
                                <td><?= $dateCreation ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" 
                                                class="btn btn-outline-primary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCompteModal"
                                                onclick="loadEditData(<?= $compte['compte_id'] ?>)"
                                                title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="mouvements.php?compte_id=<?= $compte['compte_id'] ?>" 
                                           class="btn btn-outline-info"
                                           title="Voir mouvements">
                                            <i class="fas fa-exchange-alt"></i>
                                        </a>
                                        <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal"
                                                onclick="setDeleteId(<?= $compte['compte_id'] ?>, '<?= htmlspecialchars(addslashes($compte['nom'])) ?>')"
                                                title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
        <!-- Modal Ajout Compte -->
        <div class="modal fade" id="addCompteModal" tabindex="-1" aria-labelledby="addCompteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addCompteModalLabel">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nouveau Compte
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-compte.php" method="POST" id="addForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nom du compte <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nom" required 
                                           placeholder="Ex: Caisse Principale">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type" required>
                                        <option value="Caisse" selected>Caisse</option>
                                        <option value="Banque">Banque</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Numéro de compte</label>
                                    <input type="text" class="form-control" name="numero_compte" 
                                           placeholder="Ex: 0123456789">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Devise <span class="text-danger">*</span></label>
                                    <select class="form-select" name="devise" required>
                                        <option value="$" selected>$ USD</option>
                                        <option value="€">€ EUR</option>
                                        <option value="FCFA">FCFA</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Solde initial <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="solde_initial" value="0" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Responsable</label>
                                    <select class="form-select" name="responsable_id">
                                        <option value="">Sélectionner un responsable</option>
                                        <?php foreach ($utilisateurs as $user): ?>
                                            <option value="<?= $user['utilisateur_id'] ?>">
                                                <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-light border">
                                        <i class="bi bi-info-circle text-primary me-2"></i>
                                        <small class="text-muted">Le solde actuel sera automatiquement égal au solde initial lors de la création.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Modification Compte -->
        <div class="modal fade" id="editCompteModal" tabindex="-1" aria-labelledby="editCompteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editCompteModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Modifier le Compte
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" action="../models/update/update-compte.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="editId" name="compte_id">
                        <div class="modal-body" id="editModalBody">
                            <!-- Chargé dynamiquement -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Suppression -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteModalLabel">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Confirmation de suppression
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer le compte :</p>
                        <h5 class="text-danger" id="deleteCompteName"></h5>
                        <p class="text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette action est irréversible. Le compte sera marqué comme supprimé dans le système.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <a id="deleteConfirmBtn" href="" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Supprimer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Gestion du sidebar mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebarToggler = document.querySelector('.sidebar-toggler');

            // Toggle sidebar on mobile
            mobileMenuBtn.addEventListener('click', function() {
                sidebar.classList.add('active');
                overlay.style.display = 'block';
            });

            // Close sidebar when clicking the toggler
            sidebarToggler.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
            });

            // Close sidebar when clicking overlay
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                this.style.display = 'none';
            });

            // Gestion des sous-menus
            const hasSubItems = document.querySelectorAll('.sidebar-item.has-sub');

            hasSubItems.forEach(item => {
                const link = item.querySelector('.sidebar-link');

                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Fermer les autres sous-menus
                    hasSubItems.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('active')) {
                            otherItem.classList.remove('active');
                        }
                    });

                    // Basculer l'état actif
                    item.classList.toggle('active');
                });
            });

            // Fermer le sidebar sur mobile quand on clique sur un lien
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 992) {
                        sidebar.classList.remove('active');
                        overlay.style.display = 'none';
                    }
                });
            });

            // Initialisation des fonctionnalités
            initializeSearch();
            initializeFilters();
        });

        // Initialisation des filtres
        function initializeFilters() {
            // Écouteurs pour les filtres
            document.getElementById('filterType').addEventListener('change', filterComptes);
            document.getElementById('filterDevise').addEventListener('change', filterComptes);
            document.getElementById('filterSoldeMin').addEventListener('input', filterComptes);
            document.getElementById('filterSoldeMax').addEventListener('input', filterComptes);
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterComptes();
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                document.getElementById('filterType').value = '';
                document.getElementById('filterDevise').value = '';
                document.getElementById('filterSoldeMin').value = '';
                document.getElementById('filterSoldeMax').value = '';
                filterComptes();
            });
        }
        
        function filterComptes() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('filterType').value;
            const deviseFilter = document.getElementById('filterDevise').value;
            const soldeMin = parseFloat(document.getElementById('filterSoldeMin').value) || -Infinity;
            const soldeMax = parseFloat(document.getElementById('filterSoldeMax').value) || Infinity;
            
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            const accountCards = document.querySelectorAll('.col-md-4.col-lg-3');
            
            let visibleRows = 0;
            let visibleCards = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (typeFilter) activeFilters.push(`Type: ${typeFilter}`);
            if (deviseFilter) activeFilters.push(`Devise: ${deviseFilter}`);
            if (soldeMin !== -Infinity) activeFilters.push(`Solde min: ${soldeMin}`);
            if (soldeMax !== Infinity) activeFilters.push(`Solde max: ${soldeMax}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const nom = row.getAttribute('data-nom');
                const type = row.getAttribute('data-type');
                const devise = row.getAttribute('data-devise');
                const numero = row.getAttribute('data-numero');
                const solde = parseFloat(row.getAttribute('data-solde'));
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && 
                    !nom.includes(searchTerm) && 
                    !numero.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre type
                if (typeFilter && type !== typeFilter) {
                    matches = false;
                }
                
                // Filtre devise
                if (deviseFilter && devise !== deviseFilter) {
                    matches = false;
                }
                
                // Filtre solde
                if (solde < soldeMin || solde > soldeMax) {
                    matches = false;
                }
                
                row.style.display = matches ? '' : 'none';
                if (matches) visibleRows++;
            });
            
            // Filtrer les cartes
            accountCards.forEach(card => {
                const accountCard = card.querySelector('.account-card');
                const nom = accountCard.querySelector('.card-title').textContent.toLowerCase();
                const type = accountCard.querySelector('.badge').textContent;
                const devise = accountCard.querySelector('h4').textContent.charAt(0);
                const soldeText = accountCard.querySelector('h4').textContent;
                const solde = parseFloat(soldeText.replace(/[^0-9.-]+/g,""));
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && !nom.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre type
                if (typeFilter && type !== typeFilter) {
                    matches = false;
                }
                
                // Filtre devise
                if (deviseFilter && devise !== deviseFilter) {
                    matches = false;
                }
                
                // Filtre solde
                if (solde < soldeMin || solde > soldeMax) {
                    matches = false;
                }
                
                card.style.display = matches ? '' : 'none';
                if (matches) visibleCards++;
            });
            
            // Mettre à jour les compteurs
            document.getElementById('tableVisibleCount').textContent = visibleRows;
            
            // Afficher/cacher les messages "aucun résultat"
            if (activeFilters.length > 0) {
                filterInfo.style.display = 'block';
                filterText.textContent = activeFilters.join(' | ') + ` - ${visibleRows} résultat(s)`;
                
                if (visibleRows === 0) {
                    tableNoResults.style.display = 'block';
                    document.getElementById('tableContainer').style.display = 'none';
                } else {
                    tableNoResults.style.display = 'none';
                    document.getElementById('tableContainer').style.display = 'block';
                }
            } else {
                filterInfo.style.display = 'none';
                tableNoResults.style.display = 'none';
                document.getElementById('tableContainer').style.display = 'block';
            }
            
            // Re-numéroter les lignes visibles
            renumberVisibleRows();
        }
        
        // Re-numéroter les lignes visibles
        function renumberVisibleRows() {
            let counter = 1;
            document.querySelectorAll('.table-row').forEach(row => {
                if (row.style.display !== 'none') {
                    row.querySelector('td:first-child').textContent = counter;
                    counter++;
                }
            });
        }
        
        // Tri du tableau
        let sortDirection = 1;
        let lastSortedColumn = -1;
        
        function sortTable(columnIndex) {
            const table = document.getElementById('comptesTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            // Si on clique sur la même colonne, inverser la direction
            if (lastSortedColumn === columnIndex) {
                sortDirection *= -1;
            } else {
                sortDirection = 1;
                lastSortedColumn = columnIndex;
            }
            
            rows.sort((a, b) => {
                let aText, bText;
                
                if (columnIndex === 1) {
                    // Colonne Nom
                    aText = a.querySelector('strong').textContent.toLowerCase();
                    bText = b.querySelector('strong').textContent.toLowerCase();
                } else if (columnIndex === 2) {
                    // Colonne Type
                    aText = a.getAttribute('data-type');
                    bText = b.getAttribute('data-type');
                } else if (columnIndex === 3) {
                    // Colonne N° Compte
                    aText = a.cells[3].textContent.toLowerCase();
                    bText = b.cells[3].textContent.toLowerCase();
                } else if (columnIndex === 4) {
                    // Colonne Solde (numérique)
                    aText = parseFloat(a.getAttribute('data-solde'));
                    bText = parseFloat(b.getAttribute('data-solde'));
                } else if (columnIndex === 5) {
                    // Colonne Devise
                    aText = a.getAttribute('data-devise');
                    bText = b.getAttribute('data-devise');
                } else if (columnIndex === 6) {
                    // Colonne Responsable
                    aText = a.cells[6].textContent.toLowerCase();
                    bText = b.cells[6].textContent.toLowerCase();
                } else {
                    // Autres colonnes
                    aText = a.cells[columnIndex].textContent.toLowerCase();
                    bText = b.cells[columnIndex].textContent.toLowerCase();
                }
                
                if (aText < bText) return -1 * sortDirection;
                if (aText > bText) return 1 * sortDirection;
                return 0;
            });
            
            // Réinsérer les lignes triées
            rows.forEach(row => table.appendChild(row));
            
            // Re-numéroter les lignes
            renumberVisibleRows();
        }
        
        // Charger les données pour modification
        function loadEditData(compteId) {
            fetch(`../models/get/get-compte.php?id=${compteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const compte = data.compte;
                        document.getElementById('editId').value = compteId;
                        
                        let html = `
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nom du compte <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nom" value="${compte.nom}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type" required>
                                        <option value="Caisse" ${compte.type == 'Caisse' ? 'selected' : ''}>Caisse</option>
                                        <option value="Banque" ${compte.type == 'Banque' ? 'selected' : ''}>Banque</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Numéro de compte</label>
                                    <input type="text" class="form-control" name="numero_compte" value="${compte.numero_compte || ''}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Devise <span class="text-danger">*</span></label>
                                    <select class="form-select" name="devise" required>
                                        <option value="$" ${compte.devise == '$' ? 'selected' : ''}>$ USD</option>
                                        <option value="€" ${compte.devise == '€' ? 'selected' : ''}>€ EUR</option>
                                        <option value="FCFA" ${compte.devise == 'FCFA' ? 'selected' : ''}>FCFA</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Solde actuel</label>
                                    <div class="input-group">
                                        <span class="input-group-text">${compte.devise}</span>
                                        <input type="number" class="form-control" name="solde_actuel" value="${compte.solde_actuel}" step="0.01" required readonly>
                                    </div>
                                    <small class="text-muted">Le solde ne peut pas être modifié directement ici. Utilisez le module "Mouvements"</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Responsable</label>
                                    <select class="form-select" name="responsable_id">
                                        <option value="">Sélectionner un responsable</option>
                                        <?php foreach ($utilisateurs as $user): ?>
                                        <option value="<?= $user['utilisateur_id'] ?>" ${compte.responsable_id == <?= $user['utilisateur_id'] ?> ? 'selected' : ''}>
                                            <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-light border">
                                        <i class="bi bi-info-circle text-primary me-2"></i>
                                        <small class="text-muted">Pour modifier le solde, veuillez utiliser le module "Mouvements" pour enregistrer des entrées/sorties.</small>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('editModalBody').innerHTML = html;
                    } else {
                        alert('Erreur lors du chargement des données du compte');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données');
                });
        }
        
        // Préparer la suppression
        function setDeleteId(id, nom) {
            document.getElementById('deleteCompteName').textContent = nom;
            const deleteUrl = `../models/delete/delete-compte.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Validation du formulaire d'ajout
        document.getElementById('addForm')?.addEventListener('submit', function(e) {
            const nom = this.querySelector('[name="nom"]').value.trim();
            const soldeInitial = this.querySelector('[name="solde_initial"]').value;
            
            if (!nom) {
                e.preventDefault();
                alert('Le nom du compte est requis');
                return false;
            }
            
            if (soldeInitial === '' || isNaN(soldeInitial)) {
                e.preventDefault();
                alert('Le solde initial doit être un nombre valide');
                return false;
            }
            
            return true;
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>