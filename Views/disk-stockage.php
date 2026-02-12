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
$canAccessRoles = hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Fonction pour générer le prochain matricule
function genererMatriculeDisque($conn, $prefixe = 'EVO', $type = 'HDD') {
    // Nettoyer le préfixe
    $cleanPrefix = str_replace('/', '', $prefixe);
    
    // Déterminer le format en fonction du préfixe
    if ($prefixe === 'EVO/D' || $prefixe === 'EVO') {
        // Format: EVO/D1/HDD
        $basePrefix = 'EVO/D';
    } elseif ($prefixe === 'EVO/HDD' || $prefixe === 'EVO/SSD' || $prefixe === 'EVO/SERVER' || $prefixe === 'EVO/NAS') {
        // Format: EVO/SERVER1/SSD
        $parts = explode('/', $prefixe);
        $basePrefix = $parts[0] . '/' . $parts[1]; // EVO/SERVER
    } else {
        // Format par défaut
        $basePrefix = $cleanPrefix;
    }
    
    // Rechercher le dernier numéro utilisé
    $query = "SELECT matricule FROM DISK_STOCKAGE 
              WHERE matricule LIKE ? AND statut = 0 
              ORDER BY LENGTH(matricule), matricule DESC LIMIT 1";
    
    $stmt = $conn->prepare($query);
    
    // Pattern de recherche selon le format
    if (strpos($basePrefix, '/') !== false) {
        $pattern = $basePrefix . '%/' . $type;
    } else {
        $pattern = $basePrefix . '%/' . $type;
    }
    
    $stmt->execute([$pattern]);
    $lastDisk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $nextNumber = 1;
    
    if ($lastDisk && preg_match('/' . preg_quote($basePrefix, '/') . '(\d+)\/' . preg_quote($type, '/') . '/', $lastDisk['matricule'], $matches)) {
        $nextNumber = intval($matches[1]) + 1;
    } else {
        // Essayer un pattern plus large
        $stmt->execute([$basePrefix . '%']);
        $allDisks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($allDisks as $disk) {
            if (preg_match('/' . preg_quote($basePrefix, '/') . '(\d+)\/[A-Z]+/', $disk['matricule'], $matches)) {
                $num = intval($matches[1]);
                if ($num >= $nextNumber) {
                    $nextNumber = $num + 1;
                }
            }
        }
    }
    
    // Générer le matricule final
    if ($prefixe === 'EVO/D' || $prefixe === 'EVO') {
        $matricule = "EVO/D" . $nextNumber . "/" . $type;
    } elseif (strpos($prefixe, '/') !== false) {
        $matricule = $basePrefix . $nextNumber . "/" . $type;
    } else {
        $matricule = $cleanPrefix . $nextNumber . "/" . $type;
    }
    
    // Vérifier l'unicité
    $checkQuery = "SELECT COUNT(*) FROM DISK_STOCKAGE WHERE matricule = ? AND statut = 0";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$matricule]);
    
    // Si le matricule existe déjà, incrémenter
    $attempts = 0;
    while ($checkStmt->fetchColumn() > 0 && $attempts < 100) {
        $nextNumber++;
        
        if ($prefixe === 'EVO/D' || $prefixe === 'EVO') {
            $matricule = "EVO/D" . $nextNumber . "/" . $type;
        } elseif (strpos($prefixe, '/') !== false) {
            $matricule = $basePrefix . $nextNumber . "/" . $type;
        } else {
            $matricule = $cleanPrefix . $nextNumber . "/" . $type;
        }
        
        $checkStmt->execute([$matricule]);
        $attempts++;
    }
    
    return $matricule;
}

// Définir le menu actif pour le sidebar
$ActiveProjets = 1;

# Récupérer les disques de stockage avec leurs informations
try {
    $query = "
        SELECT 
            ds.*,
            COUNT(p.production_id) as nb_productions,
            COALESCE(SUM(OCTET_LENGTH(p.chemin_stockage)), 0) / (1024*1024*1024) as total_size_gb,
            CASE 
                WHEN ds.etat = 'Fonctionnel' THEN 1
                WHEN ds.etat = 'Maintenance' THEN 2
                WHEN ds.etat = 'HS' THEN 3
                ELSE 4
            END as etat_order
        FROM DISK_STOCKAGE ds
        LEFT JOIN PRODUCTION p ON ds.disk_id = p.disk_id AND p.statut = 0
        WHERE ds.statut = 0
        GROUP BY ds.disk_id
        ORDER BY ds.disk_id
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $disks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les statistiques
    $queryStats = "
        SELECT 
            COUNT(*) as total,
            COALESCE(SUM(capacite_go), 0) as total_capacite_go,
            COALESCE(SUM(capacite_go) / 1024, 0) as total_capacite_to,
            COALESCE(COUNT(CASE WHEN etat = 'Fonctionnel' THEN 1 END), 0) as fonctionnel,
            COALESCE(COUNT(CASE WHEN etat = 'Maintenance' THEN 1 END), 0) as maintenance,
            COALESCE(COUNT(CASE WHEN etat = 'HS' THEN 1 END), 0) as hs,
            COALESCE(AVG(capacite_go), 0) as moyenne_capacite_go
        FROM DISK_STOCKAGE 
        WHERE statut = 0
    ";
    $stmtStats = $conn->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Calculer l'espace utilisé (approximation)
    $queryEspace = "
        SELECT 
            COALESCE(SUM(OCTET_LENGTH(chemin_stockage)), 0) as total_bytes
        FROM PRODUCTION 
        WHERE statut = 0 AND disk_id IS NOT NULL
    ";
    $stmtEspace = $conn->prepare($queryEspace);
    $stmtEspace->execute();
    $espace = $stmtEspace->fetch(PDO::FETCH_ASSOC);
    
    // Initialiser les variables avec des valeurs par défaut
    $total_espace_go = $stats['total_capacite_go'] ?? 0;
    $espace_utilise_go = ($espace['total_bytes'] ?? 0) / (1024*1024*1024);
    $espace_libre_go = max(0, $total_espace_go - $espace_utilise_go);
    $pourcentage_utilise = ($total_espace_go > 0) ? min(100, ($espace_utilise_go / $total_espace_go) * 100) : 0;
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des disques: " . $e->getMessage());
    $disks = [];
    $stats = [
        'total' => 0, 
        'total_capacite_go' => 0, 
        'total_capacite_to' => 0,
        'fonctionnel' => 0,
        'maintenance' => 0,
        'hs' => 0,
        'moyenne_capacite_go' => 0
    ];
    // Initialiser les variables même en cas d'erreur
    $total_espace_go = 0;
    $espace_utilise_go = 0;
    $espace_libre_go = 0;
    $pourcentage_utilise = 0;
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
    <title>Disques de Stockage - Evotech Africa</title>
    
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

        /* Styles spécifiques aux disques */
        .disk-card {
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
            border-left: 4px solid #3b82f6;
            margin-bottom: 20px;
        }
        
        .disk-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .disk-icon {
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
        
        .disk-badge {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .capacity-badge {
            background: linear-gradient(135deg, #0d6efd, #3d8bfd);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .etat-badge-fonctionnel {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .etat-badge-maintenance {
            background: linear-gradient(135deg, #fd7e14, #ffc107);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .etat-badge-hs {
            background: linear-gradient(135deg, #dc3545, #e35d6a);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
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
        
        .progress-bar-custom {
            border-radius: 10px;
            overflow: hidden;
            height: 20px;
        }
        
        .space-usage {
            margin-bottom: 15px;
        }
        
        .space-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .storage-stats-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            height: 100%;
        }
        
        .disk-type-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Responsive table */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .btn-group-sm .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }
        
        /* Preview matricule */
        .matricule-preview {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #0d6efd;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
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
                                    <li>
                                        <a href="types-production.php">Types de production</a>
                                    </li>
                                <?php endif; ?>
                                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                    <li class="active">
                                        <a href="disk-stockage.php">Disques de stockage</a>
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

                    <!-- Autres modules... (gardez le reste de votre sidebar) -->

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
                <i class="fas fa-hdd me-2"></i>
                Disques de Stockage
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un disque..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDiskModal">
                        <i class="fas fa-plus-circle me-1"></i> Nouveau Disque
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
                                <small class="text-muted">disques</small>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-hdd"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Capacité totale</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= number_format($stats['total_capacite_to'], 1) ?>
                                </h2>
                                <small class="text-muted">To</small>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-database"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">État</h6>
                                <div class="d-flex">
                                    <span class="badge bg-success me-2"><?= $stats['fonctionnel'] ?> Fonc.</span>
                                    <span class="badge bg-warning me-2"><?= $stats['maintenance'] ?> Maint.</span>
                                    <span class="badge bg-danger"><?= $stats['hs'] ?> HS</span>
                                </div>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="fas fa-chart-pie"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Espace utilisé</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= number_format($pourcentage_utilise, 1) ?>%
                                </h2>
                                <small class="text-muted">
                                    <?= number_format($espace_utilise_go, 1) ?> Go / <?= number_format($total_espace_go, 1) ?> Go
                                </small>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre de progression de l'espace utilisé -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-chart-pie me-2 text-primary"></i>
                    Utilisation de l'espace de stockage
                </h5>
                <div class="space-usage">
                    <div class="space-label">
                        <span>Espace libre: <?= number_format($espace_libre_go, 1) ?> Go</span>
                        <span>Espace utilisé: <?= number_format($espace_utilise_go, 1) ?> Go</span>
                    </div>
                    <div class="progress progress-bar-custom">
                        <div class="progress-bar bg-success" 
                             role="progressbar" 
                             style="width: <?= min(100, $pourcentage_utilise) ?>%"
                             aria-valuenow="<?= $pourcentage_utilise ?>"
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?= number_format($pourcentage_utilise, 1) ?>%
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

        <!-- Section principale -->
        <div class="row mb-4">
            <div class="col-md-8">
                <!-- Filtres -->
                <div class="filter-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">État</label>
                            <select class="form-select" id="filterEtat">
                                <option value="">Tous les états</option>
                                <option value="Fonctionnel">Fonctionnel</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="HS">HS</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Capacité</label>
                            <select class="form-select" id="filterCapacite">
                                <option value="">Toutes capacités</option>
                                <option value="0-500">Moins de 500 Go</option>
                                <option value="500-1000">500 Go à 1 To</option>
                                <option value="1000-2000">1 To à 2 To</option>
                                <option value="2000-99999">Plus de 2 To</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table des disques -->
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Liste des disques de stockage
                            <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($disks) ?></span> visible(s))</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Message aucun résultat -->
                        <div class="no-results" id="tableNoResults">
                            <i class="fas fa-search text-muted"></i>
                            <h5 class="mt-2">Aucun résultat trouvé</h5>
                            <p class="text-muted">Aucun disque ne correspond à vos critères</p>
                        </div>

                        <!-- Tableau -->
                        <div class="table-responsive" id="tableContainer">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>
                                            <button class="btn btn-sm p-0 border-0 bg-transparent text-start" onclick="sortTable(1)">
                                                Disque <i class="fas fa-sort ms-1"></i>
                                            </button>
                                        </th>
                                        <th>Matricule</th>
                                        <th>Capacité</th>
                                        <th>État</th>
                                        <th>Utilisation</th>
                                        <th width="120" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="disksTable">
                                    <?php
                                    $n = 0;
                                    foreach ($disks as $disk):
                                        $n++;
                                        
                                        // Calculer l'utilisation pour ce disque
                                        $disk_usage_gb = $disk['total_size_gb'] ?? 0;
                                        $disk_capacity_gb = $disk['capacite_go'] / 1024;
                                        $disk_usage_percent = $disk_capacity_gb > 0 ? ($disk_usage_gb / $disk_capacity_gb) * 100 : 0;
                                        
                                        // Déterminer la classe pour l'état
                                        $etat_class = '';
                                        switch ($disk['etat']) {
                                            case 'Fonctionnel':
                                                $etat_class = 'etat-badge-fonctionnel';
                                                break;
                                            case 'Maintenance':
                                                $etat_class = 'etat-badge-maintenance';
                                                break;
                                            case 'HS':
                                                $etat_class = 'etat-badge-hs';
                                                break;
                                        }
                                        
                                        // Déterminer l'icône selon l'état
                                        $disk_icon = 'fas fa-hdd';
                                        if ($disk['etat'] === 'Maintenance') {
                                            $disk_icon = 'fas fa-tools';
                                        } elseif ($disk['etat'] === 'HS') {
                                            $disk_icon = 'fas fa-exclamation-triangle';
                                        }
                                    ?>
                                    <tr class="table-row disk-card" 
                                        data-matricule="<?= htmlspecialchars(strtolower($disk['matricule'])) ?>"
                                        data-etat="<?= $disk['etat'] ?>"
                                        data-capacite="<?= $disk['capacite_go'] ?>"
                                        data-usage="<?= $disk_usage_percent ?>">
                                        <td class="text-center"><?= $n ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="disk-icon me-3">
                                                    <i class="<?= $disk_icon ?>"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-primary">Disque #<?= $disk['disk_id'] ?></strong>
                                                    <?php if ($disk['emplacement_physique']): ?>
                                                        <div class="text-muted small mt-1">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?= htmlspecialchars($disk['emplacement_physique']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($disk['date_acquisition']): ?>
                                                        <div class="text-muted small mt-1">
                                                            <i class="fas fa-calendar-alt me-1"></i>
                                                            Acheté le <?= date('d/m/Y', strtotime($disk['date_acquisition'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="capacity-badge">
                                                <i class="fas fa-tag me-1"></i>
                                                <?= htmlspecialchars($disk['matricule']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= number_format($disk['capacite_go'] / 1024, 1) ?> To</div>
                                            <div class="progress mt-1" style="height: 6px;">
                                                <div class="progress-bar bg-primary" 
                                                     style="width: <?= min(100, $disk_usage_percent) ?>%">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="<?= $etat_class ?>">
                                                <i class="fas fa-circle me-1"></i>
                                                <?= $disk['etat'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($disk['nb_productions'] > 0): ?>
                                                <span class="capacity-badge">
                                                    <i class="fas fa-file me-1"></i>
                                                    <?= $disk['nb_productions'] ?> fichier(s)
                                                </span>
                                                <div class="text-muted small mt-1">
                                                    <?= number_format($disk_usage_gb, 1) ?> Go / <?= number_format($disk_capacity_gb, 1) ?> To
                                                </div>
                                            <?php else: ?>
                                                <span class="disk-badge">Vide</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" 
                                                        class="btn btn-outline-secondary"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editDiskModal"
                                                        onclick="loadEditData(<?= $disk['disk_id'] ?>)"
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($disk['nb_productions'] == 0): ?>
                                                <button type="button" 
                                                        class="btn btn-outline-danger"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal"
                                                        onclick="setDeleteId(<?= $disk['disk_id'] ?>, '<?= htmlspecialchars(addslashes($disk['matricule'])) ?>')"
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
                <!-- Card Statistiques de stockage -->
                <div class="storage-stats-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Statistiques de stockage
                    </h5>
                    
                    <!-- Distribution par état -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Distribution par état</h6>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">Fonctionnel</span>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2"><?= $stats['fonctionnel'] ?></span>
                                    <div class="progress" style="width: 100px; height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?= $stats['total'] > 0 ? ($stats['fonctionnel'] / $stats['total']) * 100 : 0 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">Maintenance</span>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2"><?= $stats['maintenance'] ?></span>
                                    <div class="progress" style="width: 100px; height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: <?= $stats['total'] > 0 ? ($stats['maintenance'] / $stats['total']) * 100 : 0 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">HS</span>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2"><?= $stats['hs'] ?></span>
                                    <div class="progress" style="width: 100px; height: 8px;">
                                        <div class="progress-bar bg-danger" style="width: <?= $stats['total'] > 0 ? ($stats['hs'] / $stats['total']) * 100 : 0 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Capacité moyenne -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Capacité moyenne</h6>
                        <div class="text-center">
                            <div class="display-5 fw-bold text-primary mb-1">
                                <?= number_format($stats['moyenne_capacite_go'] / 1024, 1) ?> To
                            </div>
                            <small class="text-muted">par disque</small>
                        </div>
                    </div>
                    
                    <!-- Conseils d'utilisation -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold mb-3">Conseils de gestion</h6>
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Planifiez des sauvegardes régulières
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Surveillez l'espace libre disponible
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Maintenez les disques en bon état
                            </li>
                            <li>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Archivez les anciennes productions
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
        <!-- Modal Ajout Disque -->
        <div class="modal fade" id="addDiskModal" tabindex="-1" aria-labelledby="addDiskModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="addDiskModalLabel">
                            <i class="fas fa-plus-circle me-2"></i>
                            Nouveau Disque de Stockage
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-disk-stockage-post.php" method="POST" id="addForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Préfixe <span class="text-danger">*</span></label>
                                    <select class="form-select" name="prefixe" required id="prefixeSelect">
                                        <option value="EVO/D">EVO/D (Disque standard)</option>
                                        <option value="EVO/HDD">EVO/HDD (Disque dur)</option>
                                        <option value="EVO/SSD">EVO/SSD (SSD)</option>
                                        <option value="EVO/SERVER">EVO/SERVER (Serveur)</option>
                                        <option value="EVO/NAS">EVO/NAS (NAS)</option>
                                        <option value="EVO/ARCHIVE">EVO/ARCHIVE (Archive)</option>
                                    </select>
                                    <small class="text-muted">Sélectionnez le type de stockage</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type de stockage <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type_stockage" required id="typeStockageSelect">
                                        <option value="HDD">HDD (Disque dur)</option>
                                        <option value="SSD">SSD</option>
                                        <option value="NVMe">NVMe</option>
                                        <option value="NAS">NAS</option>
                                        <option value="CLOUD">Cloud</option>
                                        <option value="BACKUP">Backup</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Matricule généré</label>
                                    <div class="input-group">
                                        <span class="input-group-text matricule-preview" id="matriculePreview">
                                            <i class="fas fa-hdd me-2"></i>
                                            Génération...
                                        </span>
                                        <input type="text" class="form-control" 
                                               id="matriculeInput" 
                                               value="" 
                                               readonly
                                               style="font-family: 'Courier New', monospace; font-weight: bold;">
                                    </div>
                                    <small class="text-muted">
                                        Format: [Préfixe][Numéro]/[Type] - Généré automatiquement
                                    </small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Capacité (Go) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="capacite_go" required 
                                           min="1" max="100000" value="1000">
                                    <small class="text-muted">Capacité en gigaoctets (ex: 1000 pour 1 To)</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">État <span class="text-danger">*</span></label>
                                    <select class="form-select" name="etat" required>
                                        <option value="Fonctionnel" selected>Fonctionnel</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="HS">HS</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Emplacement physique</label>
                                    <input type="text" class="form-control" name="emplacement_physique" 
                                           placeholder="Ex: Salle serveur, Bureau DG, Armoire N°2">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Date d'acquisition</label>
                                    <input type="date" class="form-control" name="date_acquisition" 
                                           value="<?= date('Y-m-d') ?>">
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-light border mt-3">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        <small class="text-muted">
                                            Le matricule est généré automatiquement selon le format : [Préfixe][Numéro]/[Type]
                                            Ex: EVO/D1/HDD, EVO/SERVER1/SSD, EVO/NAS1/NAS
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
                                <i class="fas fa-check-circle me-1"></i> Ajouter le disque
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Modification Disque -->
        <div class="modal fade" id="editDiskModal" tabindex="-1" aria-labelledby="editDiskModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-primary" id="editDiskModalLabel">
                            <i class="fas fa-edit me-2"></i>
                            Modifier le Disque de Stockage
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" action="../models/update/update-disk-stockage-post.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="editId" name="disk_id">
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
                        <p>Êtes-vous sûr de vouloir supprimer le disque :</p>
                        <h5 class="text-danger" id="deleteDiskMatricule"></h5>
                        <p class="text-muted mt-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Cette action est irréversible. Le disque sera marqué comme supprimé dans le système.
                        </p>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention !</strong> Vous ne pouvez pas supprimer un disque contenant des productions.
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
        // Fonction pour générer le matricule côté client (prévisualisation)
        function genererMatriculePreview() {
            const prefixe = document.getElementById('prefixeSelect').value;
            const type = document.getElementById('typeStockageSelect').value;
            
            // Extraire le base prefix (sans le type)
            let basePrefix = 'EVO/D';
            if (prefixe.includes('/')) {
                const parts = prefixe.split('/');
                if (parts.length >= 2) {
                    basePrefix = parts[0] + '/' + parts[1];
                }
            }
            
            // Pour la prévisualisation, on utilise un numéro temporaire
            const matriculePreview = basePrefix + 'X' + '/' + type;
            
            // Mettre à jour l'affichage
            document.getElementById('matriculePreview').innerHTML = 
                `<i class="fas fa-hdd me-2"></i>${matriculePreview}`;
            document.getElementById('matriculeInput').value = 'Génération côté serveur...';
            
            return {
                prefixe: prefixe,
                type: type,
                preview: matriculePreview
            };
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
            
            // Générer la prévisualisation du matricule
            genererMatriculePreview();
        });

        // Écouteurs pour les changements de sélecteurs
        document.getElementById('prefixeSelect').addEventListener('change', genererMatriculePreview);
        document.getElementById('typeStockageSelect').addEventListener('change', genererMatriculePreview);

        // Initialisation des filtres
        function initializeFilters() {
            document.getElementById('filterEtat').addEventListener('change', filterDisks);
            document.getElementById('filterCapacite').addEventListener('change', filterDisks);
        }

        // Recherche et filtrage
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const clearFilterBtn = document.getElementById('clearFilter');
            
            searchInput.addEventListener('keyup', function() {
                filterDisks();
            });
            
            clearFilterBtn.addEventListener('click', function() {
                searchInput.value = '';
                document.getElementById('filterEtat').value = '';
                document.getElementById('filterCapacite').value = '';
                filterDisks();
            });
        }
        
        function filterDisks() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const etatFilter = document.getElementById('filterEtat').value;
            const capaciteFilter = document.getElementById('filterCapacite').value;
            
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            
            let visibleRows = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (etatFilter) activeFilters.push(`État: ${etatFilter}`);
            if (capaciteFilter) activeFilters.push(`Capacité: ${document.querySelector('#filterCapacite option[value="' + capaciteFilter + '"]')?.textContent}`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const matricule = row.getAttribute('data-matricule');
                const etat = row.getAttribute('data-etat');
                const capacite = parseInt(row.getAttribute('data-capacite'));
                
                let matches = true;
                
                // Filtre recherche
                if (searchTerm && !matricule.includes(searchTerm)) {
                    matches = false;
                }
                
                // Filtre état
                if (etatFilter && etat !== etatFilter) {
                    matches = false;
                }
                
                // Filtre capacité
                if (capaciteFilter) {
                    const [min, max] = capaciteFilter.split('-').map(Number);
                    if (capacite < min || capacite >= max) {
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
            const table = document.getElementById('disksTable');
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
                    // Colonne Disque (ID)
                    aText = parseInt(a.querySelector('strong').textContent.replace('Disque #', '')) || 0;
                    bText = parseInt(b.querySelector('strong').textContent.replace('Disque #', '')) || 0;
                } else if (columnIndex === 3) {
                    // Colonne Capacité (numérique)
                    aText = parseFloat(a.getAttribute('data-capacite')) || 0;
                    bText = parseFloat(b.getAttribute('data-capacite')) || 0;
                } else if (columnIndex === 4) {
                    // Colonne État (texte)
                    aText = a.getAttribute('data-etat') || '';
                    bText = b.getAttribute('data-etat') || '';
                } else if (columnIndex === 5) {
                    // Colonne Utilisation (numérique)
                    aText = parseFloat(a.getAttribute('data-usage')) || 0;
                    bText = parseFloat(b.getAttribute('data-usage')) || 0;
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
        function loadEditData(diskId) {
            fetch(`../models/get/get-disk-stockage.php?id=${diskId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const disk = data.disk;
                        document.getElementById('editId').value = diskId;
                        
                        let html = `
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Matricule <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="matricule" value="${disk.matricule}" required readonly>
                                    <small class="text-muted">Le matricule ne peut pas être modifié</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Capacité (Go) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="capacite_go" value="${disk.capacite_go || 1000}" required min="1" max="100000">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">État <span class="text-danger">*</span></label>
                                    <select class="form-select" name="etat" required>
                                        <option value="Fonctionnel" ${disk.etat === 'Fonctionnel' ? 'selected' : ''}>Fonctionnel</option>
                                        <option value="Maintenance" ${disk.etat === 'Maintenance' ? 'selected' : ''}>Maintenance</option>
                                        <option value="HS" ${disk.etat === 'HS' ? 'selected' : ''}>HS</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Emplacement physique</label>
                                    <input type="text" class="form-control" name="emplacement_physique" value="${disk.emplacement_physique || ''}">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Date d'acquisition</label>
                                    <input type="date" class="form-control" name="date_acquisition" value="${disk.date_acquisition || '<?= date('Y-m-d') ?>'}">
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-info mt-2">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Utilisation :</strong> Ce disque contient ${disk.nb_productions || 0} production(s)
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('editModalBody').innerHTML = html;
                    } else {
                        alert('Erreur lors du chargement des données du disque');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données');
                });
        }
        
        // Préparer la suppression
        function setDeleteId(id, matricule) {
            document.getElementById('deleteDiskMatricule').textContent = matricule;
            
            // Mettre à jour le lien de suppression
            const deleteUrl = `../models/delete/delete-disk-stockage.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }
        
        // Validation du formulaire d'ajout
        document.getElementById('addForm')?.addEventListener('submit', function(e) {
            const capacite = this.querySelector('[name="capacite_go"]').value;
            
            if (!capacite || capacite < 1 || capacite > 100000) {
                e.preventDefault();
                alert('La capacité doit être comprise entre 1 et 100000 Go');
                return false;
            }
            
            // Afficher un message de confirmation
            if (!confirm('Confirmez-vous l\'ajout de ce nouveau disque ?')) {
                e.preventDefault();
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