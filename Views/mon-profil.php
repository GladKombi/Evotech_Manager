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

// Définir le menu actif pour le sidebar
$ActiveProfil = 1;

// Récupérer les informations de l'utilisateur
try {
    // Déterminer si la session appartient à un agent
    $isAgent = (is_array($userRoles) && in_array('agent', $userRoles)) || $userType === 'agent';

    if ($isAgent) {
        // Charger les données depuis la table AGENTS (liaison via email professionnel)
        $query = "
            SELECT 
                a.*, 
                u.utilisateur_id, u.nom, u.prenom, u.email, u.profil,
                GROUP_CONCAT(r.fonction SEPARATOR ', ') as roles_list,
                d.nom as departement_nom, d.code as departement_code
            FROM AGENTS a
            LEFT JOIN UTILISATEURS u ON u.email = a.email_professionnel AND u.statut = 0
            LEFT JOIN ATTRIBUTIONS attr ON u.utilisateur_id = attr.utilisateur_id AND attr.statut = 0
            LEFT JOIN ROLES r ON attr.role_id = r.role_id AND r.statut = 0
            LEFT JOIN DEPARTEMENTS d ON u.utilisateur_id = d.responsable_id
            WHERE u.utilisateur_id = ? AND a.statut = 0
            GROUP BY a.agent_id
        ";

        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Fallback : tenter de charger depuis UTILISATEURS si pas d'enregistrement agent
            $fallback = $conn->prepare(
                "SELECT u.*, GROUP_CONCAT(r.fonction SEPARATOR ', ') as roles_list, d.nom as departement_nom, d.code as departement_code
                 FROM UTILISATEURS u
                 LEFT JOIN ATTRIBUTIONS attr ON u.utilisateur_id = attr.utilisateur_id AND attr.statut = 0
                 LEFT JOIN ROLES r ON attr.role_id = r.role_id AND r.statut = 0
                 LEFT JOIN DEPARTEMENTS d ON u.utilisateur_id = d.responsable_id
                 WHERE u.utilisateur_id = ? AND u.statut = 0
                 GROUP BY u.utilisateur_id"
            );
            $fallback->execute([$userId]);
            $user = $fallback->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        // Chargement standard depuis UTILISATEURS pour les administrateurs/autres
        $query = "
            SELECT 
                u.*,
                GROUP_CONCAT(r.fonction SEPARATOR ', ') as roles_list,
                d.nom as departement_nom,
                d.code as departement_code,
                a.photo_profil as agent_photo,
                a.telephone_professionnel,
                a.email_professionnel
            FROM UTILISATEURS u
            LEFT JOIN ATTRIBUTIONS attr ON u.utilisateur_id = attr.utilisateur_id AND attr.statut = 0
            LEFT JOIN ROLES r ON attr.role_id = r.role_id AND r.statut = 0
            LEFT JOIN DEPARTEMENTS d ON u.utilisateur_id = d.responsable_id
            LEFT JOIN AGENTS a ON u.email = a.email_professionnel
            WHERE u.utilisateur_id = ? AND u.statut = 0
            GROUP BY u.utilisateur_id
        ";

        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user) {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header('Location: dashboard.php');
        exit();
    }
    
    // Récupérer les connexions récentes
    $queryConnexions = "
        SELECT * FROM connexions 
        WHERE user_id = ? 
        ORDER BY login_time DESC 
        LIMIT 10
    ";
    $stmtConnexions = $conn->prepare($queryConnexions);
    $stmtConnexions->execute([$userId]);
    $connexions = $stmtConnexions->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les statistiques d'activité
    $queryStats = "
        SELECT 
            COUNT(*) as total_connexions,
            MAX(login_time) as derniere_connexion,
            DATE_FORMAT(login_time, '%Y-%m') as mois,
            COUNT(*) as connexions_mois
        FROM connexions 
        WHERE user_id = ? 
        AND login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute([$userId]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les tâches assignées récentes
    $queryTaches = "
        SELECT 
            t.*,
            p.reference as projet_reference,
            p.titre as projet_titre
        FROM TACHES t
        LEFT JOIN PROJETS p ON t.projet_id = p.projet_id
        WHERE t.assigne_a = (
            SELECT agent_id FROM AGENTS 
            WHERE email_professionnel = ? 
            AND statut = 0 
            LIMIT 1
        )
        AND t.statut = 0
        AND t.etat IN ('À faire', 'En cours')
        ORDER BY t.date_debut_prevue DESC
        LIMIT 5
    ";
    $stmtTaches = $conn->prepare($queryTaches);
    $stmtTaches->execute([$user['email']]);
    $taches = $stmtTaches->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les timesheets récents
    $queryTimesheets = "
        SELECT 
            ts.*,
            t.description_tache,
            p.reference as projet_reference
        FROM TIMESHEET ts
        LEFT JOIN TACHES t ON ts.tache_id = t.tache_id
        LEFT JOIN PROJETS p ON t.projet_id = p.projet_id
        WHERE ts.agent_id = (
            SELECT agent_id FROM AGENTS 
            WHERE email_professionnel = ? 
            AND statut = 0 
            LIMIT 1
        )
        AND ts.statut = 0
        ORDER BY ts.date_travail DESC
        LIMIT 5
    ";
    $stmtTimesheets = $conn->prepare($queryTimesheets);
    $stmtTimesheets->execute([$user['email']]);
    $timesheets = $stmtTimesheets->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération du profil: " . $e->getMessage());
    $user = [];
    $connexions = [];
    $stats = [];
    $taches = [];
    $timesheets = [];
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
    <title>Mon Profil - Evotech Africa</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dropzone pour upload photo -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">

    <style>
        :root {
            --primary-blue: #0d6efd;
            --primary-blue-dark: #0b5ed7;
            --success-green: #198754;
            --warning-yellow: #ffc107;
            --info-blue: #0dcaf0;
            --purple: #6f42c1;
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

        /* Styles spécifiques au profil */
        .profile-header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
        }
        
        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-blue);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .avatar-upload:hover {
            background: var(--primary-blue-dark);
            transform: scale(1.1);
        }
        
        .profile-info-card {
            border-left: 4px solid var(--primary-blue);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .profile-info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .info-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .info-label {
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 500;
            color: #212529;
        }
        
        .badge-role {
            background: linear-gradient(135deg, var(--purple), #a370f7);
            color: white;
            font-weight: 500;
        }
        
        .activity-card {
            border-left: 4px solid var(--success-green);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .activity-item {
            padding: 10px 15px;
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.2s ease;
        }
        
        .activity-item:hover {
            background-color: #f8f9fa;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: var(--primary-blue);
        }
        
        .tache-badge {
            background: linear-gradient(135deg, var(--warning-yellow), #ffca2c);
            color: #000;
        }
        
        .timesheet-badge {
            background: linear-gradient(135deg, var(--info-blue), #6ec6ff);
            color: white;
        }
        
        .tab-content {
            background: white;
            border-radius: 0 0 10px 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-blue);
            border-bottom: 3px solid var(--primary-blue);
            background: transparent;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-blue-dark);
        }
        
        .password-strength {
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 3px;
        }
        
        .strength-weak {
            background-color: #dc3545;
            width: 25%;
        }
        
        .strength-fair {
            background-color: #ffc107;
            width: 50%;
        }
        
        .strength-good {
            background-color: #20c997;
            width: 75%;
        }
        
        .strength-strong {
            background-color: #198754;
            width: 100%;
        }
        
        .security-card {
            border-left: 4px solid var(--danger-red);
            border-radius: 10px;
        }
        
        .security-item {
            padding: 15px;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .security-item:last-child {
            border-bottom: none;
        }
        
        .modal-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
        }
        
        .dropzone {
            border: 2px dashed #dee2e6 !important;
            border-radius: 10px !important;
            background: #f8f9fa !important;
            min-height: 150px !important;
        }
        
        .dropzone .dz-message {
            color: #6c757d !important;
            font-size: 1rem !important;
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
                        substr($user['prenom'] ?? '', 0, 1) .
                            substr($user['nom'] ?? '', 0, 1)
                    );
                    $photoProfil = $user['profil'] ?? ($user['agent_photo'] ?? '');
                    ?>
                    <div class="avatar-container">
                        <?php if ($photoProfil): ?>
                            <img src="../<?= htmlspecialchars($photoProfil) ?>" 
                                 alt="Photo de profil" 
                                 class="profile-avatar"
                                 id="sidebarAvatar">
                        <?php else: ?>
                            <div class="avatar-circle" id="sidebarAvatarCircle">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                        <?php endif; ?>
                        <div class="avatar-upload" data-bs-toggle="modal" data-bs-target="#uploadAvatarModal">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <strong class="d-block" id="sidebarUserName">
                        <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
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
                                        <a href="transferts.php">Transferts</a>
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
                    <li class="sidebar-item active">
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
        <!-- Header profil -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="avatar-container">
                        <?php if ($photoProfil): ?>
                            <img src="../<?= htmlspecialchars($photoProfil) ?>" 
                                 alt="Photo de profil" 
                                 class="profile-avatar"
                                 id="mainAvatar">
                        <?php else: ?>
                            <div class="avatar-circle" style="width: 120px; height: 120px; font-size: 2.5rem;" id="mainAvatarCircle">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                        <?php endif; ?>
                        <div class="avatar-upload" data-bs-toggle="modal" data-bs-target="#uploadAvatarModal">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-2" id="mainUserName">
                        <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                    </h1>
                    <p class="mb-1">
                        <i class="fas fa-envelope me-2"></i>
                        <span id="mainUserEmail"><?= htmlspecialchars($user['email'] ?? '') ?></span>
                    </p>
                    <?php if (!empty($user['departement_nom'])): ?>
                        <p class="mb-1">
                            <i class="fas fa-building me-2"></i>
                            <?= htmlspecialchars($user['departement_nom']) ?> (<?= htmlspecialchars($user['departement_code']) ?>)
                        </p>
                    <?php endif; ?>
                    <div class="mt-3">
                        <?php if (!empty($user['roles_list'])): ?>
                            <?php $roles = explode(', ', $user['roles_list']); ?>
                            <?php foreach ($roles as $role): ?>
                                <span class="badge badge-role me-1"><?= htmlspecialchars(trim($role)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
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

        <!-- Onglets -->
        <ul class="nav nav-tabs mb-0" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                    <i class="fas fa-info-circle me-1"></i> Informations
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                    <i class="fas fa-shield-alt me-1"></i> Sécurité
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">
                    <i class="fas fa-history me-1"></i> Activité
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">
                    <i class="fas fa-tasks me-1"></i> Mes Tâches
                </button>
            </li>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content" id="profileTabContent">
            <!-- Onglet Informations -->
            <div class="tab-pane fade show active" id="info" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card profile-info-card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                    Informations personnelles
                                </h5>
                                
                                <div class="info-item">
                                    <div class="info-label">Nom complet</div>
                                    <div class="info-value">
                                        <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Email professionnel</div>
                                    <div class="info-value"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                                </div>
                                
                                <?php if (!empty($user['email_professionnel'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Email agent</div>
                                    <div class="info-value"><?= htmlspecialchars($user['email_professionnel']) ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($user['telephone_professionnel'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Téléphone professionnel</div>
                                    <div class="info-value"><?= htmlspecialchars($user['telephone_professionnel']) ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($user['departement_nom']): ?>
                                <div class="info-item">
                                    <div class="info-label">Département</div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($user['departement_nom']) ?> (<?= htmlspecialchars($user['departement_code']) ?>)
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <div class="info-label">Rôles</div>
                                    <div class="info-value">
                                        <?php if ($user['roles_list']): ?>
                                            <?php $roles = explode(', ', $user['roles_list']); ?>
                                            <?php foreach ($roles as $role): ?>
                                                <span class="badge badge-role me-1 mb-1"><?= htmlspecialchars(trim($role)) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Aucun rôle attribué</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Statut du compte</div>
                                    <div class="info-value">
                                        <span class="badge <?= ($user['actif'] ?? false) ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ($user['actif'] ?? false) ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Date de création</div>
                                    <div class="info-value">
                                        <?= date('d/m/Y H:i', strtotime($user['date_creation'] ?? '')) ?>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                        <i class="fas fa-edit me-1"></i> Modifier mes informations
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card profile-info-card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-chart-line text-success me-2"></i>
                                    Statistiques
                                </h5>
                                
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="stat-number text-primary">
                                            <?= $stats['total_connexions'] ?? 0 ?>
                                        </div>
                                        <div class="stat-label">Connexions totales</div>
                                    </div>
                                    
                                    <div class="col-6 mb-3">
                                        <div class="stat-number text-success">
                                            <?= $stats['connexions_mois'] ?? 0 ?>
                                        </div>
                                        <div class="stat-label">Ce mois-ci</div>
                                    </div>
                                    
                                    <div class="col-6 mb-3">
                                        <div class="stat-number text-warning">
                                            <?= count($taches) ?>
                                        </div>
                                        <div class="stat-label">Tâches en cours</div>
                                    </div>
                                    
                                    <div class="col-6 mb-3">
                                        <div class="stat-number text-info">
                                            <?= count($timesheets) ?>
                                        </div>
                                        <div class="stat-label">Timesheets</div>
                                    </div>
                                </div>
                                
                                <?php if ($stats['derniere_connexion']): ?>
                                <div class="info-item">
                                    <div class="info-label">Dernière connexion</div>
                                    <div class="info-value">
                                        <?= date('d/m/Y H:i', strtotime($stats['derniere_connexion'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card profile-info-card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-qrcode text-purple me-2"></i>
                                    Code QR Personnel
                                </h5>
                                
                                <div class="text-center">
                                    <div id="qrcode" class="mb-3"></div>
                                    <p class="text-muted small">
                                        Scanner pour accéder rapidement à votre profil
                                    </p>
                                    <button class="btn btn-outline-primary btn-sm" onclick="downloadQRCode()">
                                        <i class="fas fa-download me-1"></i> Télécharger QR Code
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet Sécurité -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card security-card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-key text-danger me-2"></i>
                                    Changer le mot de passe
                                </h5>
                                
                                <form id="changePasswordForm" action="../models/update/change-password.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mot de passe actuel</label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="current_password" 
                                                   id="currentPassword"
                                                   required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('currentPassword')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nouveau mot de passe</label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="new_password" 
                                                   id="newPassword"
                                                   required
                                                   oninput="checkPasswordStrength(this.value)">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength">
                                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                        </div>
                                        <small class="text-muted">Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Confirmer le nouveau mot de passe</label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="confirm_password" 
                                                   id="confirmPassword"
                                                   required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback" id="passwordMatchError">
                                            Les mots de passe ne correspondent pas.
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-save me-1"></i> Changer le mot de passe
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card security-card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-shield-alt text-warning me-2"></i>
                                    Sécurité du compte
                                </h5>
                                
                                <div class="security-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Authentification à deux facteurs</h6>
                                            <p class="mb-0 text-muted small">Ajoutez une couche de sécurité supplémentaire</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="twoFactorSwitch">
                                            <label class="form-check-label" for="twoFactorSwitch"></label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="security-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Notifications de sécurité</h6>
                                            <p class="mb-0 text-muted small">Recevez des alertes pour les nouvelles connexions</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="securityNotifications" checked>
                                            <label class="form-check-label" for="securityNotifications"></label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="security-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Sessions actives</h6>
                                            <p class="mb-0 text-muted small">Gérez vos sessions de connexion</p>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#sessionsModal">
                                            Voir sessions
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="security-item">
                                    <h6 class="mb-2">Conseils de sécurité</h6>
                                    <ul class="small text-muted">
                                        <li>Utilisez un mot de passe unique et complexe</li>
                                        <li>Ne partagez jamais vos identifiants</li>
                                        <li>Déconnectez-vous après chaque session</li>
                                        <li>Mettez à jour régulièrement votre mot de passe</li>
                                        <li>Activez l'authentification à deux facteurs</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet Activité -->
            <div class="tab-pane fade" id="activity" role="tabpanel">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card activity-card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-history text-success me-2"></i>
                                    Historique des connexions
                                </h5>
                                
                                <?php if (!empty($connexions)): ?>
                                    <?php foreach ($connexions as $connexion): ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon">
                                                    <i class="fas fa-sign-in-alt"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-0">Connexion réussie</h6>
                                                        <small class="text-muted">
                                                            <?= date('d/m/Y H:i', strtotime($connexion['login_time'])) ?>
                                                        </small>
                                                    </div>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-globe me-1"></i>
                                                        <?= htmlspecialchars($connexion['ip_address'] ?? 'IP inconnue') ?>
                                                        <?php if ($connexion['user_agent']): ?>
                                                            - <?= htmlspecialchars(substr($connexion['user_agent'], 0, 50)) ?>...
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-history text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Aucune activité récente</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet Tâches -->
            <div class="tab-pane fade" id="tasks" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card activity-card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-tasks text-warning me-2"></i>
                                    Mes Tâches Récentes
                                </h5>
                                
                                <?php if (!empty($taches)): ?>
                                    <?php foreach ($taches as $tache): 
                                        $etatClass = '';
                                        switch($tache['etat']) {
                                            case 'À faire': $etatClass = 'bg-secondary'; break;
                                            case 'En cours': $etatClass = 'bg-primary'; break;
                                            case 'Terminé': $etatClass = 'bg-success'; break;
                                            case 'Bloqué': $etatClass = 'bg-danger'; break;
                                            default: $etatClass = 'bg-secondary';
                                        }
                                    ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon">
                                                    <i class="fas fa-tasks"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-0"><?= htmlspecialchars($tache['description_tache']) ?></h6>
                                                        <span class="badge <?= $etatClass ?>"><?= htmlspecialchars($tache['etat']) ?></span>
                                                    </div>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-project-diagram me-1"></i>
                                                        <?= htmlspecialchars($tache['projet_reference']) ?> - <?= htmlspecialchars($tache['projet_titre']) ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="far fa-calendar me-1"></i>
                                                        Échéance: <?= date('d/m/Y', strtotime($tache['date_fin_prevue'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-tasks text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Aucune tâche assignée</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card activity-card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-clock text-info me-2"></i>
                                    Mes Timesheets Récents
                                </h5>
                                
                                <?php if (!empty($timesheets)): ?>
                                    <?php foreach ($timesheets as $ts): 
                                        $etatClass = '';
                                        switch($ts['etat']) {
                                            case 'Brouillon': $etatClass = 'bg-secondary'; break;
                                            case 'Soumise': $etatClass = 'bg-warning'; break;
                                            case 'Validée': $etatClass = 'bg-success'; break;
                                            case 'Refusée': $etatClass = 'bg-danger'; break;
                                            default: $etatClass = 'bg-secondary';
                                        }
                                    ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-0"><?= htmlspecialchars($ts['description_travail']) ?></h6>
                                                        <span class="badge <?= $etatClass ?>"><?= htmlspecialchars($ts['etat']) ?></span>
                                                    </div>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-tasks me-1"></i>
                                                        <?= htmlspecialchars($ts['description_tache']) ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="far fa-calendar me-1"></i>
                                                        Date: <?= date('d/m/Y', strtotime($ts['date_travail'])) ?>
                                                        - Heures: <?= $ts['worked_heures'] ?>h
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-clock text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Aucun timesheet enregistré</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Modal Upload Avatar -->
    <div class="modal fade" id="uploadAvatarModal" tabindex="-1" aria-labelledby="uploadAvatarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadAvatarModalLabel">
                        <i class="fas fa-camera me-2"></i>
                        Changer la photo de profil
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <?php if ($photoProfil): ?>
                            <img src="../<?= htmlspecialchars($photoProfil) ?>" 
                                 alt="Photo actuelle" 
                                 class="modal-avatar"
                                 id="currentAvatar">
                        <?php else: ?>
                            <div class="avatar-circle mx-auto" style="width: 150px; height: 150px; font-size: 3rem;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form action="../models/update/upload-avatar.php" method="POST" enctype="multipart/form-data" class="dropzone" id="avatarDropzone">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="user_id" value="<?= $userId ?>">
                        <div class="dz-message">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #6c757d;"></i>
                            <br>
                            <span>Glissez-déposez une image ici ou cliquez pour sélectionner</span>
                            <br>
                            <small class="text-muted">Formats acceptés: JPG, PNG, GIF • Max: 2MB</small>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <button class="btn btn-outline-danger btn-sm" id="removeAvatarBtn">
                            <i class="fas fa-trash me-1"></i> Supprimer la photo
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modifier Profil -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editProfileModalLabel">
                        <i class="fas fa-edit me-2"></i>
                        Modifier mes informations
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../models/update/update-profile.php" method="POST" id="editProfileForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                    
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                                <small class="text-muted">L'email ne peut pas être modifié</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Téléphone professionnel</label>
                                <input type="tel" class="form-control" name="telephone" value="<?= htmlspecialchars($user['telephone_professionnel'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Signature (optionnel)</label>
                                <textarea class="form-control" name="signature" rows="3" placeholder="Votre signature pour les emails..."><?= htmlspecialchars($user['signature'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Sessions -->
    <div class="modal fade" id="sessionsModal" tabindex="-1" aria-labelledby="sessionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="sessionsModalLabel">
                        <i class="fas fa-laptop me-2"></i>
                        Sessions actives
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Cette fonctionnalité est en développement. Vous serez informé lorsqu'elle sera disponible.
                    </div>
                    <p class="text-muted">
                        Vous pourrez bientôt voir toutes vos sessions actives et les déconnecter à distance.
                    </p>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

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
            initializeDropzone();
            initializeQRCode();
            initializePasswordValidation();
        });

        // Initialisation de Dropzone pour l'upload d'avatar
        function initializeDropzone() {
            if (typeof Dropzone !== 'undefined') {
                Dropzone.autoDiscover = false;
                
                const avatarDropzone = new Dropzone("#avatarDropzone", {
                    url: "../models/update/upload-avatar.php",
                    paramName: "avatar",
                    maxFiles: 1,
                    maxFilesize: 2, // MB
                    acceptedFiles: "image/jpeg,image/png,image/gif",
                    dictDefaultMessage: "",
                    addRemoveLinks: false,
                    init: function() {
                        this.on("success", function(file, response) {
                            try {
                                const data = JSON.parse(response);
                                if (data.success) {
                                    // Mettre à jour les avatars
                                    updateAvatars(data.avatar_url);
                                    
                                    // Fermer le modal après un délai
                                    setTimeout(() => {
                                        $('#uploadAvatarModal').modal('hide');
                                        showAlert('success', 'Photo de profil mise à jour avec succès!');
                                    }, 1000);
                                } else {
                                    showAlert('danger', data.message || 'Erreur lors du téléchargement');
                                }
                            } catch (e) {
                                showAlert('danger', 'Erreur lors du traitement de la réponse');
                            }
                        });
                        
                        this.on("error", function(file, message) {
                            showAlert('danger', message || 'Erreur lors du téléchargement');
                        });
                    }
                });
            }
        }

        // Initialisation du QR Code
        function initializeQRCode() {
            const qrcodeContainer = document.getElementById('qrcode');
            if (qrcodeContainer) {
                // Générer les données pour le QR Code
                const userData = {
                    name: "<?= addslashes($user['prenom'] . ' ' . $user['nom']) ?>",
                    email: "<?= addslashes($user['email']) ?>",
                    roles: "<?= addslashes($user['roles_list'] ?? '') ?>",
                    userId: <?= $userId ?>,
                    timestamp: new Date().toISOString()
                };
                
                // Créer le QR Code
                new QRCode(qrcodeContainer, {
                    text: JSON.stringify(userData),
                    width: 150,
                    height: 150,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        }

        // Télécharger le QR Code
        function downloadQRCode() {
            const canvas = document.querySelector('#qrcode canvas');
            if (canvas) {
                const link = document.createElement('a');
                link.download = 'qr-code-profil-<?= $userId ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            }
        }

        // Mettre à jour les avatars après upload
        function updateAvatars(avatarUrl) {
            // Mettre à jour l'avatar principal
            const mainAvatar = document.getElementById('mainAvatar');
            const mainAvatarCircle = document.getElementById('mainAvatarCircle');
            const sidebarAvatar = document.getElementById('sidebarAvatar');
            const sidebarAvatarCircle = document.getElementById('sidebarAvatarCircle');
            const currentAvatar = document.getElementById('currentAvatar');
            
            if (avatarUrl) {
                // Créer l'élément image si nécessaire
                if (!mainAvatar) {
                    if (mainAvatarCircle) {
                        const newAvatar = document.createElement('img');
                        newAvatar.id = 'mainAvatar';
                        newAvatar.className = 'profile-avatar';
                        newAvatar.alt = 'Photo de profil';
                        mainAvatarCircle.parentNode.replaceChild(newAvatar, mainAvatarCircle);
                    }
                }
                
                if (!sidebarAvatar) {
                    if (sidebarAvatarCircle) {
                        const newAvatar = document.createElement('img');
                        newAvatar.id = 'sidebarAvatar';
                        newAvatar.className = 'profile-avatar';
                        newAvatar.alt = 'Photo de profil';
                        sidebarAvatarCircle.parentNode.replaceChild(newAvatar, sidebarAvatarCircle);
                    }
                }
                
                // Mettre à jour les sources
                const mainImg = document.getElementById('mainAvatar');
                const sidebarImg = document.getElementById('sidebarAvatar');
                const modalImg = document.getElementById('currentAvatar');
                
                if (mainImg) mainImg.src = '../' + avatarUrl;
                if (sidebarImg) sidebarImg.src = '../' + avatarUrl;
                if (modalImg) modalImg.src = '../' + avatarUrl;
            } else {
                // Supprimer l'avatar (revenir aux initiales)
                const mainAvatarImg = document.getElementById('mainAvatar');
                const sidebarAvatarImg = document.getElementById('sidebarAvatar');
                
                if (mainAvatarImg) {
                    const initials = "<?= htmlspecialchars($initials) ?>";
                    const newDiv = document.createElement('div');
                    newDiv.className = 'avatar-circle';
                    newDiv.style.width = '120px';
                    newDiv.style.height = '120px';
                    newDiv.style.fontSize = '2.5rem';
                    newDiv.id = 'mainAvatarCircle';
                    newDiv.textContent = initials;
                    mainAvatarImg.parentNode.replaceChild(newDiv, mainAvatarImg);
                }
                
                if (sidebarAvatarImg) {
                    const initials = "<?= htmlspecialchars($initials) ?>";
                    const newDiv = document.createElement('div');
                    newDiv.className = 'avatar-circle';
                    newDiv.id = 'sidebarAvatarCircle';
                    newDiv.textContent = initials;
                    sidebarAvatarImg.parentNode.replaceChild(newDiv, sidebarAvatarImg);
                }
            }
        }

        // Supprimer l'avatar
        document.getElementById('removeAvatarBtn')?.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?')) {
                fetch('../models/update/remove-avatar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>',
                        user_id: <?= $userId ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateAvatars(null);
                        showAlert('success', 'Photo de profil supprimée avec succès');
                        $('#uploadAvatarModal').modal('hide');
                    } else {
                        showAlert('danger', data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'Erreur lors de la suppression');
                });
            }
        });

        // Validation du mot de passe
        function initializePasswordValidation() {
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            const passwordMatchError = document.getElementById('passwordMatchError');
            const changePasswordForm = document.getElementById('changePasswordForm');
            
            if (confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.classList.add('is-invalid');
                        passwordMatchError.style.display = 'block';
                    } else {
                        confirmPassword.classList.remove('is-invalid');
                        passwordMatchError.style.display = 'none';
                    }
                });
            }
            
            if (changePasswordForm) {
                changePasswordForm.addEventListener('submit', function(e) {
                    if (newPassword.value !== confirmPassword.value) {
                        e.preventDefault();
                        confirmPassword.classList.add('is-invalid');
                        passwordMatchError.style.display = 'block';
                        confirmPassword.focus();
                        return false;
                    }
                    
                    // Vérifier la force du mot de passe
                    if (!checkPasswordRequirements(newPassword.value)) {
                        e.preventDefault();
                        showAlert('warning', 'Le mot de passe ne respecte pas les exigences de sécurité');
                        return false;
                    }
                    
                    return true;
                });
            }
        }

        // Vérifier la force du mot de passe
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrengthBar');
            if (!strengthBar) return;
            
            let strength = 0;
            
            // Longueur minimale
            if (password.length >= 8) strength++;
            
            // Contient des minuscules
            if (/[a-z]/.test(password)) strength++;
            
            // Contient des majuscules
            if (/[A-Z]/.test(password)) strength++;
            
            // Contient des chiffres
            if (/[0-9]/.test(password)) strength++;
            
            // Contient des caractères spéciaux
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Mettre à jour la barre de force
            strengthBar.className = 'password-strength-bar';
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
            } else if (strength <= 2) {
                strengthBar.className += ' strength-weak';
            } else if (strength === 3) {
                strengthBar.className += ' strength-fair';
            } else if (strength === 4) {
                strengthBar.className += ' strength-good';
            } else {
                strengthBar.className += ' strength-strong';
            }
        }

        // Vérifier les exigences du mot de passe
        function checkPasswordRequirements(password) {
            if (password.length < 8) return false;
            if (!/[a-z]/.test(password)) return false;
            if (!/[A-Z]/.test(password)) return false;
            if (!/[0-9]/.test(password)) return false;
            return true;
        }

        // Basculer l'affichage du mot de passe
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }

        // Afficher une alerte
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const mainContent = document.querySelector('.main-content-wrapper');
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
            
            // Auto-hide après 5 secondes
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
        }

        // Mettre à jour le nom dans le sidebar après édition
        document.getElementById('editProfileForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage
                    const sidebarName = document.getElementById('sidebarUserName');
                    const mainName = document.getElementById('mainUserName');
                    
                    if (sidebarName && data.nom && data.prenom) {
                        sidebarName.textContent = data.prenom + ' ' + data.nom;
                    }
                    
                    if (mainName && data.nom && data.prenom) {
                        mainName.textContent = data.prenom + ' ' + data.nom;
                    }
                    
                    // Fermer le modal
                    $('#editProfileModal').modal('hide');
                    
                    // Afficher un message de succès
                    showAlert('success', 'Profil mis à jour avec succès!');
                } else {
                    showAlert('danger', data.message || 'Erreur lors de la mise à jour');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Erreur lors de la mise à jour');
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