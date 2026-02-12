<?php
// Démarrage de session en premier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Permissions pour cette page (agents)
$canAccessAgents = hasPermission(['DG', 'ChefDepartement'], $userRoles);
if (!$canAccessAgents) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveAgent = 1;
$ActiveDashboard = 0;
$ActiveUtilisateur = 0;
$ActiveRole = 0;
$ActiveDepartement = 0;
$ActiveAttribution = 0;
$ActiveProjet = 0;
$ActiveTache = 0;
$ActivePartenaire = 0;
$ActiveParticipation = 0;
$ActiveProduction = 0;
$ActiveTimesheet = 0;
$ActiveContrat = 0;
$ActiveActivite = 0;
$ActiveCompte = 0;
$ActiveMouvement = 0;
$ActiveBudget = 0;
$ActiveRapport = 0;
$ActiveParametre = 0;
$ActiveLog = 0;
$ActiveSauvegarde = 0;
$ActiveSecurite = 0;
$ActiveStatistique = 0;

# Récupérer les agents avec leurs départements
try {
    $query = "SELECT 
                a.agent_id,
                a.nom,
                a.prenom,
                a.date_naissance,
                a.telephone_personnel,
                a.telephone_professionnel,
                a.email_personnel,
                a.email_professionnel,
                a.situation_matrimoniale,
                a.nombre_enfants,
                a.urgence_nom,
                a.urgence_telephone,
                a.photo_profil,
                a.cv_path,
                a.etat,
                a.date_embauche,
                a.date_depart,
                a.date_creation,
                d.nom as departement_nom,
                d.code as departement_code
              FROM AGENTS a
              LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id
              WHERE a.statut = 0
              ORDER BY a.date_creation DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    # Compter les statistiques
    $totalAgents = count($agents);
    $activeAgents = 0;
    $inactiveAgents = 0;
    $suspendedAgents = 0;
    $agentsWithDepartement = 0;

    foreach ($agents as $agent) {
        switch ($agent['etat']) {
            case 'Actif':
                $activeAgents++;
                break;
            case 'Inactif':
                $inactiveAgents++;
                break;
            case 'Suspendu':
                $suspendedAgents++;
                break;
        }

        if (!empty($agent['departement_nom'])) {
            $agentsWithDepartement++;
        }
    }

    # Récupérer tous les départements pour le formulaire
    $queryDepartements = "SELECT departement_id, code, nom 
                         FROM DEPARTEMENTS 
                         WHERE statut = 0 
                         ORDER BY nom";
    $stmtDepartements = $conn->prepare($queryDepartements);
    $stmtDepartements->execute();
    $allDepartements = $stmtDepartements->fetchAll(PDO::FETCH_ASSOC);

    # Récupérer les situations matrimoniales possibles
    $situationsMatrimoniales = [
        'Célibataire',
        'Marié(e)',
        'Divorcé(e)',
        'Veuf(ve)',
        'Concubinage',
        'Séparé(e)'
    ];
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des agents: " . $e->getMessage());
    $agents = [];
    $allDepartements = [];
    $situationsMatrimoniales = [];
    $totalAgents = $activeAgents = $inactiveAgents = $suspendedAgents = $agentsWithDepartement = 0;
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
    <title>Gestion des Agents - Evotech Africa</title>

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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding-left: 280px; /* Space for sidebar */
            overflow-x: hidden;
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
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        #sidebar::-webkit-scrollbar {
            width: 6px;
        }

        #sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #sidebar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        #sidebar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
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
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: white;
            flex-shrink: 0;
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
            flex-shrink: 0;
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
            display: block;
        }

        .sidebar-item {
            margin: 0;
        }

        .sidebar-link {
            color: #495057;
            padding: 14px 25px;
            display: flex;
            align-items: center;
            text-decoration: none !important;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-size: 0.95rem;
            position: relative;
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
            flex-shrink: 0;
        }

        /* Submenu styles */
        .sidebar-item.has-sub .sidebar-link::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: auto;
            font-size: 0.8rem;
            color: #adb5bd;
            transition: transform 0.3s ease;
            flex-shrink: 0;
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
            list-style: none;
        }

        .submenu li {
            list-style: none !important;
        }

        .submenu a {
            list-style: none !important;
            text-decoration: none !important;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .sidebar-item.has-sub.active .submenu {
            display: block;
        }

        .submenu a {
            color: #6c757d;
            text-decoration: none !important;
            display: block;
            padding: 10px 25px 10px 20px;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            border-left: 2px solid transparent;
            position: relative;
            list-style: none !important;
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

        .submenu a::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            background: #adb5bd;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .submenu a:hover::before {
            background: #1e40af;
            transform: translateY(-50%) scale(1.2);
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
            flex-shrink: 0;
        }

        /* Main content styles */
        .main-content-wrapper {
            min-height: 100vh;
            padding: 20px;
            max-width: calc(100% - 280px);
            width: 100%;
        }

        @media (max-width: 992px) {
            .main-content-wrapper {
                max-width: 100%;
                padding-top: 70px;
            }
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
            border-radius: 50%;
        }

        .agent-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            height: 100%;
            background: white;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .agent-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-blue-light);
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
            display: inline-block;
        }

        .status-actif {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-inactif {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .status-suspendu {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .search-box {
            position: relative;
            max-width: 400px;
        }

        .search-box .form-control {
            padding-left: 2.5rem;
            border-radius: 25px;
            border: 1px solid #dee2e6;
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
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            background: var(--primary-blue-dark);
        }

        @media (max-width: 992px) {
            .mobile-menu-btn {
                display: flex;
            }
        }

        /* Table styles */
        .table-hover tbody tr:hover {
            background-color: var(--light-blue-bg);
        }

        .table th {
            background-color: var(--light-blue-bg);
            color: var(--primary-blue-dark);
            font-weight: 600;
            border-top: none;
            white-space: nowrap;
        }

        /* Alert styles */
        .alert-info {
            background-color: #e7f1ff;
            border-color: #b6d4fe;
            color: var(--primary-blue-dark);
        }

        /* Agent card specific */
        .agent-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background-color: var(--primary-blue);
            color: white;
            margin: 0 auto 1rem;
        }

        .contact-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            font-size: 0.85em;
        }

        .info-item i {
            width: 20px;
            color: var(--primary-blue);
            flex-shrink: 0;
        }

        /* Utility classes */
        .border-bottom {
            border-bottom: 2px solid var(--primary-blue) !important;
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .filter-info {
            background-color: #e8f4fd;
            border-left: 4px solid var(--primary-blue);
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            display: none;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .agent-email {
            font-size: 0.85em;
            color: #6c757d;
        }

        /* Badge styles */
        .badge-departement {
            background-color: var(--primary-blue);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
            display: inline-block;
        }

        /* Modal styles */
        .modal-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            border-bottom: none;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
        }

        .nav-tabs .nav-link {
            color: var(--primary-blue);
            text-decoration: none !important;
        }

        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }

        /* Form styles */
        .form-check-input:checked {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        /* Custom scrollbar for main content */
        .main-content-wrapper::-webkit-scrollbar {
            width: 8px;
        }

        .main-content-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .main-content-wrapper::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .main-content-wrapper::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
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
                <a href="dashboard.php" style="text-decoration: none;">
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
                    
                    <li class="sidebar-item active">
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
                Gestion des Agents/Employés
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un agent..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                    <i class="bi bi-plus-circle me-1"></i> Nouvel Agent
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Agents</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $totalAgents ?>
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
                                    <?= $activeAgents ?>
                                </h2>
                                <small class="text-muted">
                                    <?= $totalAgents > 0 ? round(($activeAgents / $totalAgents) * 100, 1) : 0 ?>%
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
                                <h6 class="card-subtitle mb-2 text-muted">Avec Département</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $agentsWithDepartement ?>
                                </h2>
                                <small class="text-muted">
                                    <?= $totalAgents > 0 ? round(($agentsWithDepartement / $totalAgents) * 100, 1) : 0 ?>%
                                </small>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="bi bi-building"></i>
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
                                    <?= $totalAgents ?>
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
                    Vue cartes des agents
                    <small class="text-white-50 ms-2">(<span id="cardsVisibleCount"><?= $totalAgents ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="cardsNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun agent ne correspond à votre recherche</p>
                </div>

                <!-- Cartes -->
                <div id="cardsContainer">
                    <div class="row" id="agentsCards">
                        <?php foreach ($agents as $agent):
                            $hasDepartement = !empty($agent['departement_nom']);
                            $dateEmbauche = !empty($agent['date_embauche']) ? date('d/m/Y', strtotime($agent['date_embauche'])) : 'Non définie';
                            $age = !empty($agent['date_naissance']) ?
                                floor((time() - strtotime($agent['date_naissance'])) / 31556926) : 'N/A';
                        ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4 agent-card-item"
                                data-nom="<?= htmlspecialchars(strtolower($agent['nom'] . ' ' . $agent['prenom'])) ?>"
                                data-email="<?= htmlspecialchars(strtolower($agent['email_professionnel'] ?? $agent['email_personnel'] ?? '')) ?>"
                                data-departement="<?= $hasDepartement ? htmlspecialchars(strtolower($agent['departement_nom'])) : '' ?>"
                                data-telephone="<?= htmlspecialchars(strtolower($agent['telephone_professionnel'] ?? $agent['telephone_personnel'] ?? '')) ?>"
                                data-etat="<?= htmlspecialchars(strtolower($agent['etat'])) ?>">
                                <div class="agent-card h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <span class="badge <?=
                                                                $agent['etat'] == 'Actif' ? 'bg-success' : ($agent['etat'] == 'Inactif' ? 'bg-danger' : 'bg-warning')
                                                                ?>">
                                                <?= htmlspecialchars($agent['etat']) ?>
                                            </span>
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
                                                        onclick="loadEditData(<?= $agent['agent_id'] ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#editAgentModal">
                                                        <i class="bi bi-pencil me-2"></i>Modifier
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#"
                                                        onclick="setDeleteId(<?= $agent['agent_id'] ?>, '<?= htmlspecialchars(addslashes($agent['nom'] . ' ' . $agent['prenom'])) ?>')"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                        <i class="bi bi-trash me-2"></i>Supprimer
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                        onclick="viewAgentDetails(<?= $agent['agent_id'] ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#viewAgentModal">
                                                        <i class="bi bi-eye me-2"></i>Voir détails
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-center mb-3">
                                        <?php if (!empty($agent['photo_profil'])): ?>
                                            <img src="../images/profil/agents/<?= htmlspecialchars($agent['photo_profil']) ?>"
                                                alt="Photo"
                                                class="rounded-circle profile-img"
                                                onerror="this.src='../images/profil/default.png'">
                                        <?php else: ?>
                                            <div class="agent-icon">
                                                <i class="bi bi-person-circle"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <h5 class="text-center mb-1 text-primary">
                                        <?= htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']) ?>
                                    </h5>

                                    <p class="text-center text-muted small mb-3">
                                        <?php if (!empty($agent['email_professionnel'])): ?>
                                            <?= htmlspecialchars($agent['email_professionnel']) ?>
                                        <?php elseif (!empty($agent['email_personnel'])): ?>
                                            <?= htmlspecialchars($agent['email_personnel']) ?>
                                        <?php else: ?>
                                            Aucun email
                                        <?php endif; ?>
                                    </p>

                                    <?php if ($hasDepartement): ?>
                                        <div class="text-center mb-3">
                                            <span class="badge bg-primary">
                                                <i class="bi bi-building me-1"></i>
                                                <?= htmlspecialchars($agent['departement_nom']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="contact-info">
                                        <div class="info-item">
                                            <i class="bi bi-telephone"></i>
                                            <span>
                                                <?= !empty($agent['telephone_professionnel']) ?
                                                    htmlspecialchars($agent['telephone_professionnel']) : (!empty($agent['telephone_personnel']) ?
                                                        htmlspecialchars($agent['telephone_personnel']) : 'Non défini') ?>
                                            </span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-calendar"></i>
                                            <span>Embauche: <?= $dateEmbauche ?></span>
                                        </div>
                                        <?php if (!empty($agent['date_naissance'])): ?>
                                            <div class="info-item">
                                                <i class="bi bi-cake"></i>
                                                <span>Âge: <?= $age ?> ans</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                            <small class="text-muted">
                                                ID: #<?= $agent['agent_id'] ?>
                                            </small>
                                            <small class="text-muted">
                                                <?= !empty($agent['situation_matrimoniale']) ?
                                                    htmlspecialchars($agent['situation_matrimoniale']) : 'Célibataire' ?>
                                                <?= $agent['nombre_enfants'] > 0 ? '• ' . $agent['nombre_enfants'] . ' enfant(s)' : '' ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table des agents -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-task me-2"></i>
                    Tableau des agents
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= $totalAgents ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun agent ne correspond à votre recherche</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(1)">
                                        Agent <i class="bi bi-arrow-down-up ms-1"></i>
                                    </button>
                                </th>
                                <th width="80">Photo</th>
                                <th>Email Pro</th>
                                <th>Téléphone</th>
                                <th>Département</th>
                                <th>Statut</th>
                                <th>Date Embauche</th>
                                <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                <th width="140" class="text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="agentsTable">
                            <?php
                            $n = 0;
                            foreach ($agents as $agent):
                                $n++;
                                $hasDepartement = !empty($agent['departement_nom']);
                                $dateEmbauche = !empty($agent['date_embauche']) ?
                                    date('d/m/Y', strtotime($agent['date_embauche'])) : 'Non définie';
                            ?>
                                <tr class="table-row"
                                    data-nom="<?= htmlspecialchars(strtolower($agent['nom'] . ' ' . $agent['prenom'])) ?>"
                                    data-email="<?= htmlspecialchars(strtolower($agent['email_professionnel'] ?? $agent['email_personnel'] ?? '')) ?>"
                                    data-departement="<?= $hasDepartement ? htmlspecialchars(strtolower($agent['departement_nom'])) : '' ?>"
                                    data-telephone="<?= htmlspecialchars(strtolower($agent['telephone_professionnel'] ?? $agent['telephone_personnel'] ?? '')) ?>"
                                    data-etat="<?= htmlspecialchars(strtolower($agent['etat'])) ?>">
                                    <td class="text-center"><?= $n ?></td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($agent['photo_profil'])): ?>
                                            <img src="../images/profil/agents/<?= htmlspecialchars($agent['photo_profil']) ?>"
                                                alt="Photo"
                                                class="rounded-circle profile-img"
                                                onerror="this.src='../images/profil/default.png'">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center"
                                                style="width: 45px; height: 45px; color: white;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="agent-email">
                                            <?= !empty($agent['email_professionnel']) ?
                                                htmlspecialchars($agent['email_professionnel']) :
                                                '<span class="text-muted small">Non défini</span>' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= !empty($agent['telephone_professionnel']) ?
                                                htmlspecialchars($agent['telephone_professionnel']) : (!empty($agent['telephone_personnel']) ?
                                                    htmlspecialchars($agent['telephone_personnel']) : 'Non défini') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($hasDepartement): ?>
                                            <span class="badge-departement">
                                                <?= htmlspecialchars($agent['departement_nom']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Non attribué</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($agent['etat'] == 'Actif'): ?>
                                            <span class="status-badge status-actif">
                                                <i class="bi bi-check-circle me-1"></i>Actif
                                            </span>
                                        <?php elseif ($agent['etat'] == 'Inactif'): ?>
                                            <span class="status-badge status-inactif">
                                                <i class="bi bi-x-circle me-1"></i>Inactif
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-suspendu">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Suspendu
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= $dateEmbauche ?></small>
                                    </td>
                                    <?php if (hasPermission(['DG', 'ChefDepartement'], $userRoles)): ?>
                                    <td class="text-center action-buttons">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button"
                                                class="btn btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editAgentModal"
                                                onclick="loadEditData(<?= $agent['agent_id'] ?>)"
                                                title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-outline-info"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewAgentModal"
                                                onclick="viewAgentDetails(<?= $agent['agent_id'] ?>)"
                                                title="Voir détails">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal"
                                                onclick="setDeleteId(<?= $agent['agent_id'] ?>, '<?= htmlspecialchars(addslashes($agent['nom'] . ' ' . $agent['prenom'])) ?>')"
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
    <!-- Modal Ajout Agent -->
    <div class="modal fade" id="addAgentModal" tabindex="-1" aria-labelledby="addAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- ... Le contenu du modal reste identique ... -->
            </div>
        </div>
    </div>

    <!-- Modal Modification Agent -->
    <div class="modal fade" id="editAgentModal" tabindex="-1" aria-labelledby="editAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- ... Le contenu du modal reste identique ... -->
            </div>
        </div>
    </div>

    <!-- Modal Suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- ... Le contenu du modal reste identique ... -->
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Voir Détails Agent -->
    <div class="modal fade" id="viewAgentModal" tabindex="-1" aria-labelledby="viewAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- ... Le contenu du modal reste identique ... -->
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
            
            // Fermer les sous-menus quand on clique ailleurs
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.sidebar-item.has-sub')) {
                    hasSubItems.forEach(item => {
                        item.classList.remove('active');
                    });
                }
            });
        });

        // Le reste de votre code JavaScript reste identique...
        // ... (Toutes les fonctions: filterTable, sortTable, loadEditData, viewAgentDetails, etc.)
        
        // Fonction pour préparer la suppression
        function setDeleteId(id, agentName) {
            document.getElementById('deleteAgentName').textContent = agentName;

            // Mettre à jour le lien de suppression
            var deleteUrl = '../models/delete/delete-agent.php?id=' + id + '&csrf_token=<?= $_SESSION['csrf_token'] ?>';
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
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
</body>
</html>