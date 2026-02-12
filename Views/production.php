<?php
# Se connecter à la BD
include '../connexion/connexion.php';

# Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fonction pour obtenir l'icône selon l'extension (côté PHP)
function getFileIconPHP($extension) {
    $icons = [
        // Documents
        'pdf' => 'fas fa-file-pdf',
        'doc' => 'fas fa-file-word',
        'docx' => 'fas fa-file-word',
        'txt' => 'fas fa-file-alt',
        'rtf' => 'fas fa-file-alt',
        
        // Images
        'jpg' => 'fas fa-file-image',
        'jpeg' => 'fas fa-file-image',
        'png' => 'fas fa-file-image',
        'gif' => 'fas fa-file-image',
        'bmp' => 'fas fa-file-image',
        'svg' => 'fas fa-file-image',
        
        // Archives
        'zip' => 'fas fa-file-archive',
        'rar' => 'fas fa-file-archive',
        'tar' => 'fas fa-file-archive',
        'gz' => 'fas fa-file-archive',
        '7z' => 'fas fa-file-archive',
        
        // Code
        'html' => 'fas fa-file-code',
        'css' => 'fas fa-file-code',
        'js' => 'fas fa-file-code',
        'php' => 'fas fa-file-code',
        'py' => 'fas fa-file-code',
        'java' => 'fas fa-file-code',
        'cpp' => 'fas fa-file-code',
        'c' => 'fas fa-file-code',
        
        // Données
        'xls' => 'fas fa-file-excel',
        'xlsx' => 'fas fa-file-excel',
        'csv' => 'fas fa-file-csv',
        'json' => 'fas fa-file-code',
        'xml' => 'fas fa-file-code',
        
        // Design
        'psd' => 'fas fa-file-image',
        'ai' => 'fas fa-file-image',
        'sketch' => 'fas fa-file-image',
        
        // Architecture
        'dwg' => 'fas fa-file-alt',
        'dxf' => 'fas fa-file-alt',
        
        // Vidéo/Audio
        'mp4' => 'fas fa-file-video',
        'avi' => 'fas fa-file-video',
        'mov' => 'fas fa-file-video',
        'mp3' => 'fas fa-file-audio',
        'wav' => 'fas fa-file-audio',
    ];
    
    $ext = strtolower($extension);
    return $icons[$ext] ?? 'fas fa-file';
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
$canAccessRoles = hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveProjets = 1;

# Générer un token CSRF UNIQUEMENT s'il n'existe pas déjà
if (empty($_SESSION['csrf_token_production'])) {
    $_SESSION['csrf_token_production'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_production'];

# Récupérer les productions avec leurs informations
try {
    $query = "
        SELECT 
            pr.*,
            p.reference as projet_reference,
            p.titre as projet_titre,
            p.etat as projet_etat,
            tp.nom as type_production,
            tp.extension_autorisee,
            d.matricule as disk_matricule,
            d.capacite_go,
            d.etat as disk_etat,
            CONCAT(u.prenom, ' ', u.nom) as chef_projet_nom
        FROM PRODUCTION pr
        LEFT JOIN PROJETS p ON pr.projet_id = p.projet_id AND p.statut = 0
        LEFT JOIN TYPES_PRODUCTION tp ON pr.type_prod_id = tp.type_prod_id AND tp.statut = 0
        LEFT JOIN DISK_STOCKAGE d ON pr.disk_id = d.disk_id AND d.statut = 0
        LEFT JOIN UTILISATEURS u ON p.chef_projet_id = u.utilisateur_id AND u.statut = 0
        WHERE pr.statut = 0
        ORDER BY pr.date_upload DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $productions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les statistiques
    $queryStats = "
        SELECT 
            COUNT(*) as total,
            COUNT(DISTINCT pr.projet_id) as projets_avec_production,
            COUNT(DISTINCT pr.type_prod_id) as types_différents,
            SUM(CASE WHEN p.etat = 'Terminé' THEN 1 ELSE 0 END) as productions_projets_termines,
            SUM(CASE WHEN pr.confidentialite = 'Confidentiel' THEN 1 ELSE 0 END) as confidentielles,
            SUM(CASE WHEN pr.confidentialite = 'Public' THEN 1 ELSE 0 END) as publiques
        FROM PRODUCTION pr
        LEFT JOIN PROJETS p ON pr.projet_id = p.projet_id
        WHERE pr.statut = 0
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les projets pour les filtres
    $queryProjets = "
        SELECT p.projet_id, p.reference, p.titre, p.etat
        FROM PROJETS p
        WHERE p.statut = 0
        ORDER BY p.reference DESC
    ";
    $stmtProjets = $conn->prepare($queryProjets);
    $stmtProjets->execute();
    $projetsList = $stmtProjets->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les types de production
    $queryTypes = "
        SELECT tp.type_prod_id, tp.nom, tp.description, d.nom as departement
        FROM TYPES_PRODUCTION tp
        LEFT JOIN DEPARTEMENTS d ON tp.departement_id = d.departement_id AND d.statut = 0
        WHERE tp.statut = 0
        ORDER BY tp.nom
    ";
    $stmtTypes = $conn->prepare($queryTypes);
    $stmtTypes->execute();
    $typesList = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les disques de stockage disponibles
    $queryDisks = "
        SELECT d.disk_id, d.matricule, d.capacite_go, d.etat,
               ROUND(d.capacite_go - COALESCE((
                   SELECT COUNT(*)
                   FROM PRODUCTION pr2 
                   WHERE pr2.disk_id = d.disk_id AND pr2.statut = 0
               ) * 0.001, 0), 2) as espace_libre_approx
        FROM DISK_STOCKAGE d
        WHERE d.statut = 0 AND d.etat = 'Fonctionnel'
        ORDER BY d.matricule
    ";
    $stmtDisks = $conn->prepare($queryDisks);
    $stmtDisks->execute();
    $disksList = $stmtDisks->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les projets où l'utilisateur est chef de projet
    $userProjects = [];
    if (in_array('Chef_projet', $userRoles)) {
        $queryUserProjects = "
            SELECT p.projet_id
            FROM PROJETS p
            WHERE p.chef_projet_id = ? AND p.statut = 0
        ";
        $stmtUserProjects = $conn->prepare($queryUserProjects);
        $stmtUserProjects->execute([$userId]);
        $userProjects = $stmtUserProjects->fetchAll(PDO::FETCH_COLUMN);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des productions: " . $e->getMessage());
    $productions = [];
    $stats = ['total' => 0, 'projets_avec_production' => 0, 'types_différents' => 0, 
              'productions_projets_termines' => 0, 'confidentielles' => 0, 'publiques' => 0];
    $projetsList = [];
    $typesList = [];
    $disksList = [];
    $userProjects = [];
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
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Productions - Evotech Africa</title>
    
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

        /* Badges spécifiques aux productions */
        .badge-confidentialite-public {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
        }
        
        .badge-confidentialite-interne {
            background: linear-gradient(135deg, #0d6efd, #3d8bfd);
            color: white;
        }
        
        .badge-confidentialite-confidentiel {
            background: linear-gradient(135deg, #6c757d, #adb5bd);
            color: white;
        }
        
        .badge-disk-fonctionnel {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
        }
        
        .badge-disk-maintenance {
            background: linear-gradient(135deg, #fd7e14, #ff922b);
            color: white;
        }
        
        .badge-disk-hs {
            background: linear-gradient(135deg, #dc3545, #e35d6a);
            color: white;
        }
        
        .badge-projet-termine {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
        }
        
        .badge-projet-en-cours {
            background: linear-gradient(135deg, #0d6efd, #3d8bfd);
            color: white;
        }
        
        .badge-projet-suspendu {
            background: linear-gradient(135deg, #6c757d, #adb5bd);
            color: white;
        }
        
        .badge-projet-annule {
            background: linear-gradient(135deg, #dc3545, #e35d6a);
            color: white;
        }

        .file-icon {
            width: 60px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .file-icon::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .production-card {
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
            border-left: 4px solid #3b82f6;
        }
        
        .production-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .production-card-confidentiel {
            border-left-color: #6c757d;
            background-color: #f8f9fa;
        }
        
        .production-card-public {
            border-left-color: #198754;
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
        
        .file-extension {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .disk-space {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .disk-space-fill {
            height: 100%;
            border-radius: 4px;
        }
        
        .file-size-badge {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #3b82f6;
            background-color: #e8f4fd;
        }
        
        .upload-area.dragover {
            border-color: #198754;
            background-color: #d1f7e5;
        }
        
        .file-preview {
            max-width: 100%;
            max-height: 200px;
            margin: 10px auto;
            display: block;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table-row td {
            vertical-align: middle;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
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
                                    <li class="active">
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
                <i class="fas fa-archive me-2"></i>
                Gestion des Productions
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher une production..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductionModal">
                        <i class="fas fa-plus-circle me-1"></i> Nouvelle Production
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
            <div class="col-md-2">
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
                                <i class="fas fa-archive"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Projets avec prod.</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $stats['projets_avec_production'] ?>
                                </h2>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Types différents</h6>
                                <h2 class="card-title text-info mb-0">
                                    <?= $stats['types_différents'] ?>
                                </h2>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="fas fa-tags"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Projets terminés</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= $stats['productions_projets_termines'] ?>
                                </h2>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-secondary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Confidentielles</h6>
                                <h2 class="card-title text-secondary mb-0">
                                    <?= $stats['confidentielles'] ?>
                                </h2>
                            </div>
                            <div class="text-secondary opacity-75">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Publiques</h6>
                                <h2 class="card-title text-danger mb-0">
                                    <?= $stats['publiques'] ?>
                                </h2>
                            </div>
                            <div class="text-danger opacity-75">
                                <i class="fas fa-globe"></i>
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
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label class="form-label">Type de production</label>
                    <select class="form-select" id="filterType">
                        <option value="">Tous les types</option>
                        <?php foreach ($typesList as $type): ?>
                            <option value="<?= $type['type_prod_id'] ?>">
                                <?= htmlspecialchars($type['nom']) ?>
                                <?php if ($type['departement']): ?>
                                    (<?= htmlspecialchars($type['departement']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Confidentialité</label>
                    <select class="form-select" id="filterConfidentialite">
                        <option value="">Tous</option>
                        <option value="Public">Public</option>
                        <option value="Interne">Interne</option>
                        <option value="Confidentiel">Confidentiel</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Disque de stockage</label>
                    <select class="form-select" id="filterDisk">
                        <option value="">Tous les disques</option>
                        <?php foreach ($disksList as $disk): ?>
                            <option value="<?= $disk['disk_id'] ?>">
                                <?= htmlspecialchars($disk['matricule']) ?>
                                (<?= $disk['capacite_go'] ?> Go)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <label class="form-label">Date d'upload (début)</label>
                    <input type="date" class="form-control" id="filterDateDebut">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date d'upload (fin)</label>
                    <input type="date" class="form-control" id="filterDateFin">
                </div>
                <div class="col-md-4">
                    <label class="form-label">État du projet</label>
                    <select class="form-select" id="filterProjetEtat">
                        <option value="">Tous les états</option>
                        <option value="En cours">En cours</option>
                        <option value="Terminé">Terminé</option>
                        <option value="Suspendu">Suspendu</option>
                        <option value="Annulé">Annulé</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2 text-muted">Chargement des productions...</p>
        </div>

        <!-- Table des productions -->
        <div class="card border-0 shadow" id="productionTable">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-archive me-2"></i>
                    Liste des productions
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($productions) ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="fas fa-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucune production ne correspond à vos critères</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>
                                    <button class="btn btn-sm p-0 border-0 bg-transparent text-start fw-bold" onclick="sortTable(1)">
                                        Fichier <i class="fas fa-sort ms-1"></i>
                                    </button>
                                </th>
                                <th>Projet</th>
                                <th>Type</th>
                                <th>Confidentialité</th>
                                <th>Stockage</th>
                                <th>Dates</th>
                                <th width="140" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productionsTable">
                            <?php
                            $n = 0;
                            foreach ($productions as $production):
                                $n++;
                                
                                // Classes CSS pour la confidentialité et état du disque
                                $confidentialiteClass = 'badge-confidentialite-' . strtolower($production['confidentialite']);
                                $diskEtatClass = 'badge-disk-' . strtolower(str_replace(' ', '-', $production['disk_etat']));
                                $projetEtatClass = 'badge-projet-' . strtolower(str_replace(' ', '-', $production['projet_etat']));
                                
                                // Classe pour la carte de production
                                $productionCardClass = 'production-card-' . strtolower($production['confidentialite']);
                                
                                // Formater les dates
                                $dateUpload = $production['date_upload'] ? date('d/m/Y H:i', strtotime($production['date_upload'])) : '-';
                                $dateModification = $production['date_modification'] ? date('d/m/Y H:i', strtotime($production['date_modification'])) : '-';
                                
                                // Extraire l'extension du fichier
                                $extension = pathinfo($production['nom_fichier'], PATHINFO_EXTENSION);
                                $fileNameWithoutExt = pathinfo($production['nom_fichier'], PATHINFO_FILENAME);
                                
                                // Obtenir l'icône du fichier selon le type
                                $fileIcon = getFileIconPHP($extension);
                                
                                // Vérifier si l'utilisateur peut modifier/supprimer
                                $canEditDelete = false;
                                $canDownload = true; // Par défaut tout le monde peut télécharger
                                
                                if (in_array('DG', $userRoles)) {
                                    $canEditDelete = true;
                                } elseif (in_array('Chef_projet', $userRoles)) {
                                    // Chef de projet peut modifier les productions de ses projets
                                    if (in_array($production['projet_id'], $userProjects)) {
                                        $canEditDelete = true;
                                    }
                                }
                                
                                // Vérifier la confidentialité pour le téléchargement
                                if ($production['confidentialite'] === 'Confidentiel' && 
                                    !in_array('DG', $userRoles) && 
                                    !in_array('Chef_projet', $userRoles)) {
                                    $canDownload = false;
                                }
                            ?>
                            <tr class="table-row production-card <?= $productionCardClass ?>" 
                                data-production-id="<?= $production['production_id'] ?>"
                                data-nom-fichier="<?= htmlspecialchars(strtolower($production['nom_fichier'])) ?>"
                                data-projet="<?= $production['projet_id'] ?>"
                                data-type="<?= $production['type_prod_id'] ?>"
                                data-confidentialite="<?= htmlspecialchars($production['confidentialite']) ?>"
                                data-disk="<?= $production['disk_id'] ?>"
                                data-date-upload="<?= $production['date_upload'] ?>"
                                data-projet-etat="<?= htmlspecialchars($production['projet_etat']) ?>"
                                data-extension="<?= htmlspecialchars($extension) ?>">
                                <td class="text-center"><?= $n ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="file-icon me-3">
                                            <i class="<?= $fileIcon ?>"></i>
                                        </div>
                                        <div>
                                            <strong class="text-primary"><?= htmlspecialchars($fileNameWithoutExt) ?></strong>
                                            <div class="text-muted small">
                                                <span class="file-extension"><?= strtoupper($extension) ?></span>
                                                <?php if ($production['cloud_url']): ?>
                                                    <span class="badge bg-info ms-2">
                                                        <i class="fas fa-cloud me-1"></i> Cloud
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-folder me-1"></i>
                                                <?= htmlspecialchars(basename(dirname($production['chemin_stockage']))) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($production['projet_reference']): ?>
                                        <div class="small">
                                            <i class="fas fa-project-diagram me-1 text-primary"></i>
                                            <strong><?= htmlspecialchars($production['projet_reference']) ?></strong>
                                            <div class="text-muted"><?= htmlspecialchars($production['projet_titre']) ?></div>
                                            <span class="badge <?= $projetEtatClass ?>">
                                                <?= htmlspecialchars($production['projet_etat']) ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Projet supprimé</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small">
                                        <i class="fas fa-tag me-1 text-info"></i>
                                        <?= htmlspecialchars($production['type_production']) ?>
                                    </div>
                                    <?php if ($production['extension_autorisee']): ?>
                                        <small class="text-muted d-block">
                                            Ext: <?= htmlspecialchars($production['extension_autorisee']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $confidentialiteClass ?>">
                                        <i class="fas fa-<?= $production['confidentialite'] === 'Confidentiel' ? 'lock' : ($production['confidentialite'] === 'Public' ? 'globe' : 'building') ?> me-1"></i>
                                        <?= htmlspecialchars($production['confidentialite']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <i class="fas fa-hdd me-1 text-warning"></i>
                                        <?= htmlspecialchars($production['disk_matricule']) ?>
                                        <span class="badge <?= $diskEtatClass ?> ms-1">
                                            <?= htmlspecialchars($production['disk_etat']) ?>
                                        </span>
                                    </div>
                                    <div class="disk-space">
                                        <div class="disk-space-fill bg-<?= $production['disk_etat'] === 'Fonctionnel' ? 'success' : ($production['disk_etat'] === 'Maintenance' ? 'warning' : 'danger') ?>" 
                                             style="width: <?= $production['capacite_go'] > 0 ? min(100, ($production['capacite_go'] / 1000) * 100) : 0 ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= $production['capacite_go'] ?> Go</small>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><i class="fas fa-upload me-1 text-success"></i> <?= $dateUpload ?></div>
                                        <?php if ($dateModification !== '-'): ?>
                                            <div class="text-muted">
                                                <i class="fas fa-edit me-1"></i> Modif: <?= $dateModification ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if ($canDownload): ?>
                                        <a href="download-production.php?id=<?= $production['production_id'] ?>" 
                                           class="btn btn-outline-primary"
                                           title="Télécharger"
                                           target="_blank">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-outline-secondary"
                                                title="Accès restreint - Confidential"
                                                disabled>
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($canEditDelete): ?>
                                        <button type="button" 
                                                class="btn btn-outline-secondary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editProductionModal"
                                                onclick="loadEditData(<?= $production['production_id'] ?>)"
                                                title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($canEditDelete): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal"
                                                onclick="setDeleteId(<?= $production['production_id'] ?>, '<?= htmlspecialchars(addslashes($production['nom_fichier'])) ?>')"
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($production['cloud_url']): ?>
                                    <div class="mt-2">
                                        <a href="<?= htmlspecialchars($production['cloud_url']) ?>" 
                                           class="btn btn-sm btn-outline-info w-100"
                                           title="Voir sur le cloud"
                                           target="_blank">
                                            <i class="fas fa-external-link-alt me-1"></i> Cloud
                                        </a>
                                    </div>
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
    <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
        <!-- Modal Ajout Production -->
        <div class="modal fade" id="addProductionModal" tabindex="-1" aria-labelledby="addProductionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addProductionModalLabel">
                            <i class="fas fa-plus-circle me-2"></i>
                            Nouvelle Production
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-production-post.php" method="POST" id="addForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="MAX_FILE_SIZE" value="104857600"> <!-- 100MB -->
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Projet associé <span class="text-danger">*</span></label>
                                    <select class="form-select" name="projet_id" required id="projetSelect">
                                        <option value="">Sélectionner un projet...</option>
                                        <?php foreach ($projetsList as $projet): ?>
                                            <option value="<?= $projet['projet_id'] ?>" 
                                                    data-etat="<?= htmlspecialchars($projet['etat']) ?>">
                                                <?= htmlspecialchars($projet['reference'] . ' - ' . $projet['titre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Les productions sont les résultats finaux d'un projet</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type de production <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type_prod_id" required id="typeSelect">
                                        <option value="">Sélectionner un type...</option>
                                        <?php foreach ($typesList as $type): ?>
                                            <option value="<?= $type['type_prod_id'] ?>"
                                                    data-extensions="<?= htmlspecialchars($type['extension_autorisee'] ?? '') ?>"
                                                    data-taille-max="<?= $type['taille_max_mo'] ?? 100 ?>">
                                                <?= htmlspecialchars($type['nom']) ?>
                                                <?php if ($type['departement']): ?>
                                                    (<?= htmlspecialchars($type['departement']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Disque de stockage <span class="text-danger">*</span></label>
                                    <select class="form-select" name="disk_id" required id="diskSelect">
                                        <option value="">Sélectionner un disque...</option>
                                        <?php foreach ($disksList as $disk): ?>
                                            <option value="<?= $disk['disk_id'] ?>"
                                                    data-espace-libre="<?= $disk['espace_libre_approx'] ?? $disk['capacite_go'] ?>">
                                                <?= htmlspecialchars($disk['matricule']) ?>
                                                (<?= $disk['capacite_go'] ?> Go - 
                                                <?= number_format($disk['espace_libre_approx'] ?? $disk['capacite_go'], 1) ?> Go libre)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Fichier <span class="text-danger">*</span></label>
                                    <div class="upload-area" id="uploadArea">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                        <h5>Glissez-déposez votre fichier ici</h5>
                                        <p class="text-muted">ou cliquez pour parcourir</p>
                                        <input type="file" class="d-none" name="production_file" id="fileInput" required>
                                        <div id="fileInfo" class="mt-3 d-none">
                                            <div class="alert alert-info">
                                                <i class="fas fa-file me-2"></i>
                                                <span id="fileName"></span>
                                                <span id="fileSize" class="ms-2"></span>
                                            </div>
                                            <img id="imagePreview" class="file-preview d-none" alt="Aperçu">
                                        </div>
                                        <div id="fileError" class="alert alert-danger mt-3 d-none"></div>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Taille maximale : <span id="maxFileSize">100</span> MB
                                        <br>Extensions autorisées : <span id="allowedExtensions">Toutes</span>
                                    </small>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Nom du fichier (optionnel)</label>
                                    <input type="text" class="form-control" name="nom_fichier_custom" 
                                           placeholder="Laissez vide pour conserver le nom original">
                                    <small class="text-muted">Si vide, le nom original du fichier sera utilisé</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Confidentialité</label>
                                    <select class="form-select" name="confidentialite">
                                        <option value="Interne" selected>Interne</option>
                                        <option value="Public">Public</option>
                                        <option value="Confidentiel">Confidentiel</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">URL Cloud (optionnel)</label>
                                    <input type="url" class="form-control" name="cloud_url" 
                                           placeholder="https://drive.google.com/...">
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-warning mt-2" id="projectWarning" style="display: none;">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <span id="warningText"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-check-circle me-1"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Modification Production -->
        <div class="modal fade" id="editProductionModal" tabindex="-1" aria-labelledby="editProductionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editProductionModalLabel">
                            <i class="fas fa-edit me-2"></i>
                            Modifier la Production
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" action="../models/update/update-production-post.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" id="editId" name="production_id">
                        <div class="modal-body" id="editModalBody">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2 text-muted">Chargement des données...</p>
                            </div>
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
                        <p>Êtes-vous sûr de vouloir supprimer la production :</p>
                        <h5 class="text-danger" id="deleteProductionNom"></h5>
                        <p class="text-muted mt-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Cette action est irréversible. La production sera marquée comme supprimée dans le système.
                        </p>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention !</strong> Le fichier physique sera également supprimé du serveur.
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
            initializeUpload();
        });

        // Initialisation des filtres
        function initializeFilters() {
            const filterProjet = document.getElementById('filterProjet');
            const filterType = document.getElementById('filterType');
            const filterConfidentialite = document.getElementById('filterConfidentialite');
            const filterDisk = document.getElementById('filterDisk');
            const filterDateDebut = document.getElementById('filterDateDebut');
            const filterDateFin = document.getElementById('filterDateFin');
            const filterProjetEtat = document.getElementById('filterProjetEtat');
            
            if (filterProjet) filterProjet.addEventListener('change', filterProductions);
            if (filterType) filterType.addEventListener('change', filterProductions);
            if (filterConfidentialite) filterConfidentialite.addEventListener('change', filterProductions);
            if (filterDisk) filterDisk.addEventListener('change', filterProductions);
            if (filterDateDebut) filterDateDebut.addEventListener('change', filterProductions);
            if (filterDateFin) filterDateFin.addEventListener('change', filterProductions);
            if (filterProjetEtat) filterProjetEtat.addEventListener('change', filterProductions);
        }

        // Initialisation de l'upload
        function initializeUpload() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            const projetSelect = document.getElementById('projetSelect');
            const typeSelect = document.getElementById('typeSelect');
            const diskSelect = document.getElementById('diskSelect');
            const submitBtn = document.getElementById('submitBtn');
            
            if (!uploadArea || !fileInput) return;
            
            // Click sur la zone d'upload
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Drag & drop
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', function() {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileSelection(fileInput.files[0]);
                }
            });
            
            // Changement de fichier via input
            fileInput.addEventListener('change', function() {
                if (this.files.length) {
                    handleFileSelection(this.files[0]);
                }
            });
            
            // Mise à jour des informations selon le type sélectionné
            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const extensions = selectedOption.getAttribute('data-extensions');
                    const tailleMax = selectedOption.getAttribute('data-taille-max') || 100;
                    
                    document.getElementById('maxFileSize').textContent = tailleMax;
                    document.getElementById('allowedExtensions').textContent = extensions || 'Toutes';
                });
            }
            
            // Vérification de l'état du projet
            if (projetSelect) {
                projetSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const projetEtat = selectedOption.getAttribute('data-etat');
                    const warningDiv = document.getElementById('projectWarning');
                    const warningText = document.getElementById('warningText');
                    
                    if (warningDiv && warningText) {
                        if (projetEtat === 'Annulé' || projetEtat === 'Suspendu') {
                            warningDiv.style.display = 'block';
                            warningText.textContent = `Le projet est "${projetEtat}". Assurez-vous que cette production est pertinente.`;
                        } else {
                            warningDiv.style.display = 'none';
                        }
                    }
                });
            }
            
            // Validation avant soumission
            document.getElementById('addForm')?.addEventListener('submit', function(e) {
                if (!validateUpload()) {
                    e.preventDefault();
                }
            });
        }
        
        // Gestion de la sélection de fichier
        function handleFileSelection(file) {
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const fileError = document.getElementById('fileError');
            const imagePreview = document.getElementById('imagePreview');
            
            if (!fileInfo || !fileName || !fileSize || !fileError || !imagePreview) return;
            
            // Réinitialiser
            fileError.classList.add('d-none');
            imagePreview.classList.add('d-none');
            
            // Afficher les informations du fichier
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.classList.remove('d-none');
            
            // Aperçu pour les images
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
            
            // Validation
            validateFile(file);
        }
        
        // Formatage de la taille du fichier
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Validation du fichier
        function validateFile(file) {
            const typeSelect = document.getElementById('typeSelect');
            if (!typeSelect || !typeSelect.selectedIndex) {
                return true; // Pas de type sélectionné, validation plus tard
            }
            
            const selectedOption = typeSelect.options[typeSelect.selectedIndex];
            const maxSizeMB = parseInt(selectedOption.getAttribute('data-taille-max') || 100);
            const allowedExtensions = selectedOption.getAttribute('data-extensions');
            const fileError = document.getElementById('fileError');
            
            if (!fileError) return false;
            
            // Vérifier la taille
            const maxSizeBytes = maxSizeMB * 1024 * 1024;
            if (file.size > maxSizeBytes) {
                fileError.textContent = `Fichier trop volumineux. Maximum: ${maxSizeMB}MB`;
                fileError.classList.remove('d-none');
                return false;
            }
            
            // Vérifier l'extension
            if (allowedExtensions && allowedExtensions.trim() !== '') {
                const extensions = allowedExtensions.toLowerCase().split(',').map(ext => ext.trim());
                const fileExt = file.name.toLowerCase().split('.').pop();
                
                if (!extensions.includes(fileExt)) {
                    fileError.textContent = `Extension non autorisée. Extensions autorisées: ${allowedExtensions}`;
                    fileError.classList.remove('d-none');
                    return false;
                }
            }
            
            return true;
        }
        
        // Validation complète de l'upload
        function validateUpload() {
            const fileInput = document.getElementById('fileInput');
            const projetSelect = document.getElementById('projetSelect');
            const typeSelect = document.getElementById('typeSelect');
            const diskSelect = document.getElementById('diskSelect');
            
            if (!fileInput || !fileInput.files.length) {
                alert('Veuillez sélectionner un fichier.');
                return false;
            }
            
            if (!projetSelect || !projetSelect.value) {
                alert('Veuillez sélectionner un projet.');
                return false;
            }
            
            if (!typeSelect || !typeSelect.value) {
                alert('Veuillez sélectionner un type de production.');
                return false;
            }
            
            if (!diskSelect || !diskSelect.value) {
                alert('Veuillez sélectionner un disque de stockage.');
                return false;
            }
            
            // Valider le fichier
            if (!validateFile(fileInput.files[0])) {
                return false;
            }
            
            return true;
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    filterProductions();
                });
            }
            
            if (clearFilterBtn) {
                clearFilterBtn.addEventListener('click', function() {
                    if (searchInput) searchInput.value = '';
                    if (document.getElementById('filterProjet')) document.getElementById('filterProjet').value = '';
                    if (document.getElementById('filterType')) document.getElementById('filterType').value = '';
                    if (document.getElementById('filterConfidentialite')) document.getElementById('filterConfidentialite').value = '';
                    if (document.getElementById('filterDisk')) document.getElementById('filterDisk').value = '';
                    if (document.getElementById('filterDateDebut')) document.getElementById('filterDateDebut').value = '';
                    if (document.getElementById('filterDateFin')) document.getElementById('filterDateFin').value = '';
                    if (document.getElementById('filterProjetEtat')) document.getElementById('filterProjetEtat').value = '';
                    filterProductions();
                });
            }
        }
        
        function filterProductions() {
            const searchInput = document.getElementById('searchInput');
            const filterProjet = document.getElementById('filterProjet');
            const filterType = document.getElementById('filterType');
            const filterConfidentialite = document.getElementById('filterConfidentialite');
            const filterDisk = document.getElementById('filterDisk');
            const filterDateDebut = document.getElementById('filterDateDebut');
            const filterDateFin = document.getElementById('filterDateFin');
            const filterProjetEtat = document.getElementById('filterProjetEtat');
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const projetFilter = filterProjet ? filterProjet.value : '';
            const typeFilter = filterType ? filterType.value : '';
            const confidentialiteFilter = filterConfidentialite ? filterConfidentialite.value : '';
            const diskFilter = filterDisk ? filterDisk.value : '';
            const dateDebutFilter = filterDateDebut ? filterDateDebut.value : '';
            const dateFinFilter = filterDateFin ? filterDateFin.value : '';
            const projetEtatFilter = filterProjetEtat ? filterProjetEtat.value : '';
            
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            const tableContainer = document.getElementById('tableContainer');
            const tableVisibleCount = document.getElementById('tableVisibleCount');
            
            if (!tableRows.length) return;
            
            let visibleRows = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (projetFilter) {
                const projetOption = document.querySelector('#filterProjet option[value="' + projetFilter + '"]');
                if (projetOption) activeFilters.push(`Projet: ${projetOption.textContent}`);
            }
            if (typeFilter) {
                const typeOption = document.querySelector('#filterType option[value="' + typeFilter + '"]');
                if (typeOption) activeFilters.push(`Type: ${typeOption.textContent}`);
            }
            if (confidentialiteFilter) activeFilters.push(`Confidentialité: ${confidentialiteFilter}`);
            if (diskFilter) {
                const diskOption = document.querySelector('#filterDisk option[value="' + diskFilter + '"]');
                if (diskOption) activeFilters.push(`Disque: ${diskOption.textContent}`);
            }
            if (projetEtatFilter) activeFilters.push(`État projet: ${projetEtatFilter}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const nomFichier = row.getAttribute('data-nom-fichier') || '';
                const projet = row.getAttribute('data-projet') || '';
                const type = row.getAttribute('data-type') || '';
                const confidentialite = row.getAttribute('data-confidentialite') || '';
                const disk = row.getAttribute('data-disk') || '';
                const dateUpload = row.getAttribute('data-date-upload') || '';
                const projetEtat = row.getAttribute('data-projet-etat') || '';
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && !nomFichier.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre projet
                if (projetFilter && projet !== projetFilter) {
                    matches = false;
                }
                
                // Filtre type
                if (typeFilter && type !== typeFilter) {
                    matches = false;
                }
                
                // Filtre confidentialité
                if (confidentialiteFilter && confidentialite !== confidentialiteFilter) {
                    matches = false;
                }
                
                // Filtre disque
                if (diskFilter && disk !== diskFilter) {
                    matches = false;
                }
                
                // Filtre date début
                if (dateDebutFilter && dateUpload < dateDebutFilter) {
                    matches = false;
                }
                
                // Filtre date fin
                if (dateFinFilter && dateUpload > dateFinFilter) {
                    matches = false;
                }
                
                // Filtre état projet
                if (projetEtatFilter && projetEtat !== projetEtatFilter) {
                    matches = false;
                }
                
                row.style.display = matches ? '' : 'none';
                if (matches) visibleRows++;
            });
            
            // Mettre à jour les compteurs
            if (tableVisibleCount) tableVisibleCount.textContent = visibleRows;
            
            // Afficher/cacher les messages "aucun résultat"
            if (filterInfo && filterText && tableNoResults && tableContainer) {
                if (activeFilters.length > 0) {
                    filterInfo.style.display = 'block';
                    filterText.textContent = activeFilters.join(' | ') + ` - ${visibleRows} résultat(s)`;
                    
                    if (visibleRows === 0) {
                        tableNoResults.style.display = 'block';
                        tableContainer.style.display = 'none';
                    } else {
                        tableNoResults.style.display = 'none';
                        tableContainer.style.display = 'block';
                    }
                } else {
                    filterInfo.style.display = 'none';
                    tableNoResults.style.display = 'none';
                    tableContainer.style.display = 'block';
                }
            }
            
            // Re-numéroter les lignes visibles
            renumberVisibleRows();
        }
        
        // Re-numéroter les lignes visibles
        function renumberVisibleRows() {
            let counter = 1;
            document.querySelectorAll('.table-row').forEach(row => {
                if (row.style.display !== 'none') {
                    const firstCell = row.querySelector('td:first-child');
                    if (firstCell) firstCell.textContent = counter;
                    counter++;
                }
            });
        }
        
        // Tri du tableau
        let sortDirection = 1;
        let lastSortedColumn = -1;
        
        function sortTable(columnIndex) {
            const table = document.getElementById('productionsTable');
            if (!table) return;
            
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
                    // Colonne Fichier
                    const aStrong = a.querySelector('strong');
                    const bStrong = b.querySelector('strong');
                    aText = aStrong ? aStrong.textContent.toLowerCase() : '';
                    bText = bStrong ? bStrong.textContent.toLowerCase() : '';
                } else if (columnIndex === 2) {
                    // Colonne Projet
                    const aStrong = a.querySelector('td:nth-child(3) strong');
                    const bStrong = b.querySelector('td:nth-child(3) strong');
                    aText = aStrong ? aStrong.textContent.toLowerCase() : '';
                    bText = bStrong ? bStrong.textContent.toLowerCase() : '';
                } else if (columnIndex === 3) {
                    // Colonne Type
                    aText = a.cells[3] ? a.cells[3].textContent.toLowerCase() : '';
                    bText = b.cells[3] ? b.cells[3].textContent.toLowerCase() : '';
                } else if (columnIndex === 4) {
                    // Colonne Confidentialité (ordre personnalisé)
                    const confOrder = { 'Confidentiel': 0, 'Interne': 1, 'Public': 2 };
                    aText = confOrder[a.getAttribute('data-confidentialite')] || 3;
                    bText = confOrder[b.getAttribute('data-confidentialite')] || 3;
                } else if (columnIndex === 6) {
                    // Colonne Date upload
                    aText = a.getAttribute('data-date-upload') || '';
                    bText = b.getAttribute('data-date-upload') || '';
                } else {
                    // Autres colonnes
                    aText = a.cells[columnIndex] ? a.cells[columnIndex].textContent.toLowerCase() : '';
                    bText = b.cells[columnIndex] ? b.cells[columnIndex].textContent.toLowerCase() : '';
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
        function loadEditData(productionId) {
            const modalBody = document.getElementById('editModalBody');
            if (!modalBody) return;
            
            // Afficher le spinner
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-muted">Chargement des données...</p>
                </div>
            `;
            
            fetch(`../models/get/get-production.php?id=${productionId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const production = data.production;
                        document.getElementById('editId').value = productionId;
                        
                        let html = `
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Nom du fichier</label>
                                    <input type="text" class="form-control" name="nom_fichier" value="${escapeHtml(production.nom_fichier)}" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Projet associé</label>
                                    <select class="form-select" name="projet_id" required>
                                        <option value="">Sélectionner un projet...</option>
                        `;
                        
                        <?php foreach ($projetsList as $projet): ?>
                            html += `<option value="<?= $projet['projet_id'] ?>" ${production.projet_id == <?= $projet['projet_id'] ?> ? 'selected' : ''}>
                                        <?= htmlspecialchars(addslashes($projet['reference'] . ' - ' . $projet['titre'])) ?>
                                    </option>`;
                        <?php endforeach; ?>
                        
                        html += `
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type de production</label>
                                    <select class="form-select" name="type_prod_id" required>
                                        <option value="">Sélectionner un type...</option>
                        `;
                        
                        <?php foreach ($typesList as $type): ?>
                            html += `<option value="<?= $type['type_prod_id'] ?>" ${production.type_prod_id == <?= $type['type_prod_id'] ?> ? 'selected' : ''}>
                                        <?= htmlspecialchars(addslashes($type['nom'])) ?>
                                        <?php if ($type['departement']): ?>
                                            (<?= htmlspecialchars(addslashes($type['departement'])) ?>)
                                        <?php endif; ?>
                                    </option>`;
                        <?php endforeach; ?>
                        
                        html += `
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Disque de stockage</label>
                                    <select class="form-select" name="disk_id" required>
                                        <option value="">Sélectionner un disque...</option>
                        `;
                        
                        <?php foreach ($disksList as $disk): ?>
                            html += `<option value="<?= $disk['disk_id'] ?>" ${production.disk_id == <?= $disk['disk_id'] ?> ? 'selected' : ''}>
                                        <?= htmlspecialchars(addslashes($disk['matricule'])) ?>
                                        (<?= $disk['capacite_go'] ?> Go)
                                    </option>`;
                        <?php endforeach; ?>
                        
                        html += `
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Confidentialité</label>
                                    <select class="form-select" name="confidentialite">
                                        <option value="Interne" ${production.confidentialite == 'Interne' ? 'selected' : ''}>Interne</option>
                                        <option value="Public" ${production.confidentialite == 'Public' ? 'selected' : ''}>Public</option>
                                        <option value="Confidentiel" ${production.confidentialite == 'Confidentiel' ? 'selected' : ''}>Confidentiel</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Chemin de stockage</label>
                                    <input type="text" class="form-control" name="chemin_stockage" value="${escapeHtml(production.chemin_stockage || '')}">
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">URL Cloud (optionnel)</label>
                                    <input type="url" class="form-control" name="cloud_url" value="${escapeHtml(production.cloud_url || '')}">
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-info mt-2">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Informations :</strong> Uploadé le ${production.date_upload_formatted || ''}
                                        ${production.date_modification_formatted ? ', modifié le ' + production.date_modification_formatted : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Erreur lors du chargement des données de la production
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erreur lors du chargement des données
                        </div>
                    `;
                });
        }
        
        // Fonction pour échapper les caractères HTML
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        // Préparer la suppression
        function setDeleteId(id, nomFichier) {
            const deleteNomElement = document.getElementById('deleteProductionNom');
            const deleteBtn = document.getElementById('deleteConfirmBtn');
            
            if (deleteNomElement) {
                deleteNomElement.textContent = nomFichier;
            }
            
            // Mettre à jour le lien de suppression avec le token CSRF
            if (deleteBtn) {
                deleteBtn.href = `../models/delete/delete-production.php?id=${id}&csrf_token=<?= $csrf_token ?>`;
            }
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