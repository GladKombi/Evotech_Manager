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
$canAccessRoles = hasPermission(['DG', 'ChefDepartement', 'Chef_projet'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActivePartenaires = 1;

# Récupérer les partenaires
try {
    $query = "SELECT * FROM PARTENAIRES WHERE statut = 0 ORDER BY date_creation DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques
    $queryStats = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN type = 'Entreprise' THEN 1 ELSE 0 END) as entreprises,
            SUM(CASE WHEN type = 'Particulier' THEN 1 ELSE 0 END) as particuliers
        FROM PARTENAIRES 
        WHERE statut = 0
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Compter les contrats par partenaire
    $queryContrats = "
        SELECT p.partenaire_id, p.nom, COUNT(c.contrat_id) as nb_contrats
        FROM PARTENAIRES p
        LEFT JOIN CONTRATS_COM c ON p.partenaire_id = c.partenaire_id AND c.statut = 0
        WHERE p.statut = 0
        GROUP BY p.partenaire_id
        ORDER BY nb_contrats DESC
    ";
    $stmtContrats = $conn->prepare($queryContrats);
    $stmtContrats->execute();
    $contratsStats = $stmtContrats->fetchAll(PDO::FETCH_ASSOC);
    
    // Créer un tableau associatif pour les statistiques de contrats
    $contratsParPartenaire = [];
    foreach ($contratsStats as $stat) {
        $contratsParPartenaire[$stat['partenaire_id']] = $stat['nb_contrats'];
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des partenaires: " . $e->getMessage());
    $partenaires = [];
    $stats = ['total' => 0, 'entreprises' => 0, 'particuliers' => 0];
    $contratsParPartenaire = [];
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
    <title>Gestion des Partenaires - Evotech Africa</title>
    
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

        /* Badges spécifiques aux partenaires */
        .badge-entreprise {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
        }

        .badge-particulier {
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

        .badge-contrat {
            background-color: var(--primary-blue);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
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
                                    <li class="active">
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
                <i class="bi bi-people me-2"></i>
                Gestion des Partenaires
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un partenaire..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'ChefDepartement', 'Chef_projet'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPartenaireModal">
                        <i class="bi bi-plus-circle me-1"></i> Nouveau Partenaire
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Partenaires</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $stats['total'] ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="bi bi-people"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Entreprises</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $stats['entreprises'] ?>
                                </h2>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="bi bi-building"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Particuliers</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $stats['particuliers'] ?>
                                </h2>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="bi bi-person"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Avec contrats</h6>
                                <h2 class="card-title text-info mb-0" id="filteredCount">
                                    <?= count(array_filter($contratsParPartenaire, function($value) {
                                        return $value > 0;
                                    })) ?>
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="bi bi-file-earmark-text"></i>
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
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select class="form-select" id="filterType">
                        <option value="">Tous les types</option>
                        <option value="Entreprise">Entreprise</option>
                        <option value="Particulier">Particulier</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Secteur d'activité</label>
                    <input type="text" class="form-control" id="filterSecteur" placeholder="Filtrer par secteur...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date création</label>
                    <input type="date" class="form-control" id="filterDate">
                </div>
            </div>
        </div>

        <!-- Table des partenaires -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-task me-2"></i>
                    Liste des partenaires
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($partenaires) ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun partenaire ne correspond à vos critères</p>
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
                                <th>Contact</th>
                                <th>Secteur</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(5)">
                                        Contrats <i class="bi bi-arrow-down-up ms-1"></i>
                                    </button>
                                </th>
                                <th>Date création</th>
                                <th width="140" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="partenairesTable">
                            <?php
                            $n = 0;
                            foreach ($partenaires as $partenaire):
                                $n++;
                                $typeClass = 'badge-' . strtolower($partenaire['type']);
                                $dateCreation = date('d/m/Y', strtotime($partenaire['date_creation']));
                                $nbContrats = $contratsParPartenaire[$partenaire['partenaire_id']] ?? 0;
                                $contactInfo = '';
                                if (!empty($partenaire['telephone'])) {
                                    $contactInfo .= '<i class="bi bi-telephone me-1"></i>' . htmlspecialchars($partenaire['telephone']) . '<br>';
                                }
                                if (!empty($partenaire['email'])) {
                                    $contactInfo .= '<i class="bi bi-envelope me-1"></i>' . htmlspecialchars($partenaire['email']);
                                }
                            ?>
                            <tr class="table-row" 
                                data-nom="<?= htmlspecialchars(strtolower($partenaire['nom'])) ?>"
                                data-type="<?= htmlspecialchars($partenaire['type']) ?>"
                                data-secteur="<?= htmlspecialchars(strtolower($partenaire['secteur_activite'] ?? '')) ?>"
                                data-date-creation="<?= htmlspecialchars($partenaire['date_creation']) ?>"
                                data-nb-contrats="<?= $nbContrats ?>">
                                <td class="text-center"><?= $n ?></td>
                                <td>
                                    <strong class="text-primary"><?= htmlspecialchars($partenaire['nom']) ?></strong>
                                    <?php if (!empty($partenaire['adresse'])): ?>
                                    <div class="small text-muted">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?= htmlspecialchars(substr($partenaire['adresse'], 0, 50)) ?>
                                        <?= strlen($partenaire['adresse']) > 50 ? '...' : '' ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $typeClass ?>">
                                        <?= htmlspecialchars($partenaire['type']) ?>
                                    </span>
                                </td>
                                <td class="small">
                                    <?= $contactInfo ?>
                                </td>
                                <td>
                                    <?php if (!empty($partenaire['secteur_activite'])): ?>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($partenaire['secteur_activite']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Non spécifié</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-contrat">
                                        <i class="bi bi-file-earmark-text me-1"></i>
                                        <?= $nbContrats ?> contrat(s)
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= $dateCreation ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" 
                                                class="btn btn-outline-primary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editPartenaireModal"
                                                onclick="loadEditData(<?= $partenaire['partenaire_id'] ?>)"
                                                title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($nbContrats == 0): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal"
                                                onclick="setDeleteId(<?= $partenaire['partenaire_id'] ?>, '<?= htmlspecialchars(addslashes($partenaire['nom'])) ?>')"
                                                title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="btn btn-outline-info"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#contratsModal"
                                                onclick="showContrats(<?= $partenaire['partenaire_id'] ?>, '<?= htmlspecialchars(addslashes($partenaire['nom'])) ?>')"
                                                title="Voir les contrats">
                                            <i class="bi bi-file-earmark-text"></i>
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
    <?php if (hasPermission(['DG', 'ChefDepartement', 'Chef_projet'], $userRoles)): ?>
        <!-- Modal Ajout Partenaire -->
        <div class="modal fade" id="addPartenaireModal" tabindex="-1" aria-labelledby="addPartenaireModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addPartenaireModalLabel">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nouveau Partenaire
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-partenaire-post.php" method="POST" id="addForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nom" required 
                                           placeholder="Ex: Société XYZ">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type" required>
                                        <option value="Entreprise">Entreprise</option>
                                        <option value="Particulier">Particulier</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Secteur d'activité</label>
                                    <input type="text" class="form-control" name="secteur_activite" 
                                           placeholder="Ex: Informatique, Communication...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" name="telephone" 
                                           placeholder="Ex: +237 6XX XX XX XX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           placeholder="exemple@domaine.com">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Adresse</label>
                                    <textarea class="form-control" name="adresse" rows="2" 
                                              placeholder="Adresse complète..."></textarea>
                                </div>
                            </div>
                            <div class="alert alert-light border mt-3">
                                <i class="bi bi-info-circle text-primary me-2"></i>
                                <small class="text-muted">Les partenaires peuvent être des clients, fournisseurs ou collaborateurs d'Evotech Africa.</small>
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

        <!-- Modal Modification Partenaire -->
        <div class="modal fade" id="editPartenaireModal" tabindex="-1" aria-labelledby="editPartenaireModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editPartenaireModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Modifier le Partenaire
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" action="../models/update/update-partenaire-post.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="editId" name="partenaire_id">
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
                        <p>Êtes-vous sûr de vouloir supprimer le partenaire :</p>
                        <h5 class="text-danger" id="deletePartenaireName"></h5>
                        <p class="text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette action est irréversible. Le partenaire sera marqué comme supprimé dans le système.
                        </p>
                        <div class="alert alert-warning mt-3" id="hasContratsWarning" style="display: none;">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Attention !</strong> Ce partenaire a des contrats associés. La suppression n'est pas recommandée.
                        </div>
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

    <!-- Modal Contrats du Partenaire -->
    <div class="modal fade" id="contratsModal" tabindex="-1" aria-labelledby="contratsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="contratsModalLabel">
                        <i class="bi bi-file-earmark-text me-2"></i>
                        Contrats du partenaire
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6 id="contratsPartenaireTitle" class="text-primary"></h6>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Type</th>
                                    <th>État</th>
                                    <th>Montant</th>
                                    <th>Date début</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="contratsList">
                                <!-- Les contrats seront chargés ici dynamiquement -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center py-3" id="noContratsMessage" style="display: none;">
                        <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                        <h6 class="mt-3 text-muted">Aucun contrat</h6>
                        <p class="text-muted small">Ce partenaire n'a aucun contrat associé.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

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
            document.getElementById('filterType').addEventListener('change', filterPartenaires);
            document.getElementById('filterSecteur').addEventListener('keyup', filterPartenaires);
            document.getElementById('filterDate').addEventListener('change', filterPartenaires);
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterPartenaires();
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                document.getElementById('filterType').value = '';
                document.getElementById('filterSecteur').value = '';
                document.getElementById('filterDate').value = '';
                filterPartenaires();
            });
        }
        
        function filterPartenaires() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('filterType').value;
            const secteurFilter = document.getElementById('filterSecteur').value.toLowerCase();
            const dateFilter = document.getElementById('filterDate').value;
            
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            
            let visibleRows = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (typeFilter) activeFilters.push(`Type: ${typeFilter}`);
            if (secteurFilter) activeFilters.push(`Secteur: "${secteurFilter}"`);
            if (dateFilter) activeFilters.push(`À partir du: ${dateFilter}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const nom = row.getAttribute('data-nom');
                const type = row.getAttribute('data-type');
                const secteur = row.getAttribute('data-secteur');
                const dateCreation = row.getAttribute('data-date-creation');
                const nbContrats = parseInt(row.getAttribute('data-nb-contrats'));
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && !nom.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre type
                if (typeFilter && type !== typeFilter) {
                    matches = false;
                }
                
                // Filtre secteur
                if (secteurFilter && !secteur.includes(secteurFilter)) {
                    matches = false;
                }
                
                // Filtre date
                if (dateFilter && dateCreation < dateFilter) {
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
            const table = document.getElementById('partenairesTable');
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
                } else if (columnIndex === 5) {
                    // Colonne Contrats (numérique)
                    aText = parseInt(a.getAttribute('data-nb-contrats'));
                    bText = parseInt(b.getAttribute('data-nb-contrats'));
                } else if (columnIndex === 6) {
                    // Colonne Date création
                    aText = a.getAttribute('data-date-creation');
                    bText = b.getAttribute('data-date-creation');
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
        function loadEditData(partenaireId) {
            fetch(`../models/get/get-partenaire.php?id=${partenaireId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const partenaire = data.partenaire;
                        document.getElementById('editId').value = partenaireId;
                        
                        let html = `
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nom" value="${partenaire.nom}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type" required>
                                        <option value="Entreprise" ${partenaire.type == 'Entreprise' ? 'selected' : ''}>Entreprise</option>
                                        <option value="Particulier" ${partenaire.type == 'Particulier' ? 'selected' : ''}>Particulier</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Secteur d'activité</label>
                                    <input type="text" class="form-control" name="secteur_activite" value="${partenaire.secteur_activite || ''}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" name="telephone" value="${partenaire.telephone || ''}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="${partenaire.email || ''}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Adresse</label>
                                    <textarea class="form-control" name="adresse" rows="2">${partenaire.adresse || ''}</textarea>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('editModalBody').innerHTML = html;
                    } else {
                        alert('Erreur lors du chargement des données du partenaire');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données');
                });
        }
        
        // Voir les contrats d'un partenaire
        function showContrats(partenaireId, partenaireNom) {
            // Mettre à jour le titre
            document.getElementById('contratsPartenaireTitle').textContent = `Contrats du partenaire : ${partenaireNom}`;
            
            // Afficher un message de chargement
            document.getElementById('contratsList').innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 text-muted">Chargement des contrats...</p>
                    </td>
                </tr>
            `;
            document.getElementById('noContratsMessage').style.display = 'none';
            
            // Charger les contrats via AJAX
            fetch(`../models/get/contrats-by-partenaire.php?partenaire_id=${partenaireId}`)
                .then(response => response.json())
                .then(data => {
                    const contratsList = document.getElementById('contratsList');
                    const noContratsMessage = document.getElementById('noContratsMessage');
                    
                    if (data.success && data.contrats.length > 0) {
                        let contratsHtml = '';
                        data.contrats.forEach((contrat, index) => {
                            const dateDebut = new Date(contrat.date_debut).toLocaleDateString('fr-FR');
                            const montant = parseFloat(contrat.montant).toLocaleString('fr-FR', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            const etatClass = 'badge-' + contrat.etat.toLowerCase().replace(' ', '-');
                            
                            contratsHtml += `
                                <tr>
                                    <td>
                                        <strong class="text-primary">${contrat.reference}</strong>
                                    </td>
                                    <td>${contrat.type_contrat}</td>
                                    <td>
                                        <span class="badge ${etatClass}">${contrat.etat}</span>
                                    </td>
                                    <td class="fw-bold">$${montant}</td>
                                    <td>${dateDebut}</td>
                                    <td>
                                        <a href="contrats-com.php?view=${contrat.contrat_id}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                        contratsList.innerHTML = contratsHtml;
                        noContratsMessage.style.display = 'none';
                    } else {
                        contratsList.innerHTML = '';
                        noContratsMessage.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('contratsList').innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Erreur lors du chargement des contrats
                            </td>
                        </tr>
                    `;
                });
        }
        
        // Préparer la suppression
        function setDeleteId(id, nom) {
            document.getElementById('deletePartenaireName').textContent = nom;
            
            // Vérifier s'il y a des contrats
            const row = document.querySelector(`.table-row[data-nom="${nom.toLowerCase().replace(/"/g, '\\"')}"]`);
            const nbContrats = row ? parseInt(row.getAttribute('data-nb-contrats')) : 0;
            
            const warning = document.getElementById('hasContratsWarning');
            if (nbContrats > 0) {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
            
            // Mettre à jour le lien de suppression
            const deleteUrl = `../models/delete/delete-partenaire.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Validation du formulaire d'ajout
        document.getElementById('addForm')?.addEventListener('submit', function(e) {
            const nom = this.querySelector('[name="nom"]').value.trim();
            
            if (!nom) {
                e.preventDefault();
                alert('Le nom du partenaire est requis');
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