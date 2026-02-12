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
$canAccessRoles = hasPermission(['DG', 'ChefDepartement'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveContrats = 1;

# Récupérer les contrats avec jointures
try {
    $query = "
        SELECT 
            c.*,
            p.nom as partenaire_nom,
            a.nom as responsable_nom,
            a.prenom as responsable_prenom
        FROM CONTRATS_COM c
        LEFT JOIN PARTENAIRES p ON c.partenaire_id = p.partenaire_id
        LEFT JOIN AGENTS a ON c.responsable_id = a.agent_id
        WHERE c.statut = 0
        ORDER BY c.date_creation DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $contrats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les partenaires pour le formulaire
    $queryPartenaires = "SELECT partenaire_id, nom FROM PARTENAIRES WHERE statut = 0 ORDER BY nom";
    $stmtPartenaires = $conn->prepare($queryPartenaires);
    $stmtPartenaires->execute();
    $partenaires = $stmtPartenaires->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les agents (responsables possibles)
    $queryAgents = "SELECT agent_id, nom, prenom FROM AGENTS WHERE etat = 'Actif' AND statut = 0 ORDER BY nom";
    $stmtAgents = $conn->prepare($queryAgents);
    $stmtAgents->execute();
    $agents = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques
    $queryStats = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN etat = 'Actif' THEN 1 ELSE 0 END) as actifs,
            SUM(CASE WHEN etat = 'Terminé' THEN 1 ELSE 0 END) as termines,
            SUM(CASE WHEN etat = 'En attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN etat = 'Résilié' THEN 1 ELSE 0 END) as resilies,
            SUM(montant) as total_montant
        FROM CONTRATS_COM 
        WHERE statut = 0
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des contrats: " . $e->getMessage());
    $contrats = [];
    $partenaires = [];
    $agents = [];
    $stats = ['total' => 0, 'actifs' => 0, 'termines' => 0, 'en_attente' => 0, 'resilies' => 0, 'total_montant' => 0];
}

# Messages de session
$success_msg = $_SESSION['msg'] ?? null;
$error_msg = $_SESSION['error'] ?? null;
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
    <title>Contrats Communication - Evotech Africa</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">

    <style>
        :root {
            --primary-blue: #0d6efd;
            --primary-blue-dark: #0b5ed7;
            --primary-blue-light: #3d8bfd;
            --secondary-blue: #6ea8fe;
            --light-blue-bg: #f0f8ff;
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

        /* Badges spécifiques aux contrats */
        .badge-actif {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
        }

        .badge-termine {
            background: linear-gradient(135deg, #6c757d, #adb5bd);
            color: white;
        }

        .badge-en-attente {
            background: linear-gradient(135deg, #ffc107, #ffca2c);
            color: #000;
        }

        .badge-resilie {
            background: linear-gradient(135deg, #dc3545, #e35d6a);
            color: white;
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

                        <li class="sidebar-item has-sub active">
                            <a href="#" class='sidebar-link'>
                                <i class="fas fa-file-contract text-success"></i>
                                <span>Contrats Communication</span>
                            </a>
                            <ul class="submenu">
                                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                    <li class="active">
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

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="fas fa-money-bill-wave text-success"></i>
                                <span>Gestion Financière</span>
                            </a>
                            <ul class="submenu">
                                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                    <li>
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
                <i class="bi bi-file-earmark-text me-2"></i>
                Contrats de Communication
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un contrat..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContratModal">
                        <i class="bi bi-plus-circle me-1"></i> Nouveau Contrat
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Contrats</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $stats['total'] ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="bi bi-files"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Actifs</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $stats['actifs'] ?>
                                </h2>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="bi bi-check-circle"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">En Attente</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $stats['en_attente'] ?>
                                </h2>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="bi bi-clock"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Valeur Totale</h6>
                                <h2 class="card-title text-info mb-0" id="filteredCount">
                                    $<?= number_format($stats['total_montant'], 2, ',', ' ') ?>
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                    <label class="form-label">État</label>
                    <select class="form-select" id="filterEtat">
                        <option value="">Tous les états</option>
                        <option value="Actif">Actif</option>
                        <option value="En attente">En attente</option>
                        <option value="Terminé">Terminé</option>
                        <option value="Résilié">Résilié</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Périodicité</label>
                    <select class="form-select" id="filterPeriodicite">
                        <option value="">Toutes</option>
                        <option value="Mensuel">Mensuel</option>
                        <option value="Trimestriel">Trimestriel</option>
                        <option value="Annuel">Annuel</option>
                        <option value="Forfait">Forfait</option>
                        <option value="Horaire">Horaire</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date début</label>
                    <input type="date" class="form-control" id="filterDateDebut">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="filterDateFin">
                </div>
            </div>
        </div>

        <!-- Table des contrats seulement -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-task me-2"></i>
                    Liste des contrats
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($contrats) ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun contrat ne correspond à vos critères</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(1)">
                                        Référence <i class="bi bi-arrow-down-up ms-1"></i>
                                    </button>
                                </th>
                                <th>Partenaire</th>
                                <th>Type</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(4)">
                                        Montant <i class="bi bi-arrow-down-up ms-1"></i>
                                    </button>
                                </th>
                                <th>État</th>
                                <th>Périodicité</th>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th width="140" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="contratsTable">
                            <?php
                            $n = 0;
                            foreach ($contrats as $contrat):
                                $n++;
                                $montant = number_format($contrat['montant'], 2, ',', ' ');
                                $etatClass = 'badge-' . strtolower(str_replace(' ', '-', $contrat['etat']));
                                $dateDebut = date('d/m/Y', strtotime($contrat['date_debut']));
                                $dateFin = $contrat['date_fin'] ? date('d/m/Y', strtotime($contrat['date_fin'])) : '-';
                            ?>
                            <tr class="table-row" 
                                data-reference="<?= htmlspecialchars(strtolower($contrat['reference'])) ?>"
                                data-partenaire="<?= htmlspecialchars(strtolower($contrat['partenaire_nom'])) ?>"
                                data-type="<?= htmlspecialchars(strtolower($contrat['type_contrat'])) ?>"
                                data-etat="<?= htmlspecialchars($contrat['etat']) ?>"
                                data-periodicite="<?= htmlspecialchars($contrat['periodicite']) ?>"
                                data-date-debut="<?= htmlspecialchars($contrat['date_debut']) ?>"
                                data-montant="<?= $contrat['montant'] ?>">
                                <td class="text-center"><?= $n ?></td>
                                <td>
                                    <strong class="text-primary"><?= htmlspecialchars($contrat['reference']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($contrat['partenaire_nom']) ?></td>
                                <td><?= htmlspecialchars($contrat['type_contrat']) ?></td>
                                <td class="fw-bold">$<?= $montant ?></td>
                                <td>
                                    <span class="badge <?= $etatClass ?>">
                                        <?= htmlspecialchars($contrat['etat']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($contrat['periodicite']) ?>
                                    </span>
                                </td>
                                <td><?= $dateDebut ?></td>
                                <td><?= $dateFin ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" 
                                                class="btn btn-outline-primary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editContratModal"
                                                onclick="loadEditData(<?= $contrat['contrat_id'] ?>)"
                                                title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="activites-contrat.php?contrat_id=<?= $contrat['contrat_id'] ?>" 
                                           class="btn btn-outline-info"
                                           title="Activités">
                                            <i class="bi bi-list-task"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal"
                                                onclick="setDeleteId(<?= $contrat['contrat_id'] ?>, '<?= htmlspecialchars(addslashes($contrat['reference'])) ?>')"
                                                title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
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
    <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
        <!-- Modal Ajout Contrat -->
        <div class="modal fade" id="addContratModal" tabindex="-1" aria-labelledby="addContratModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addContratModalLabel">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nouveau Contrat
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-contrat-com.php" method="POST" id="addForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Partenaire <span class="text-danger">*</span></label>
                                    <select class="form-select" name="partenaire_id" required>
                                        <option value="">Sélectionner un partenaire</option>
                                        <?php foreach ($partenaires as $partenaire): ?>
                                            <option value="<?= $partenaire['partenaire_id'] ?>">
                                                <?= htmlspecialchars($partenaire['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type de contrat <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="type_contrat" required 
                                           placeholder="Ex: Gestion Réseaux Sociaux">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date début <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="date_debut" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date fin</label>
                                    <input type="date" class="form-control" name="date_fin">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Montant <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="montant" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Périodicité</label>
                                    <select class="form-select" name="periodicite">
                                        <option value="Mensuel">Mensuel</option>
                                        <option value="Trimestriel">Trimestriel</option>
                                        <option value="Annuel">Annuel</option>
                                        <option value="Forfait">Forfait</option>
                                        <option value="Hebdomadaire">Hebdomadaire</option>
                                        <option value="Horaire">Horaire</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Responsable</label>
                                    <select class="form-select" name="responsable_id">
                                        <option value="">Sélectionner un responsable</option>
                                        <?php foreach ($agents as $agent): ?>
                                            <option value="<?= $agent['agent_id'] ?>">
                                                <?= htmlspecialchars($agent['prenom'] . ' ' . $agent['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">État</label>
                                    <select class="form-select" name="etat">
                                        <option value="Actif">Actif</option>
                                        <option value="En attente">En attente</option>
                                        <option value="Terminé">Terminé</option>
                                        <option value="Résilié">Résilié</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Conditions particulières</label>
                                    <textarea class="form-control" name="conditions_particulieres" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="alert alert-light border mt-3">
                                <i class="bi bi-info-circle text-primary me-2"></i>
                                <small class="text-muted">La référence sera générée automatiquement après l'enregistrement.</small>
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

        <!-- Modal Modification Contrat -->
        <div class="modal fade" id="editContratModal" tabindex="-1" aria-labelledby="editContratModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editContratModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Modifier le Contrat
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" action="../models/update/update-contrat-com.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="editId" name="contrat_id">
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
                        <p>Êtes-vous sûr de vouloir supprimer le contrat :</p>
                        <h5 class="text-danger" id="deleteContratName"></h5>
                        <p class="text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette action est irréversible. Le contrat sera marqué comme supprimé dans le système.
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/locales/bootstrap-datepicker.fr.min.js"></script>

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
            initializeDateFilters();
        });

        // Initialisation des filtres date
        function initializeDateFilters() {
            // Écouteurs pour les filtres
            document.getElementById('filterEtat').addEventListener('change', filterContrats);
            document.getElementById('filterPeriodicite').addEventListener('change', filterContrats);
            document.getElementById('filterDateDebut').addEventListener('change', filterContrats);
            document.getElementById('filterDateFin').addEventListener('change', filterContrats);
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterContrats();
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                document.getElementById('filterEtat').value = '';
                document.getElementById('filterPeriodicite').value = '';
                document.getElementById('filterDateDebut').value = '';
                document.getElementById('filterDateFin').value = '';
                filterContrats();
            });
        }
        
        function filterContrats() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const etatFilter = document.getElementById('filterEtat').value;
            const periodiciteFilter = document.getElementById('filterPeriodicite').value;
            const dateDebutFilter = document.getElementById('filterDateDebut').value;
            const dateFinFilter = document.getElementById('filterDateFin').value;
            
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            
            let visibleRows = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (etatFilter) activeFilters.push(`État: ${etatFilter}`);
            if (periodiciteFilter) activeFilters.push(`Périodicité: ${periodiciteFilter}`);
            if (dateDebutFilter) activeFilters.push(`À partir du: ${dateDebutFilter}`);
            if (dateFinFilter) activeFilters.push(`Jusqu'au: ${dateFinFilter}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const reference = row.getAttribute('data-reference');
                const partenaire = row.getAttribute('data-partenaire');
                const type = row.getAttribute('data-type');
                const etat = row.getAttribute('data-etat');
                const periodicite = row.getAttribute('data-periodicite');
                const dateDebut = row.getAttribute('data-date-debut');
                const montant = parseFloat(row.getAttribute('data-montant'));
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && 
                    !reference.includes(searchTerm) && 
                    !partenaire.includes(searchTerm) &&
                    !type.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre état
                if (etatFilter && etat !== etatFilter) {
                    matches = false;
                }
                
                // Filtre périodicité
                if (periodiciteFilter && periodicite !== periodiciteFilter) {
                    matches = false;
                }
                
                // Filtre date début
                if (dateDebutFilter && dateDebut < dateDebutFilter) {
                    matches = false;
                }
                
                row.style.display = matches ? '' : 'none';
                if (matches) visibleRows++;
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
            const table = document.getElementById('contratsTable');
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
                    // Colonne Référence
                    aText = a.querySelector('strong').textContent.toLowerCase();
                    bText = b.querySelector('strong').textContent.toLowerCase();
                } else if (columnIndex === 2) {
                    // Colonne Partenaire
                    aText = a.cells[2].textContent.toLowerCase();
                    bText = b.cells[2].textContent.toLowerCase();
                } else if (columnIndex === 3) {
                    // Colonne Type
                    aText = a.cells[3].textContent.toLowerCase();
                    bText = b.cells[3].textContent.toLowerCase();
                } else if (columnIndex === 4) {
                    // Colonne Montant (numérique)
                    aText = parseFloat(a.getAttribute('data-montant'));
                    bText = parseFloat(b.getAttribute('data-montant'));
                } else if (columnIndex === 5) {
                    // Colonne État
                    aText = a.getAttribute('data-etat');
                    bText = b.getAttribute('data-etat');
                } else if (columnIndex === 6) {
                    // Colonne Périodicité
                    aText = a.getAttribute('data-periodicite');
                    bText = b.getAttribute('data-periodicite');
                } else if (columnIndex === 7) {
                    // Colonne Date début
                    aText = a.getAttribute('data-date-debut');
                    bText = b.getAttribute('data-date-debut');
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
        function loadEditData(contratId) {
            fetch(`../models/get/get-contrat.php?id=${contratId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const contrat = data.contrat;
                        document.getElementById('editId').value = contratId;
                        
                        let html = `
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Partenaire <span class="text-danger">*</span></label>
                                    <select class="form-select" name="partenaire_id" required>
                                        <option value="">Sélectionner un partenaire</option>
                                        <?php foreach ($partenaires as $p): ?>
                                        <option value="<?= $p['partenaire_id'] ?>" ${contrat.partenaire_id == <?= $p['partenaire_id'] ?> ? 'selected' : ''}>
                                            <?= htmlspecialchars($p['nom']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type de contrat <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="type_contrat" value="${contrat.type_contrat}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date début <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="date_debut" value="${contrat.date_debut}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date fin</label>
                                    <input type="date" class="form-control" name="date_fin" value="${contrat.date_fin || ''}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Montant <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="montant" value="${contrat.montant}" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Périodicité</label>
                                    <select class="form-select" name="periodicite">
                                        <option value="Mensuel" ${contrat.periodicite == 'Mensuel' ? 'selected' : ''}>Mensuel</option>
                                        <option value="Trimestriel" ${contrat.periodicite == 'Trimestriel' ? 'selected' : ''}>Trimestriel</option>
                                        <option value="Annuel" ${contrat.periodicite == 'Annuel' ? 'selected' : ''}>Annuel</option>
                                        <option value="Forfait" ${contrat.periodicite == 'Forfait' ? 'selected' : ''}>Forfait</option>
                                        <option value="Horaire" ${contrat.periodicite == 'Horaire' ? 'selected' : ''}>Horaire</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Responsable</label>
                                    <select class="form-select" name="responsable_id">
                                        <option value="">Sélectionner un responsable</option>
                                        <?php foreach ($agents as $a): ?>
                                        <option value="<?= $a['agent_id'] ?>" ${contrat.responsable_id == <?= $a['agent_id'] ?> ? 'selected' : ''}>
                                            <?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">État</label>
                                    <select class="form-select" name="etat">
                                        <option value="Actif" ${contrat.etat == 'Actif' ? 'selected' : ''}>Actif</option>
                                        <option value="En attente" ${contrat.etat == 'En attente' ? 'selected' : ''}>En attente</option>
                                        <option value="Terminé" ${contrat.etat == 'Terminé' ? 'selected' : ''}>Terminé</option>
                                        <option value="Résilié" ${contrat.etat == 'Résilié' ? 'selected' : ''}>Résilié</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3">${contrat.description || ''}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Conditions particulières</label>
                                    <textarea class="form-control" name="conditions_particulieres" rows="2">${contrat.conditions_particulieres || ''}</textarea>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('editModalBody').innerHTML = html;
                    } else {
                        alert('Erreur lors du chargement des données du contrat');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données');
                });
        }
        
        // Changer l'état d'un contrat
        function changerEtat(contratId, nouvelEtat, reference) {
            if (confirm(`Êtes-vous sûr de vouloir marquer le contrat "${reference}" comme "${nouvelEtat}" ?`)) {
                const formData = new FormData();
                formData.append('contrat_id', contratId);
                formData.append('etat', nouvelEtat);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                fetch('../models/update/change-etat-contrat.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('État modifié avec succès');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la modification');
                });
            }
        }
        
        // Préparer la suppression
        function setDeleteId(id, reference) {
            document.getElementById('deleteContratName').textContent = reference;
            const deleteUrl = `../models/delete/delete-contrat.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Validation du formulaire d'ajout
        document.getElementById('addForm')?.addEventListener('submit', function(e) {
            const dateDebut = this.querySelector('[name="date_debut"]').value;
            const dateFin = this.querySelector('[name="date_fin"]').value;
            const montant = this.querySelector('[name="montant"]').value;
            
            if (!dateDebut) {
                e.preventDefault();
                alert('La date de début est requise');
                return false;
            }
            
            if (dateFin && new Date(dateFin) < new Date(dateDebut)) {
                e.preventDefault();
                alert('La date de fin ne peut pas être antérieure à la date de début');
                return false;
            }
            
            if (!montant || parseFloat(montant) <= 0) {
                e.preventDefault();
                alert('Le montant doit être supérieur à 0');
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
        
        // Initialisation datepicker (si nécessaire)
        $(document).ready(function() {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                language: 'fr',
                autoclose: true
            });
        });
    </script>
</body>
</html>