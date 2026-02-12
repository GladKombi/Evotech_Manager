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

// Fonction pour obtenir l'icône selon le type de production (version PHP)
function getTypeIcon($typeName) {
    $typeLower = strtolower($typeName);
    
    if (strpos($typeLower, 'plan') !== false || strpos($typeLower, 'architecture') !== false || 
        strpos($typeLower, 'dwg') !== false || strpos($typeLower, 'dxf') !== false) {
        return 'fas fa-drafting-compass';
    } else if (strpos($typeLower, 'code') !== false || strpos($typeLower, 'source') !== false || 
               strpos($typeLower, 'programme') !== false) {
        return 'fas fa-code';
    } else if (strpos($typeLower, 'charte') !== false || strpos($typeLower, 'graphique') !== false || 
               strpos($typeLower, 'design') !== false) {
        return 'fas fa-palette';
    } else if (strpos($typeLower, 'rapport') !== false || strpos($typeLower, 'document') !== false || 
               strpos($typeLower, 'pdf') !== false) {
        return 'fas fa-file-alt';
    } else if (strpos($typeLower, 'image') !== false || strpos($typeLower, 'photo') !== false || 
               strpos($typeLower, 'jpg') !== false || strpos($typeLower, 'png') !== false) {
        return 'fas fa-image';
    } else if (strpos($typeLower, 'video') !== false || strpos($typeLower, 'film') !== false || 
               strpos($typeLower, 'mp4') !== false) {
        return 'fas fa-video';
    } else if (strpos($typeLower, 'audio') !== false || strpos($typeLower, 'son') !== false || 
               strpos($typeLower, 'mp3') !== false) {
        return 'fas fa-music';
    } else if (strpos($typeLower, 'archive') !== false || strpos($typeLower, 'zip') !== false || 
               strpos($typeLower, 'rar') !== false) {
        return 'fas fa-file-archive';
    } else if (strpos($typeLower, 'donnée') !== false || strpos($typeLower, 'data') !== false || 
               strpos($typeLower, 'excel') !== false || strpos($typeLower, 'csv') !== false) {
        return 'fas fa-database';
    } else if (strpos($typeLower, 'présentation') !== false || strpos($typeLower, 'powerpoint') !== false || 
               strpos($typeLower, 'ppt') !== false) {
        return 'fas fa-presentation';
    } else {
        return 'fas fa-file';
    }
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

# Récupérer les types de production avec leurs informations
try {
    $query = "
        SELECT 
            tp.*,
            d.nom as departement_nom,
            d.code as departement_code,
            COUNT(p.production_id) as nb_productions
        FROM TYPES_PRODUCTION tp
        LEFT JOIN DEPARTEMENTS d ON tp.departement_id = d.departement_id AND d.statut = 0
        LEFT JOIN PRODUCTION p ON tp.type_prod_id = p.type_prod_id AND p.statut = 0
        WHERE tp.statut = 0
        GROUP BY tp.type_prod_id
        ORDER BY tp.nom
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les statistiques
    $queryStats = "
        SELECT 
            COUNT(*) as total,
            COUNT(DISTINCT departement_id) as departements_concernes,
            SUM(taille_max_mo) as total_taille_max,
            AVG(taille_max_mo) as moyenne_taille_max
        FROM TYPES_PRODUCTION 
        WHERE statut = 0
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les départements pour les filtres et le formulaire
    $queryDepartements = "
        SELECT departement_id, nom, code
        FROM DEPARTEMENTS 
        WHERE statut = 0 
        ORDER BY nom
    ";
    $stmtDepartements = $conn->prepare($queryDepartements);
    $stmtDepartements->execute();
    $departementsList = $stmtDepartements->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les types les plus utilisés
    $queryTopTypes = "
        SELECT 
            tp.type_prod_id,
            tp.nom,
            COUNT(p.production_id) as usage_count
        FROM TYPES_PRODUCTION tp
        LEFT JOIN PRODUCTION p ON tp.type_prod_id = p.type_prod_id AND p.statut = 0
        WHERE tp.statut = 0
        GROUP BY tp.type_prod_id
        ORDER BY usage_count DESC
        LIMIT 5
    ";
    $stmtTopTypes = $conn->prepare($queryTopTypes);
    $stmtTopTypes->execute();
    $topTypes = $stmtTopTypes->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des types de production: " . $e->getMessage());
    $types = [];
    $stats = ['total' => 0, 'departements_concernes' => 0, 'total_taille_max' => 0, 'moyenne_taille_max' => 0];
    $departementsList = [];
    $topTypes = [];
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
    <title>Types de Production - Evotech Africa</title>
    
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

        /* Styles spécifiques aux types de production */
        .type-card {
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
            border-left: 4px solid #3b82f6;
            margin-bottom: 20px;
        }
        
        .type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .type-icon {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            margin: 0 auto 15px;
        }
        
        .type-badge {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .usage-badge {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .extensions-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        
        .extension-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
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
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            display: none;
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .top-types-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            height: 100%;
        }
        
        .top-type-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .top-type-item:last-child {
            border-bottom: none;
        }
        
        .usage-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .usage-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #3b82f6, #1e40af);
        }
        
        .departement-badge {
            background: linear-gradient(135deg, #0d6efd, #3d8bfd);
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

                        <li class="sidebar-item has-sub active">
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
                                    <li class="active">
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
                <i class="fas fa-tags me-2"></i>
                Types de Production
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un type..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                        <i class="fas fa-plus-circle me-1"></i> Nouveau Type
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
                                <h6 class="card-subtitle mb-2 text-muted">Total</h6>
                                <h2 class="card-title text-primary mb-0" id="totalCount">
                                    <?= $stats['total'] ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-tags"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Départements</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $stats['departements_concernes'] ?>
                                </h2>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-building"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Taille max totale</h6>
                                <h2 class="card-title text-info mb-0">
                                    <?= $stats['total_taille_max'] ?> MB
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="fas fa-hdd"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Moyenne taille max</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= round($stats['moyenne_taille_max'], 1) ?> MB
                                </h2>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-chart-bar"></i>
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

        <!-- Section Top Types -->
        <div class="row mb-4">
            <div class="col-md-8">
                <!-- Filtres -->
                <div class="filter-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Département</label>
                            <select class="form-select" id="filterDepartement">
                                <option value="">Tous les départements</option>
                                <?php foreach ($departementsList as $departement): ?>
                                    <option value="<?= $departement['departement_id'] ?>">
                                        <?= htmlspecialchars($departement['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Taille maximale</label>
                            <select class="form-select" id="filterTaille">
                                <option value="">Toutes les tailles</option>
                                <option value="0-50">Moins de 50MB</option>
                                <option value="50-100">50MB à 100MB</option>
                                <option value="100-500">100MB à 500MB</option>
                                <option value="500-9999">Plus de 500MB</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table des types -->
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Liste des types de production
                            <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($types) ?></span> visible(s))</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Message aucun résultat -->
                        <div class="no-results" id="tableNoResults">
                            <i class="fas fa-search text-muted"></i>
                            <h5 class="mt-2">Aucun résultat trouvé</h5>
                            <p class="text-muted">Aucun type de production ne correspond à vos critères</p>
                        </div>

                        <!-- Tableau -->
                        <div class="table-responsive" id="tableContainer">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>
                                            <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(1)">
                                                Type <i class="fas fa-sort ms-1"></i>
                                            </button>
                                        </th>
                                        <th>Département</th>
                                        <th>Extensions</th>
                                        <th>Taille max</th>
                                        <th>Utilisation</th>
                                        <th width="120" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="typesTable">
                                    <?php
                                    $n = 0;
                                    foreach ($types as $type):
                                        $n++;
                                        
                                        // Formater les extensions
                                        $extensionsArray = !empty($type['extension_autorisee']) ? 
                                            explode(',', $type['extension_autorisee']) : [];
                                        
                                        // Icone selon le nom du type
                                        $typeIcon = getTypeIcon($type['nom']);
                                        
                                        // Pourcentage d'utilisation (exemple)
                                        $usagePercent = min(100, ($type['nb_productions'] * 10));
                                    ?>
                                    <tr class="table-row type-card" 
                                        data-nom="<?= htmlspecialchars(strtolower($type['nom'])) ?>"
                                        data-departement="<?= $type['departement_id'] ?>"
                                        data-taille="<?= $type['taille_max_mo'] ?>"
                                        data-usage="<?= $type['nb_productions'] ?>">
                                        <td class="text-center"><?= $n ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="type-icon me-3">
                                                    <i class="<?= $typeIcon ?>"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-primary"><?= htmlspecialchars($type['nom']) ?></strong>
                                                    <?php if ($type['description']): ?>
                                                        <div class="text-muted small mt-1">
                                                            <?= htmlspecialchars(substr($type['description'], 0, 100)) ?>
                                                            <?= strlen($type['description']) > 100 ? '...' : '' ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($type['departement_nom']): ?>
                                                <span class="departement-badge">
                                                    <i class="fas fa-building me-1"></i>
                                                    <?= htmlspecialchars($type['departement_nom']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Non spécifié</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($extensionsArray)): ?>
                                                <div class="extensions-list">
                                                    <?php foreach ($extensionsArray as $ext): ?>
                                                        <span class="extension-badge"><?= strtoupper(trim($ext)) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">Toutes</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= $type['taille_max_mo'] ?> MB</div>
                                            <div class="usage-bar">
                                                <div class="usage-bar-fill" style="width: <?= min(100, ($type['taille_max_mo'] / 10)) ?>%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($type['nb_productions'] > 0): ?>
                                                <span class="usage-badge">
                                                    <i class="fas fa-file me-1"></i>
                                                    <?= $type['nb_productions'] ?> production(s)
                                                </span>
                                            <?php else: ?>
                                                <span class="type-badge">Non utilisé</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" 
                                                        class="btn btn-outline-secondary"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editTypeModal"
                                                        onclick="loadEditData(<?= $type['type_prod_id'] ?>)"
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($type['nb_productions'] == 0): ?>
                                                <button type="button" 
                                                        class="btn btn-outline-danger"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal"
                                                        onclick="setDeleteId(<?= $type['type_prod_id'] ?>, '<?= htmlspecialchars(addslashes($type['nom'])) ?>')"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php else: ?>
                                                <button type="button" 
                                                        class="btn btn-outline-secondary"
                                                        title="Impossible de supprimer (utilisé)"
                                                        disabled>
                                                    <i class="fas fa-ban"></i>
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
            
            <div class="col-md-4">
                <!-- Card Top Types -->
                <div class="top-types-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Types les plus utilisés
                    </h5>
                    
                    <?php if (!empty($topTypes)): ?>
                        <?php 
                        $maxUsage = max(array_column($topTypes, 'usage_count'));
                        foreach ($topTypes as $topType): 
                            $percent = $maxUsage > 0 ? ($topType['usage_count'] / $maxUsage) * 100 : 0;
                        ?>
                        <div class="top-type-item">
                            <div>
                                <strong class="d-block"><?= htmlspecialchars($topType['nom']) ?></strong>
                                <small class="text-muted"><?= $topType['usage_count'] ?> production(s)</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary"><?= $topType['usage_count'] ?></div>
                                <div class="progress" style="width: 100px; height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucune donnée d'utilisation disponible</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold mb-3">Conseils d'utilisation</h6>
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Créez des types spécifiques à chaque département
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Définissez des tailles maximales appropriées
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Restreignez les extensions pour la sécurité
                            </li>
                            <li>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Ajoutez des descriptions claires
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
        <!-- Modal Ajout Type -->
        <div class="modal fade" id="addTypeModal" tabindex="-1" aria-labelledby="addTypeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addTypeModalLabel">
                            <i class="fas fa-plus-circle me-2"></i>
                            Nouveau Type de Production
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-type-production-post.php" method="POST" id="addForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Nom du type <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nom" required 
                                           placeholder="Ex: Plan Architecture, Code Source, Charte Graphique">
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3" 
                                              placeholder="Décrivez ce type de production..."></textarea>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Département associé</label>
                                    <select class="form-select" name="departement_id">
                                        <option value="">Sélectionner un département...</option>
                                        <?php foreach ($departementsList as $departement): ?>
                                            <option value="<?= $departement['departement_id'] ?>">
                                                <?= htmlspecialchars($departement['nom']) ?>
                                                (<?= htmlspecialchars($departement['code']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Taille maximale (MB) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="taille_max_mo" required 
                                           min="1" max="10000" value="100">
                                    <small class="text-muted">Taille maximale autorisée pour ce type de fichier</small>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Extensions autorisées</label>
                                    <input type="text" class="form-control" name="extension_autorisee" 
                                           placeholder="Ex: pdf,docx,zip (séparées par des virgules)">
                                    <small class="text-muted">
                                        Laissez vide pour autoriser toutes les extensions. Ex: "pdf,docx,zip"
                                    </small>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-light border mt-3">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        <small class="text-muted">
                                            Les types de production définissent les catégories de fichiers que vous pouvez uploader.
                                            Ils aident à organiser et sécuriser vos productions.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle me-1"></i> Créer le type
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Modification Type -->
        <div class="modal fade" id="editTypeModal" tabindex="-1" aria-labelledby="editTypeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editTypeModalLabel">
                            <i class="fas fa-edit me-2"></i>
                            Modifier le Type de Production
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" action="../models/update/update-type-production-post.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="editId" name="type_prod_id">
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
                        <p>Êtes-vous sûr de vouloir supprimer le type :</p>
                        <h5 class="text-danger" id="deleteTypeNom"></h5>
                        <p class="text-muted mt-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Cette action est irréversible. Le type sera marqué comme supprimé dans le système.
                        </p>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention !</strong> Vous ne pouvez pas supprimer un type utilisé par des productions.
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
        // Fonction pour obtenir l'icône selon le type de production (version JavaScript - pour référence)
        function getTypeIconJS(typeName) {
            const typeLower = typeName.toLowerCase();
            
            if (typeLower.includes('plan') || typeLower.includes('architecture') || typeLower.includes('dwg') || typeLower.includes('dxf')) {
                return 'fas fa-drafting-compass';
            } else if (typeLower.includes('code') || typeLower.includes('source') || typeLower.includes('programme')) {
                return 'fas fa-code';
            } else if (typeLower.includes('charte') || typeLower.includes('graphique') || typeLower.includes('design')) {
                return 'fas fa-palette';
            } else if (typeLower.includes('rapport') || typeLower.includes('document') || typeLower.includes('pdf')) {
                return 'fas fa-file-alt';
            } else if (typeLower.includes('image') || typeLower.includes('photo') || typeLower.includes('jpg') || typeLower.includes('png')) {
                return 'fas fa-image';
            } else if (typeLower.includes('video') || typeLower.includes('film') || typeLower.includes('mp4')) {
                return 'fas fa-video';
            } else if (typeLower.includes('audio') || typeLower.includes('son') || typeLower.includes('mp3')) {
                return 'fas fa-music';
            } else if (typeLower.includes('archive') || typeLower.includes('zip') || typeLower.includes('rar')) {
                return 'fas fa-file-archive';
            } else if (typeLower.includes('donnée') || typeLower.includes('data') || typeLower.includes('excel') || typeLower.includes('csv')) {
                return 'fas fa-database';
            } else if (typeLower.includes('présentation') || typeLower.includes('powerpoint') || typeLower.includes('ppt')) {
                return 'fas fa-presentation';
            } else {
                return 'fas fa-file';
            }
        }

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
            document.getElementById('filterDepartement').addEventListener('change', filterTypes);
            document.getElementById('filterTaille').addEventListener('change', filterTypes);
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterTypes();
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                document.getElementById('filterDepartement').value = '';
                document.getElementById('filterTaille').value = '';
                filterTypes();
            });
        }
        
        function filterTypes() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const departementFilter = document.getElementById('filterDepartement').value;
            const tailleFilter = document.getElementById('filterTaille').value;
            
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            
            let visibleRows = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (departementFilter) activeFilters.push(`Département: ${document.querySelector('#filterDepartement option[value="' + departementFilter + '"]')?.textContent}`);
            if (tailleFilter) activeFilters.push(`Taille: ${document.querySelector('#filterTaille option[value="' + tailleFilter + '"]')?.textContent}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const nom = row.getAttribute('data-nom');
                const departement = row.getAttribute('data-departement');
                const taille = parseInt(row.getAttribute('data-taille'));
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && !nom.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre département
                if (departementFilter && departement !== departementFilter) {
                    matches = false;
                }
                
                // Filtre taille
                if (tailleFilter) {
                    const [min, max] = tailleFilter.split('-').map(Number);
                    if (taille < min || taille >= max) {
                        matches = false;
                    }
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
            const table = document.getElementById('typesTable');
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
                } else if (columnIndex === 4) {
                    // Colonne Taille (numérique)
                    aText = parseFloat(a.getAttribute('data-taille')) || 0;
                    bText = parseFloat(b.getAttribute('data-taille')) || 0;
                } else if (columnIndex === 5) {
                    // Colonne Utilisation (numérique)
                    aText = parseInt(a.getAttribute('data-usage')) || 0;
                    bText = parseInt(b.getAttribute('data-usage')) || 0;
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
        function loadEditData(typeId) {
            fetch(`../models/get/get-type-production.php?id=${typeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const type = data.type;
                        document.getElementById('editId').value = typeId;
                        
                        let html = `
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Nom du type <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nom" value="${type.nom}" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3">${type.description || ''}</textarea>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Département associé</label>
                                    <select class="form-select" name="departement_id">
                                        <option value="">Sélectionner un département...</option>
                                        <?php foreach ($departementsList as $departement): ?>
                                            <option value="<?= $departement['departement_id'] ?>" ${type.departement_id == <?= $departement['departement_id'] ?> ? 'selected' : ''}>
                                                <?= htmlspecialchars($departement['nom']) ?>
                                                (<?= htmlspecialchars($departement['code']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Taille maximale (MB) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="taille_max_mo" value="${type.taille_max_mo || 100}" required min="1" max="10000">
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Extensions autorisées</label>
                                    <input type="text" class="form-control" name="extension_autorisee" value="${type.extension_autorisee || ''}">
                                    <small class="text-muted">Ex: "pdf,docx,zip" (séparées par des virgules, laissez vide pour toutes)</small>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-info mt-2">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Utilisation :</strong> Ce type est utilisé par ${type.nb_productions || 0} production(s)
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('editModalBody').innerHTML = html;
                    } else {
                        alert('Erreur lors du chargement des données du type');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données');
                });
        }
        
        // Préparer la suppression
        function setDeleteId(id, nom) {
            document.getElementById('deleteTypeNom').textContent = nom;
            
            // Mettre à jour le lien de suppression
            const deleteUrl = `../models/delete/delete-type-production.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Validation du formulaire d'ajout
        document.getElementById('addForm')?.addEventListener('submit', function(e) {
            const nom = this.querySelector('[name="nom"]').value.trim();
            const taille = this.querySelector('[name="taille_max_mo"]').value;
            
            if (!nom) {
                e.preventDefault();
                alert('Le nom du type est requis');
                return false;
            }
            
            if (!taille || taille < 1 || taille > 10000) {
                e.preventDefault();
                alert('La taille maximale doit être comprise entre 1 et 10000 MB');
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