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

// Permissions pour cette page (attributions)
$canAccessAttributions = hasPermission(['DG', 'ChefDepartement'], $userRoles);
if (!$canAccessAttributions) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveAttributions = 1;

# Récupérer les attributions avec détails des utilisateurs et rôles
try {
    $query = "SELECT 
                a.attribution_id,
                a.date_attribution,
                a.date_creation,
                u.utilisateur_id,
                u.nom as user_nom,
                u.prenom as user_prenom,
                u.email as user_email,
                u.profil as user_profil,
                u.actif as user_actif,
                r.role_id,
                r.fonction as role_fonction
              FROM ATTRIBUTIONS a
              INNER JOIN UTILISATEURS u ON a.utilisateur_id = u.utilisateur_id AND u.statut = 0
              INNER JOIN ROLES r ON a.role_id = r.role_id AND r.statut = 0
              WHERE a.statut = 0
              ORDER BY a.date_creation DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $attributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    # Compter les statistiques
    $totalAttributions = count($attributions);
    $attributionsActives = 0;
    $usersAvecAttributions = [];
    $rolesUtilises = [];

    foreach ($attributions as $attribution) {
        if ($attribution['user_actif']) {
            $attributionsActives++;
        }
        $usersAvecAttributions[$attribution['utilisateur_id']] = $attribution['user_nom'] . ' ' . $attribution['user_prenom'];
        $rolesUtilises[$attribution['role_id']] = $attribution['role_fonction'];
    }

    $uniqueUsers = count($usersAvecAttributions);
    $uniqueRoles = count($rolesUtilises);

    # Récupérer tous les utilisateurs actifs sans attributions
    $queryUsers = "SELECT u.utilisateur_id, u.nom, u.prenom, u.email, u.profil, u.actif
                   FROM UTILISATEURS u
                   LEFT JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id AND a.statut = 0
                   WHERE u.statut = 0 
                   AND u.actif = 1
                   AND a.attribution_id IS NULL
                   ORDER BY u.nom, u.prenom";

    $stmtUsers = $conn->prepare($queryUsers);
    $stmtUsers->execute();
    $usersSansAttribution = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    # Récupérer tous les rôles disponibles
    $queryRoles = "SELECT role_id, fonction 
                   FROM ROLES 
                   WHERE statut = 0 
                   ORDER BY fonction";
    $stmtRoles = $conn->prepare($queryRoles);
    $stmtRoles->execute();
    $allRoles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

    # Récupérer les attributions multiples (utilisateurs avec plusieurs rôles)
    $queryMultiRoles = "SELECT 
                          u.utilisateur_id,
                          u.nom,
                          u.prenom,
                          GROUP_CONCAT(r.fonction ORDER BY r.fonction SEPARATOR ', ') as roles,
                          COUNT(r.role_id) as nombre_roles
                        FROM UTILISATEURS u
                        INNER JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id AND a.statut = 0
                        INNER JOIN ROLES r ON a.role_id = r.role_id AND r.statut = 0
                        WHERE u.statut = 0
                        GROUP BY u.utilisateur_id
                        HAVING COUNT(r.role_id) > 1
                        ORDER BY nombre_roles DESC";

    $stmtMultiRoles = $conn->prepare($queryMultiRoles);
    $stmtMultiRoles->execute();
    $usersMultiRoles = $stmtMultiRoles->fetchAll(PDO::FETCH_ASSOC);

    $usersAvecMultiRoles = count($usersMultiRoles);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des attributions: " . $e->getMessage());
    $attributions = [];
    $usersSansAttribution = [];
    $allRoles = [];
    $usersMultiRoles = [];
    $totalAttributions = $attributionsActives = $uniqueUsers = $uniqueRoles = $usersAvecMultiRoles = 0;
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
    <title>Gestion des Attributions - Evotech Africa</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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

        .attribution-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            height: 100%;
            background: white;
            padding: 1.5rem;
        }

        .attribution-card:hover {
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

        /* Styles spécifiques aux attributions */
        .profile-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border: 2px solid var(--primary-blue);
            background-color: #f0f0f0;
        }
        
        .profile-img.error {
            display: none;
        }

        .badge-role {
            background-color: var(--primary-blue);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .badge-multi-role {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #20c997 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
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

        .attribution-icon {
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

        .user-status {
            font-size: 0.75em;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Select2 custom styles */
        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            height: calc(2.25rem + 2px);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem + 2px);
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px);
        }

        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            min-height: calc(2.25rem + 2px);
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--primary-blue);
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
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
                            <li class="sidebar-item active">
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
                <i class="bi bi-person-badge me-2"></i>
                Gestion des Attributions
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher une attribution..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttributionModal">
                        <i class="bi bi-plus-circle me-1"></i> Nouvelle Attribution
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Attributions</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $totalAttributions ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="bi bi-person-badge"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Utilisateurs Attribués</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $uniqueUsers ?>
                                </h2>
                                <small class="text-muted">
                                    <?= count($usersSansAttribution) ?> sans attribution
                                </small>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="bi bi-people"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Rôles Utilisés</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $uniqueRoles ?>
                                </h2>
                                <small class="text-muted">
                                    <?= $usersAvecMultiRoles ?> avec plusieurs rôles
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
                                    <?= $totalAttributions ?>
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
                    Vue cartes des attributions
                    <small class="text-white-50 ms-2">(<span id="cardsVisibleCount"><?= $totalAttributions ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="cardsNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucune attribution ne correspond à votre recherche</p>
                </div>

                <!-- Cartes -->
                <div id="cardsContainer">
                    <div class="row" id="attributionsCards">
                        <?php foreach ($attributions as $attribution):
                            $dateAttribution = !empty($attribution['date_attribution']) ? 
                                date('d/m/Y', strtotime($attribution['date_attribution'])) : 'Aujourd\'hui';
                            $dateCreation = !empty($attribution['date_creation']) ? 
                                date('d/m/Y', strtotime($attribution['date_creation'])) : 'Non définie';
                            $isActive = $attribution['user_actif'];
                        ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4 attribution-card-item"
                                data-nom="<?= htmlspecialchars(strtolower($attribution['user_nom'] . ' ' . $attribution['user_prenom'])) ?>"
                                data-email="<?= htmlspecialchars(strtolower($attribution['user_email'])) ?>"
                                data-role="<?= htmlspecialchars(strtolower($attribution['role_fonction'])) ?>"
                                data-statut="<?= $isActive ? 'actif' : 'inactif' ?>">
                                <div class="attribution-card h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <?php if ($isActive): ?>
                                                <span class="user-status status-active">
                                                    <i class="bi bi-check-circle me-1"></i>Actif
                                                </span>
                                            <?php else: ?>
                                                <span class="user-status status-inactive">
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
                                                            onclick="loadEditData(<?= $attribution['attribution_id'] ?>)"
                                                            data-bs-toggle="modal" data-bs-target="#editAttributionModal">
                                                            <i class="bi bi-pencil me-2"></i>Modifier
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#"
                                                            onclick="setDeleteId(<?= $attribution['attribution_id'] ?>, '<?= htmlspecialchars(addslashes($attribution['user_nom'] . ' ' . $attribution['user_prenom'])) ?>', '<?= htmlspecialchars(addslashes($attribution['role_fonction'])) ?>')"
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                            <i class="bi bi-trash me-2"></i>Supprimer
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-center mb-3">
                                        <div class="attribution-icon mx-auto">
                                            <i class="bi bi-person-badge"></i>
                                        </div>
                                    </div>

                                    <h5 class="text-center mb-1 text-primary">
                                        <?= htmlspecialchars($attribution['user_nom'] . ' ' . $attribution['user_prenom']) ?>
                                    </h5>

                                    <p class="text-center text-muted small mb-3">
                                        <?= htmlspecialchars($attribution['user_email']) ?>
                                    </p>

                                    <div class="text-center mb-3">
                                        <span class="badge-role">
                                            <i class="bi bi-shield me-1"></i>
                                            <?= htmlspecialchars($attribution['role_fonction']) ?>
                                        </span>
                                    </div>

                                    <div class="bg-light rounded p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-calendar text-primary me-2"></i>
                                            <span class="small">Attribué le: <?= $dateAttribution ?></span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-clock text-primary me-2"></i>
                                            <span class="small">Créé le: <?= $dateCreation ?></span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                        <small class="text-muted">
                                            ID: #<?= $attribution['attribution_id'] ?>
                                        </small>
                                        <small class="text-muted">
                                            Utilisateur: #<?= $attribution['utilisateur_id'] ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table des attributions -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-task me-2"></i>
                    Tableau des attributions
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= $totalAttributions ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucune attribution ne correspond à votre recherche</p>
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
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(4)">
                                        Rôle <i class="bi bi-arrow-down-up ms-1"></i>
                                    </button>
                                </th>
                                <th>Date Attribution</th>
                                <th>Statut</th>
                                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                    <th width="100" class="text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="attributionsTable">
                            <?php
                            $n = 0;
                            foreach ($attributions as $attribution):
                                $n++;
                                $dateAttribution = !empty($attribution['date_attribution']) ?
                                    date('d/m/Y', strtotime($attribution['date_attribution'])) : 'Aujourd\'hui';
                                $isActive = $attribution['user_actif'];
                            ?>
                                <tr class="table-row"
                                    data-nom="<?= htmlspecialchars(strtolower($attribution['user_nom'] . ' ' . $attribution['user_prenom'])) ?>"
                                    data-email="<?= htmlspecialchars(strtolower($attribution['user_email'])) ?>"
                                    data-role="<?= htmlspecialchars(strtolower($attribution['role_fonction'])) ?>"
                                    data-statut="<?= $isActive ? 'actif' : 'inactif' ?>">
                                    <td class="text-center"><?= $n ?></td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($attribution['user_nom'] . ' ' . $attribution['user_prenom']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($attribution['user_profil'])): ?>
                                            <img src="../images/profil/utilisateurs/<?= htmlspecialchars($attribution['user_profil']) ?>"
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
                                            <?= htmlspecialchars($attribution['user_email']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-role">
                                            <i class="bi bi-shield me-1"></i>
                                            <?= htmlspecialchars($attribution['role_fonction']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= $dateAttribution ?></small>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="user-status status-active">
                                                <i class="bi bi-check-circle me-1"></i>Actif
                                            </span>
                                        <?php else: ?>
                                            <span class="user-status status-inactive">
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
                                                    data-bs-target="#editAttributionModal"
                                                    onclick="loadEditData(<?= $attribution['attribution_id'] ?>)"
                                                    title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    onclick="setDeleteId(<?= $attribution['attribution_id'] ?>, '<?= htmlspecialchars(addslashes($attribution['user_nom'] . ' ' . $attribution['user_prenom'])) ?>', '<?= htmlspecialchars(addslashes($attribution['role_fonction'])) ?>')"
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
        <!-- Modal Ajout Attribution -->
        <div class="modal fade" id="addAttributionModal" tabindex="-1" aria-labelledby="addAttributionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addAttributionModalLabel">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nouvelle Attribution
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-attribution.php" method="POST" id="addAttributionForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="addUtilisateur" class="form-label">Utilisateur <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="addUtilisateur" name="utilisateur_id" required>
                                        <option value="">Sélectionner un utilisateur</option>
                                        <?php foreach ($usersSansAttribution as $user): ?>
                                            <option value="<?= $user['utilisateur_id'] ?>">
                                                <?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?> 
                                                (<?= htmlspecialchars($user['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">
                                        <?= count($usersSansAttribution) ?> utilisateur(s) sans attribution disponible(s)
                                    </small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="addRole" class="form-label">Rôle <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="addRole" name="role_id" required>
                                        <option value="">Sélectionner un rôle</option>
                                        <?php foreach ($allRoles as $role): ?>
                                            <option value="<?= $role['role_id'] ?>">
                                                <?= htmlspecialchars($role['fonction']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="addDateAttribution" class="form-label">Date d'attribution</label>
                                    <input type="date" class="form-control" id="addDateAttribution" name="date_attribution"
                                           value="<?= date('Y-m-d') ?>">
                                    <small class="text-muted">Date à laquelle le rôle est attribué</small>
                                </div>
                                
                                <div class="col-12">
                                    <div class="alert alert-light border">
                                        <i class="bi bi-info-circle text-primary me-2"></i>
                                        <small class="text-muted">
                                            Une attribution définit les permissions d'un utilisateur dans le système.
                                            Un utilisateur peut avoir plusieurs rôles simultanément.
                                        </small>
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

        <!-- Modal Modification Attribution -->
        <div class="modal fade" id="editAttributionModal" tabindex="-1" aria-labelledby="editAttributionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editAttributionModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Modifier l'Attribution
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-attribution.php" method="POST" id="editAttributionForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="editAttributionId" name="attribution_id">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Utilisateur</label>
                                    <p id="editUserName" class="form-control-static"></p>
                                    <small class="text-muted" id="editUserEmail"></small>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="editRole" class="form-label">Nouveau Rôle <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="editRole" name="role_id" required>
                                        <option value="">Sélectionner un rôle</option>
                                        <?php foreach ($allRoles as $role): ?>
                                            <option value="<?= $role['role_id'] ?>">
                                                <?= htmlspecialchars($role['fonction']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="editDateAttribution" class="form-label">Date d'attribution</label>
                                    <input type="date" class="form-control" id="editDateAttribution" name="date_attribution">
                                </div>
                                
                                <div class="col-12">
                                    <div class="alert alert-warning border">
                                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                        <small class="text-muted">
                                            La modification changera immédiatement les permissions de l'utilisateur.
                                        </small>
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
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteModalLabel">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Confirmation de suppression
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer l'attribution :</p>
                        <div class="alert alert-warning">
                            <strong>Utilisateur :</strong> <span id="deleteUserName" class="text-primary"></span><br>
                            <strong>Rôle :</strong> <span id="deleteRoleName" class="text-primary"></span>
                        </div>
                        <p class="text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette action est irréversible. L'utilisateur perdra les permissions associées à ce rôle.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" id="deleteConfirmBtn" class="btn btn-danger" data-attribution-id="0">
                            <i class="bi bi-trash me-1"></i> Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/fr.js"></script>

    <script>
        // Variable globale pour stocker l'ID de l'attribution à supprimer
        let currentDeleteAttributionId = null;
        
        // Gestion des erreurs d'images de profil
        document.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG' && e.target.classList.contains('profile-img')) {
                // Marquer comme erreur pour éviter une boucle
                e.target.classList.add('error');
                e.target.style.display = 'none';
                
                // Créer un fallback avatar
                const fallbackAvatar = document.createElement('div');
                fallbackAvatar.className = 'rounded-circle bg-primary d-inline-flex align-items-center justify-content-center';
                fallbackAvatar.style.cssText = 'width: 45px; height: 45px; color: white;';
                fallbackAvatar.innerHTML = '<i class="bi bi-person"></i>';
                
                // Remplacer l'image par le fallback
                e.target.parentElement.replaceChild(fallbackAvatar, e.target);
            }
        }, true);
        
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
            initializeSelect2();
        });

        // Initialisation Select2
        function initializeSelect2() {
            $('.select2').select2({
                placeholder: "Sélectionner...",
                allowClear: true,
                dropdownParent: $('#addAttributionModal, #editAttributionModal'),
                width: '100%',
                language: 'fr'
            });
            
            // Reset Select2 quand le modal est fermé
            $('#addAttributionModal').on('hidden.bs.modal', function () {
                $('#addUtilisateur').val(null).trigger('change');
                $('#addRole').val(null).trigger('change');
            });
        }
        
        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterAttributions(this.value);
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterAttributions('');
            });
        }
        
        function filterAttributions(searchTerm) {
            const searchLower = searchTerm.toLowerCase();
            const attributionCards = document.querySelectorAll('.attribution-card-item');
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const cardsNoResults = document.getElementById('cardsNoResults');
            const tableNoResults = document.getElementById('tableNoResults');
            
            let visibleCards = 0;
            let visibleRows = 0;
            
            // Filtrer les cartes
            attributionCards.forEach(card => {
                const nom = card.getAttribute('data-nom');
                const email = card.getAttribute('data-email');
                const role = card.getAttribute('data-role');
                const statut = card.getAttribute('data-statut');
                
                const matches = searchTerm === '' || 
                    nom.includes(searchLower) || 
                    email.includes(searchLower) || 
                    role.includes(searchLower) || 
                    statut.includes(searchLower);
                
                card.style.display = matches ? 'block' : 'none';
                if (matches) visibleCards++;
            });
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const nom = row.getAttribute('data-nom');
                const email = row.getAttribute('data-email');
                const role = row.getAttribute('data-role');
                const statut = row.getAttribute('data-statut');
                
                const matches = searchTerm === '' || 
                    nom.includes(searchLower) || 
                    email.includes(searchLower) || 
                    role.includes(searchLower) || 
                    statut.includes(searchLower);
                
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
            const table = document.getElementById('attributionsTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                let aText, bText;
                
                if (columnIndex === 1) {
                    // Colonne Utilisateur
                    aText = a.querySelector('strong').textContent.toLowerCase();
                    bText = b.querySelector('strong').textContent.toLowerCase();
                } else if (columnIndex === 4) {
                    // Colonne Rôle
                    aText = a.querySelector('.badge-role').textContent.toLowerCase();
                    bText = b.querySelector('.badge-role').textContent.toLowerCase();
                } else if (columnIndex === 5) {
                    // Colonne Date (convertir en timestamp)
                    aText = new Date(a.cells[5].textContent.split('/').reverse().join('-'));
                    bText = new Date(b.cells[5].textContent.split('/').reverse().join('-'));
                } else if (columnIndex === 6) {
                    // Colonne Statut
                    aText = a.querySelector('.user-status').textContent.toLowerCase();
                    bText = b.querySelector('.user-status').textContent.toLowerCase();
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
        function loadEditData(attributionId) {
            fetch(`../models/fetch/fetch-attribution-details.php?id=${attributionId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const attribution = data.attribution;
                        
                        document.getElementById('editAttributionId').value = attribution.attribution_id;
                        document.getElementById('editUserName').textContent = 
                            `${attribution.user_prenom} ${attribution.user_nom}`;
                        document.getElementById('editUserEmail').textContent = 
                            `(${attribution.user_email})`;
                        document.getElementById('editDateAttribution').value = 
                            attribution.date_attribution.split(' ')[0];
                        
                        // Sélectionner le rôle actuel
                        const editRoleSelect = document.getElementById('editRole');
                        if (editRoleSelect) {
                            editRoleSelect.value = attribution.role_id;
                        }
                    } else {
                        alert('Erreur: ' + (data.message || 'Impossible de charger les données'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données');
                });
        }
        
        // Préparer la suppression
        function setDeleteId(id, userName, roleName) {
            // Stocker l'ID dans la variable globale
            currentDeleteAttributionId = id;
            
            // Stocker aussi dans l'attribut data du bouton (pour la redondance)
            document.getElementById('deleteConfirmBtn').setAttribute('data-attribution-id', id);
            
            // Mettre à jour le contenu du modal
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteRoleName').textContent = roleName;
            
            console.log('ID d\'attribution défini:', id); // Debug
        }
        
        // Gestionnaire de suppression
        document.getElementById('deleteConfirmBtn')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Essayer d'abord la variable globale, puis le dataset, puis l'attribut data
            let attributionId = currentDeleteAttributionId || 
                                this.dataset.attributionId || 
                                this.getAttribute('data-attribution-id');
            
            console.log('ID à supprimer:', attributionId); // Debug
            
            if (!attributionId || attributionId === '0') {
                alert('Erreur: ID manquant. Veuillez réessayer.');
                return;
            }
            
            // Fermer le modal
            const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (deleteModal) {
                deleteModal.hide();
            }
            
            // Effectuer la suppression via GET (redirection)
            window.location.href = `../models/delete/delete-attribution.php?id=${attributionId}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
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