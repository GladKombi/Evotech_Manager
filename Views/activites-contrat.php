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

// Vérifier si un contrat_id est passé en paramètre
$contrat_id = isset($_GET['contrat_id']) ? intval($_GET['contrat_id']) : 0;

// Définir le menu actif pour le sidebar
$ActiveContrats = 1;

try {
    // Si un contrat_id est spécifié, récupérer ses informations
    $contrat_info = null;
    $contrat_reference = "Tous les contrats";
    
    if ($contrat_id > 0) {
        $queryContrat = "
            SELECT c.*, p.nom as partenaire_nom 
            FROM CONTRATS_COM c
            LEFT JOIN PARTENAIRES p ON c.partenaire_id = p.partenaire_id
            WHERE c.contrat_id = ? AND c.statut = 0
        ";
        $stmtContrat = $conn->prepare($queryContrat);
        $stmtContrat->execute([$contrat_id]);
        $contrat_info = $stmtContrat->fetch(PDO::FETCH_ASSOC);
        
        if ($contrat_info) {
            $contrat_reference = $contrat_info['reference'];
        } else {
            $_SESSION['error'] = "Contrat non trouvé";
            header('Location: contrats-com.php');
            exit();
        }
    }
    
    # Récupérer les activités avec jointures
    if ($contrat_id > 0) {
        $query = "
            SELECT 
                a.*,
                c.reference as contrat_reference,
                p.nom as partenaire_nom
            FROM ACTIVITES_CONTRAT a
            JOIN CONTRATS_COM c ON a.contrat_id = c.contrat_id
            LEFT JOIN PARTENAIRES p ON c.partenaire_id = p.partenaire_id
            WHERE a.contrat_id = ? AND a.statut = 0
            ORDER BY a.date_activite DESC, a.date_creation DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([$contrat_id]);
    } else {
        $query = "
            SELECT 
                a.*,
                c.reference as contrat_reference,
                p.nom as partenaire_nom
            FROM ACTIVITES_CONTRAT a
            JOIN CONTRATS_COM c ON a.contrat_id = c.contrat_id
            LEFT JOIN PARTENAIRES p ON c.partenaire_id = p.partenaire_id
            WHERE a.statut = 0 AND c.statut = 0
            ORDER BY a.date_activite DESC, a.date_creation DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute();
    }
    
    $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer la liste des contrats pour le filtre
    $queryContrats = "
        SELECT c.contrat_id, c.reference, p.nom as partenaire_nom 
        FROM CONTRATS_COM c
        LEFT JOIN PARTENAIRES p ON c.partenaire_id = p.partenaire_id
        WHERE c.statut = 0 AND c.etat IN ('Actif', 'En attente')
        ORDER BY c.reference
    ";
    $stmtContrats = $conn->prepare($queryContrats);
    $stmtContrats->execute();
    $contrats = $stmtContrats->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les types d'activités existants pour suggestions
    $queryTypes = "
        SELECT DISTINCT description_activite 
        FROM ACTIVITES_CONTRAT 
        WHERE statut = 0 
        ORDER BY description_activite
    ";
    $stmtTypes = $conn->prepare($queryTypes);
    $stmtTypes->execute();
    $types_activites = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);
    
    // Statistiques
    if ($contrat_id > 0) {
        $queryStats = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut_activite = 'Planifiée' THEN 1 ELSE 0 END) as planifiees,
                SUM(CASE WHEN statut_activite = 'Réalisée' THEN 1 ELSE 0 END) as realisees,
                SUM(CASE WHEN statut_activite = 'Annulée' THEN 1 ELSE 0 END) as annulees,
                SUM(cout) as total_cout
            FROM ACTIVITES_CONTRAT 
            WHERE contrat_id = ? AND statut = 0
        ";
        $stmtStats = $conn->prepare($queryStats);
        $stmtStats->execute([$contrat_id]);
    } else {
        $queryStats = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut_activite = 'Planifiée' THEN 1 ELSE 0 END) as planifiees,
                SUM(CASE WHEN statut_activite = 'Réalisée' THEN 1 ELSE 0 END) as realisees,
                SUM(CASE WHEN statut_activite = 'Annulée' THEN 1 ELSE 0 END) as annulees,
                SUM(cout) as total_cout
            FROM ACTIVITES_CONTRAT 
            WHERE statut = 0
        ";
        $stmtStats = $conn->prepare($queryStats);
        $stmtStats->execute();
    }
    
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des activités: " . $e->getMessage());
    $activites = [];
    $contrats = [];
    $types_activites = [];
    $stats = ['total' => 0, 'planifiees' => 0, 'realisees' => 0, 'annulees' => 0, 'total_cout' => 0];
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
    <title>Activités Contrat - Evotech Africa</title>
    
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
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
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
            background: linear-gradient(135deg, var(--info-color) 0%, #0d6efd 100%);
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

        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
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

        /* Badges spécifiques aux activités */
        .badge-planifiee {
            background: linear-gradient(135deg, var(--warning-color), #ffca2c);
            color: #000;
        }

        .badge-realisee {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
        }

        .badge-annulee {
            background: linear-gradient(135deg, var(--danger-color), #e35d6a);
            color: white;
        }

        .contrat-info-card {
            border-left: 4px solid var(--info-color);
            background: linear-gradient(135deg, #e3f2fd, #f8f9fa);
        }

        .activity-timeline {
            position: relative;
            padding-left: 30px;
            margin-left: 10px;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary-blue-light);
        }

        .activity-item {
            position: relative;
            margin-bottom: 20px;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-blue);
            border: 2px solid white;
            box-shadow: 0 0 0 3px var(--primary-blue-light);
        }

        .activity-item.realisee::before {
            background: var(--success-color);
            box-shadow: 0 0 0 3px #20c99733;
        }

        .activity-item.annulee::before {
            background: var(--danger-color);
            box-shadow: 0 0 0 3px #dc354533;
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

        .datalist-options {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background: white;
            position: absolute;
            z-index: 1000;
            width: 100%;
            display: none;
        }

        .datalist-option {
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .datalist-option:hover {
            background-color: #f8f9fa;
        }

        .link-url {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
                                    <li>
                                        <a href="contrats-com.php">Contrats</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                    <li class="active">
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
            <div>
                <h1 class="h3 fw-bold text-primary">
                    <i class="bi bi-calendar-check me-2"></i>
                    Activités des Contrats
                </h1>
                <?php if ($contrat_info): ?>
                <div class="mt-2">
                    <a href="contrats-com.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Retour aux contrats
                    </a>
                    <span class="text-muted">
                        <i class="bi bi-file-earmark-text"></i> 
                        Contrat: <strong><?= htmlspecialchars($contrat_info['reference']) ?></strong>
                        | Partenaire: <strong><?= htmlspecialchars($contrat_info['partenaire_nom']) ?></strong>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher une activité..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActiviteModal">
                        <i class="bi bi-plus-circle me-1"></i> Nouvelle Activité
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Activités</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $stats['total'] ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="bi bi-list-task"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Réalisées</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $stats['realisees'] ?>
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
                                <h6 class="card-subtitle mb-2 text-muted">Planifiées</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $stats['planifiees'] ?>
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
                                <h6 class="card-subtitle mb-2 text-muted">Coût Total</h6>
                                <h2 class="card-title text-info mb-0" id="filteredCount">
                                    $<?= number_format($stats['total_cout'] ?? 0, 2, ',', ' ') ?>
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="bi bi-cash"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information du contrat (si spécifique) -->
        <?php if ($contrat_info): ?>
        <div class="card contrat-info-card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">Type de contrat</small>
                        <p class="mb-0 fw-bold"><?= htmlspecialchars($contrat_info['type_contrat']) ?></p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Montant du contrat</small>
                        <p class="mb-0 fw-bold text-success">$<?= number_format($contrat_info['montant'], 2, ',', ' ') ?></p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">État</small>
                        <p class="mb-0">
                            <span class="badge 
                                <?= $contrat_info['etat'] == 'Actif' ? 'bg-success' : 
                                   ($contrat_info['etat'] == 'En attente' ? 'bg-warning text-dark' : 
                                   ($contrat_info['etat'] == 'Terminé' ? 'bg-secondary' : 'bg-danger')) ?>">
                                <?= htmlspecialchars($contrat_info['etat']) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
                    <label class="form-label">Contrat</label>
                    <select class="form-select" id="filterContrat">
                        <option value="">Tous les contrats</option>
                        <?php foreach ($contrats as $c): ?>
                            <option value="<?= $c['contrat_id'] ?>" 
                                <?= ($contrat_id == $c['contrat_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['reference']) ?> - <?= htmlspecialchars($c['partenaire_nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select class="form-select" id="filterStatut">
                        <option value="">Tous les statuts</option>
                        <option value="Planifiée">Planifiée</option>
                        <option value="Réalisée">Réalisée</option>
                        <option value="Annulée">Annulée</option>
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
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label">Type d'activité</label>
                    <input type="text" class="form-control" id="filterType" list="typesList" placeholder="Tapez pour rechercher...">
                    <datalist id="typesList">
                        <?php foreach ($types_activites as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prix minimum</label>
                    <input type="number" class="form-control" id="filterPrixMin" placeholder="0" step="0.01">
                </div>
            </div>
        </div>

        <!-- Onglets pour différentes vues -->
        <ul class="nav nav-tabs mb-4" id="viewTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-view" type="button" role="tab">
                    <i class="bi bi-table me-1"></i> Vue Tableau
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-view" type="button" role="tab">
                    <i class="bi bi-calendar-event me-1"></i> Vue Chronologique
                </button>
            </li>
        </ul>

        <div class="tab-content" id="viewTabsContent">
            <!-- Vue Tableau -->
            <div class="tab-pane fade show active" id="table-view" role="tabpanel">
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-task me-2"></i>
                            Liste des activités
                            <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($activites) ?></span> visible(s))</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Message aucun résultat -->
                        <div class="no-results" id="tableNoResults">
                            <i class="bi bi-search text-muted"></i>
                            <h5 class="mt-2">Aucune activité trouvée</h5>
                            <p class="text-muted">Aucune activité ne correspond à vos critères</p>
                            <?php if ($contrat_id > 0): ?>
                                <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addActiviteModal">
                                    <i class="bi bi-plus-circle me-1"></i> Ajouter la première activité
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Tableau -->
                        <div class="table-responsive" id="tableContainer">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Date</th>
                                        <th>Contrat</th>
                                        <th>Type d'activité</th>
                                        <th>Détails</th>
                                        <th>Lien</th>
                                        <th>Coût</th>
                                        <th>Statut</th>
                                        <th width="160" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="activitesTable">
                                    <?php if (empty($activites)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                                <p class="mt-2">Aucune activité trouvée</p>
                                                <?php if ($contrat_id): ?>
                                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addActiviteModal">
                                                        <i class="bi bi-plus-circle me-1"></i> Ajouter une activité
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        $n = 0;
                                        foreach ($activites as $activite):
                                            $n++;
                                            $cout = $activite['cout'] ? number_format($activite['cout'], 2, ',', ' ') . ' $' : '-';
                                            $statutClass = 'badge-' . strtolower($activite['statut_activite']);
                                            $dateActivite = date('d/m/Y', strtotime($activite['date_activite']));
                                            $linkPreview = $activite['lien_url'] ? '<a href="' . htmlspecialchars($activite['lien_url']) . '" target="_blank" class="link-url" title="' . htmlspecialchars($activite['lien_url']) . '"><i class="bi bi-link"></i></a>' : '-';
                                        ?>
                                        <tr class="table-row activity-row" 
                                            data-date="<?= htmlspecialchars($activite['date_activite']) ?>"
                                            data-contrat="<?= htmlspecialchars(strtolower($activite['contrat_reference'])) ?>"
                                            data-type="<?= htmlspecialchars(strtolower($activite['description_activite'])) ?>"
                                            data-statut="<?= htmlspecialchars($activite['statut_activite']) ?>"
                                            data-cout="<?= $activite['cout'] ?>"
                                            data-details="<?= htmlspecialchars(strtolower($activite['details'] ?? '')) ?>">
                                            <td class="text-center"><?= $n ?></td>
                                            <td>
                                                <span class="fw-semibold"><?= $dateActivite ?></span>
                                            </td>
                                            <td>
                                                <a href="?contrat_id=<?= $activite['contrat_id'] ?>" class="text-decoration-none">
                                                    <span class="text-primary"><?= htmlspecialchars($activite['contrat_reference']) ?></span>
                                                </a>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($activite['partenaire_nom']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($activite['description_activite']) ?></td>
                                            <td><?= htmlspecialchars(substr($activite['details'] ?? '', 0, 100)) . (strlen($activite['details'] ?? '') > 100 ? '...' : '') ?></td>
                                            <td><?= $linkPreview ?></td>
                                            <td class="fw-semibold <?= $activite['cout'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                                <?= $cout ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $statutClass ?>">
                                                    <?= htmlspecialchars($activite['statut_activite']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" 
                                                            class="btn btn-outline-primary"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editActiviteModal"
                                                            onclick="loadEditData(<?= $activite['activite_id'] ?>)"
                                                            title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-outline-success"
                                                            onclick="changerStatut(<?= $activite['activite_id'] ?>, 'Réalisée', '<?= htmlspecialchars(addslashes($activite['description_activite'])) ?>')"
                                                            title="Marquer comme réalisée">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal"
                                                            onclick="setDeleteId(<?= $activite['activite_id'] ?>, '<?= htmlspecialchars(addslashes($activite['description_activite'])) ?>')"
                                                            title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vue Chronologique -->
            <div class="tab-pane fade" id="timeline-view" role="tabpanel">
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar-event me-2"></i>
                            Chronologie des activités
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline" id="timelineContent">
                            <?php
                            // Grouper par date
                            $activitiesByDate = [];
                            foreach ($activites as $activite) {
                                $date = $activite['date_activite'];
                                $activitiesByDate[$date][] = $activite;
                            }
                            
                            krsort($activitiesByDate); // Tri décroissant par date
                            
                            $timelineCount = 0;
                            foreach ($activitiesByDate as $date => $dateActivities):
                                $formattedDate = date('d/m/Y', strtotime($date));
                            ?>
                            <div class="activity-item-group mb-4">
                                <h6 class="text-primary mb-3 border-bottom pb-2">
                                    <i class="bi bi-calendar-date me-2"></i><?= $formattedDate ?>
                                    <span class="badge bg-primary ms-2"><?= count($dateActivities) ?></span>
                                </h6>
                                
                                <?php foreach ($dateActivities as $activite):
                                    $timelineCount++;
                                    $statutClass = strtolower($activite['statut_activite']);
                                    $coutDisplay = $activite['cout'] ? number_format($activite['cout'], 2, ',', ' ') . ' $' : 'Gratuit';
                                ?>
                                <div class="activity-item <?= $statutClass ?> mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-1">
                                                        <?= htmlspecialchars($activite['description_activite']) ?>
                                                        <span class="badge ms-2 <?= 'badge-' . $statutClass ?>">
                                                            <?= htmlspecialchars($activite['statut_activite']) ?>
                                                        </span>
                                                    </h6>
                                                    <p class="card-text text-muted mb-2">
                                                        <small>
                                                            <i class="bi bi-file-earmark-text me-1"></i>
                                                            Contrat: <a href="?contrat_id=<?= $activite['contrat_id'] ?>" class="text-decoration-none">
                                                                <?= htmlspecialchars($activite['contrat_reference']) ?>
                                                            </a>
                                                            | <i class="bi bi-person me-1"></i>
                                                            <?= htmlspecialchars($activite['partenaire_nom']) ?>
                                                        </small>
                                                    </p>
                                                    <?php if (!empty($activite['details'])): ?>
                                                    <p class="card-text mb-2"><?= htmlspecialchars($activite['details']) ?></p>
                                                    <?php endif; ?>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php if ($activite['lien_url']): ?>
                                                        <a href="<?= htmlspecialchars($activite['lien_url']) ?>" 
                                                           target="_blank" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-link me-1"></i> Voir le lien
                                                        </a>
                                                        <?php endif; ?>
                                                        <span class="badge bg-<?= $activite['cout'] > 0 ? 'danger' : 'success' ?>">
                                                            <i class="bi bi-cash me-1"></i> <?= $coutDisplay ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" 
                                                            class="btn btn-outline-primary"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editActiviteModal"
                                                            onclick="loadEditData(<?= $activite['activite_id'] ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php 
                            endforeach;
                            
                            if ($timelineCount === 0): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Aucune activité à afficher</h5>
                                <p class="text-muted">Ajoutez des activités pour les voir dans la chronologie</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
        <!-- Modal Ajout Activité -->
        <div class="modal fade" id="addActiviteModal" tabindex="-1" aria-labelledby="addActiviteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addActiviteModalLabel">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nouvelle Activité
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-activite-contrat.php" method="POST" id="addForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="contrat_id" value="<?= $contrat_id ?>">
                        <div class="modal-body">
                            <div class="row g-3">
                                <?php if (!$contrat_id): ?>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Contrat <span class="text-danger">*</span></label>
                                    <select class="form-select" name="contrat_id" required>
                                        <option value="">Sélectionner un contrat</option>
                                        <?php foreach ($contrats as $contrat): ?>
                                            <option value="<?= $contrat['contrat_id'] ?>">
                                                <?= htmlspecialchars($contrat['reference']) ?> - <?= htmlspecialchars($contrat['partenaire_nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date d'activité <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="date_activite" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type d'activité <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="description_activite" required 
                                           list="addTypesList" placeholder="Ex: Publication Facebook, Article de blog...">
                                    <datalist id="addTypesList">
                                        <?php foreach ($types_activites as $type): ?>
                                            <option value="<?= htmlspecialchars($type) ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Détails</label>
                                    <textarea class="form-control" name="details" rows="3" 
                                              placeholder="Description détaillée de l'activité..."></textarea>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Lien URL</label>
                                    <input type="url" class="form-control" name="lien_url" 
                                           placeholder="https://example.com">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Coût ($)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="cout" step="0.01" min="0" value="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Statut</label>
                                    <select class="form-select" name="statut_activite">
                                        <option value="Planifiée">Planifiée</option>
                                        <option value="Réalisée">Réalisée</option>
                                        <option value="Annulée">Annulée</option>
                                    </select>
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

        <!-- Modal Modification Activité -->
        <div class="modal fade" id="editActiviteModal" tabindex="-1" aria-labelledby="editActiviteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editActiviteModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Modifier l'Activité
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" action="../models/update/update-activite-contrat.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="editId" name="activite_id">
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
                        <p>Êtes-vous sûr de vouloir supprimer l'activité :</p>
                        <h5 class="text-danger" id="deleteActiviteName"></h5>
                        <p class="text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette action est irréversible. L'activité sera marquée comme supprimée dans le système.
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
            initializeFilters();
            initializeDatePickers();
            
            // Actualiser la timeline quand on change d'onglet
            document.getElementById('timeline-tab').addEventListener('click', function() {
                updateTimelineView();
            });
        });

        // Initialisation des filtres
        function initializeFilters() {
            // Écouteurs pour les filtres
            document.getElementById('filterContrat').addEventListener('change', function() {
                const contratId = this.value;
                if (contratId) {
                    window.location.href = `activites-contrat.php?contrat_id=${contratId}`;
                } else {
                    window.location.href = 'activites-contrat.php';
                }
            });
            
            document.getElementById('filterStatut').addEventListener('change', filterActivites);
            document.getElementById('filterDateDebut').addEventListener('change', filterActivites);
            document.getElementById('filterDateFin').addEventListener('change', filterActivites);
            document.getElementById('filterType').addEventListener('keyup', filterActivites);
            document.getElementById('filterPrixMin').addEventListener('keyup', filterActivites);
        }

        // Initialisation des datepickers
        function initializeDatePickers() {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                language: 'fr',
                autoclose: true,
                todayHighlight: true
            });
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterActivites();
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                document.getElementById('filterStatut').value = '';
                document.getElementById('filterDateDebut').value = '';
                document.getElementById('filterDateFin').value = '';
                document.getElementById('filterType').value = '';
                document.getElementById('filterPrixMin').value = '';
                filterActivites();
            });
        }
        
        function filterActivites() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statutFilter = document.getElementById('filterStatut').value;
            const dateDebutFilter = document.getElementById('filterDateDebut').value;
            const dateFinFilter = document.getElementById('filterDateFin').value;
            const typeFilter = document.getElementById('filterType').value.toLowerCase();
            const prixMinFilter = parseFloat(document.getElementById('filterPrixMin').value) || 0;
            
            const tableRows = document.querySelectorAll('.table-row.activity-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            
            let visibleRows = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (statutFilter) activeFilters.push(`Statut: ${statutFilter}`);
            if (typeFilter) activeFilters.push(`Type: ${typeFilter}`);
            if (dateDebutFilter) activeFilters.push(`À partir du: ${dateDebutFilter}`);
            if (dateFinFilter) activeFilters.push(`Jusqu'au: ${dateFinFilter}`);
            if (prixMinFilter > 0) activeFilters.push(`Prix min: $${prixMinFilter}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const date = row.getAttribute('data-date');
                const contrat = row.getAttribute('data-contrat');
                const type = row.getAttribute('data-type');
                const statut = row.getAttribute('data-statut');
                const cout = parseFloat(row.getAttribute('data-cout')) || 0;
                const details = row.getAttribute('data-details');
                
                let matches = true;
                
                // Filtre recherche globale
                if (searchTerm && 
                    !type.includes(searchTerm) && 
                    !details.includes(searchTerm) &&
                    !contrat.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre statut
                if (statutFilter && statut !== statutFilter) {
                    matches = false;
                }
                
                // Filtre type
                if (typeFilter && !type.includes(typeFilter)) {
                    matches = false;
                }
                
                // Filtre date début
                if (dateDebutFilter && date < dateDebutFilter) {
                    matches = false;
                }
                
                // Filtre date fin
                if (dateFinFilter && date > dateFinFilter) {
                    matches = false;
                }
                
                // Filtre prix minimum
                if (prixMinFilter > 0 && cout < prixMinFilter) {
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
            document.querySelectorAll('.table-row.activity-row').forEach(row => {
                if (row.style.display !== 'none') {
                    row.querySelector('td:first-child').textContent = counter;
                    counter++;
                }
            });
        }
        
        // Mettre à jour la vue timeline
        function updateTimelineView() {
            const tableRows = document.querySelectorAll('.table-row.activity-row');
            const timelineContent = document.getElementById('timelineContent');
            
            // Grouper les activités visibles par date
            const activitiesByDate = {};
            
            tableRows.forEach(row => {
                if (row.style.display !== 'none') {
                    const date = row.getAttribute('data-date');
                    const contrat = row.querySelector('td:nth-child(3) a span').textContent;
                    const partenaire = row.querySelector('td:nth-child(3) small').textContent;
                    const type = row.querySelector('td:nth-child(4)').textContent;
                    const details = row.querySelector('td:nth-child(5)').textContent;
                    const lienUrl = row.querySelector('td:nth-child(6) a')?.href || '';
                    const cout = parseFloat(row.getAttribute('data-cout')) || 0;
                    const statut = row.getAttribute('data-statut');
                    
                    if (!activitiesByDate[date]) {
                        activitiesByDate[date] = [];
                    }
                    
                    activitiesByDate[date].push({
                        contrat: contrat,
                        partenaire: partenaire,
                        type: type,
                        details: details,
                        lienUrl: lienUrl,
                        cout: cout,
                        statut: statut
                    });
                }
            });
            
            // Trier les dates
            const sortedDates = Object.keys(activitiesByDate).sort((a, b) => new Date(b) - new Date(a));
            
            // Générer le HTML de la timeline
            let timelineHTML = '';
            
            if (sortedDates.length === 0) {
                timelineHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Aucune activité à afficher</h5>
                        <p class="text-muted">Les filtres actuels ne retournent aucune activité</p>
                    </div>
                `;
            } else {
                sortedDates.forEach(date => {
                    const formattedDate = new Date(date).toLocaleDateString('fr-FR');
                    const activities = activitiesByDate[date];
                    
                    timelineHTML += `
                        <div class="activity-item-group mb-4">
                            <h6 class="text-primary mb-3 border-bottom pb-2">
                                <i class="bi bi-calendar-date me-2"></i>${formattedDate}
                                <span class="badge bg-primary ms-2">${activities.length}</span>
                            </h6>
                    `;
                    
                    activities.forEach(activity => {
                        const statutClass = activity.statut.toLowerCase();
                        const coutDisplay = activity.cout > 0 ? activity.cout.toFixed(2) + ' $' : 'Gratuit';
                        
                        timelineHTML += `
                            <div class="activity-item ${statutClass} mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1">
                                                    ${activity.type}
                                                    <span class="badge ms-2 badge-${statutClass}">
                                                        ${activity.statut}
                                                    </span>
                                                </h6>
                                                <p class="card-text text-muted mb-2">
                                                    <small>
                                                        <i class="bi bi-file-earmark-text me-1"></i>
                                                        Contrat: ${activity.contrat}
                                                        | <i class="bi bi-person me-1"></i>
                                                        ${activity.partenaire}
                                                    </small>
                                                </p>
                                                ${activity.details ? `<p class="card-text mb-2">${activity.details}</p>` : ''}
                                                <div class="d-flex flex-wrap gap-2">
                                                    ${activity.lienUrl ? `
                                                    <a href="${activity.lienUrl}" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-link me-1"></i> Voir le lien
                                                    </a>
                                                    ` : ''}
                                                    <span class="badge bg-${activity.cout > 0 ? 'danger' : 'success'}">
                                                        <i class="bi bi-cash me-1"></i> ${coutDisplay}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    timelineHTML += `</div>`;
                });
            }
            
            timelineContent.innerHTML = timelineHTML;
        }
        
        // Charger les données pour modification
        function loadEditData(activiteId) {
            console.log('Chargement des données pour l\'activité ID:', activiteId);
            
            fetch(`../models/get/get-activite.php?id=${activiteId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Données reçues:', data);
                    
                    if (data.success && data.activite) {
                        const activite = data.activite;
                        console.log('Activité à éditer:', activite);
                        
                        document.getElementById('editId').value = activiteId;
                        
                        let html = `
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date d'activité <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="date_activite" value="${activite.date_activite || ''}" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type d'activité <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="description_activite" value="${activite.description_activite || ''}" required>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Détails</label>
                                    <textarea class="form-control" name="details" rows="3">${activite.details || ''}</textarea>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Lien URL</label>
                                    <input type="url" class="form-control" name="lien_url" value="${activite.lien_url || ''}">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Coût ($)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="cout" value="${activite.cout || 0}" step="0.01" min="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Statut</label>
                                    <select class="form-select" name="statut_activite">
                                        <option value="Planifiée" ${activite.statut_activite == 'Planifiée' ? 'selected' : ''}>Planifiée</option>
                                        <option value="Réalisée" ${activite.statut_activite == 'Réalisée' ? 'selected' : ''}>Réalisée</option>
                                        <option value="Annulée" ${activite.statut_activite == 'Annulée' ? 'selected' : ''}>Annulée</option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="contrat_id" value="${activite.contrat_id || <?= $contrat_id ?>}">
                        `;
                        
                        document.getElementById('editModalBody').innerHTML = html;
                        console.log('HTML inséré avec succès');
                    } else {
                        console.error('Erreur dans la réponse:', data);
                        alert('Erreur lors du chargement des données de l\'activité: ' + (data.message || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur fetch:', error);
                    alert('Erreur lors du chargement des données. Vérifiez la console pour plus de détails.');
                });
        }
        
        // Changer le statut d'une activité
        function changerStatut(activiteId, nouvelStatut, description) {
            if (confirm(`Êtes-vous sûr de vouloir marquer l'activité "${description}" comme "${nouvelStatut}" ?`)) {
                const formData = new FormData();
                formData.append('activite_id', activiteId);
                formData.append('statut_activite', nouvelStatut);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                fetch('../models/update/change-statut-activite.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Statut modifié avec succès');
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
        function setDeleteId(id, description) {
            document.getElementById('deleteActiviteName').textContent = description;
            const deleteUrl = `../models/delete/delete-activite.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Validation du formulaire d'ajout
        document.getElementById('addForm')?.addEventListener('submit', function(e) {
            const dateActivite = this.querySelector('[name="date_activite"]').value;
            const description = this.querySelector('[name="description_activite"]').value;
            const contratId = this.querySelector('[name="contrat_id"]').value;
            
            if (!dateActivite) {
                e.preventDefault();
                alert('La date d\'activité est requise');
                return false;
            }
            
            if (!description) {
                e.preventDefault();
                alert('Le type d\'activité est requis');
                return false;
            }
            
            <?php if (!$contrat_id): ?>
            if (!contratId) {
                e.preventDefault();
                alert('Le contrat est requis');
                return false;
            }
            <?php endif; ?>
            
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
        
        // Fonction pour exporter les activités
        function exportToExcel() {
            const visibleRows = document.querySelectorAll('.table-row.activity-row[style*="display: table-row"]');
            
            if (visibleRows.length === 0) {
                alert("Aucune activité à exporter");
                return;
            }
            
            let csv = [];
            
            // Entête
            const headers = ['Date', 'Contrat', 'Partenaire', 'Type d\'activité', 'Détails', 'Coût', 'Statut'];
            csv.push(headers.join(';'));
            
            // Données
            visibleRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const rowData = [
                    cells[1].textContent,
                    cells[2].querySelector('a span').textContent,
                    cells[2].querySelector('small').textContent,
                    cells[3].textContent,
                    cells[4].textContent.replace(/;/g, ','),
                    cells[6].textContent.replace('$', '').trim(),
                    cells[7].querySelector('.badge').textContent
                ];
                csv.push(rowData.join(';'));
            });
            
            // Téléchargement
            const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + csv.join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "activites_" + new Date().toISOString().slice(0, 10) + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Fonction pour imprimer
        function printTable() {
            const activeTab = document.querySelector('#viewTabs .nav-link.active').id;
            
            if (activeTab === 'timeline-tab') {
                // Imprimer la timeline
                const printWindow = window.open('', '_blank');
                const timelineContent = document.getElementById('timelineContent').innerHTML;
                
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Chronologie des activités - ${new Date().toLocaleDateString()}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            h1 { color: #0d6efd; }
                            .activity-item { margin-bottom: 20px; padding-left: 30px; position: relative; }
                            .activity-item::before { content: ''; position: absolute; left: 0; top: 10px; width: 12px; height: 12px; border-radius: 50%; background: #0d6efd; }
                            .card { border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin-bottom: 10px; }
                            .badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <h1>Chronologie des activités - Evotech Africa</h1>
                        <h3>Date d'édition: ${new Date().toLocaleDateString()}</h3>
                        ${timelineContent}
                    </body>
                    </html>
                `);
            } else {
                // Imprimer le tableau
                const printWindow = window.open('', '_blank');
                const table = document.getElementById('activitesTable');
                
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Liste des activités - ${new Date().toLocaleDateString()}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            h1 { color: #0d6efd; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                            th { background-color: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; text-align: left; }
                            td { padding: 8px; border: 1px solid #dee2e6; }
                        </style>
                    </head>
                    <body>
                        <h1>Liste des activités - Evotech Africa</h1>
                        <h3>Date d'édition: ${new Date().toLocaleDateString()}</h3>
                        ${table.outerHTML}
                    </body>
                    </html>
                `);
            }
            
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => printWindow.print(), 250);
        }
        
        // Ajouter les boutons d'export
        const headerSection = document.querySelector('.d-flex.justify-content-between.align-items-center');
        if (headerSection) {
            const exportDiv = document.createElement('div');
            exportDiv.className = 'btn-group ms-3';
            exportDiv.innerHTML = `
                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </button>
                <button type="button" class="btn btn-secondary" onclick="printTable()">
                    <i class="bi bi-printer me-1"></i> Imprimer
                </button>
            `;
            headerSection.appendChild(exportDiv);
        }
        
        // Test de débogage - vérifier que le script charge
        console.log('Script activites-contrat.php chargé');
        
        // Test de la fonction loadEditData
        function testLoadEditData() {
            // Récupérer un ID d'activité valide pour tester
            const firstRow = document.querySelector('.table-row.activity-row');
            if (firstRow) {
                const editButton = firstRow.querySelector('button[onclick*="loadEditData"]');
                if (editButton) {
                    const onclick = editButton.getAttribute('onclick');
                    const match = onclick.match(/loadEditData\((\d+)\)/);
                    if (match && match[1]) {
                        console.log('ID trouvé pour test:', match[1]);
                        // Décommentez la ligne suivante pour tester automatiquement
                        // loadEditData(match[1]);
                    }
                }
            }
        }
        
        // Exécuter le test après le chargement
        setTimeout(testLoadEditData, 1000);
    </script>
</body>
</html>