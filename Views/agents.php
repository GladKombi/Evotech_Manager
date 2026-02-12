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

// Permissions pour cette page (agents)
$canAccessAgents = hasPermission(['DG', 'ChefDepartement'], $userRoles);
if (!$canAccessAgents) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveAgent = 1;

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

        /* Submenu styles */
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

        .agent-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            height: 100%;
            background: white;
            padding: 1.5rem;
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

        /* Styles pour la recherche et filtrage */
        .no-results {
            text-align: center;
            padding: 3rem;
            display: none;
        }

        .no-results i {
            font-size: 3rem;
            color: #dee2e6;
        }

        .filter-info {
            background-color: #e7f1ff;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }

        .filter-info i {
            color: #0d6efd;
        }

        /* Styles pour les modals */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
            padding: 1.5rem;
        }

        /* Responsive pour les modals */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
        }

        /* Indicateur de chargement */
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* Améliorations pour les cartes */
        .agent-card-item {
            transition: all 0.3s ease;
        }

        .table-row {
            transition: all 0.3s ease;
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

        <!-- Information de filtrage -->
        <div class="filter-info" id="filterInfo">
            <i class="bi bi-funnel me-2"></i>
            <span id="filterText">Affichage des résultats filtrés</span>
            <button type="button" class="btn btn-sm btn-outline-primary ms-3" id="clearFilter">
                <i class="bi bi-x-circle me-1"></i> Effacer le filtre
            </button>
        </div>

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
                                            <div class="agent-icon mx-auto bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 60px; height: 60px;">
                                                <i class="bi bi-person-circle text-white"></i>
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

                                    <div class="bg-light rounded p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-telephone text-primary me-2"></i>
                                            <span class="small">
                                                <?= !empty($agent['telephone_professionnel']) ?
                                                    htmlspecialchars($agent['telephone_professionnel']) : (!empty($agent['telephone_personnel']) ?
                                                        htmlspecialchars($agent['telephone_personnel']) : 'Non défini') ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-calendar text-primary me-2"></i>
                                            <span class="small">Embauche: <?= $dateEmbauche ?></span>
                                        </div>
                                        <?php if (!empty($agent['date_naissance'])): ?>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-cake text-primary me-2"></i>
                                                <span class="small">Âge: <?= $age ?> ans</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

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
                                        <span class="small">
                                            <?= !empty($agent['email_professionnel']) ?
                                                htmlspecialchars($agent['email_professionnel']) :
                                                '<span class="text-muted">Non défini</span>' ?>
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
                                            <span class="badge bg-primary">
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
                                        <td class="text-center">
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
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAgentModalLabel">
                            <i class="bi bi-person-plus me-2"></i>
                            Ajouter un nouvel agent
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-Agent-post.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nom" class="form-label">Nom *</label>
                                        <input type="text" class="form-control" id="nom" name="nom" required placeholder="Ex: DUPONT">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="prenom" class="form-label">Prénom *</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" required placeholder="Ex: Jean">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date_naissance" class="form-label">Date de naissance</label>
                                        <input type="date" class="form-control" id="date_naissance" name="date_naissance">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="situation_matrimoniale" class="form-label">Situation matrimoniale</label>
                                        <select class="form-select" id="situation_matrimoniale" name="situation_matrimoniale">
                                            <option value="">Sélectionner...</option>
                                            <?php foreach ($situationsMatrimoniales as $situation): ?>
                                                <option value="<?= $situation ?>"><?= $situation ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre_enfants" class="form-label">Nombre d'enfants</label>
                                        <input type="number" class="form-control" id="nombre_enfants" name="nombre_enfants" min="0" value="0" placeholder="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="departement_id" class="form-label">Département</label>
                                        <select class="form-select" id="departement_id" name="departement_id">
                                            <option value="">Sélectionner un département...</option>
                                            <?php foreach ($allDepartements as $dept): ?>
                                                <option value="<?= $dept['departement_id'] ?>"><?= htmlspecialchars($dept['nom']) ?> (<?= $dept['code'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="telephone_personnel" class="form-label">Téléphone personnel</label>
                                        <input type="tel" class="form-control" id="telephone_personnel" name="telephone_personnel" placeholder="Ex: +243812345678">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="telephone_professionnel" class="form-label">Téléphone professionnel</label>
                                        <input type="tel" class="form-control" id="telephone_professionnel" name="telephone_professionnel" placeholder="Ex: +243812345678">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email_personnel" class="form-label">Email personnel</label>
                                        <input type="email" class="form-control" id="email_personnel" name="email_personnel" placeholder="Ex: jean@example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email_professionnel" class="form-label">Email professionnel *</label>
                                        <input type="email" class="form-control" id="email_professionnel" name="email_professionnel" required placeholder="Ex: j.dupont@entreprise.com">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="etat" class="form-label">État</label>
                                        <select class="form-select" id="etat" name="etat">
                                            <option value="Actif">Actif</option>
                                            <option value="Inactif">Inactif</option>
                                            <option value="Suspendu">Suspendu</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="urgence_nom" class="form-label">Nom du contact d'urgence</label>
                                        <input type="text" class="form-control" id="urgence_nom" name="urgence_nom" placeholder="Nom du contact d'urgence">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="urgence_telephone" class="form-label">Téléphone d'urgence</label>
                                        <input type="tel" class="form-control" id="urgence_telephone" name="urgence_telephone" placeholder="Ex: +243812345678">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="photo_profil" class="form-label">Photo de profil</label>
                                        <input type="file" class="form-control" id="photo_profil" name="photo_profil" accept="image/*">
                                        <small class="text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cv" class="form-label">CV (PDF)</label>
                                        <input type="file" class="form-control" id="cv" name="cv_path" accept=".pdf">
                                        <small class="text-muted">Format accepté: PDF (max 5MB)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Modification Agent -->
        <div class="modal fade" id="editAgentModal" tabindex="-1" aria-labelledby="editAgentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAgentModalLabel">
                            <i class="bi bi-person-gear me-2"></i>
                            Modifier l'agent
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-Agent-post.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" id="edit_agent_id" name="agent_id">
                            <input type="hidden" name="action" value="edit">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_nom" class="form-label">Nom *</label>
                                        <input type="text" class="form-control" id="edit_nom" name="nom" required placeholder="Ex: DUPONT">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_prenom" class="form-label">Prénom *</label>
                                        <input type="text" class="form-control" id="edit_prenom" name="prenom" required placeholder="Ex: Jean">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_date_naissance" class="form-label">Date de naissance</label>
                                        <input type="date" class="form-control" id="edit_date_naissance" name="date_naissance">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_situation_matrimoniale" class="form-label">Situation matrimoniale</label>
                                        <select class="form-select" id="edit_situation_matrimoniale" name="situation_matrimoniale">
                                            <option value="">Sélectionner...</option>
                                            <?php foreach ($situationsMatrimoniales as $situation): ?>
                                                <option value="<?= $situation ?>"><?= $situation ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_nombre_enfants" class="form-label">Nombre d'enfants</label>
                                        <input type="number" class="form-control" id="edit_nombre_enfants" name="nombre_enfants" min="0" value="0" placeholder="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_departement_id" class="form-label">Département</label>
                                        <select class="form-select" id="edit_departement_id" name="departement_id">
                                            <option value="">Sélectionner un département...</option>
                                            <?php foreach ($allDepartements as $dept): ?>
                                                <option value="<?= $dept['departement_id'] ?>"><?= htmlspecialchars($dept['nom']) ?> (<?= $dept['code'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_telephone_personnel" class="form-label">Téléphone personnel</label>
                                        <input type="tel" class="form-control" id="edit_telephone_personnel" name="telephone_personnel" placeholder="Ex: +243812345678">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_telephone_professionnel" class="form-label">Téléphone professionnel</label>
                                        <input type="tel" class="form-control" id="edit_telephone_professionnel" name="telephone_professionnel" placeholder="Ex: +243812345678">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_email_personnel" class="form-label">Email personnel</label>
                                        <input type="email" class="form-control" id="edit_email_personnel" name="email_personnel" placeholder="Ex: jean@example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_email_professionnel" class="form-label">Email professionnel *</label>
                                        <input type="email" class="form-control" id="edit_email_professionnel" name="email_professionnel" required placeholder="Ex: j.dupont@entreprise.com">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="edit_etat" class="form-label">État</label>
                                        <select class="form-select" id="edit_etat" name="etat">
                                            <option value="Actif">Actif</option>
                                            <option value="Inactif">Inactif</option>
                                            <option value="Suspendu">Suspendu</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_urgence_nom" class="form-label">Nom du contact d'urgence</label>
                                        <input type="text" class="form-control" id="edit_urgence_nom" name="urgence_nom" placeholder="Nom du contact d'urgence">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_urgence_telephone" class="form-label">Téléphone d'urgence</label>
                                        <input type="tel" class="form-control" id="edit_urgence_telephone" name="urgence_telephone" placeholder="Ex: +243812345678">
                                    </div>
                                </div>
                            </div>

                            <!-- Section pour la photo actuelle -->
                            <div id="currentPhoto"></div>

                            <div class="mb-3">
                                <label for="edit_photo_profil" class="form-label">Nouvelle photo de profil</label>
                                <input type="file" class="form-control" id="edit_photo_profil" name="photo_profil" accept="image/*">
                                <small class="text-muted">Laisser vide pour conserver la photo actuelle</small>
                            </div>

                            <!-- Section pour le CV actuel -->
                            <div id="currentCv"></div>

                            <div class="mb-3">
                                <label for="edit_cv" class="form-label">Nouveau CV (PDF)</label>
                                <input type="file" class="form-control" id="edit_cv" name="cv_path" accept=".pdf">
                                <small class="text-muted">Laisser vide pour conserver le CV actuel</small>
                            </div>

                            <div class="mb-3">
                                <label for="edit_date_depart" class="form-label">Date de départ</label>
                                <input type="date" class="form-control" id="edit_date_depart" name="date_depart">
                                <small class="text-muted">À remplir uniquement si l'agent quitte l'entreprise</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Mettre à jour
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
                        <h5 class="modal-title text-white" id="deleteModalLabel">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Confirmer la suppression
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
                        </div>
                        <p class="text-center">
                            Êtes-vous sûr de vouloir supprimer l'agent <strong id="deleteAgentName"></strong> ?
                        </p>
                        <p class="text-muted text-center small">
                            Cette action est irréversible. Toutes les données associées à cet agent seront supprimées.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <a href="#" id="deleteConfirmBtn" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Supprimer définitivement
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal Voir Détails Agent -->
    <div class="modal fade" id="viewAgentModal" tabindex="-1" aria-labelledby="viewAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewAgentModalLabel">
                        <i class="bi bi-person-vcard me-2"></i>
                        Détails de l'agent
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewAgentDetails">
                    <!-- Les détails seront chargés dynamiquement ici -->
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

            // Initialiser le filtrage
            filterTable('');
        });

        // Fonction de recherche/filtrage
        function filterTable(searchTerm) {
            searchTerm = searchTerm.toLowerCase();
            let visibleCards = 0;
            let visibleRows = 0;

            // Filtrer les cartes
            const cardItems = document.querySelectorAll('.agent-card-item');
            cardItems.forEach(card => {
                const nom = card.getAttribute('data-nom') || '';
                const email = card.getAttribute('data-email') || '';
                const departement = card.getAttribute('data-departement') || '';
                const telephone = card.getAttribute('data-telephone') || '';
                const etat = card.getAttribute('data-etat') || '';

                const matches = nom.includes(searchTerm) ||
                               email.includes(searchTerm) ||
                               departement.includes(searchTerm) ||
                               telephone.includes(searchTerm) ||
                               etat.includes(searchTerm);

                if (matches || searchTerm === '') {
                    card.style.display = 'block';
                    visibleCards++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Filtrer les lignes du tableau
            const tableRows = document.querySelectorAll('.table-row');
            tableRows.forEach(row => {
                const nom = row.getAttribute('data-nom') || '';
                const email = row.getAttribute('data-email') || '';
                const departement = row.getAttribute('data-departement') || '';
                const telephone = row.getAttribute('data-telephone') || '';
                const etat = row.getAttribute('data-etat') || '';

                const matches = nom.includes(searchTerm) ||
                               email.includes(searchTerm) ||
                               departement.includes(searchTerm) ||
                               telephone.includes(searchTerm) ||
                               etat.includes(searchTerm);

                if (matches || searchTerm === '') {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Mettre à jour les compteurs
            document.getElementById('cardsVisibleCount').textContent = visibleCards;
            document.getElementById('tableVisibleCount').textContent = visibleRows;
            document.getElementById('filteredCount').textContent = visibleCards;

            // Afficher/masquer les messages "Aucun résultat"
            const cardsNoResults = document.getElementById('cardsNoResults');
            const tableNoResults = document.getElementById('tableNoResults');
            
            if (cardsNoResults) {
                cardsNoResults.style.display = visibleCards === 0 ? 'block' : 'none';
            }
            if (tableNoResults) {
                tableNoResults.style.display = visibleRows === 0 ? 'block' : 'none';
            }

            // Afficher/masquer les conteneurs
            const cardsContainer = document.getElementById('cardsContainer');
            const tableContainer = document.getElementById('tableContainer');
            
            if (cardsContainer) {
                cardsContainer.style.display = visibleCards === 0 ? 'none' : 'block';
            }
            if (tableContainer) {
                tableContainer.style.display = visibleRows === 0 ? 'none' : 'block';
            }

            // Gérer l'affichage des informations de filtrage
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            
            if (searchTerm !== '') {
                filterInfo.style.display = 'block';
                filterText.textContent = `Recherche : "${searchTerm}" - ${visibleCards} résultat(s) trouvé(s)`;
            } else {
                filterInfo.style.display = 'none';
            }
        }

        // Gestionnaire d'événement pour la recherche
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const clearFilterBtn = document.getElementById('clearFilter');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    filterTable(this.value);
                });

                // Initialiser le filtrage
                filterTable(searchInput.value);
            }

            if (clearFilterBtn) {
                clearFilterBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    filterTable('');
                    searchInput.focus();
                });
            }

            // Cacher initialement l'info de filtrage
            const filterInfo = document.getElementById('filterInfo');
            if (filterInfo) {
                filterInfo.style.display = 'none';
            }
        });

        // Fonction pour charger les données dans le modal d'édition
        async function loadEditData(agentId) {
            try {
                // Afficher un indicateur de chargement
                const modal = document.getElementById('editAgentModal');
                const modalBody = modal.querySelector('.modal-body');
                const originalContent = modalBody.innerHTML;
                
                modalBody.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-3">Chargement des données de l'agent...</p>
                    </div>
                `;

                const response = await fetch(`../models/fetch/fetch-agent.php?id=${agentId}`);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Remplir le formulaire avec les données
                    const agent = data.agent;
                    
                    // Restaurer le contenu original du modal
                    modalBody.innerHTML = originalContent;
                    
                    // Remplir les champs du formulaire
                    document.getElementById('edit_agent_id').value = agent.agent_id;
                    document.getElementById('edit_nom').value = agent.nom || '';
                    document.getElementById('edit_prenom').value = agent.prenom || '';
                    document.getElementById('edit_date_naissance').value = agent.date_naissance || '';
                    document.getElementById('edit_telephone_personnel').value = agent.telephone_personnel || '';
                    document.getElementById('edit_telephone_professionnel').value = agent.telephone_professionnel || '';
                    document.getElementById('edit_email_personnel').value = agent.email_personnel || '';
                    document.getElementById('edit_email_professionnel').value = agent.email_professionnel || '';
                    document.getElementById('edit_situation_matrimoniale').value = agent.situation_matrimoniale || 'Célibataire';
                    document.getElementById('edit_nombre_enfants').value = agent.nombre_enfants || 0;
                    document.getElementById('edit_urgence_nom').value = agent.urgence_nom || '';
                    document.getElementById('edit_urgence_telephone').value = agent.urgence_telephone || '';
                    const editDateEmbaucheEl = document.getElementById('edit_date_embauche');
                    if (editDateEmbaucheEl) editDateEmbaucheEl.value = agent.date_embauche || '';
                    document.getElementById('edit_etat').value = agent.etat || 'Actif';
                    document.getElementById('edit_date_depart').value = agent.date_depart || '';
                    
                    // Département
                    const departementSelect = document.getElementById('edit_departement_id');
                    if (departementSelect) {
                        departementSelect.value = agent.departement_id || '';
                    }

                    // Afficher l'image actuelle si elle existe
                    const currentPhoto = document.getElementById('currentPhoto');
                    if (currentPhoto && agent.photo_profil) {
                        currentPhoto.innerHTML = `
                            <div class="mb-3">
                                <label class="form-label">Photo actuelle :</label>
                                <div>
                                    <img src="../images/profil/agents/${agent.photo_profil}" 
                                         class="img-thumbnail" 
                                         style="max-width: 150px;"
                                         onerror="this.src='../images/profil/default.png'">
                                </div>
                            </div>
                        `;
                    } else {
                        currentPhoto.innerHTML = '';
                    }
                    
                    // Afficher le CV actuel si il existe
                    const currentCv = document.getElementById('currentCv');
                    if (currentCv && agent.cv_path) {
                        currentCv.innerHTML = `
                            <div class="mb-3">
                                <label class="form-label">CV actuel :</label>
                                <div>
                                    <a href="../uploads/cv/${agent.cv_path}" 
                                       target="_blank" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>Voir le CV actuel
                                    </a>
                                </div>
                            </div>
                        `;
                    } else {
                        currentCv.innerHTML = '';
                    }
                    
                } else {
                    throw new Error(data.message || 'Erreur lors du chargement des données');
                }
            } catch (error) {
                console.error('Erreur:', error);
                
                // Restaurer le contenu original et afficher un message d'erreur
                const modal = document.getElementById('editAgentModal');
                const modalBody = modal.querySelector('.modal-body');
                modalBody.innerHTML = originalContent;
                
                // Ajouter un message d'erreur en haut du formulaire
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger alert-dismissible fade show';
                errorAlert.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Erreur lors du chargement des données : ${error.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const form = modalBody.querySelector('form');
                if (form) {
                    modalBody.insertBefore(errorAlert, form);
                }
            }
        }

        // Fonction pour afficher les détails d'un agent
        async function viewAgentDetails(agentId) {
            try {
                const modal = document.getElementById('viewAgentModal');
                const modalBody = modal.querySelector('.modal-body');
                
                // Afficher l'indicateur de chargement
                modalBody.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-3">Chargement des détails de l'agent...</p>
                    </div>
                `;

                const response = await fetch(`../models/fetch/fetch-agent-details.php?id=${agentId}`);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    const agent = data.agent;
                    
                    // Calculer l'âge
                    let age = 'N/A';
                    if (agent.date_naissance) {
                        const birthDate = new Date(agent.date_naissance);
                        const today = new Date();
                        age = today.getFullYear() - birthDate.getFullYear();
                        const monthDiff = today.getMonth() - birthDate.getMonth();
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }
                    }
                    
                    // Formater les dates
                    const formatDate = (dateString) => {
                        if (!dateString) return 'Non définie';
                        const date = new Date(dateString);
                        return date.toLocaleDateString('fr-FR');
                    };
                    
                    // Créer le contenu HTML
                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-4 text-center">
                                ${agent.photo_profil ? 
                                    `<img src="../images/profil/agents/${agent.photo_profil}" 
                                          class="img-fluid rounded-circle mb-3" 
                                          style="max-width: 200px;"
                                          onerror="this.src='../images/profil/default.png'">` :
                                    `<div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                          style="width: 200px; height: 200px;">
                                        <i class="bi bi-person text-white" style="font-size: 5rem;"></i>
                                    </div>`
                                }
                                <h4 class="mb-1">${agent.nom} ${agent.prenom}</h4>
                                <p class="text-muted">ID: #${agent.agent_id}</p>
                                <span class="badge ${agent.etat === 'Actif' ? 'bg-success' : agent.etat === 'Inactif' ? 'bg-danger' : 'bg-warning'} fs-6">
                                    ${agent.etat || 'Non défini'}
                                </span>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2"></i>Informations Personnelles</h5>
                                        <div class="mb-2"><strong>Date de naissance:</strong> ${formatDate(agent.date_naissance)} (${age} ans)</div>
                                        <div class="mb-2"><strong>Situation matrimoniale:</strong> ${agent.situation_matrimoniale || 'Non définie'}</div>
                                        <div class="mb-2"><strong>Nombre d'enfants:</strong> ${agent.nombre_enfants || 0}</div>
                                        
                                        <h5 class="border-bottom pb-2 mb-3 mt-4"><i class="bi bi-building me-2"></i>Informations Professionnelles</h5>
                                        <div class="mb-2"><strong>Département:</strong> ${agent.departement_nom ? `<span class="badge bg-primary">${agent.departement_nom}</span>` : 'Non attribué'}</div>
                                        <div class="mb-2"><strong>Date d'embauche:</strong> ${formatDate(agent.date_embauche)}</div>
                                        <div class="mb-2"><strong>Date de départ:</strong> ${formatDate(agent.date_depart) || 'Toujours en poste'}</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-telephone me-2"></i>Coordonnées</h5>
                                        <div class="mb-2"><strong>Téléphone personnel:</strong> ${agent.telephone_personnel || 'Non défini'}</div>
                                        <div class="mb-2"><strong>Téléphone professionnel:</strong> ${agent.telephone_professionnel || 'Non défini'}</div>
                                        <div class="mb-2"><strong>Email personnel:</strong> ${agent.email_personnel || 'Non défini'}</div>
                                        <div class="mb-2"><strong>Email professionnel:</strong> ${agent.email_professionnel || 'Non défini'}</div>
                                        
                                        <h5 class="border-bottom pb-2 mb-3 mt-4"><i class="bi bi-exclamation-triangle me-2"></i>Contact d'urgence</h5>
                                        <div class="mb-2"><strong>Nom:</strong> ${agent.urgence_nom || 'Non défini'}</div>
                                        <div class="mb-2"><strong>Téléphone:</strong> ${agent.urgence_telephone || 'Non défini'}</div>
                                        
                                        ${agent.cv_path ? 
                                            `<div class="mt-4">
                                                <a href="../uploads/cv/${agent.cv_path}" 
                                                   target="_blank" 
                                                   class="btn btn-outline-primary">
                                                    <i class="bi bi-file-earmark-pdf me-1"></i>Télécharger le CV
                                                </a>
                                            </div>` : ''
                                        }
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-clock-history me-2"></i>Dates importantes</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card bg-light">
                                                    <div class="card-body text-center">
                                                        <small class="text-muted">Date de création</small>
                                                        <div class="fw-bold">${formatDate(agent.date_creation)}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-light">
                                                    <div class="card-body text-center">
                                                        <small class="text-muted">Dernière modification</small>
                                                        <div class="fw-bold">${formatDate(agent.date_modification) || 'Jamais modifié'}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    throw new Error(data.message || 'Erreur lors du chargement des détails');
                }
            } catch (error) {
                console.error('Erreur:', error);
                
                const modal = document.getElementById('viewAgentModal');
                const modalBody = modal.querySelector('.modal-body');
                
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Erreur lors du chargement des détails : ${error.message}
                    </div>
                `;
            }
        }

        // Fonction de tri du tableau
        function sortTable(columnIndex) {
            const table = document.getElementById('agentsTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            const tbody = table.parentNode;
            
            // Supprimer l'en-tête des lignes
            rows.splice(0, 1);
            
            // Déterminer l'ordre de tri
            const isAscending = table.getAttribute('data-sort-order') !== 'asc';
            
            rows.sort((a, b) => {
                let aValue, bValue;
                
                switch(columnIndex) {
                    case 1: // Nom
                        aValue = a.getAttribute('data-nom') || '';
                        bValue = b.getAttribute('data-nom') || '';
                        break;
                    case 4: // Téléphone
                        aValue = a.getAttribute('data-telephone') || '';
                        bValue = b.getAttribute('data-telephone') || '';
                        break;
                    case 5: // Département
                        aValue = a.getAttribute('data-departement') || '';
                        bValue = b.getAttribute('data-departement') || '';
                        break;
                    case 6: // Statut
                        aValue = a.getAttribute('data-etat') || '';
                        bValue = b.getAttribute('data-etat') || '';
                        break;
                    default:
                        aValue = a.cells[columnIndex]?.textContent.trim() || '';
                        bValue = b.cells[columnIndex]?.textContent.trim() || '';
                }
                
                if (isAscending) {
                    return aValue.localeCompare(bValue, 'fr', { sensitivity: 'base' });
                } else {
                    return bValue.localeCompare(aValue, 'fr', { sensitivity: 'base' });
                }
            });
            
            // Réinsérer les lignes triées
            rows.forEach(row => tbody.appendChild(row));
            
            // Mettre à jour l'indicateur d'ordre de tri
            table.setAttribute('data-sort-order', isAscending ? 'asc' : 'desc');
            
            // Mettre à jour l'icône de tri dans l'en-tête
            const headers = table.parentNode.parentNode.querySelectorAll('th');
            headers.forEach((header, index) => {
                const icon = header.querySelector('i');
                if (icon) {
                    if (index === columnIndex) {
                        icon.className = isAscending ? 'bi bi-arrow-up ms-1' : 'bi bi-arrow-down ms-1';
                    } else {
                        icon.className = 'bi bi-arrow-down-up ms-1';
                    }
                }
            });
        }

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

        // Gestion de la soumission des formulaires modaux
        document.addEventListener('DOMContentLoaded', function() {
            // Réinitialiser les formulaires modaux quand ils sont fermés
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function () {
                    // Réinitialiser uniquement le formulaire d'ajout
                    if (this.id === 'addAgentModal') {
                        const form = this.querySelector('form');
                        if (form) {
                            form.reset();
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>