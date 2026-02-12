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
function hasPermission($requiredRoles, $userRoles) {
    if (empty($requiredRoles)) return true;
    foreach ($requiredRoles as $role) {
        if (in_array($role, $userRoles)) return true;
    }
    return false;
}

// Permissions pour cette page
$canAccessRoles = hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveProjets = 1;

# Récupérer les projets avec leurs informations
try {
    $query = "
        SELECT 
            p.*,
            pa.nom as partenaire_nom,
            pa.type as partenaire_type,
            CONCAT(u.prenom, ' ', u.nom) as chef_projet_nom,
            COUNT(DISTINCT part.agent_id) as nb_participants,
            COUNT(DISTINCT t.tache_id) as nb_taches,
            COUNT(DISTINCT prod.production_id) as nb_productions
        FROM PROJETS p
        LEFT JOIN PARTENAIRES pa ON p.partenaire_id = pa.partenaire_id AND pa.statut = 0
        LEFT JOIN UTILISATEURS u ON p.chef_projet_id = u.utilisateur_id AND u.statut = 0
        LEFT JOIN PARTICIPATION part ON p.projet_id = part.projet_id AND part.statut = 0
        LEFT JOIN TACHES t ON p.projet_id = t.projet_id AND t.statut = 0
        LEFT JOIN PRODUCTION prod ON p.projet_id = prod.projet_id AND prod.statut = 0
        WHERE p.statut = 0
        GROUP BY p.projet_id
        ORDER BY p.date_creation DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les statistiques
    $queryStats = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN etat = 'En cours' THEN 1 ELSE 0 END) as en_cours,
            SUM(CASE WHEN etat = 'Terminé' THEN 1 ELSE 0 END) as termines,
            SUM(CASE WHEN etat = 'Suspendu' THEN 1 ELSE 0 END) as suspendus,
            SUM(CASE WHEN etat = 'Annulé' THEN 1 ELSE 0 END) as annules,
            SUM(budget_estime) as budget_total
        FROM PROJETS 
        WHERE statut = 0
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les chefs de projet disponibles
    $queryChefs = "
        SELECT u.utilisateur_id, CONCAT(u.prenom, ' ', u.nom) as nom_complet
        FROM UTILISATEURS u
        JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id
        JOIN ROLES r ON a.role_id = r.role_id
        WHERE r.fonction = 'Chef_projet' 
        AND u.statut = 0 
        AND a.statut = 0
        ORDER BY u.nom, u.prenom
    ";
    $stmtChefs = $conn->prepare($queryChefs);
    $stmtChefs->execute();
    $chefs = $stmtChefs->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les partenaires pour le filtre
    $queryPartenaires = "
        SELECT partenaire_id, nom 
        FROM PARTENAIRES 
        WHERE statut = 0 
        ORDER BY nom
    ";
    $stmtPartenaires = $conn->prepare($queryPartenaires);
    $stmtPartenaires->execute();
    $partenairesList = $stmtPartenaires->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des projets: " . $e->getMessage());
    $projets = [];
    $stats = ['total' => 0, 'en_cours' => 0, 'termines' => 0, 'suspendus' => 0, 'annules' => 0, 'budget_total' => 0];
    $chefs = [];
    $partenairesList = [];
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
    <title>Gestion des Projets - Evotech Africa</title>
    
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

        /* Badges spécifiques aux projets */
        .badge-etat-en-cours {
            background: linear-gradient(135deg, #0d6efd, #3d8bfd);
            color: white;
        }
        
        .badge-etat-termine {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
        }
        
        .badge-etat-suspendu {
            background: linear-gradient(135deg, #6c757d, #adb5bd);
            color: white;
        }
        
        .badge-etat-annule {
            background: linear-gradient(135deg, #dc3545, #e35d6a);
            color: white;
        }
        
        .badge-priorite-haute {
            background: linear-gradient(135deg, #fd7e14, #ff922b);
            color: white;
        }
        
        .badge-priorite-urgent {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .badge-priorite-normale {
            background: linear-gradient(135deg, #0d6efd, #3d8bfd);
            color: white;
        }
        
        .badge-priorite-basse {
            background: linear-gradient(135deg, #6c757d, #adb5bd);
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

        .badge-projet {
            background-color: var(--primary-blue);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        /* Progress bar */
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            border-radius: 4px;
        }
        
        .project-metric {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .project-metric:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }
        
        .project-metric .number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .project-metric .label {
            font-size: 0.85rem;
            color: #6c757d;
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

                        <li class="sidebar-item has-sub active">
                            <a href="#" class='sidebar-link'>
                                <i class="fas fa-project-diagram text-info"></i>
                                <span>Projets & Production</span>
                            </a>
                            <ul class="submenu">
                                <?php if (hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles)): ?>
                                    <li class="active">
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
                                    <li >
                                        <a href="disk-stockage.php">Disques de stockage</a>
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

                                <?php if (hasPermission(['DG', 'comptable', 'actionnaire'], $userRoles)): ?>
                                    <li>
                                        <a href="rapports-financiers.php">Rapports financiers</a>
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
                <i class="fas fa-project-diagram me-2"></i>
                Gestion des Projets
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un projet..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjetModal">
                        <i class="fas fa-plus-circle me-1"></i> Nouveau Projet
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages d'alerte -->
        <?php if (isset($success_msg) && !empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg) && !empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="row mb-4 g-3">
            <div class="col-md-2">
                <div class="card stat-card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $stats['total'] ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">En cours</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $stats['en_cours'] ?>
                                </h2>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Terminés</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $stats['termines'] ?>
                                </h2>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-secondary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Suspendus</h6>
                                <h2 class="card-title text-secondary mb-0">
                                    <?= $stats['suspendus'] ?>
                                </h2>
                            </div>
                            <div class="text-secondary opacity-75">
                                <i class="fas fa-pause-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Annulés</h6>
                                <h2 class="card-title text-danger mb-0">
                                    <?= $stats['annules'] ?>
                                </h2>
                            </div>
                            <div class="text-danger opacity-75">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Budget total</h6>
                                <h2 class="card-title text-info mb-0">
                                    $<?= number_format($stats['budget_total'] ?? 0, 0, ',', ' ') ?>
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information de filtrage -->
        <div class="filter-info" id="filterInfo">
            <i class="fas fa-filter me-2"></i>
            <span id="filterText">Affichage des résultats filtrés</span>
            <button type="button" class="btn btn-sm btn-outline-primary ms-3" id="clearFilter">
                <i class="fas fa-times-circle me-1"></i> Effacer le filtre
            </button>
        </div>

        <!-- Filtres -->
        <div class="filter-section">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">État</label>
                    <select class="form-select" id="filterEtat">
                        <option value="">Tous les états</option>
                        <option value="En cours">En cours</option>
                        <option value="Terminé">Terminé</option>
                        <option value="Suspendu">Suspendu</option>
                        <option value="Annulé">Annulé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priorité</label>
                    <select class="form-select" id="filterPriorite">
                        <option value="">Toutes les priorités</option>
                        <option value="Basse">Basse</option>
                        <option value="Normale">Normale</option>
                        <option value="Haute">Haute</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Chef de projet</label>
                    <select class="form-select" id="filterChef">
                        <option value="">Tous les chefs</option>
                        <?php foreach ($chefs as $chef): ?>
                            <option value="<?= $chef['utilisateur_id'] ?>">
                                <?= htmlspecialchars($chef['nom_complet']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Partenaire</label>
                    <select class="form-select" id="filterPartenaire">
                        <option value="">Tous les partenaires</option>
                        <?php foreach ($partenairesList as $partenaire): ?>
                            <option value="<?= $partenaire['partenaire_id'] ?>">
                                <?= htmlspecialchars($partenaire['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="filterDateDebut">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date de fin prévue</label>
                    <input type="date" class="form-control" id="filterDateFin">
                </div>
            </div>
        </div>

        <!-- Table des projets -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list-task me-2"></i>
                    Liste des projets
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($projets) ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="fas fa-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun projet ne correspond à vos critères</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(1)">
                                        Projet <i class="fas fa-sort ms-1"></i>
                                    </button>
                                </th>
                                <th>État</th>
                                <th>Priorité</th>
                                <th>Partenaire</th>
                                <th>Chef de projet</th>
                                <th>Dates</th>
                                <th>Métriques</th>
                                <th>Budget</th>
                                <th width="140" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="projetsTable">
                            <?php
                            $n = 0;
                            foreach ($projets as $projet):
                                $n++;
                                
                                // Classes CSS pour l'état et priorité
                                $etatClass = 'badge-etat-' . strtolower(str_replace(' ', '-', $projet['etat']));
                                $prioriteClass = 'badge-priorite-' . strtolower($projet['priorite']);
                                
                                // Formater les dates
                                $dateDebut = $projet['date_debut'] ? date('d/m/Y', strtotime($projet['date_debut'])) : '-';
                                $dateFin = $projet['date_fin_prevue'] ? date('d/m/Y', strtotime($projet['date_fin_prevue'])) : '-';
                                
                                // Calculer les jours restants
                                $joursRestants = '';
                                $joursRestantsClass = '';
                                if ($projet['date_fin_prevue'] && $projet['etat'] == 'En cours') {
                                    $dateFinObj = new DateTime($projet['date_fin_prevue']);
                                    $today = new DateTime();
                                    $interval = $today->diff($dateFinObj);
                                    $jours = $interval->days;
                                    
                                    if ($dateFinObj < $today) {
                                        $joursRestants = "<span class='text-danger'>-$jours j</span>";
                                        $joursRestantsClass = 'text-danger';
                                    } else {
                                        $joursRestants = "$jours j";
                                        if ($jours <= 7) {
                                            $joursRestantsClass = 'text-warning';
                                        }
                                    }
                                }
                                
                                // Formater le budget
                                $budget = $projet['budget_estime'] > 0 
                                    ? '$' . number_format($projet['budget_estime'], 2, ',', ' ') 
                                    : '<span class="text-muted">-</span>';
                            ?>
                            <tr class="table-row" 
                                data-titre="<?= htmlspecialchars(strtolower($projet['titre'])) ?>"
                                data-etat="<?= htmlspecialchars($projet['etat']) ?>"
                                data-priorite="<?= htmlspecialchars($projet['priorite']) ?>"
                                data-chef="<?= $projet['chef_projet_id'] ?>"
                                data-partenaire="<?= $projet['partenaire_id'] ?>"
                                data-date-debut="<?= $projet['date_debut'] ?>"
                                data-date-fin="<?= $projet['date_fin_prevue'] ?>"
                                data-nb-participants="<?= $projet['nb_participants'] ?>"
                                data-nb-taches="<?= $projet['nb_taches'] ?>"
                                data-nb-productions="<?= $projet['nb_productions'] ?>"
                                data-budget="<?= $projet['budget_estime'] ?>">
                                <td class="text-center"><?= $n ?></td>
                                <td>
                                    <div>
                                        <strong class="text-primary"><?= htmlspecialchars($projet['reference']) ?></strong>
                                        <div class="small text-muted">
                                            <?= htmlspecialchars($projet['titre']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $etatClass ?>">
                                        <?= htmlspecialchars($projet['etat']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $prioriteClass ?>">
                                        <?= htmlspecialchars($projet['priorite']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($projet['partenaire_nom']): ?>
                                        <div class="small">
                                            <i class="fas fa-user-tie me-1 text-primary"></i>
                                            <?= htmlspecialchars($projet['partenaire_nom']) ?>
                                            <?php if ($projet['partenaire_type']): ?>
                                                <span class="badge bg-light text-dark ms-1">
                                                    <?= htmlspecialchars($projet['partenaire_type']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Aucun</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small">
                                        <i class="fas fa-user-cog me-1 text-success"></i>
                                        <?= htmlspecialchars($projet['chef_projet_nom'] ?? 'Non assigné') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><i class="fas fa-play-circle me-1 text-info"></i> <?= $dateDebut ?></div>
                                        <div class="<?= $joursRestantsClass ?>">
                                            <i class="fas fa-flag-checkered me-1"></i> <?= $dateFin ?>
                                            <?php if ($joursRestants): ?>
                                                <br><small>(<?= $joursRestants ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <div class="project-metric">
                                            <div class="number text-primary"><?= $projet['nb_participants'] ?></div>
                                            <div class="label">Participants</div>
                                        </div>
                                        <div class="project-metric">
                                            <div class="number text-info"><?= $projet['nb_taches'] ?></div>
                                            <div class="label">Tâches</div>
                                        </div>
                                        <div class="project-metric">
                                            <div class="number text-success"><?= $projet['nb_productions'] ?></div>
                                            <div class="label">Productions</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= $budget ?></div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="detail-projet.php?id=<?= $projet['projet_id'] ?>" 
                                           class="btn btn-outline-primary"
                                           title="Détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                        <button type="button" 
                                                class="btn btn-outline-secondary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editProjetModal"
                                                onclick="loadEditData(<?= $projet['projet_id'] ?>)"
                                                title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission(['DG'], $userRoles) && $projet['etat'] != 'Terminé'): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal"
                                                onclick="setDeleteId(<?= $projet['projet_id'] ?>, '<?= htmlspecialchars(addslashes($projet['titre'])) ?>')"
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
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
    <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
        <!-- Modal Ajout Projet -->
        <div class="modal fade" id="addProjetModal" tabindex="-1" aria-labelledby="addProjetModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addProjetModalLabel">
                            <i class="fas fa-plus-circle me-2"></i>
                            Nouveau Projet
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-projet-post.php" method="POST" id="addForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Titre du projet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="titre" required 
                                           placeholder="Ex: Développement Application Mobile XYZ">
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description_projet" rows="3" 
                                              placeholder="Décrivez le projet..."></textarea>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Partenaire / Client</label>
                                    <select class="form-select" name="partenaire_id">
                                        <option value="">Sélectionner un partenaire...</option>
                                        <?php foreach ($partenairesList as $partenaire): ?>
                                            <option value="<?= $partenaire['partenaire_id'] ?>">
                                                <?= htmlspecialchars($partenaire['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Optionnel - client associé au projet</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Chef de projet <span class="text-danger">*</span></label>
                                    <select class="form-select" name="chef_projet_id" required>
                                        <option value="">Sélectionner un chef de projet...</option>
                                        <?php foreach ($chefs as $chef): ?>
                                            <option value="<?= $chef['utilisateur_id'] ?>">
                                                <?= htmlspecialchars($chef['nom_complet']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date de début <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="date_debut" required 
                                           min="<?= date('Y-m-d') ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date de fin prévue</label>
                                    <input type="date" class="form-control" name="date_fin_prevue">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Budget estimé ($)</label>
                                    <input type="number" class="form-control" name="budget_estime" 
                                           min="0" step="0.01" placeholder="0.00">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Priorité</label>
                                    <select class="form-select" name="priorite">
                                        <option value="Normale">Normale</option>
                                        <option value="Basse">Basse</option>
                                        <option value="Haute">Haute</option>
                                        <option value="Urgent">Urgent</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">État initial</label>
                                    <select class="form-select" name="etat">
                                        <option value="En cours">En cours</option>
                                        <option value="Suspendu">Suspendu</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="alert alert-light border mt-3">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                <small class="text-muted">
                                    Un projet peut être créé sans partenaire. Vous pourrez ajouter des tâches, participants et productions après la création.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle me-1"></i> Créer le projet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Modification Projet -->
        <div class="modal fade" id="editProjetModal" tabindex="-1" aria-labelledby="editProjetModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editProjetModalLabel">
                            <i class="fas fa-edit me-2"></i>
                            Modifier le Projet
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" action="../models/update/update-projet-post.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="editId" name="projet_id">
                        <div class="modal-body" id="editModalBody">
                            <!-- Chargé dynamiquement -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle me-1"></i> Mettre à jour
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
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Confirmation de suppression
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer le projet :</p>
                        <h5 class="text-danger" id="deleteProjetTitre"></h5>
                        <p class="text-muted mt-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Cette action est irréversible. Le projet sera marqué comme supprimé dans le système.
                        </p>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention !</strong> Les tâches, participations et productions associées seront également affectées.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <a id="deleteConfirmBtn" href="" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Supprimer
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
            
            // Pré-remplir la date d'aujourd'hui pour les champs de date
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="date_debut"]').min = today;
        });

        // Initialisation des filtres
        function initializeFilters() {
            document.getElementById('filterEtat').addEventListener('change', filterProjets);
            document.getElementById('filterPriorite').addEventListener('change', filterProjets);
            document.getElementById('filterChef').addEventListener('change', filterProjets);
            document.getElementById('filterPartenaire').addEventListener('change', filterProjets);
            document.getElementById('filterDateDebut').addEventListener('change', filterProjets);
            document.getElementById('filterDateFin').addEventListener('change', filterProjets);
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterProjets();
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                document.getElementById('filterEtat').value = '';
                document.getElementById('filterPriorite').value = '';
                document.getElementById('filterChef').value = '';
                document.getElementById('filterPartenaire').value = '';
                document.getElementById('filterDateDebut').value = '';
                document.getElementById('filterDateFin').value = '';
                filterProjets();
            });
        }
        
        function filterProjets() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const etatFilter = document.getElementById('filterEtat').value;
            const prioriteFilter = document.getElementById('filterPriorite').value;
            const chefFilter = document.getElementById('filterChef').value;
            const partenaireFilter = document.getElementById('filterPartenaire').value;
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
            if (prioriteFilter) activeFilters.push(`Priorité: ${prioriteFilter}`);
            if (chefFilter) activeFilters.push(`Chef: ${document.querySelector('#filterChef option[value="' + chefFilter + '"]')?.textContent}`);
            if (partenaireFilter) activeFilters.push(`Partenaire: ${document.querySelector('#filterPartenaire option[value="' + partenaireFilter + '"]')?.textContent}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const titre = row.getAttribute('data-titre');
                const etat = row.getAttribute('data-etat');
                const priorite = row.getAttribute('data-priorite');
                const chef = row.getAttribute('data-chef');
                const partenaire = row.getAttribute('data-partenaire');
                const dateDebut = row.getAttribute('data-date-debut');
                const dateFin = row.getAttribute('data-date-fin');
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && !titre.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre état
                if (etatFilter && etat !== etatFilter) {
                    matches = false;
                }
                
                // Filtre priorité
                if (prioriteFilter && priorite !== prioriteFilter) {
                    matches = false;
                }
                
                // Filtre chef
                if (chefFilter && chef !== chefFilter) {
                    matches = false;
                }
                
                // Filtre partenaire
                if (partenaireFilter && partenaire !== partenaireFilter) {
                    matches = false;
                }
                
                // Filtre date début
                if (dateDebutFilter && dateDebut < dateDebutFilter) {
                    matches = false;
                }
                
                // Filtre date fin
                if (dateFinFilter && dateFin > dateFinFilter) {
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
            const table = document.getElementById('projetsTable');
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
                    // Colonne Titre
                    aText = a.querySelector('strong').textContent.toLowerCase();
                    bText = b.querySelector('strong').textContent.toLowerCase();
                } else if (columnIndex === 2) {
                    // Colonne État
                    aText = a.getAttribute('data-etat');
                    bText = b.getAttribute('data-etat');
                } else if (columnIndex === 3) {
                    // Colonne Priorité (ordre personnalisé)
                    const prioriteOrder = { 'Urgent': 0, 'Haute': 1, 'Normale': 2, 'Basse': 3 };
                    aText = prioriteOrder[a.getAttribute('data-priorite')] || 4;
                    bText = prioriteOrder[b.getAttribute('data-priorite')] || 4;
                } else if (columnIndex === 6) {
                    // Colonne Date début
                    aText = a.getAttribute('data-date-debut') || '';
                    bText = b.getAttribute('data-date-debut') || '';
                } else if (columnIndex === 7) {
                    // Colonne Participants (numérique)
                    aText = parseInt(a.getAttribute('data-nb-participants'));
                    bText = parseInt(b.getAttribute('data-nb-participants'));
                } else if (columnIndex === 8) {
                    // Colonne Budget (numérique)
                    aText = parseFloat(a.getAttribute('data-budget')) || 0;
                    bText = parseFloat(b.getAttribute('data-budget')) || 0;
                } else {
                    // Autres colonnes
                    aText = a.cells[columnIndex]?.textContent.toLowerCase() || '';
                    bText = b.cells[columnIndex]?.textContent.toLowerCase() || '';
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
        function loadEditData(projetId) {
            fetch(`../models/get/get-projet.php?id=${projetId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const projet = data.projet;
                        document.getElementById('editId').value = projetId;
                        
                        let html = `
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Titre du projet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="titre" value="${projet.titre}" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description_projet" rows="3">${projet.description_projet || ''}</textarea>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Partenaire / Client</label>
                                    <select class="form-select" name="partenaire_id">
                                        <option value="">Sélectionner un partenaire...</option>
                                        <?php foreach ($partenairesList as $partenaire): ?>
                                            <option value="<?= $partenaire['partenaire_id'] ?>" ${projet.partenaire_id == <?= $partenaire['partenaire_id'] ?> ? 'selected' : ''}>
                                                <?= htmlspecialchars($partenaire['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Chef de projet <span class="text-danger">*</span></label>
                                    <select class="form-select" name="chef_projet_id" required>
                                        <option value="">Sélectionner un chef de projet...</option>
                                        <?php foreach ($chefs as $chef): ?>
                                            <option value="<?= $chef['utilisateur_id'] ?>" ${projet.chef_projet_id == <?= $chef['utilisateur_id'] ?> ? 'selected' : ''}>
                                                <?= htmlspecialchars($chef['nom_complet']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date de début <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="date_debut" value="${projet.date_debut}" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date de fin prévue</label>
                                    <input type="date" class="form-control" name="date_fin_prevue" value="${projet.date_fin_prevue || ''}">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Budget estimé ($)</label>
                                    <input type="number" class="form-control" name="budget_estime" value="${projet.budget_estime || ''}">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Priorité</label>
                                    <select class="form-select" name="priorite">
                                        <option value="Normale" ${projet.priorite == 'Normale' ? 'selected' : ''}>Normale</option>
                                        <option value="Basse" ${projet.priorite == 'Basse' ? 'selected' : ''}>Basse</option>
                                        <option value="Haute" ${projet.priorite == 'Haute' ? 'selected' : ''}>Haute</option>
                                        <option value="Urgent" ${projet.priorite == 'Urgent' ? 'selected' : ''}>Urgent</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">État</label>
                                    <select class="form-select" name="etat">
                                        <option value="En cours" ${projet.etat == 'En cours' ? 'selected' : ''}>En cours</option>
                                        <option value="Terminé" ${projet.etat == 'Terminé' ? 'selected' : ''}>Terminé</option>
                                        <option value="Suspendu" ${projet.etat == 'Suspendu' ? 'selected' : ''}>Suspendu</option>
                                        <option value="Annulé" ${projet.etat == 'Annulé' ? 'selected' : ''}>Annulé</option>
                                    </select>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('editModalBody').innerHTML = html;
                    } else {
                        alert('Erreur lors du chargement des données du projet');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données');
                });
        }
        
        // Préparer la suppression
        function setDeleteId(id, titre) {
            document.getElementById('deleteProjetTitre').textContent = titre;
            
            // Mettre à jour le lien de suppression
            const deleteUrl = `../models/delete/delete-projet.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Validation du formulaire d'ajout
        document.getElementById('addForm')?.addEventListener('submit', function(e) {
            const titre = this.querySelector('[name="titre"]').value.trim();
            const chefProjet = this.querySelector('[name="chef_projet_id"]').value;
            const dateDebut = this.querySelector('[name="date_debut"]').value;
            
            if (!titre) {
                e.preventDefault();
                alert('Le titre du projet est requis');
                return false;
            }
            
            if (!chefProjet) {
                e.preventDefault();
                alert('Veuillez sélectionner un chef de projet');
                return false;
            }
            
            if (!dateDebut) {
                e.preventDefault();
                alert('La date de début est requise');
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