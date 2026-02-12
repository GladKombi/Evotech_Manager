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

// Permissions pour cette page (départements)
$canAccessDepartements = hasPermission(['DG', 'ChefDepartement'], $userRoles);
if (!$canAccessDepartements) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveDepartements = 1;

# Récupérer les départements avec leurs responsables
try {
    $query = "SELECT 
                d.departement_id,
                d.code,
                d.nom,
                d.description,
                d.date_creation,
                u.utilisateur_id as responsable_id,
                CONCAT(u.prenom, ' ', u.nom) as responsable_nom,
                u.email as responsable_email,
                COUNT(a.agent_id) as nombre_agents
              FROM DEPARTEMENTS d
              LEFT JOIN UTILISATEURS u ON d.responsable_id = u.utilisateur_id AND u.statut = 0
              LEFT JOIN AGENTS a ON d.departement_id = a.departement_id AND a.statut = 0
              WHERE d.statut = 0
              GROUP BY d.departement_id
              ORDER BY d.date_creation DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $departements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    # Compter les statistiques
    $totalDepartements = count($departements);
    $departementsAvecResponsable = 0;
    $departementsAvecAgents = 0;
    $totalAgentsDepartements = 0;

    foreach ($departements as $departement) {
        if (!empty($departement['responsable_id'])) {
            $departementsAvecResponsable++;
        }
        if ($departement['nombre_agents'] > 0) {
            $departementsAvecAgents++;
            $totalAgentsDepartements += $departement['nombre_agents'];
        }
    }

    # Récupérer tous les utilisateurs pour les responsables
    $queryUsers = "SELECT utilisateur_id, nom, prenom, email 
                   FROM UTILISATEURS 
                   WHERE statut = 0 AND actif = 1 
                   ORDER BY nom, prenom";
    $stmtUsers = $conn->prepare($queryUsers);
    $stmtUsers->execute();
    $allUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    # Départements par défaut (système)
    $systemDepartements = ['Informatique', 'Architecture', 'Communication'];

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des départements: " . $e->getMessage());
    $departements = [];
    $allUsers = [];
    $totalDepartements = $departementsAvecResponsable = $departementsAvecAgents = $totalAgentsDepartements = 0;
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
    <title>Gestion des Départements - Evotech Africa</title>
    
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
        }

        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }
        }

        /* Sidebar Styles */
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

        .departement-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            height: 100%;
            background: white;
            padding: 1.5rem;
        }

        .departement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-blue-light);
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

        /* Styles spécifiques aux départements */
        .departement-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
        }

        .badge-departement {
            background-color: var(--primary-blue);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .code-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: monospace;
            font-weight: 600;
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

        .description-text {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .agents-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .agent-item {
            padding: 6px 10px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s;
        }

        .agent-item:hover {
            background-color: #f8f9fa;
        }

        .agent-item:last-child {
            border-bottom: none;
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
                            <li class="sidebar-item active">
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
                <i class="bi bi-building me-2"></i>
                Gestion des Départements
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un département..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartementModal">
                        <i class="bi bi-plus-circle me-1"></i> Nouveau Département
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Départements</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $totalDepartements ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="bi bi-building"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Avec Responsable</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $departementsAvecResponsable ?>
                                </h2>
                                <small class="text-muted">
                                    <?= $totalDepartements > 0 ? round(($departementsAvecResponsable / $totalDepartements) * 100, 1) : 0 ?>%
                                </small>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="bi bi-person-badge"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Agents</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $totalAgentsDepartements ?>
                                </h2>
                                <small class="text-muted">
                                    <?= $totalDepartements > 0 ? round(($totalAgentsDepartements / $totalDepartements), 1) : 0 ?> par dép.
                                </small>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="bi bi-people"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Résultats trouvés</h6>
                                <h2 class="card-title text-info mb-0" id="filteredCount">
                                    <?= $totalDepartements ?>
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="bi bi-filter-circle"></i>
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

        <!-- Vue cartes -->
        <div class="card border-0 shadow mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-grid me-2"></i>
                    Vue cartes des départements
                    <small class="text-white-50 ms-2">(<span id="cardsVisibleCount"><?= $totalDepartements ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="cardsNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun département ne correspond à votre recherche</p>
                </div>

                <!-- Cartes -->
                <div id="cardsContainer">
                    <div class="row" id="departementsCards">
                        <?php foreach ($departements as $departement):
                            $hasResponsable = !empty($departement['responsable_id']);
                            $dateCreation = !empty($departement['date_creation']) ? 
                                date('d/m/Y', strtotime($departement['date_creation'])) : 'Non définie';
                            $nombreAgents = $departement['nombre_agents'] ?? 0;
                            $isSystem = in_array($departement['nom'], $systemDepartements);
                        ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4 departement-card-item"
                                data-nom="<?= htmlspecialchars(strtolower($departement['nom'])) ?>"
                                data-code="<?= htmlspecialchars(strtolower($departement['code'])) ?>"
                                data-responsable="<?= $hasResponsable ? htmlspecialchars(strtolower($departement['responsable_nom'])) : '' ?>"
                                data-agents="<?= $nombreAgents ?>">
                                <div class="departement-card h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <?php if ($isSystem): ?>
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-shield-check me-1"></i>Système
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            onclick="loadEditData(<?= $departement['departement_id'] ?>)"
                                                            data-bs-toggle="modal" data-bs-target="#editDepartementModal">
                                                            <i class="bi bi-pencil me-2"></i>Modifier
                                                        </a>
                                                    </li>
                                                    <?php if (!$isSystem && $nombreAgents == 0): ?>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#"
                                                            onclick="setDeleteId(<?= $departement['departement_id'] ?>, '<?= htmlspecialchars(addslashes($departement['nom'])) ?>')"
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                            <i class="bi bi-trash me-2"></i>Supprimer
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            onclick="showDepartementDetails(<?= $departement['departement_id'] ?>)"
                                                            data-bs-toggle="modal" data-bs-target="#viewDepartementModal">
                                                            <i class="bi bi-eye me-2"></i>Voir détails
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            onclick="showAgentsDepartement(<?= $departement['departement_id'] ?>, '<?= htmlspecialchars(addslashes($departement['nom'])) ?>')"
                                                            data-bs-toggle="modal" data-bs-target="#agentsModal">
                                                            <i class="bi bi-people me-2"></i>Voir les agents
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-center mb-3">
                                        <div class="departement-icon mx-auto">
                                            <i class="bi bi-building"></i>
                                        </div>
                                    </div>

                                    <h5 class="text-center mb-1 text-primary">
                                        <?= htmlspecialchars($departement['nom']) ?>
                                    </h5>

                                    <p class="text-center mb-3">
                                        <span class="code-badge"><?= htmlspecialchars($departement['code']) ?></span>
                                    </p>

                                    <?php if ($hasResponsable): ?>
                                        <div class="text-center mb-3">
                                            <span class="badge bg-success">
                                                <i class="bi bi-person-badge me-1"></i>
                                                <?= htmlspecialchars($departement['responsable_nom']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($departement['description'])): ?>
                                        <p class="text-muted small description-text mb-3">
                                            <?= htmlspecialchars($departement['description']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="bg-light rounded p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-people text-primary me-1"></i>
                                                <span class="small">Agents: <?= $nombreAgents ?></span>
                                            </div>
                                            <div>
                                                <i class="bi bi-calendar text-primary me-1"></i>
                                                <span class="small">Créé: <?= $dateCreation ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                        <small class="text-muted">
                                            ID: #<?= $departement['departement_id'] ?>
                                        </small>
                                        <small class="text-muted">
                                            <?= $hasResponsable ? 'Avec resp.' : 'Sans resp.' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table des départements -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-task me-2"></i>
                    Tableau des départements
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= $totalDepartements ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun département ne correspond à votre recherche</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(1)">
                                        Département <i class="bi bi-arrow-down-up ms-1"></i>
                                    </button>
                                </th>
                                <th>Code</th>
                                <th>Responsable</th>
                                <th>Agents</th>
                                <th>Date Création</th>
                                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                    <th width="140" class="text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="departementsTable">
                            <?php
                            $n = 0;
                            foreach ($departements as $departement):
                                $n++;
                                $hasResponsable = !empty($departement['responsable_id']);
                                $dateCreation = !empty($departement['date_creation']) ?
                                    date('d/m/Y', strtotime($departement['date_creation'])) : 'Non définie';
                                $nombreAgents = $departement['nombre_agents'] ?? 0;
                                $isSystem = in_array($departement['nom'], $systemDepartements);
                            ?>
                                <tr class="table-row"
                                    data-nom="<?= htmlspecialchars(strtolower($departement['nom'])) ?>"
                                    data-code="<?= htmlspecialchars(strtolower($departement['code'])) ?>"
                                    data-responsable="<?= $hasResponsable ? htmlspecialchars(strtolower($departement['responsable_nom'])) : '' ?>"
                                    data-agents="<?= $nombreAgents ?>">
                                    <td class="text-center"><?= $n ?></td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($departement['nom']) ?></strong>
                                        <?php if ($isSystem): ?>
                                            <span class="badge bg-primary ms-2">Système</span>
                                        <?php endif; ?>
                                        <?php if (!empty($departement['description'])): ?>
                                            <br>
                                            <small class="text-muted"><?= substr(htmlspecialchars($departement['description']), 0, 50) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="code-badge"><?= htmlspecialchars($departement['code']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($hasResponsable): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-person-badge me-1"></i>
                                                <?= htmlspecialchars($departement['responsable_nom']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Non attribué</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-departement">
                                            <i class="bi bi-people me-1"></i>
                                            <?= $nombreAgents ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= $dateCreation ?></small>
                                    </td>
                                    <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button"
                                                    class="btn btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editDepartementModal"
                                                    onclick="loadEditData(<?= $departement['departement_id'] ?>)"
                                                    title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewDepartementModal"
                                                    onclick="showDepartementDetails(<?= $departement['departement_id'] ?>)"
                                                    title="Voir détails">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-warning"
                                                    onclick="suspendDepartement(<?= $departement['departement_id'] ?>, '<?= htmlspecialchars(addslashes($departement['nom'])) ?>')"
                                                    title="Suspendre">
                                                    <i class="bi bi-pause-circle"></i>
                                                </button>
                                                <?php if (!$isSystem && $nombreAgents == 0): ?>
                                                <button type="button"
                                                    class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    onclick="setDeleteId(<?= $departement['departement_id'] ?>, '<?= htmlspecialchars(addslashes($departement['nom'])) ?>')"
                                                    title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
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
        <!-- Modal Ajout Département -->
        <div class="modal fade" id="addDepartementModal" tabindex="-1" aria-labelledby="addDepartementModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addDepartementModalLabel">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nouveau Département
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-departement-post.php" method="POST" id="addDepartementForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="addNom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="addNom" name="nom" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="addCode" class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="addCode" name="code" required maxlength="10">
                                    <small class="text-muted">Format court (ex: Dev., Arch., Com)</small>
                                </div>
                                
                                <div class="col-12">
                                    <label for="addDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="addDescription" name="description" rows="3"></textarea>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="addResponsable" class="form-label">Responsable</label>
                                    <select class="form-select" id="addResponsable" name="responsable_id">
                                        <option value="">Sélectionner un responsable</option>
                                        <?php foreach ($allUsers as $user): ?>
                                            <option value="<?= $user['utilisateur_id'] ?>">
                                                <?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?> 
                                                (<?= htmlspecialchars($user['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Laissez vide si aucun responsable pour l'instant</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Modification Département -->
        <div class="modal fade" id="editDepartementModal" tabindex="-1" aria-labelledby="editDepartementModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editDepartementModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Modifier le Département
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/update/update-departement.php" method="POST" id="editDepartementForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="editDepartementId" name="departement_id">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="editNom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editNom" name="nom" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="editCode" class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editCode" name="code" required maxlength="10">
                                </div>
                                
                                <div class="col-12">
                                    <label for="editDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="editResponsable" class="form-label">Responsable</label>
                                    <select class="form-select" id="editResponsable" name="responsable_id">
                                        <option value="">Sélectionner un responsable</option>
                                        <?php foreach ($allUsers as $user): ?>
                                            <option value="<?= $user['utilisateur_id'] ?>">
                                                <?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?> 
                                                (<?= htmlspecialchars($user['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Mettre à jour
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
                        <p>Êtes-vous sûr de vouloir supprimer le département :</p>
                        <h5 class="text-danger" id="deleteDepartementName"></h5>
                        <p class="text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette action est irréversible. Le département sera marqué comme supprimé dans le système.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <a id="deleteConfirmBtn" href="#" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Supprimer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal Suspendre Département -->
    <div class="modal fade" id="suspendModal" tabindex="-1" aria-labelledby="suspendModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="suspendModalLabel">
                        <i class="bi bi-pause-circle me-2"></i>
                        Confirmation de suspension
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir suspendre le département :</p>
                    <h5 class="text-warning" id="suspendDepartementName"></h5>
                    <p class="text-muted mt-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Ce département sera rendu inactif. Les agents et données resteront intacts mais il ne sera plus accessible.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-warning" id="suspendConfirmBtn">
                        <i class="bi bi-pause-circle me-1"></i> Suspendre
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Voir Détails Département -->
    <div class="modal fade" id="viewDepartementModal" tabindex="-1" aria-labelledby="viewDepartementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="viewDepartementModalLabel">
                        <i class="bi bi-eye me-2"></i>
                        Détails du Département
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="departement-icon mx-auto mb-3">
                                <i class="bi bi-building"></i>
                            </div>
                            <h4 id="viewNom" class="mb-1"></h4>
                            <p class="text-muted" id="viewCode"></p>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">ID Département</label>
                                    <p id="viewDepartementId" class="form-control-static"></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Nombre d'Agents</label>
                                    <p id="viewNombreAgents"></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Responsable</label>
                                    <p id="viewResponsable"></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Date de création</label>
                                    <p id="viewDateCreation"></p>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Description</label>
                                    <p id="viewDescription" class="form-control-static"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agents du Département -->
    <div class="modal fade" id="agentsModal" tabindex="-1" aria-labelledby="agentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="agentsModalLabel">
                        <i class="bi bi-people me-2"></i>
                        Agents du Département
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6 id="agentsDepartementTitle" class="text-primary"></h6>
                    </div>
                    
                    <div class="agents-list" id="agentsList">
                        <!-- Les agents seront chargés ici dynamiquement -->
                    </div>
                    
                    <div class="text-center py-3" id="noAgentsMessage" style="display: none;">
                        <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                        <h6 class="mt-3 text-muted">Aucun agent dans ce département</h6>
                        <p class="text-muted small">Ce département n'a actuellement aucun agent attribué.</p>
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
        });

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterDepartements(this.value);
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterDepartements('');
            });
        }
        
        function filterDepartements(searchTerm) {
            const searchLower = searchTerm.toLowerCase();
            const departementCards = document.querySelectorAll('.departement-card-item');
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const cardsNoResults = document.getElementById('cardsNoResults');
            const tableNoResults = document.getElementById('tableNoResults');
            
            let visibleCards = 0;
            let visibleRows = 0;
            
            // Filtrer les cartes
            departementCards.forEach(card => {
                const nom = card.getAttribute('data-nom');
                const code = card.getAttribute('data-code');
                const responsable = card.getAttribute('data-responsable');
                
                const matches = searchTerm === '' || 
                    nom.includes(searchLower) || 
                    code.includes(searchLower) || 
                    responsable.includes(searchLower);
                
                card.style.display = matches ? 'block' : 'none';
                if (matches) visibleCards++;
            });
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const nom = row.getAttribute('data-nom');
                const code = row.getAttribute('data-code');
                const responsable = row.getAttribute('data-responsable');
                
                const matches = searchTerm === '' || 
                    nom.includes(searchLower) || 
                    code.includes(searchLower) || 
                    responsable.includes(searchLower);
                
                row.style.display = matches ? '' : 'none';
                if (matches) visibleRows++;
            });
            
            // Mettre à jour les compteurs
            document.getElementById('cardsVisibleCount').textContent = visibleCards;
            document.getElementById('tableVisibleCount').textContent = visibleRows;
            document.getElementById('filteredCount').textContent = visibleCards;
            
            // Afficher/cacher les messages "aucun résultat"
            if (searchTerm !== '') {
                filterInfo.style.display = 'block';
                filterText.textContent = `Recherche: "${searchTerm}" - ${visibleCards} résultat(s) trouvé(s)`;
                
                if (visibleCards === 0) {
                    cardsNoResults.style.display = 'block';
                    document.getElementById('cardsContainer').style.display = 'none';
                } else {
                    cardsNoResults.style.display = 'none';
                    document.getElementById('cardsContainer').style.display = 'block';
                }
                
                if (visibleRows === 0) {
                    tableNoResults.style.display = 'block';
                    document.getElementById('tableContainer').style.display = 'none';
                } else {
                    tableNoResults.style.display = 'none';
                    document.getElementById('tableContainer').style.display = 'block';
                }
            } else {
                filterInfo.style.display = 'none';
                cardsNoResults.style.display = 'none';
                tableNoResults.style.display = 'none';
                document.getElementById('cardsContainer').style.display = 'block';
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
        
        function sortTable(columnIndex) {
            const table = document.getElementById('departementsTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                let aText, bText;
                
                if (columnIndex === 1) {
                    // Colonne Département
                    aText = a.querySelector('strong').textContent.toLowerCase();
                    bText = b.querySelector('strong').textContent.toLowerCase();
                } else if (columnIndex === 2) {
                    // Colonne Code
                    aText = a.querySelector('.code-badge').textContent.toLowerCase();
                    bText = b.querySelector('.code-badge').textContent.toLowerCase();
                } else if (columnIndex === 3) {
                    // Colonne Responsable
                    const aBadge = a.querySelector('.badge');
                    const bBadge = b.querySelector('.badge');
                    aText = aBadge ? aBadge.textContent.toLowerCase() : 'non attribué';
                    bText = bBadge ? bBadge.textContent.toLowerCase() : 'non attribué';
                } else if (columnIndex === 4) {
                    // Colonne Agents (numérique)
                    aText = parseInt(a.querySelector('.badge-departement').textContent);
                    bText = parseInt(b.querySelector('.badge-departement').textContent);
                } else {
                    // Autres colonnes
                    aText = a.cells[columnIndex].textContent.toLowerCase();
                    bText = b.cells[columnIndex].textContent.toLowerCase();
                }
                
                if (aText < bText) return -1 * sortDirection;
                if (aText > bText) return 1 * sortDirection;
                return 0;
            });
            
            // Inverser la direction pour le prochain tri
            sortDirection *= -1;
            
            // Réinsérer les lignes triées
            rows.forEach(row => table.appendChild(row));
            
            // Re-numéroter les lignes
            renumberVisibleRows();
        }
        
        // Charger les données pour modification
        function loadEditData(departementId) {
            fetch(`../models/fetch/fetch-departement.php?id=${departementId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const departement = data.departement;
                        
                        document.getElementById('editDepartementId').value = departement.departement_id;
                        document.getElementById('editNom').value = departement.nom;
                        document.getElementById('editCode').value = departement.code;
                        document.getElementById('editDescription').value = departement.description || '';
                        document.getElementById('editResponsable').value = departement.responsable_id || '';
                    } else {
                        alert('Erreur: ' + (data.message || 'Données non disponibles'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données: ' + error.message);
                });
        }
        
        // Voir les détails d'un département
        function showDepartementDetails(departementId) {
            fetch(`../models/fetch/fetch-departement.php?id=${departementId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const departement = data.departement;
                        
                        document.getElementById('viewNom').textContent = departement.nom;
                        document.getElementById('viewCode').textContent = `Code: ${departement.code}`;
                        document.getElementById('viewDepartementId').textContent = `#${departement.departement_id}`;
                        document.getElementById('viewNombreAgents').textContent = departement.nombre_agents || 0;
                        
                        if (departement.responsable_nom) {
                            document.getElementById('viewResponsable').innerHTML = 
                                `<span class="badge bg-success">${departement.responsable_nom}</span>`;
                        } else {
                            document.getElementById('viewResponsable').innerHTML = 
                                '<span class="text-muted">Non attribué</span>';
                        }
                        
                        document.getElementById('viewDateCreation').textContent = 
                            new Date(departement.date_creation).toLocaleDateString('fr-FR');
                        
                        document.getElementById('viewDescription').textContent = 
                            departement.description || 'Aucune description';
                    } else {
                        alert('Erreur: ' + (data.message || 'Données non disponibles'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des détails: ' + error.message);
                });
        }
        
        // Voir les agents d'un département
        function showAgentsDepartement(departementId, departementNom) {
            // Mettre à jour le titre
            document.getElementById('agentsDepartementTitle').textContent = 
                `Agents du département : ${departementNom}`;
            
            // Afficher un message de chargement
            document.getElementById('agentsList').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-muted">Chargement des agents...</p>
                </div>
            `;
            document.getElementById('noAgentsMessage').style.display = 'none';
            
            // Charger les agents via AJAX
            fetch(`../models/get/agents-by-departement.php?departement_id=${departementId}`)
                .then(response => response.json())
                .then(data => {
                    const agentsList = document.getElementById('agentsList');
                    const noAgentsMessage = document.getElementById('noAgentsMessage');
                    
                    if (data.success && data.agents.length > 0) {
                        let agentsHtml = '';
                        data.agents.forEach((agent, index) => {
                            agentsHtml += `
                                <div class="agent-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${agent.nom} ${agent.prenom}</strong>
                                            <div class="text-muted small">${agent.email_professionnel || agent.email_personnel || 'Pas d\'email'}</div>
                                        </div>
                                        <div>
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-telephone me-1"></i>
                                                ${agent.telephone_professionnel || agent.telephone_personnel || 'N/A'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        agentsList.innerHTML = agentsHtml;
                        noAgentsMessage.style.display = 'none';
                    } else {
                        agentsList.innerHTML = '';
                        noAgentsMessage.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('agentsList').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Erreur lors du chargement des agents
                        </div>
                    `;
                });
        }
        
        // Préparer la suppression
        function setDeleteId(id, departementName) {
            document.getElementById('deleteDepartementName').textContent = departementName;
            
            // Mettre à jour le lien de suppression
            const deleteUrl = `../models/delete/delete-departement.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Variable globale pour stocker l'ID du département à suspendre
        let suspendDepartementId = null;
        
        // Suspendre un département - Ouvrir le modal
        function suspendDepartement(departementId, departementName) {
            suspendDepartementId = departementId;
            document.getElementById('suspendDepartementName').textContent = departementName;
            
            // Ouvrir le modal
            const suspendModal = new bootstrap.Modal(document.getElementById('suspendModal'));
            suspendModal.show();
        }
        
        // Confirmation de la suspension
        document.getElementById('suspendConfirmBtn').addEventListener('click', function() {
            if (!suspendDepartementId) return;
            
            const formData = new FormData();
            formData.append('departement_id', suspendDepartementId);
            
            // Fermer le modal
            const suspendModal = bootstrap.Modal.getInstance(document.getElementById('suspendModal'));
            suspendModal.hide();
            
            fetch('../models/delete/suspend-departement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Erreur lors de la suspension');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Afficher un message de succès
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Succès!</strong> Le département a été suspendu avec succès.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    // Insérer l'alerte en haut de la page
                    const container = document.querySelector('.container-fluid');
                    if (container) {
                        container.insertBefore(alertDiv, container.firstChild);
                    }
                    
                    // Recharger après 1 seconde
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Erreur: ' + (data.message || 'Impossible de suspendre le département'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur: ' + error.message);
            });
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