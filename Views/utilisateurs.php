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

// Permissions pour cette page (utilisateurs)
$canAccessUsers = hasPermission(['DG', 'ChefDepartement'], $userRoles);
if (!$canAccessUsers) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveUsers = 1;

# Récupérer les utilisateurs avec leurs rôles
try {
    $query = "SELECT 
                u.utilisateur_id,
                u.nom,
                u.prenom,
                u.email,
                u.profil,
                u.actif,
                u.date_creation,
                u.deleted_at,
                GROUP_CONCAT(r.fonction ORDER BY r.fonction SEPARATOR ', ') as roles,
                GROUP_CONCAT(r.role_id ORDER BY r.role_id SEPARATOR ',') as role_ids
              FROM UTILISATEURS u
              LEFT JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id AND a.statut = 0
              LEFT JOIN ROLES r ON a.role_id = r.role_id AND r.statut = 0
              WHERE u.statut = 0
              GROUP BY u.utilisateur_id
              ORDER BY u.date_creation DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    # Compter les statistiques
    $totalUtilisateurs = count($utilisateurs);
    $activeUsers = 0;
    $inactiveUsers = 0;
    $usersWithRoles = 0;

    foreach ($utilisateurs as $user) {
        if ($user['actif']) {
            $activeUsers++;
        } else {
            $inactiveUsers++;
        }

        if (!empty($user['roles'])) {
            $usersWithRoles++;
        }
    }

    # Récupérer tous les rôles disponibles pour le formulaire
    $queryRoles = "SELECT role_id, fonction 
                   FROM ROLES 
                   WHERE statut = 0 
                   ORDER BY fonction";
    $stmtRoles = $conn->prepare($queryRoles);
    $stmtRoles->execute();
    $allRoles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

    # Récupérer tous les départements pour les filtres
    $queryDepartements = "SELECT departement_id, code, nom 
                         FROM DEPARTEMENTS 
                         WHERE statut = 0 
                         ORDER BY nom";
    $stmtDepartements = $conn->prepare($queryDepartements);
    $stmtDepartements->execute();
    $allDepartements = $stmtDepartements->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
    $utilisateurs = [];
    $allRoles = [];
    $allDepartements = [];
    $totalUtilisateurs = $activeUsers = $inactiveUsers = $usersWithRoles = 0;
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
    <title>Gestion des Utilisateurs - Evotech Africa</title>

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

        /* Sidebar Styles - Utiliser le même CSS que agents.php */
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

        .profile-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border: 2px solid var(--primary-blue);
        }
        
        .profile-img.error {
            display: none;
        }

        .user-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            height: 100%;
            background: white;
            padding: 1.5rem;
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-blue-light);
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .role-badge {
            font-size: 0.75em;
            margin-right: 3px;
            margin-bottom: 3px;
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

        /* Filter info */
        .filter-info {
            background-color: #e8f4fd;
            border: 1px solid #b6d4fe;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 20px;
            display: none;
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
                            <li class="sidebar-item active">
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
                <i class="bi bi-people-fill me-2"></i>
                Gestion des Utilisateurs
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un utilisateur..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus-circle me-1"></i> Nouvel Utilisateur
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Utilisateurs</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $totalUtilisateurs ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="bi bi-people-fill"></i>
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
                                    <?= $activeUsers ?>
                                </h2>
                                <small class="text-muted">
                                    <?= $totalUtilisateurs > 0 ? round(($activeUsers / $totalUtilisateurs) * 100, 1) : 0 ?>%
                                </small>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="bi bi-person-check-fill"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Avec Rôles</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $usersWithRoles ?>
                                </h2>
                                <small class="text-muted">
                                    <?= $totalUtilisateurs > 0 ? round(($usersWithRoles / $totalUtilisateurs) * 100, 1) : 0 ?>%
                                </small>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="bi bi-shield-check"></i>
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
                                    <?= $totalUtilisateurs ?>
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
                    Vue cartes des utilisateurs
                    <small class="text-white-50 ms-2">(<span id="cardsVisibleCount"><?= $totalUtilisateurs ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="cardsNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun utilisateur ne correspond à votre recherche</p>
                </div>

                <!-- Cartes -->
                <div id="cardsContainer">
                    <div class="row" id="usersCards">
                        <?php foreach ($utilisateurs as $user):
                            $hasRoles = !empty($user['roles']);
                            $dateCreation = !empty($user['date_creation']) ? 
                                date('d/m/Y', strtotime($user['date_creation'])) : 'Non définie';
                            $isActive = $user['actif'];
                            $roleArray = $hasRoles ? explode(', ', $user['roles']) : [];
                        ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4 user-card-item"
                                data-nom="<?= htmlspecialchars(strtolower($user['nom'] . ' ' . $user['prenom'])) ?>"
                                data-email="<?= htmlspecialchars(strtolower($user['email'] ?? '')) ?>"
                                data-roles="<?= $hasRoles ? htmlspecialchars(strtolower(implode(' ', $roleArray))) : '' ?>"
                                data-statut="<?= $isActive ? 'actif' : 'inactif' ?>">
                                <div class="user-card h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>Actif
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-x-circle me-1"></i>Inactif
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
                                                            onclick="loadEditData(<?= $user['utilisateur_id'] ?>)"
                                                            data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                            <i class="bi bi-pencil me-2"></i>Modifier
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#"
                                                            onclick="setDeleteId(<?= $user['utilisateur_id'] ?>, '<?= htmlspecialchars(addslashes($user['nom'] . ' ' . $user['prenom'])) ?>')"
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                            <i class="bi bi-trash me-2"></i>Supprimer
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            onclick="viewUserDetails(<?= $user['utilisateur_id'] ?>)"
                                                            data-bs-toggle="modal" data-bs-target="#viewUserModal">
                                                            <i class="bi bi-eye me-2"></i>Voir détails
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-center mb-3">
                                        <?php if (!empty($user['profil'])): ?>
                                            <img src="../images/profil/utilisateurs/<?= htmlspecialchars($user['profil']) ?>"
                                                alt="Photo"
                                                class="rounded-circle profile-img">
                                        <?php else: ?>
                                            <div class="user-icon mx-auto bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 60px; height: 60px;">
                                                <i class="bi bi-person-circle text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <h5 class="text-center mb-1 text-primary">
                                        <?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>
                                    </h5>

                                    <p class="text-center text-muted small mb-3">
                                        <?= !empty($user['email']) ? htmlspecialchars($user['email']) : 'Aucun email' ?>
                                    </p>

                                    <!-- <?php if ($hasRoles): ?>
                                        <div class="mb-3">
                                            <div class="text-center">
                                                <?php foreach ($roleArray as $role): ?>
                                                    <span class="badge bg-info role-badge">
                                                        <?= htmlspecialchars($role) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?> -->

                                    <div class="bg-light rounded p-3">
                                        <!-- <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-calendar text-primary me-2"></i>
                                            <span class="small">Créé le: <?= $dateCreation ?></span>
                                        </div> -->
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person text-primary me-2"></i>
                                            <span class="small">ID: #<?= $user['utilisateur_id'] ?></span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                        <small class="text-muted">
                                            <?= $hasRoles ? count($roleArray) . ' rôle(s)' : 'Aucun rôle' ?>
                                        </small>
                                        <small class="text-muted">
                                            <?= $isActive ? 
                                                '<i class="bi bi-check-circle text-success"></i> Connecté' : 
                                                '<i class="bi bi-x-circle text-danger"></i> Déconnecté' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table des utilisateurs -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-task me-2"></i>
                    Tableau des utilisateurs
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= $totalUtilisateurs ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun utilisateur ne correspond à votre recherche</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(1)">
                                        Utilisateur <i class="bi bi-arrow-down-up ms-1"></i>
                                    </button>
                                </th>
                                <th width="80">Photo</th>
                                <th>Email</th>
                                <th>Statut</th>
                                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                    <th width="140" class="text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="usersTable">
                            <?php
                            $n = 0;
                            foreach ($utilisateurs as $user):
                                $n++;
                                $hasRoles = !empty($user['roles']);
                                $dateCreation = !empty($user['date_creation']) ?
                                    date('d/m/Y H:i', strtotime($user['date_creation'])) : 'Non définie';
                                $isActive = $user['actif'];
                                $roleArray = $hasRoles ? explode(', ', $user['roles']) : [];
                            ?>
                                <tr class="table-row"
                                    data-nom="<?= htmlspecialchars(strtolower($user['nom'] . ' ' . $user['prenom'])) ?>"
                                    data-email="<?= htmlspecialchars(strtolower($user['email'] ?? '')) ?>"
                                    data-roles="<?= $hasRoles ? htmlspecialchars(strtolower(implode(' ', $roleArray))) : '' ?>"
                                    data-statut="<?= $isActive ? 'actif' : 'inactif' ?>">
                                    <td class="text-center"><?= $n ?></td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($user['profil'])): ?>
                                            <img src="../images/profil/utilisateurs/<?= htmlspecialchars($user['profil']) ?>"
                                                alt="Photo"
                                                class="rounded-circle profile-img">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center"
                                                style="width: 45px; height: 45px; color: white;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="small">
                                            <?= !empty($user['email']) ? htmlspecialchars($user['email']) : '<span class="text-muted">Non défini</span>' ?>
                                        </span>
                                    </td>                                    
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="status-badge status-active">
                                                <i class="bi bi-check-circle me-1"></i>Actif
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <i class="bi bi-x-circle me-1"></i>Inactif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button"
                                                    class="btn btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal"
                                                    onclick="loadEditData(<?= $user['utilisateur_id'] ?>)"
                                                    title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewUserModal"
                                                    onclick="viewUserDetails(<?= $user['utilisateur_id'] ?>)"
                                                    title="Voir détails">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    onclick="setDeleteId(<?= $user['utilisateur_id'] ?>, '<?= htmlspecialchars(addslashes($user['nom'] . ' ' . $user['prenom'])) ?>')"
                                                    title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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
        <!-- Modal Ajout Utilisateur -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addUserModalLabel">
                            <i class="bi bi-person-plus me-2"></i>
                            Ajouter un nouvel utilisateur
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addUserForm" action="../models/insert/add-user.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="addNom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="addNom" name="nom" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="addPrenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="addPrenom" name="prenom" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="addEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="addEmail" name="email">
                                    <small class="text-muted">Si vide, un email sera généré automatiquement</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="addPassword" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="addPassword" name="mot_de_passe" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleAddPassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimum 8 caractères</small>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="addProfil" class="form-label">Photo de profil</label>
                                    <div class="mb-2" id="addProfilPreview" style="display: none;">
                                        <img id="addProfilImg" src="" alt="Aperçu photo" 
                                            class="rounded-circle" 
                                            style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #0d6efd;">
                                    </div>
                                    <input type="file" class="form-control" id="addProfil" name="profil" accept="image/*">
                                    <small class="text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Rôles <span class="text-danger">*</span></label>
                                    <div class="roles-checkbox">
                                        <?php foreach ($allRoles as $role): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                    id="role_<?= $role['role_id'] ?>" 
                                                    name="roles[]" 
                                                    value="<?= $role['role_id'] ?>">
                                                <label class="form-check-label" for="role_<?= $role['role_id'] ?>">
                                                    <?= htmlspecialchars($role['fonction']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="addActif" name="actif" checked>
                                        <label class="form-check-label" for="addActif">
                                            Compte actif
                                        </label>
                                    </div>
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

        <!-- Modal Modification Utilisateur -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editUserModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Modifier l'utilisateur
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editUserForm" action="../models/update/update-user.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" id="editUserId" name="utilisateur_id">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="editNom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editNom" name="nom" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="editPrenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editPrenom" name="prenom" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="editEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="editEmail" name="email">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="editPassword" class="form-label">Nouveau mot de passe</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="editPassword" name="mot_de_passe">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Laissez vide pour ne pas changer</small>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="editProfil" class="form-label">Photo de profil</label>
                                    <div class="mb-3 p-3 bg-light rounded border">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-auto text-center">
                                                <small class="d-block text-muted mb-2">Photo actuelle</small>
                                                <img id="currentProfil" src="" alt="Photo actuelle" 
                                                    class="rounded-circle profile-img" 
                                                    style="width: 70px; height: 70px;">
                                            </div>
                                            <div class="col-auto" id="previewArrowContainer" style="display: none;">
                                                <i class="bi bi-arrow-right text-primary" style="font-size: 1.5rem;"></i>
                                            </div>
                                            <div class="col-auto text-center" id="newProfilPreviewContainer" style="display: none;">
                                                <small class="d-block text-muted mb-2">Nouvelle photo</small>
                                                <img id="editProfilPreview" src="" alt="Aperçu nouvelle photo" 
                                                    class="rounded-circle" 
                                                    style="width: 70px; height: 70px; object-fit: cover; border: 3px solid #0d6efd;">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="file" class="form-control" id="editProfil" name="profil" accept="image/*">
                                    <small class="text-muted">Laisser vide pour conserver la photo actuelle</small>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Rôles <span class="text-danger">*</span></label>
                                    <div class="roles-checkbox" id="editRolesContainer">
                                        <?php foreach ($allRoles as $role): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input role-checkbox" type="checkbox" 
                                                    id="edit_role_<?= $role['role_id'] ?>" 
                                                    name="roles[]" 
                                                    value="<?= $role['role_id'] ?>">
                                                <label class="form-check-label" for="edit_role_<?= $role['role_id'] ?>">
                                                    <?= htmlspecialchars($role['fonction']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="editActif" name="actif">
                                        <label class="form-check-label" for="editActif">
                                            Compte actif
                                        </label>
                                    </div>
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
                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="deleteModalLabel">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Confirmer la suppression
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer l'utilisateur :</p>
                        <h5 class="text-danger" id="deleteUserName"></h5>
                        <p class="text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette action est irréversible. L'utilisateur sera marqué comme supprimé dans le système.
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

    <!-- Modal Voir Détails Utilisateur -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="viewUserModalLabel">
                        <i class="bi bi-person-circle me-2"></i>
                        Détails de l'utilisateur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img id="viewProfil" src="" alt="Photo de profil" 
                                class="rounded-circle mb-3" 
                                style="width: 120px; height: 120px; object-fit: cover;">
                            <h4 id="viewNomComplet" class="mb-1"></h4>
                            <p class="text-muted" id="viewEmail"></p>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">ID Utilisateur</label>
                                    <p id="viewUserId" class="form-control-static"></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Statut</label>
                                    <p id="viewStatut"></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Date de création</label>
                                    <p id="viewDateCreation"></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Date de dernière modification</label>
                                    <p id="viewDeletedAt"></p>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Rôles assignés</label>
                                    <div id="viewRoles"></div>
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
            initializePasswordToggles();
        });

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterUsers(this.value);
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterUsers('');
            });
        }
        
        function filterUsers(searchTerm) {
            const searchLower = searchTerm.toLowerCase();
            const userCards = document.querySelectorAll('.user-card-item');
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const cardsNoResults = document.getElementById('cardsNoResults');
            const tableNoResults = document.getElementById('tableNoResults');
            
            let visibleCards = 0;
            let visibleRows = 0;
            
            // Filtrer les cartes
            userCards.forEach(card => {
                const nom = card.getAttribute('data-nom');
                const email = card.getAttribute('data-email');
                const roles = card.getAttribute('data-roles');
                
                const matches = searchTerm === '' || 
                    nom.includes(searchLower) || 
                    email.includes(searchLower) || 
                    roles.includes(searchLower);
                
                card.style.display = matches ? 'block' : 'none';
                if (matches) visibleCards++;
            });
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const nom = row.getAttribute('data-nom');
                const email = row.getAttribute('data-email');
                const roles = row.getAttribute('data-roles');
                
                const matches = searchTerm === '' || 
                    nom.includes(searchLower) || 
                    email.includes(searchLower) || 
                    roles.includes(searchLower);
                
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
        }
        
        // Tri du tableau
        let sortDirection = 1;
        
        function sortTable(columnIndex) {
            const table = document.getElementById('usersTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.toLowerCase();
                const bText = b.cells[columnIndex].textContent.toLowerCase();
                
                if (aText < bText) return -1 * sortDirection;
                if (aText > bText) return 1 * sortDirection;
                return 0;
            });
            
            // Inverser la direction pour le prochain tri
            sortDirection *= -1;
            
            // Réinsérer les lignes triées
            rows.forEach(row => table.appendChild(row));
        }
        
        // Toggle mot de passe
        function initializePasswordToggles() {
            const toggleAddPassword = document.getElementById('toggleAddPassword');
            const toggleEditPassword = document.getElementById('toggleEditPassword');
            
            if (toggleAddPassword) {
                toggleAddPassword.addEventListener('click', function() {
                    const passwordInput = document.getElementById('addPassword');
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
                });
            }
            
            if (toggleEditPassword) {
                toggleEditPassword.addEventListener('click', function() {
                    const passwordInput = document.getElementById('editPassword');
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
                });
            }
        }
        
        // Charger les données pour modification
        function loadEditData(userId) {
            fetch(`../models/get/user-details.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        
                        document.getElementById('editUserId').value = user.utilisateur_id;
                        document.getElementById('editNom').value = user.nom;
                        document.getElementById('editPrenom').value = user.prenom;
                        document.getElementById('editEmail').value = user.email;
                        document.getElementById('editActif').checked = user.actif == 1;
                        
                        // Photo actuelle
                        const profilImg = document.getElementById('currentProfil');
                        if (user.profil) {
                            profilImg.src = `../images/profil/utilisateurs/${user.profil}`;
                            profilImg.style.display = 'block';
                        } else {
                            profilImg.src = '../images/profil/default.png';
                            profilImg.style.display = 'block';
                        }
                        
                        // Rôles
                        document.querySelectorAll('.role-checkbox').forEach(checkbox => {
                            checkbox.checked = false;
                        });
                        
                        if (user.role_ids) {
                            const roleIds = user.role_ids.split(',');
                            roleIds.forEach(roleId => {
                                const checkbox = document.getElementById(`edit_role_${roleId}`);
                                if (checkbox) checkbox.checked = true;
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données');
                });
        }
        
        // Voir les détails
        function viewUserDetails(userId) {
            fetch(`../models/get/user-details.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        
                        document.getElementById('viewNomComplet').textContent = 
                            `${user.nom} ${user.prenom}`;
                        document.getElementById('viewEmail').textContent = user.email || 'Non défini';
                        document.getElementById('viewUserId').textContent = `#${user.utilisateur_id}`;
                        document.getElementById('viewStatut').innerHTML = user.actif == 1 ? 
                            '<span class="badge bg-success">Actif</span>' : 
                            '<span class="badge bg-danger">Inactif</span>';
                        document.getElementById('viewDateCreation').textContent = 
                            new Date(user.date_creation).toLocaleString('fr-FR');
                        document.getElementById('viewDeletedAt').textContent = 
                            user.deleted_at ? new Date(user.deleted_at).toLocaleString('fr-FR') : 'Jamais';
                        
                        // Photo
                        const profilImg = document.getElementById('viewProfil');
                        if (user.profil) {
                            profilImg.src = `../images/profil/utilisateurs/${user.profil}`;
                        } else {
                            profilImg.src = '../images/profil/default.png';
                        }
                        
                        // Rôles
                        const rolesContainer = document.getElementById('viewRoles');
                        if (user.roles) {
                            const roles = user.roles.split(', ');
                            rolesContainer.innerHTML = roles.map(role => 
                                `<span class="badge bg-info me-1">${role}</span>`
                            ).join('');
                        } else {
                            rolesContainer.innerHTML = '<span class="text-muted">Aucun rôle assigné</span>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des détails');
                });
        }
        
        // Préparer la suppression
        function setDeleteId(id, userName) {
            document.getElementById('deleteUserName').textContent = userName;
            
            // Mettre à jour le lien de suppression
            const deleteUrl = `../models/delete/delete-user.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Prévisualisation photo pour l'ajout d'utilisateur
        const addProfilInput = document.getElementById('addProfil');
        if (addProfilInput) {
            addProfilInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const preview = document.getElementById('addProfilPreview');
                        const previewImg = document.getElementById('addProfilImg');
                        previewImg.src = event.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // Prévisualisation photo pour la modification d'utilisateur
        const editProfilInput = document.getElementById('editProfil');
        if (editProfilInput) {
            editProfilInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const previewImg = document.getElementById('editProfilPreview');
                        const previewContainer = document.getElementById('newProfilPreviewContainer');
                        const arrowContainer = document.getElementById('previewArrowContainer');
                        previewImg.src = event.target.result;
                        previewContainer.style.display = 'block';
                        arrowContainer.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Si aucun fichier n'est sélectionné, masquer les conteneurs
                    document.getElementById('newProfilPreviewContainer').style.display = 'none';
                    document.getElementById('previewArrowContainer').style.display = 'none';
                }
            });
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>

    <script>
        // Gestionnaire d'erreur pour les images de profil
        document.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG' && e.target.classList.contains('profile-img')) {
                e.target.classList.add('error');
                e.target.style.display = 'none';
                
                const fallbackAvatar = document.createElement('div');
                fallbackAvatar.className = 'rounded-circle bg-primary d-inline-flex align-items-center justify-content-center';
                fallbackAvatar.style.cssText = 'width: 45px; height: 45px; color: white;';
                fallbackAvatar.innerHTML = '<i class="bi bi-person"></i>';
                
                e.target.parentElement.replaceChild(fallbackAvatar, e.target);
            }
        }, true);
    </script>
</body>

</html>