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
$canAccessRoles = hasPermission(['DG', 'comptable'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveTransferts = 1;

# Récupérer les transferts avec jointures
try {
    // Récupérer les comptes pour les filtres et formulaire
    $queryComptes = "SELECT compte_id, nom, type, devise, solde_actuel FROM COMPTES WHERE statut = 0 ORDER BY type, nom";
    $stmtComptes = $conn->prepare($queryComptes);
    $stmtComptes->execute();
    $comptes = $stmtComptes->fetchAll(PDO::FETCH_ASSOC);

    # Récupérer les transferts
    $query = "
        SELECT 
            t.*,
            cs.nom as compte_source_nom,
            cs.type as compte_source_type,
            cs.devise as compte_source_devise,
            cd.nom as compte_dest_nom,
            cd.type as compte_dest_type,
            cd.devise as compte_dest_devise,
            u.nom as valide_par_nom,
            u.prenom as valide_par_prenom
        FROM TRANSFERTS_COMPTE t
        LEFT JOIN COMPTES cs ON t.compte_source_id = cs.compte_id
        LEFT JOIN COMPTES cd ON t.compte_destination_id = cd.compte_id
        LEFT JOIN UTILISATEURS u ON t.valide_par = u.utilisateur_id
        WHERE t.statut = 0
        ORDER BY t.date_transfert DESC, t.date_creation DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $transferts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques globales
    $queryStats = "
        SELECT 
            COUNT(*) as total,
            SUM(montant) as total_montant,
            AVG(montant) as moyenne_montant,
            MIN(montant) as montant_min,
            MAX(montant) as montant_max
        FROM TRANSFERTS_COMPTE 
        WHERE statut = 0
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des transferts: " . $e->getMessage());
    $transferts = [];
    $comptes = [];
    $stats = ['total' => 0, 'total_montant' => 0, 'moyenne_montant' => 0, 'montant_min' => 0, 'montant_max' => 0];
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
    <title>Transferts entre Comptes - Evotech Africa</title>
    
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
            --success-green: #198754;
            --info-blue: #0dcaf0;
            --warning-yellow: #ffc107;
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

        /* Styles spécifiques aux transferts */
        .transfert-card {
            border-left: 4px solid var(--info-blue);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .transfert-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .transfert-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, var(--info-blue), #6ec6ff);
            color: white;
            font-size: 1.5rem;
        }
        
        .compte-source {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 10px;
        }
        
        .compte-destination {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            padding: 10px;
        }
        
        .arrow-container {
            position: relative;
            height: 20px;
            margin: 10px 0;
        }
        
        .arrow-icon {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            color: var(--success-green);
            font-size: 1.5rem;
            background: white;
            padding: 0 5px;
            z-index: 2;
        }
        
        .arrow-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        
        .badge-transfert {
            background: linear-gradient(135deg, var(--info-blue), #6ec6ff);
            color: white;
        }
        
        .montant-transfert {
            color: var(--info-blue);
            font-weight: bold;
            font-size: 1.25rem;
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
        
        .quick-actions {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-btn {
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .action-transfert {
            background: linear-gradient(135deg, #cfe2ff, #9ec5fe);
            color: var(--primary-blue);
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

                        <li class="sidebar-item has-sub active">
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
                                    <li class="active">
                                        <a href="transferts.php">Transferts</a>
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
                <i class="fas fa-exchange-alt me-2"></i>
                Transferts entre Comptes
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un transfert..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransfertModal">
                        <i class="bi bi-plus-circle me-1"></i> Nouveau Transfert
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Transferts</h6>
                                <h2 class="card-title text-primary mb-0">
                                    <?= $stats['total'] ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-exchange-alt"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Total Montant</h6>
                                <h2 class="card-title text-success mb-0">
                                    $<?= number_format($stats['total_montant'] ?? 0, 2, ',', ' ') ?>
                                </h2>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-money-bill-wave"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Montant Moyen</h6>
                                <h2 class="card-title text-info mb-0">
                                    $<?= number_format($stats['moyenne_montant'] ?? 0, 2, ',', ' ') ?>
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="fas fa-calculator"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Montant Max</h6>
                                <h2 class="card-title text-warning mb-0">
                                    $<?= number_format($stats['montant_max'] ?? 0, 2, ',', ' ') ?>
                                </h2>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-arrow-up"></i>
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

        <!-- Filtres avancés -->
        <div class="filter-section">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Compte source</label>
                    <select class="form-select" id="filterCompteSource">
                        <option value="">Tous les comptes</option>
                        <?php foreach ($comptes as $compte): ?>
                            <option value="<?= $compte['compte_id'] ?>">
                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Compte destination</label>
                    <select class="form-select" id="filterCompteDest">
                        <option value="">Tous les comptes</option>
                        <?php foreach ($comptes as $compte): ?>
                            <option value="<?= $compte['compte_id'] ?>">
                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date début</label>
                    <input type="date" class="form-control" id="filterDateDebut">
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <label class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="filterDateFin">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Montant minimum</label>
                    <input type="number" class="form-control" id="filterMontantMin" placeholder="0" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Montant maximum</label>
                    <input type="number" class="form-control" id="filterMontantMax" placeholder="1000000" step="0.01">
                </div>
            </div>
        </div>

        <!-- Transferts en Cartes -->
        <div class="row mb-4 g-3" id="transfertCards">
            <?php foreach ($transferts as $n => $transfert): 
                $dateTransfert = new DateTime($transfert['date_transfert']);
                $dateFormatee = $dateTransfert->format('d/m/Y à H:i');
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card transfert-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="card-title mb-1">
                                    <i class="fas fa-exchange-alt text-info me-2"></i>
                                    Transfert
                                </h6>
                                <span class="badge badge-transfert">ID: <?= $transfert['transfert_id'] ?></span>
                            </div>
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>
                                <?= $dateFormatee ?>
                            </small>
                        </div>

                        <!-- Compte Source -->
                        <div class="compte-source mb-3">
                            <small class="text-muted d-block mb-2">De</small>
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="fas fa-university text-primary"></i>
                                </div>
                                <div>
                                    <p class="mb-0 fw-semibold"><?= htmlspecialchars($transfert['compte_source_nom']) ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($transfert['compte_source_type']) ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Flèche -->
                        <div class="arrow-container mb-3">
                            <div class="arrow-line"></div>
                            <div class="arrow-icon">
                                <i class="fas fa-arrow-down text-success"></i>
                            </div>
                        </div>

                        <!-- Compte Destination -->
                        <div class="compte-destination mb-3">
                            <small class="text-muted d-block mb-2">Vers</small>
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="fas fa-university text-success"></i>
                                </div>
                                <div>
                                    <p class="mb-0 fw-semibold"><?= htmlspecialchars($transfert['compte_dest_nom']) ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($transfert['compte_dest_type']) ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Montant -->
                        <div class="alert alert-light mb-3 text-center">
                            <p class="mb-0">
                                <span class="montant-transfert">
                                    <?= $transfert['compte_source_devise'] ?><?= number_format($transfert['montant'], 2, ',', ' ') ?>
                                </span>
                            </p>
                        </div>

                        <!-- Motif -->
                        <div class="mb-3">
                            <small class="text-muted d-block">Motif</small>
                            <p class="mb-0"><?= htmlspecialchars($transfert['motif']) ?></p>
                        </div>

                        <?php if ($transfert['reference_transfert']): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Référence</small>
                            <p class="mb-0"><code><?= htmlspecialchars($transfert['reference_transfert']) ?></code></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($transfert['valide_par_nom']): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Validé par</small>
                            <p class="mb-0">
                                <i class="fas fa-user-check text-success me-1"></i>
                                <?= htmlspecialchars($transfert['valide_par_prenom'] . ' ' . $transfert['valide_par_nom']) ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal"
                                        onclick="setDeleteId(<?= $transfert['transfert_id'] ?>, 'Transfert du <?= $dateFormatee ?> - <?= htmlspecialchars(addslashes($transfert['motif'])) ?>')">
                                    <i class="bi bi-trash"></i> Annuler
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($transferts)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                Aucun transfert enregistré
            </div>
        <?php endif; ?>

        <!-- Table des transferts -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Liste détaillée des transferts
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($transferts) ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun transfert ne correspond à vos critères</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Date</th>
                                <th>De</th>
                                <th>Vers</th>
                                <th>Montant</th>
                                <th>Motif</th>
                                <th>Référence</th>
                                <th width="80" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transfertsTable">
                            <?php
                            $n = 0;
                            foreach ($transferts as $transfert):
                                $n++;
                                $dateTransfert = new DateTime($transfert['date_transfert']);
                                $dateFormatee = $dateTransfert->format('d/m/Y H:i');
                                $montantFormatted = number_format($transfert['montant'], 2, ',', ' ');
                            ?>
                            <tr class="table-row" 
                                data-compte-source="<?= htmlspecialchars(strtolower($transfert['compte_source_nom'])) ?>"
                                data-compte-dest="<?= htmlspecialchars(strtolower($transfert['compte_dest_nom'])) ?>"
                                data-montant="<?= $transfert['montant'] ?>"
                                data-date="<?= $transfert['date_transfert'] ?>"
                                data-reference="<?= htmlspecialchars(strtolower($transfert['reference_transfert'] ?? '')) ?>"
                                data-motif="<?= htmlspecialchars(strtolower($transfert['motif'])) ?>">
                                <td class="text-center"><?= $n ?></td>
                                <td><?= $dateFormatee ?></td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($transfert['compte_source_nom']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($transfert['compte_source_type']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($transfert['compte_dest_nom']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($transfert['compte_dest_type']) ?></small>
                                    </div>
                                </td>
                                <td class="fw-bold text-info">
                                    <?= $transfert['compte_source_devise'] ?><?= $montantFormatted ?>
                                </td>
                                <td><?= htmlspecialchars($transfert['motif']) ?></td>
                                <td>
                                    <?= $transfert['reference_transfert'] ? htmlspecialchars($transfert['reference_transfert']) : '-' ?>
                                </td>
                                <td class="text-center">
                                    <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal"
                                            onclick="setDeleteId(<?= $transfert['transfert_id'] ?>, 'Transfert du <?= $dateFormatee ?> - <?= htmlspecialchars(addslashes($transfert['motif'])) ?>')"
                                            title="Annuler">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
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
    <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
        <!-- Modal Ajout Transfert -->
        <div class="modal fade" id="addTransfertModal" tabindex="-1" aria-labelledby="addTransfertModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addTransfertModalLabel">
                            <i class="fas fa-exchange-alt me-2"></i>
                            Nouveau Transfert
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-transfert.php" method="POST" id="addTransfertForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Compte Source <span class="text-danger">*</span></label>
                                    <select class="form-select" name="compte_source_id" required id="compteSource">
                                        <option value="">Sélectionner un compte</option>
                                        <?php foreach ($comptes as $compte): ?>
                                            <option value="<?= $compte['compte_id'] ?>" data-devise="<?= htmlspecialchars($compte['devise']) ?>" data-solde="<?= $compte['solde_actuel'] ?>">
                                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?> - Solde: <?= $compte['devise'] ?><?= number_format($compte['solde_actuel'], 2, ',', ' ') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Compte Destination <span class="text-danger">*</span></label>
                                    <select class="form-select" name="compte_destination_id" required id="compteDestination">
                                        <option value="">Sélectionner un compte</option>
                                        <?php foreach ($comptes as $compte): ?>
                                            <option value="<?= $compte['compte_id'] ?>" data-devise="<?= htmlspecialchars($compte['devise']) ?>">
                                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?> - Solde: <?= $compte['devise'] ?><?= number_format($compte['solde_actuel'], 2, ',', ' ') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Montant <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="transfertDevise">$</span>
                                        <input type="number" class="form-control" name="montant" step="0.01" min="0.01" required id="transfertMontant">
                                        <div class="invalid-feedback" id="transfertSoldeError">
                                            Le montant ne peut pas dépasser le solde du compte source.
                                        </div>
                                    </div>
                                    <small class="text-muted" id="transfertSoldeInfo">Solde disponible: $0.00</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Motif <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="motif" rows="3" required placeholder="Ex: Transfert inter-comptes, Approvisionnement caisse..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Référence du Transfert</label>
                                    <input type="text" class="form-control" name="reference_transfert" placeholder="Ex: TRF-2026-001">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Effectuer le Transfert
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
                            Confirmation d'annulation
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir annuler le transfert :</p>
                        <h5 class="text-danger" id="deleteTransfertName"></h5>
                        <p class="text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette action est irréversible. Le transfert sera annulé et les soldes des comptes seront restaurés.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <a id="deleteConfirmBtn" href="" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Annuler le transfert
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
            initializeFormValidation();
        });

        // Initialisation des filtres
        function initializeFilters() {
            // Écouteurs pour les filtres
            document.getElementById('filterCompteSource').addEventListener('change', filterTransferts);
            document.getElementById('filterCompteDest').addEventListener('change', filterTransferts);
            document.getElementById('filterDateDebut').addEventListener('change', filterTransferts);
            document.getElementById('filterDateFin').addEventListener('change', filterTransferts);
            document.getElementById('filterMontantMin').addEventListener('input', filterTransferts);
            document.getElementById('filterMontantMax').addEventListener('input', filterTransferts);
        }

        // Validation du formulaire de transfert
        function initializeFormValidation() {
            const compteSourceSelect = document.getElementById('compteSource');
            const compteDestSelect = document.getElementById('compteDestination');
            const transfertMontantInput = document.getElementById('transfertMontant');
            
            // Mise à jour devise et solde pour compte source
            compteSourceSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const devise = selectedOption.getAttribute('data-devise');
                    const solde = parseFloat(selectedOption.getAttribute('data-solde'));
                    
                    document.getElementById('transfertDevise').textContent = devise;
                    document.getElementById('transfertSoldeInfo').textContent = `Solde disponible: ${devise}${solde.toFixed(2)}`;
                    
                    // Réinitialiser la validation
                    transfertMontantInput.classList.remove('is-invalid');
                }
            });
            
            // Validation montant
            transfertMontantInput.addEventListener('input', function() {
                const selectedOption = compteSourceSelect.options[compteSourceSelect.selectedIndex];
                if (selectedOption.value) {
                    const solde = parseFloat(selectedOption.getAttribute('data-solde'));
                    const montant = parseFloat(this.value) || 0;
                    
                    if (montant > solde) {
                        this.classList.add('is-invalid');
                        document.getElementById('transfertSoldeError').style.display = 'block';
                    } else {
                        this.classList.remove('is-invalid');
                        document.getElementById('transfertSoldeError').style.display = 'none';
                    }
                }
            });
            
            // Empêcher même compte source et destination
            compteDestSelect.addEventListener('change', function() {
                const sourceId = compteSourceSelect.value;
                const destId = this.value;
                
                if (sourceId && destId && sourceId === destId) {
                    alert('Le compte source et le compte destination ne peuvent pas être identiques.');
                    this.value = '';
                }
            });
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterTransferts();
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                document.getElementById('filterCompteSource').value = '';
                document.getElementById('filterCompteDest').value = '';
                document.getElementById('filterDateDebut').value = '';
                document.getElementById('filterDateFin').value = '';
                document.getElementById('filterMontantMin').value = '';
                document.getElementById('filterMontantMax').value = '';
                filterTransferts();
            });
        }
        
        function filterTransferts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const compteSourceFilter = document.getElementById('filterCompteSource').value;
            const compteDestFilter = document.getElementById('filterCompteDest').value;
            const dateDebutFilter = document.getElementById('filterDateDebut').value;
            const dateFinFilter = document.getElementById('filterDateFin').value;
            const montantMin = parseFloat(document.getElementById('filterMontantMin').value) || -Infinity;
            const montantMax = parseFloat(document.getElementById('filterMontantMax').value) || Infinity;
            
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            const transfertCards = document.querySelectorAll('.col-md-6.col-lg-4');
            
            let visibleRows = 0;
            let visibleCards = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (compteSourceFilter) activeFilters.push(`Compte source: ${document.querySelector('#filterCompteSource option:checked').textContent.split('(')[0].trim()}`);
            if (compteDestFilter) activeFilters.push(`Compte destination: ${document.querySelector('#filterCompteDest option:checked').textContent.split('(')[0].trim()}`);
            if (dateDebutFilter) activeFilters.push(`À partir du: ${dateDebutFilter}`);
            if (dateFinFilter) activeFilters.push(`Jusqu'au: ${dateFinFilter}`);
            if (montantMin !== -Infinity) activeFilters.push(`Montant min: ${montantMin}`);
            if (montantMax !== Infinity) activeFilters.push(`Montant max: ${montantMax}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const compteSource = row.getAttribute('data-compte-source');
                const compteDest = row.getAttribute('data-compte-dest');
                const montant = parseFloat(row.getAttribute('data-montant'));
                const date = row.getAttribute('data-date');
                const reference = row.getAttribute('data-reference');
                const motif = row.getAttribute('data-motif');
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && 
                    !compteSource.includes(searchTerm) && 
                    !compteDest.includes(searchTerm) &&
                    !motif.includes(searchTerm) &&
                    !reference.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre compte source
                if (compteSourceFilter && !compteSource.includes(compteSourceFilter.toLowerCase())) {
                    matches = false;
                }
                
                // Filtre compte destination
                if (compteDestFilter && !compteDest.includes(compteDestFilter.toLowerCase())) {
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
                
                // Filtre montant
                if (montant < montantMin || montant > montantMax) {
                    matches = false;
                }
                
                row.style.display = matches ? '' : 'none';
                if (matches) visibleRows++;
            });
            
            // Filtrer les cartes (simplifié pour cet exemple)
            transfertCards.forEach(card => {
                // Pour simplifier, on filtre juste les cartes basées sur la recherche
                // Une implémentation plus complète nécessiterait d'ajouter des data-attributes aux cartes
                const cardText = card.textContent.toLowerCase();
                let matches = true;
                
                if (searchTerm && !cardText.includes(searchTerm)) {
                    matches = false;
                }
                
                card.style.display = matches ? '' : 'none';
                if (matches) visibleCards++;
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
        
        // Préparer la suppression
        function setDeleteId(id, description) {
            document.getElementById('deleteTransfertName').textContent = description;
            const deleteUrl = `../models/delete/delete-transfert.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Validation du formulaire
        document.getElementById('addTransfertForm')?.addEventListener('submit', function(e) {
            const compteSource = this.querySelector('[name="compte_source_id"]').value;
            const compteDest = this.querySelector('[name="compte_destination_id"]').value;
            const montant = parseFloat(this.querySelector('[name="montant"]').value);
            const motif = this.querySelector('[name="motif"]').value.trim();
            
            if (!motif) {
                e.preventDefault();
                alert('Le motif est requis');
                return false;
            }
            
            if (!compteSource || !compteDest) {
                e.preventDefault();
                alert('Veuillez sélectionner les comptes source et destination');
                return false;
            }
            
            if (compteSource === compteDest) {
                e.preventDefault();
                alert('Le compte source et le compte destination ne peuvent pas être identiques');
                return false;
            }
            
            if (!montant || montant <= 0) {
                e.preventDefault();
                alert('Le montant doit être supérieur à 0');
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