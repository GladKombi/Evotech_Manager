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
$canAccessRoles = hasPermission(['DG', 'Chef_projet'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveProjets = 1;

try {
    // Récupérer les participations avec toutes les informations nécessaires
    $query = "
        SELECT 
            p.participation_id,
            p.agent_id,
            p.projet_id,
            p.date_creation,
            pr.reference,
            pr.titre as projet_titre,
            pr.etat as projet_etat,
            CONCAT(a.nom, ' ', a.prenom) as agent_nom,
            a.email_professionnel,
            d.nom as departement_nom,
            d.departement_id,
            (
                SELECT COUNT(*) 
                FROM TACHES t 
                WHERE t.projet_id = p.projet_id 
                AND t.assigne_a = p.agent_id 
                AND t.statut = 0
            ) as nb_taches_assignees,
            (
                SELECT COUNT(*) 
                FROM TIMESHEET ts 
                WHERE ts.agent_id = p.agent_id 
                AND EXISTS (
                    SELECT 1 FROM TACHES t2 
                    WHERE t2.tache_id = ts.tache_id 
                    AND t2.projet_id = p.projet_id
                )
                AND ts.statut = 0
            ) as nb_timesheets
        FROM PARTICIPATION p
        LEFT JOIN PROJETS pr ON p.projet_id = pr.projet_id AND pr.statut = 0
        LEFT JOIN AGENTS a ON p.agent_id = a.agent_id AND a.statut = 0
        LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id AND d.statut = 0
        WHERE p.statut = 0
        ORDER BY p.date_creation DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les projets disponibles
    $queryProjets = "
        SELECT projet_id, reference, titre, etat 
        FROM PROJETS 
        WHERE statut = 0 
        AND etat IN ('En cours', 'Suspendu')
        ORDER BY reference
    ";
    $stmtProjets = $conn->prepare($queryProjets);
    $stmtProjets->execute();
    $projetsList = $stmtProjets->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les agents disponibles
    $queryAgents = "
        SELECT 
            a.agent_id,
            CONCAT(a.nom, ' ', a.prenom) as nom_complet,
            a.email_professionnel,
            d.nom as departement_nom,
            (
                SELECT COUNT(*) 
                FROM PARTICIPATION p 
                WHERE p.agent_id = a.agent_id 
                AND p.statut = 0
            ) as nb_projets
        FROM AGENTS a
        LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id AND d.statut = 0
        WHERE a.statut = 0 
        AND a.etat = 'Actif'
        ORDER BY a.nom, a.prenom
    ";
    $stmtAgents = $conn->prepare($queryAgents);
    $stmtAgents->execute();
    $agentsList = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques
    $queryStats = "
        SELECT 
            COUNT(*) as total_participations,
            COUNT(DISTINCT a.agent_id) as agents_actifs,
            COUNT(DISTINCT pr.projet_id) as projets_avec_participants,
            (
                SELECT AVG(projet_count) 
                FROM (
                    SELECT COUNT(DISTINCT projet_id) as projet_count
                    FROM PARTICIPATION 
                    WHERE statut = 0 
                    GROUP BY agent_id
                ) agent_projects
            ) as moyenne_projets_par_agent
        FROM PARTICIPATION p
        LEFT JOIN AGENTS a ON p.agent_id = a.agent_id AND a.statut = 0
        LEFT JOIN PROJETS pr ON p.projet_id = pr.projet_id AND pr.statut = 0
        WHERE p.statut = 0
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données: " . $e->getMessage());
    $participations = [];
    $projetsList = [];
    $agentsList = [];
    $stats = [
        'total_participations' => 0, 
        'agents_actifs' => 0, 
        'projets_avec_participants' => 0,
        'moyenne_projets_par_agent' => 0
    ];
    
    // Afficher l'erreur en développement
    if (isset($_SESSION['debug_mode']) && $_SESSION['debug_mode']) {
        echo "<!-- ERREUR SQL: " . $e->getMessage() . " -->";
    }
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
    <title>Gestion des Participations - Evotech Africa</title>
    
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

        /* Badges spécifiques */
        .badge-departement {
            background-color: #20c997;
            color: white;
        }
        
        .badge-projet {
            background-color: var(--primary-blue);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .badge-tache {
            background-color: #fd7e14;
            color: white;
        }
        
        .badge-timesheet {
            background-color: #6f42c1;
            color: white;
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

        .metric-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .metric-card:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }
        
        .metric-card .number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .metric-card .label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .agent-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }
        
        /* Badges pour l'état du projet */
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
                                    <li class="active">
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
                <i class="fas fa-user-friends me-2"></i>
                Gestion des Participations
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher une participation..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addParticipationModal">
                        <i class="fas fa-user-plus me-1"></i> Nouvelle Participation
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
            <div class="col-md-3">
                <div class="card stat-card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Participations</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $stats['total_participations'] ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-user-friends"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Agents actifs</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $stats['agents_actifs'] ?>
                                </h2>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-user-tie"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Projets</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $stats['projets_avec_participants'] ?>
                                </h2>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-project-diagram"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Moy. projets/agent</h6>
                                <h2 class="card-title text-info mb-0">
                                    <?= number_format($stats['moyenne_projets_par_agent'] ?? 0, 1) ?>
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="fas fa-chart-line"></i>
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
                <div class="col-md-4">
                    <label class="form-label">Projet</label>
                    <select class="form-select" id="filterProjet">
                        <option value="">Tous les projets</option>
                        <?php foreach ($projetsList as $projet): ?>
                            <option value="<?= $projet['projet_id'] ?>">
                                <?= htmlspecialchars($projet['reference'] . ' - ' . $projet['titre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Agent</label>
                    <select class="form-select" id="filterAgent">
                        <option value="">Tous les agents</option>
                        <?php foreach ($agentsList as $agent): ?>
                            <option value="<?= $agent['agent_id'] ?>">
                                <?= htmlspecialchars($agent['nom_complet']) ?>
                                <?php if ($agent['departement_nom']): ?>
                                    (<?= htmlspecialchars($agent['departement_nom']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Département</label>
                    <select class="form-select" id="filterDepartement">
                        <option value="">Tous les départements</option>
                        <?php
                        // Récupérer les départements uniques
                        $departements = [];
                        foreach ($agentsList as $agent) {
                            if ($agent['departement_nom'] && !in_array($agent['departement_nom'], $departements)) {
                                $departements[] = $agent['departement_nom'];
                            }
                        }
                        sort($departements);
                        foreach ($departements as $departement):
                        ?>
                            <option value="<?= htmlspecialchars($departement) ?>">
                                <?= htmlspecialchars($departement) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Date de début (après)</label>
                    <input type="date" class="form-control" id="filterDateDebut">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date de fin (avant)</label>
                    <input type="date" class="form-control" id="filterDateFin">
                </div>
            </div>
        </div>

        <!-- Table des participations -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list-task me-2"></i>
                    Liste des participations
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($participations) ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="fas fa-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucune participation ne correspond à vos critères</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(1)">
                                        Agent <i class="fas fa-sort ms-1"></i>
                                    </button>
                                </th>
                                <th>Projet</th>
                                <th>Département</th>
                                <th>Date d'ajout</th>
                                <th>Métriques</th>
                                <th width="100" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="participationsTable">
                            <?php
                            $n = 0;
                            foreach ($participations as $participation):
                                $n++;
                                
                                // Générer les initiales de l'agent
                                $agent_initials = '';
                                if ($participation['agent_nom']) {
                                    $names = explode(' ', $participation['agent_nom']);
                                    $agent_initials = strtoupper(
                                        substr($names[0] ?? '', 0, 1) .
                                        substr($names[1] ?? '', 0, 1)
                                    );
                                }
                                
                                // Formater la date
                                $dateCreation = $participation['date_creation'] 
                                    ? date('d/m/Y', strtotime($participation['date_creation'])) 
                                    : '-';
                                
                                // Classes CSS pour l'état du projet
                                $etatClass = 'badge-etat-' . strtolower(str_replace(' ', '-', $participation['projet_etat'] ?? ''));
                                
                                // Valeurs par défaut pour les métriques
                                $nb_taches = $participation['nb_taches_assignees'] ?? 0;
                                $nb_timesheets = $participation['nb_timesheets'] ?? 0;
                            ?>
                            <tr class="table-row" 
                                data-agent="<?= htmlspecialchars(strtolower($participation['agent_nom'])) ?>"
                                data-projet="<?= $participation['projet_id'] ?>"
                                data-departement="<?= htmlspecialchars(strtolower($participation['departement_nom'] ?? '')) ?>"
                                data-date="<?= $participation['date_creation'] ?>"
                                data-nb-taches="<?= $nb_taches ?>"
                                data-nb-timesheets="<?= $nb_timesheets ?>">
                                <td class="text-center"><?= $n ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="agent-avatar me-3">
                                            <?= htmlspecialchars($agent_initials) ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($participation['agent_nom']) ?></strong>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($participation['email_professionnel']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($participation['reference']): ?>
                                        <div>
                                            <span class="badge-projet me-1">
                                                <?= htmlspecialchars($participation['reference']) ?>
                                            </span>
                                            <?php if ($participation['projet_etat']): ?>
                                                <span class="badge <?= $etatClass ?> ms-1">
                                                    <?= htmlspecialchars($participation['projet_etat']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <div class="small mt-1">
                                                <?= htmlspecialchars($participation['projet_titre']) ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Projet supprimé</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($participation['departement_nom']): ?>
                                        <span class="badge badge-departement">
                                            <?= htmlspecialchars($participation['departement_nom']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?= $dateCreation ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <div class="metric-card">
                                            <div class="number text-primary"><?= $nb_taches ?></div>
                                            <div class="label">Tâches</div>
                                        </div>
                                        <div class="metric-card">
                                            <div class="number text-info"><?= $nb_timesheets ?></div>
                                            <div class="label">Timesheets</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal"
                                                onclick="setDeleteId(<?= $participation['participation_id'] ?>, '<?= htmlspecialchars(addslashes($participation['agent_nom'] . ' - ' . $participation['projet_titre'])) ?>')"
                                                title="Retirer la participation">
                                            <i class="fas fa-user-minus"></i>
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
    <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
        <!-- Modal Ajout Participation -->
        <div class="modal fade" id="addParticipationModal" tabindex="-1" aria-labelledby="addParticipationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addParticipationModalLabel">
                            <i class="fas fa-user-plus me-2"></i>
                            Nouvelle Participation
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-participation-post.php" method="POST" id="addForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Information :</strong> Une participation permet d'associer un agent à un projet. L'agent pourra ensuite être assigné à des tâches et soumettre des timesheets pour ce projet.
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Projet <span class="text-danger">*</span></label>
                                    <select class="form-select" name="projet_id" required id="projetSelect">
                                        <option value="">Sélectionner un projet...</option>
                                        <?php foreach ($projetsList as $projet): ?>
                                            <option value="<?= $projet['projet_id'] ?>">
                                                <?= htmlspecialchars($projet['reference'] . ' - ' . $projet['titre']) ?>
                                                <?php if ($projet['etat'] != 'En cours'): ?>
                                                    (<?= $projet['etat'] ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Agent <span class="text-danger">*</span></label>
                                    <select class="form-select" name="agent_id" required id="agentSelect">
                                        <option value="">Sélectionner un agent...</option>
                                        <?php foreach ($agentsList as $agent): ?>
                                            <option value="<?= $agent['agent_id'] ?>" data-departement="<?= htmlspecialchars($agent['departement_nom'] ?? '') ?>">
                                                <?= htmlspecialchars($agent['nom_complet']) ?>
                                                <?php if ($agent['departement_nom']): ?>
                                                    (<?= htmlspecialchars($agent['departement_nom']) ?>)
                                                <?php endif; ?>
                                                <?php if ($agent['nb_projets'] > 0): ?>
                                                    - <?= $agent['nb_projets'] ?> projet(s)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-light border">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Agent sélectionné :</strong>
                                                <span id="selectedAgentInfo" class="text-muted ms-2">Aucun agent sélectionné</span>
                                            </div>
                                            <div>
                                                <strong>Projet sélectionné :</strong>
                                                <span id="selectedProjetInfo" class="text-muted ms-2">Aucun projet sélectionné</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-warning" id="duplicateWarning" style="display: none;">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Attention :</strong> Cette participation existe déjà. L'agent est déjà assigné à ce projet.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-check-circle me-1"></i> Ajouter la participation
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
                            Retirer la participation
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir retirer cette participation :</p>
                        <h5 class="text-danger" id="deleteParticipationInfo"></h5>
                        <p class="text-muted mt-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Cette action est réversible. L'agent ne pourra plus être assigné à de nouvelles tâches sur ce projet.
                        </p>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Note :</strong> Les tâches déjà assignées et les timesheets soumis ne seront pas affectés.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <a id="deleteConfirmBtn" href="" class="btn btn-danger">
                            <i class="fas fa-user-minus me-1"></i> Retirer la participation
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
            initializeModalListeners();
        });

        // Initialisation des filtres
        function initializeFilters() {
            document.getElementById('filterProjet').addEventListener('change', filterParticipations);
            document.getElementById('filterAgent').addEventListener('change', filterParticipations);
            document.getElementById('filterDepartement').addEventListener('change', filterParticipations);
            document.getElementById('filterDateDebut').addEventListener('change', filterParticipations);
            document.getElementById('filterDateFin').addEventListener('change', filterParticipations);
        }

        // Initialisation des listeners du modal
        function initializeModalListeners() {
            const projetSelect = document.getElementById('projetSelect');
            const agentSelect = document.getElementById('agentSelect');
            const submitBtn = document.getElementById('submitBtn');
            const duplicateWarning = document.getElementById('duplicateWarning');
            
            if (projetSelect && agentSelect) {
                projetSelect.addEventListener('change', updateSelectedInfo);
                agentSelect.addEventListener('change', updateSelectedInfo);
                
                // Vérifier les doublons
                function updateSelectedInfo() {
                    const projetId = projetSelect.value;
                    const agentId = agentSelect.value;
                    const selectedAgentOption = agentSelect.options[agentSelect.selectedIndex];
                    const selectedProjetOption = projetSelect.options[projetSelect.selectedIndex];
                    
                    // Mettre à jour les infos affichées
                    document.getElementById('selectedAgentInfo').textContent = 
                        selectedAgentOption.text || 'Aucun agent sélectionné';
                    document.getElementById('selectedProjetInfo').textContent = 
                        selectedProjetOption.text || 'Aucun projet sélectionné';
                    
                    // Vérifier si cette participation existe déjà
                    if (projetId && agentId) {
                        checkDuplicate(projetId, agentId);
                    } else {
                        duplicateWarning.style.display = 'none';
                        submitBtn.disabled = false;
                    }
                }
                
                function checkDuplicate(projetId, agentId) {
                    fetch(`../models/check/check-participation.php?projet_id=${projetId}&agent_id=${agentId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                duplicateWarning.style.display = 'block';
                                submitBtn.disabled = true;
                            } else {
                                duplicateWarning.style.display = 'none';
                                submitBtn.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la vérification:', error);
                            duplicateWarning.style.display = 'none';
                            submitBtn.disabled = false;
                        });
                }
            }
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterParticipations();
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                document.getElementById('filterProjet').value = '';
                document.getElementById('filterAgent').value = '';
                document.getElementById('filterDepartement').value = '';
                document.getElementById('filterDateDebut').value = '';
                document.getElementById('filterDateFin').value = '';
                filterParticipations();
            });
        }
        
        function filterParticipations() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const projetFilter = document.getElementById('filterProjet').value;
            const agentFilter = document.getElementById('filterAgent').value;
            const departementFilter = document.getElementById('filterDepartement').value.toLowerCase();
            const dateDebutFilter = document.getElementById('filterDateDebut').value;
            const dateFinFilter = document.getElementById('filterDateFin').value;
            
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            
            let visibleRows = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (projetFilter) {
                const optionText = document.querySelector('#filterProjet option[value="' + projetFilter + '"]')?.textContent;
                if (optionText) activeFilters.push(`Projet: ${optionText}`);
            }
            if (agentFilter) {
                const optionText = document.querySelector('#filterAgent option[value="' + agentFilter + '"]')?.textContent;
                if (optionText) activeFilters.push(`Agent: ${optionText}`);
            }
            if (departementFilter) activeFilters.push(`Département: ${departementFilter}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const agent = row.getAttribute('data-agent');
                const projet = row.getAttribute('data-projet');
                const departement = row.getAttribute('data-departement');
                const date = row.getAttribute('data-date');
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && !agent.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre projet
                if (projetFilter && projet !== projetFilter) {
                    matches = false;
                }
                
                // Filtre agent
                if (agentFilter) {
                    const agentId = row.querySelector('strong')?.closest('tr')?.getAttribute('data-agent-id') || '';
                    if (agentId !== agentFilter) {
                        matches = false;
                    }
                }
                
                // Filtre département
                if (departementFilter && !departement.includes(departementFilter)) {
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
            const table = document.getElementById('participationsTable');
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
                    // Colonne Agent
                    aText = a.querySelector('strong')?.textContent.toLowerCase() || '';
                    bText = b.querySelector('strong')?.textContent.toLowerCase() || '';
                } else if (columnIndex === 2) {
                    // Colonne Projet
                    aText = a.querySelector('.badge-projet')?.textContent || '';
                    bText = b.querySelector('.badge-projet')?.textContent || '';
                } else if (columnIndex === 3) {
                    // Colonne Département
                    aText = a.querySelector('.badge-departement')?.textContent || '';
                    bText = b.querySelector('.badge-departement')?.textContent || '';
                } else if (columnIndex === 4) {
                    // Colonne Date
                    aText = a.getAttribute('data-date') || '';
                    bText = b.getAttribute('data-date') || '';
                } else if (columnIndex === 5) {
                    // Colonne Tâches (numérique)
                    aText = parseInt(a.getAttribute('data-nb-taches')) || 0;
                    bText = parseInt(b.getAttribute('data-nb-taches')) || 0;
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
        
        // Préparer la suppression
        function setDeleteId(id, info) {
            document.getElementById('deleteParticipationInfo').textContent = info;
            
            // Mettre à jour le lien de suppression
            const deleteUrl = `../models/delete/delete-participation.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Validation du formulaire d'ajout
        document.getElementById('addForm')?.addEventListener('submit', function(e) {
            const projetId = this.querySelector('[name="projet_id"]').value;
            const agentId = this.querySelector('[name="agent_id"]').value;
            const submitBtn = document.getElementById('submitBtn');
            
            if (!projetId) {
                e.preventDefault();
                alert('Veuillez sélectionner un projet');
                return false;
            }
            
            if (!agentId) {
                e.preventDefault();
                alert('Veuillez sélectionner un agent');
                return false;
            }
            
            // Vérifier si le bouton est désactivé (doublon)
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                alert('Cette participation existe déjà');
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