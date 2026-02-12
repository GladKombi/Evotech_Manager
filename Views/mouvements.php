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

// Récupérer le filtre compte_id si spécifié
$compte_id = isset($_GET['compte_id']) ? intval($_GET['compte_id']) : 0;

// Définir le menu actif pour le sidebar
$ActiveMouvements = 1;

# Récupérer les comptes TOUJOURS disponibles
try {
    $queryComptes = "SELECT compte_id, nom, type, devise, solde_actuel FROM COMPTES WHERE statut = 0 ORDER BY type, nom";
    $stmtComptes = $conn->prepare($queryComptes);
    $stmtComptes->execute();
    $comptes = $stmtComptes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des comptes: " . $e->getMessage());
    $comptes = [];
}

# Récupérer les mouvements avec jointures
try {
    // Construire la requête avec filtres
    $whereClause = "WHERE m.statut = 0";
    $params = [];
    
    if ($compte_id > 0) {
        $whereClause .= " AND m.compte_id = ?";
        $params[] = $compte_id;
    }
    
    $query = "
        SELECT 
            m.*,
            c.nom as compte_nom,
            c.type as compte_type,
            c.devise as compte_devise,
            p.reference as projet_reference,
            p.titre as projet_titre,
            cc.reference as contrat_reference,
            cc.type_contrat as contrat_type,
            u.nom as cree_par_nom,
            u.prenom as cree_par_prenom
        FROM MOUVEMENT_COMPTE m
        LEFT JOIN COMPTES c ON m.compte_id = c.compte_id
        LEFT JOIN PROJETS p ON m.projet_id = p.projet_id
        LEFT JOIN CONTRATS_COM cc ON m.contrat_id = cc.contrat_id
        LEFT JOIN UTILISATEURS u ON m.cree_par = u.utilisateur_id
        $whereClause
        ORDER BY m.date_mouvement DESC, m.date_creation DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les projets pour le formulaire
    $queryProjets = "
        SELECT projet_id, reference, titre 
        FROM PROJETS 
        WHERE statut = 0 AND etat IN ('En cours', 'Suspendu')
        ORDER BY date_debut DESC
    ";
    $stmtProjets = $conn->prepare($queryProjets);
    $stmtProjets->execute();
    $projets = $stmtProjets->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les contrats pour le formulaire
    $queryContrats = "
        SELECT contrat_id, reference, type_contrat 
        FROM CONTRATS_COM 
        WHERE statut = 0 AND etat = 'Actif'
        ORDER BY date_debut DESC
    ";
    $stmtContrats = $conn->prepare($queryContrats);
    $stmtContrats->execute();
    $contrats = $stmtContrats->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques globales
    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN m.type_mouvement = 'entrée' THEN 1 ELSE 0 END) as entrees,
            SUM(CASE WHEN m.type_mouvement = 'sortie' THEN 1 ELSE 0 END) as sorties,
            SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) as total_entrees,
            SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END) as total_sorties,
            (SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) - 
             SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END)) as solde_net
        FROM MOUVEMENT_COMPTE m
        WHERE m.statut = 0
    ";
    
    if ($compte_id > 0) {
        $statsQuery .= " AND m.compte_id = ?";
        $statsStmt = $conn->prepare($statsQuery);
        $statsStmt->execute([$compte_id]);
    } else {
        $statsStmt = $conn->prepare($statsQuery);
        $statsStmt->execute();
    }
    
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer le nom du compte filtré
    $compte_filtre = null;
    if ($compte_id > 0) {
        $queryCompte = "SELECT nom, solde_actuel, devise FROM COMPTES WHERE compte_id = ? AND statut = 0";
        $stmtCompte = $conn->prepare($queryCompte);
        $stmtCompte->execute([$compte_id]);
        $compte_filtre = $stmtCompte->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des mouvements: " . $e->getMessage());
    $mouvements = [];
    $projets = [];
    $contrats = [];
    $stats = ['total' => 0, 'entrees' => 0, 'sorties' => 0, 'total_entrees' => 0, 'total_sorties' => 0, 'solde_net' => 0];
    $compte_filtre = null;
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
    <title>Mouvements Financiers - Evotech Africa</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-blue: #0d6efd;
            --primary-blue-dark: #0b5ed7;
            --primary-blue-light: #3d8bfd;
            --success-green: #198754;
            --danger-red: #dc3545;
            --warning-yellow: #ffc107;
            --info-blue: #0dcaf0;
            --whatsapp-green: #25D366;
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

        .btn-whatsapp {
            background-color: var(--whatsapp-green);
            border-color: var(--whatsapp-green);
            color: white;
        }

        .btn-whatsapp:hover {
            background-color: #128C7E;
            border-color: #128C7E;
            color: white;
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

        /* Styles spécifiques aux mouvements */
        .entree-badge {
            background: linear-gradient(135deg, var(--success-green), #20c997);
            color: white;
        }
        
        .sortie-badge {
            background: linear-gradient(135deg, var(--danger-red), #e35d6a);
            color: white;
        }
        
        .montant-entree {
            color: var(--success-green) !important;
            font-weight: bold;
        }
        
        .montant-sortie {
            color: var(--danger-red) !important;
            font-weight: bold;
        }
        
        .filtre-compte {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 1px solid #90caf9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .transaction-entree {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success-green);
        }
        
        .transaction-sortie {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-red);
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
        
        .mouvement-card {
            border-left: 4px solid;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .mouvement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .mouvement-entree {
            border-left-color: var(--success-green);
        }
        
        .mouvement-sortie {
            border-left-color: var(--danger-red);
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
        
        .action-entree {
            background: linear-gradient(135deg, #d1e7dd, #a3cfbb);
            color: var(--success-green);
        }
        
        .action-sortie {
            background: linear-gradient(135deg, #f8d7da, #f1aeb5);
            color: var(--danger-red);
        }
        
        .action-transfert {
            background: linear-gradient(135deg, #cfe2ff, #9ec5fe);
            color: var(--primary-blue);
        }
        
        .badge-projet {
            background: linear-gradient(135deg, #6f42c1, #a370f7);
            color: white;
        }
        
        .badge-contrat {
            background: linear-gradient(135deg, #fd7e14, #ffa94d);
            color: white;
        }

        /* Action group styles */
        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .btn-action i {
            font-size: 14px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-whatsapp-action {
            background-color: #25D366;
            color: white;
            border: none;
        }

        .btn-whatsapp-action:hover {
            background-color: #128C7E;
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
                                    <li class="active">
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
                <i class="fas fa-exchange-alt me-2"></i>
                Mouvements Financiers
                <?php if ($compte_filtre): ?>
                    <small class="text-muted fs-6"> - <?= htmlspecialchars($compte_filtre['nom']) ?> (Solde: <?= $compte_filtre['devise'] ?><?= number_format($compte_filtre['solde_actuel'], 2, ',', ' ') ?>)</small>
                <?php endif; ?>
            </h1>
            <div class="d-flex align-items-center">
                <!-- Barre de recherche -->
                <div class="search-box me-3">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="Rechercher un mouvement..."
                        aria-label="Rechercher">
                </div>
                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                    <div class="dropdown me-2">
                        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-plus-circle me-1"></i> Nouveau
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addEntreeModal">
                                    <i class="bi bi-plus-circle text-success me-2"></i> Entrée
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addSortieModal">
                                    <i class="bi bi-dash-circle text-danger me-2"></i> Sortie
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addTransfertModal">
                                    <i class="bi bi-arrow-left-right text-primary me-2"></i> Transfert
                                </a>
                            </li>
                        </ul>
                    </div>
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

        <!-- Filtre par compte -->
        <?php if ($compte_id > 0): ?>
        <div class="filtre-compte">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">
                        <i class="fas fa-filter text-primary me-2"></i>
                        Filtre actif : Compte
                    </h6>
                    <p class="mb-0">
                        <strong><?= htmlspecialchars($compte_filtre['nom']) ?></strong> - 
                        Solde actuel : <span class="fw-bold"><?= $compte_filtre['devise'] ?><?= number_format($compte_filtre['solde_actuel'], 2, ',', ' ') ?></span>
                    </p>
                </div>
                <a href="mouvements.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-x-circle me-1"></i> Retirer le filtre
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions rapides -->
        <div class="quick-actions">
            <div class="row g-2">
                <div class="col-md-4">
                    <a href="#" class="action-btn action-entree" data-bs-toggle="modal" data-bs-target="#addEntreeModal">
                        <div class="transaction-icon transaction-entree">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Encaissement</h6>
                            <small>Entrée d'argent</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="#" class="action-btn action-sortie" data-bs-toggle="modal" data-bs-target="#addSortieModal">
                        <div class="transaction-icon transaction-sortie">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Décaissement</h6>
                            <small>Sortie d'argent</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="#" class="action-btn action-transfert" data-bs-toggle="modal" data-bs-target="#addTransfertModal">
                        <div class="transaction-icon">
                            <i class="fas fa-exchange-alt" style="color: var(--primary-blue);"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Transfert</h6>
                            <small>Entre comptes</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4 g-3">
            <div class="col-md-3">
                <div class="card stat-card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Mouvements</h6>
                                <h2 class="card-title text-primary mb-0">
                                    <?= $stats['total'] ?>
                                </h2>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-list"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Entrées</h6>
                                <h2 class="card-title text-success mb-0">
                                    <?= $stats['entrees'] ?>
                                </h2>
                                <small class="text-muted"><?= isset($stats['total_entrees']) ? '$' . number_format($stats['total_entrees'], 2, ',', ' ') : '$0' ?></small>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Sorties</h6>
                                <h2 class="card-title text-danger mb-0">
                                    <?= $stats['sorties'] ?>
                                </h2>
                                <small class="text-muted"><?= isset($stats['total_sorties']) ? '$' . number_format($stats['total_sorties'], 2, ',', ' ') : '$0' ?></small>
                            </div>
                            <div class="text-danger opacity-75">
                                <i class="fas fa-arrow-up"></i>
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
                                <h6 class="card-subtitle mb-2 text-muted">Solde Net</h6>
                                <h2 class="card-title text-warning mb-0">
                                    <?= isset($stats['solde_net']) ? '$' . number_format($stats['solde_net'], 2, ',', ' ') : '$0' ?>
                                </h2>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-balance-scale"></i>
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
                <div class="col-md-3">
                    <label class="form-label">Type de mouvement</label>
                    <select class="form-select" id="filterType">
                        <option value="">Tous les types</option>
                        <option value="entrée">Entrée</option>
                        <option value="sortie">Sortie</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Compte</label>
                    <select class="form-select" id="filterCompte">
                        <option value="">Tous les comptes</option>
                        <?php foreach ($comptes as $compte): ?>
                            <option value="<?= $compte['compte_id'] ?>" <?= $compte_id == $compte['compte_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?>)
                            </option>
                        <?php endforeach; ?>
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
                <div class="col-md-4">
                    <label class="form-label">Montant minimum</label>
                    <input type="number" class="form-control" id="filterMontantMin" placeholder="0" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Montant maximum</label>
                    <input type="number" class="form-control" id="filterMontantMax" placeholder="1000000" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Motif</label>
                    <input type="text" class="form-control" id="filterMotif" placeholder="Rechercher par motif...">
                </div>
            </div>
        </div>

        <!-- Vue par cartes -->
        <div class="row mb-4 g-3">
            <?php foreach ($mouvements as $mouvement): 
                $type = $mouvement['type_mouvement'];
                $typeClass = $type == 'entrée' ? 'mouvement-entree' : 'mouvement-sortie';
                $badgeClass = $type == 'entrée' ? 'entree-badge' : 'sortie-badge';
                $montantClass = $type == 'entrée' ? 'montant-entree' : 'montant-sortie';
                $dateMouvement = date('d/m/Y H:i', strtotime($mouvement['date_mouvement']));
                $montantFormatted = number_format($mouvement['montant'], 2, ',', ' ');
                $prefixe_bon = ($type == 'entrée') ? 'BEC' : 'BSC';
                $annee = date('Y', strtotime($mouvement['date_mouvement']));
                $numero_bon = $prefixe_bon . '-' . $annee . '-' . str_pad($mouvement['mouvement_id'], 6, '0', STR_PAD_LEFT);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card mouvement-card <?= $typeClass ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="card-title mb-1"><?= htmlspecialchars($mouvement['motif']) ?></h6>
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($type) ?></span>
                                <span class="badge bg-dark ms-1"><?= $numero_bon ?></span>
                                <?php if ($mouvement['projet_id']): ?>
                                    <span class="badge badge-projet ms-1">Projet</span>
                                <?php endif; ?>
                                <?php if ($mouvement['contrat_id']): ?>
                                    <span class="badge badge-contrat ms-1">Contrat</span>
                                <?php endif; ?>
                            </div>
                            <h5 class="<?= $montantClass ?> mb-0">
                                <?php if ($type == 'entrée'): ?>+<?php else: ?>-<?php endif; ?>
                                <?= $mouvement['compte_devise'] ?><?= $montantFormatted ?>
                            </h5>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Compte</small>
                            <p class="mb-0">
                                <i class="fas fa-university me-1"></i>
                                <?= htmlspecialchars($mouvement['compte_nom']) ?> (<?= htmlspecialchars($mouvement['compte_type']) ?>)
                            </p>
                        </div>
                        
                        <?php if ($mouvement['projet_reference']): ?>
                        <div class="mb-2">
                            <small class="text-muted d-block">Projet lié</small>
                            <p class="mb-0">
                                <i class="fas fa-project-diagram me-1"></i>
                                <?= htmlspecialchars($mouvement['projet_reference']) ?> - <?= htmlspecialchars($mouvement['projet_titre']) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($mouvement['contrat_reference']): ?>
                        <div class="mb-2">
                            <small class="text-muted d-block">Contrat lié</small>
                            <p class="mb-0">
                                <i class="fas fa-file-contract me-1"></i>
                                <?= htmlspecialchars($mouvement['contrat_reference']) ?> - <?= htmlspecialchars($mouvement['contrat_type']) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Bénéficiaire</small>
                            <p class="mb-0">
                                <i class="fas fa-user me-1"></i>
                                <?= $type == 'entrée' ? 'Apporteur / Client' : 'Bénéficiaire / Fournisseur' ?>
                            </p>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>
                                <?= $dateMouvement ?>
                            </small>
                            <div class="action-group">
                                <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                    <!-- Bouton Modifier -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary btn-action"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            onclick="loadMouvement(<?= $mouvement['mouvement_id'] ?>)"
                                            title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <!-- Bouton Imprimer Bon -->
                                    <?php if ($mouvement['type_mouvement'] === 'entrée'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success btn-action"
                                                onclick="imprimerBonTicket(<?= $mouvement['mouvement_id'] ?>, 'entree')"
                                                title="Imprimer bon d'entrée">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    <?php elseif ($mouvement['type_mouvement'] === 'sortie'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger btn-action"
                                                onclick="imprimerBonTicket(<?= $mouvement['mouvement_id'] ?>, 'sortie')"
                                                title="Imprimer bon de sortie">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Bouton WhatsApp -->
                                    <?php if ($mouvement['type_mouvement'] === 'entrée'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-whatsapp-action btn-action"
                                                onclick="partagerBonWhatsApp(<?= $mouvement['mouvement_id'] ?>, 'entree')"
                                                title="Partager le bon d'entrée sur WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </button>
                                    <?php elseif ($mouvement['type_mouvement'] === 'sortie'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-whatsapp-action btn-action"
                                                onclick="partagerBonWhatsApp(<?= $mouvement['mouvement_id'] ?>, 'sortie')"
                                                title="Partager le bon de sortie sur WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Bouton Supprimer -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger btn-action"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal"
                                            onclick="setDeleteId(<?= $mouvement['mouvement_id'] ?>, 'Mouvement du <?= date('d/m/Y', strtotime($mouvement['date_mouvement'])) ?> - <?= htmlspecialchars(addslashes($mouvement['motif'])) ?>')"
                                            title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Table des mouvements -->
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Liste détaillée des mouvements
                    <small class="text-white-50 ms-2">(<span id="tableVisibleCount"><?= count($mouvements) ?></span> visible(s))</small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Message aucun résultat -->
                <div class="no-results" id="tableNoResults">
                    <i class="bi bi-search text-muted"></i>
                    <h5 class="mt-2">Aucun résultat trouvé</h5>
                    <p class="text-muted">Aucun mouvement ne correspond à vos critères</p>
                </div>

                <!-- Tableau -->
                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Date</th>
                                <th>N° Bon</th>
                                <th>Compte</th>
                                <th>Type</th>
                                <th>Motif</th>
                                <th>Montant</th>
                                <th>Bénéficiaire</th>
                                <th width="120" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="mouvementsTable">
                            <?php
                            $n = 0;
                            foreach ($mouvements as $mouvement):
                                $n++;
                                $type = $mouvement['type_mouvement'];
                                $badgeClass = $type == 'entrée' ? 'entree-badge' : 'sortie-badge';
                                $montantClass = $type == 'entrée' ? 'montant-entree' : 'montant-sortie';
                                $dateMouvement = date('d/m/Y H:i', strtotime($mouvement['date_mouvement']));
                                $montantFormatted = number_format($mouvement['montant'], 2, ',', ' ');
                                $prefixe_bon = ($type == 'entrée') ? 'BEC' : 'BSC';
                                $annee = date('Y', strtotime($mouvement['date_mouvement']));
                                $numero_bon = $prefixe_bon . '-' . $annee . '-' . str_pad($mouvement['mouvement_id'], 6, '0', STR_PAD_LEFT);
                            ?>
                            <tr class="table-row" 
                                data-type="<?= htmlspecialchars($type) ?>"
                                data-compte="<?= htmlspecialchars(strtolower($mouvement['compte_nom'])) ?>"
                                data-motif="<?= htmlspecialchars(strtolower($mouvement['motif'])) ?>"
                                data-montant="<?= $mouvement['montant'] ?>"
                                data-date="<?= $mouvement['date_mouvement'] ?>"
                                data-reference="<?= htmlspecialchars(strtolower($mouvement['reference_piece'] ?? '')) ?>">
                                <td class="text-center"><?= $n ?></td>
                                <td><?= $dateMouvement ?></td>
                                <td><span class="badge bg-secondary"><?= $numero_bon ?></span></td>
                                <td><?= htmlspecialchars($mouvement['compte_nom']) ?></td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst($type) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($mouvement['motif']) ?></td>
                                <td class="fw-bold <?= $montantClass ?>">
                                    <?php if ($type == 'entrée'): ?>+<?php else: ?>-<?php endif; ?>
                                    <?= $mouvement['compte_devise'] ?><?= $montantFormatted ?>
                                </td>
                                <td>
                                    <?= $type == 'entrée' ? 'Apporteur' : 'Bénéficiaire' ?>
                                </td>
                                <td class="text-center">
                                    <div class="action-group justify-content-center">
                                        <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
                                            <!-- Modifier -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary btn-action"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal"
                                                    onclick="loadMouvement(<?= $mouvement['mouvement_id'] ?>)"
                                                    title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <!-- Imprimer -->
                                            <?php if ($mouvement['type_mouvement'] === 'entrée'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success btn-action"
                                                        onclick="imprimerBonTicket(<?= $mouvement['mouvement_id'] ?>, 'entree')"
                                                        title="Imprimer bon d'entrée">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            <?php elseif ($mouvement['type_mouvement'] === 'sortie'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger btn-action"
                                                        onclick="imprimerBonTicket(<?= $mouvement['mouvement_id'] ?>, 'sortie')"
                                                        title="Imprimer bon de sortie">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- WhatsApp -->
                                            <?php if ($mouvement['type_mouvement'] === 'entrée'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-whatsapp-action btn-action"
                                                        onclick="partagerBonWhatsApp(<?= $mouvement['mouvement_id'] ?>, 'entree')"
                                                        title="Partager sur WhatsApp">
                                                    <i class="bi bi-whatsapp"></i>
                                                </button>
                                            <?php elseif ($mouvement['type_mouvement'] === 'sortie'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-whatsapp-action btn-action"
                                                        onclick="partagerBonWhatsApp(<?= $mouvement['mouvement_id'] ?>, 'sortie')"
                                                        title="Partager sur WhatsApp">
                                                    <i class="bi bi-whatsapp"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Supprimer -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger btn-action"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal"
                                                    onclick="setDeleteId(<?= $mouvement['mouvement_id'] ?>, 'Mouvement du <?= date('d/m/Y', strtotime($mouvement['date_mouvement'])) ?>')"
                                                    title="Supprimer">
                                                <i class="bi bi-trash"></i>
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

    <!-- Modals -->
    <?php if (hasPermission(['DG', 'comptable'], $userRoles)): ?>
        <!-- Modal Ajout Entrée -->
        <div class="modal fade" id="addEntreeModal" tabindex="-1" aria-labelledby="addEntreeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="addEntreeModalLabel">
                            <i class="fas fa-arrow-down me-2"></i>
                            Nouvelle Entrée
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-mouvement.php" method="POST" id="addEntreeForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="type_mouvement" value="entrée">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Compte <span class="text-danger">*</span></label>
                                    <select class="form-select" name="compte_id" required>
                                        <option value="">Sélectionner un compte</option>
                                        <?php foreach ($comptes as $compte): ?>
                                            <option value="<?= $compte['compte_id'] ?>" data-devise="<?= htmlspecialchars($compte['devise']) ?>">
                                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?> - Solde: <?= $compte['devise'] ?><?= number_format($compte['solde_actuel'], 2, ',', ' ') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Montant <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="entreeDevise">$</span>
                                        <input type="number" class="form-control" name="montant" step="0.01" min="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Motif <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="motif" required 
                                           placeholder="Ex: Paiement client, Remboursement...">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Référence pièce</label>
                                    <input type="text" class="form-control" name="reference_piece" 
                                           placeholder="Ex: FACT-2024-001, RECU-001...">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Projet lié</label>
                                    <select class="form-select" name="projet_id">
                                        <option value="">Aucun projet</option>
                                        <?php foreach ($projets as $projet): ?>
                                            <option value="<?= $projet['projet_id'] ?>">
                                                <?= htmlspecialchars($projet['reference']) ?> - <?= htmlspecialchars($projet['titre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Contrat lié</label>
                                    <select class="form-select" name="contrat_id">
                                        <option value="">Aucun contrat</option>
                                        <?php foreach ($contrats as $contrat): ?>
                                            <option value="<?= $contrat['contrat_id'] ?>">
                                                <?= htmlspecialchars($contrat['reference']) ?> - <?= htmlspecialchars($contrat['type_contrat']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i> Enregistrer l'entrée
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Ajout Sortie -->
        <div class="modal fade" id="addSortieModal" tabindex="-1" aria-labelledby="addSortieModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="addSortieModalLabel">
                            <i class="fas fa-arrow-up me-2"></i>
                            Nouvelle Sortie
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/add/add-mouvement.php" method="POST" id="addSortieForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="type_mouvement" value="sortie">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Compte <span class="text-danger">*</span></label>
                                    <select class="form-select" name="compte_id" required id="sortieCompte">
                                        <option value="">Sélectionner un compte</option>
                                        <?php foreach ($comptes as $compte): ?>
                                            <option value="<?= $compte['compte_id'] ?>" data-devise="<?= htmlspecialchars($compte['devise']) ?>" data-solde="<?= $compte['solde_actuel'] ?>">
                                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?> - Solde: <?= $compte['devise'] ?><?= number_format($compte['solde_actuel'], 2, ',', ' ') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Montant <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="sortieDevise">$</span>
                                        <input type="number" class="form-control" name="montant" step="0.01" min="0.01" required id="sortieMontant">
                                        <div class="invalid-feedback" id="soldeError">
                                            Le montant ne peut pas dépasser le solde du compte.
                                        </div>
                                    </div>
                                    <small class="text-muted" id="soldeInfo">Solde disponible: $0.00</small>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Motif <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="motif" required 
                                           placeholder="Ex: Achat fournitures, Paiement salaire...">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Référence pièce</label>
                                    <input type="text" class="form-control" name="reference_piece" 
                                           placeholder="Ex: FACT-2024-001, RECU-001...">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Projet lié</label>
                                    <select class="form-select" name="projet_id">
                                        <option value="">Aucun projet</option>
                                        <?php foreach ($projets as $projet): ?>
                                            <option value="<?= $projet['projet_id'] ?>">
                                                <?= htmlspecialchars($projet['reference']) ?> - <?= htmlspecialchars($projet['titre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Contrat lié</label>
                                    <select class="form-select" name="contrat_id">
                                        <option value="">Aucun contrat</option>
                                        <?php foreach ($contrats as $contrat): ?>
                                            <option value="<?= $contrat['contrat_id'] ?>">
                                                <?= htmlspecialchars($contrat['reference']) ?> - <?= htmlspecialchars($contrat['type_contrat']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-check-circle me-1"></i> Enregistrer la sortie
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Transfert -->
        <div class="modal fade" id="addTransfertModal" tabindex="-1" aria-labelledby="addTransfertModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
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
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Compte source <span class="text-danger">*</span></label>
                                    <select class="form-select" name="compte_source_id" required id="compteSource">
                                        <option value="">Sélectionner le compte source</option>
                                        <?php foreach ($comptes as $compte): ?>
                                            <option value="<?= $compte['compte_id'] ?>" data-devise="<?= htmlspecialchars($compte['devise']) ?>" data-solde="<?= $compte['solde_actuel'] ?>">
                                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?> - Solde: <?= $compte['devise'] ?><?= number_format($compte['solde_actuel'], 2, ',', ' ') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Compte destination <span class="text-danger">*</span></label>
                                    <select class="form-select" name="compte_destination_id" required id="compteDestination">
                                        <option value="">Sélectionner le compte destination</option>
                                        <?php foreach ($comptes as $compte): ?>
                                            <option value="<?= $compte['compte_id'] ?>" data-devise="<?= htmlspecialchars($compte['devise']) ?>">
                                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?> - Solde: <?= $compte['devise'] ?><?= number_format($compte['solde_actuel'], 2, ',', ' ') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
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
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Motif <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="motif" required 
                                           placeholder="Ex: Transfert inter-comptes, Approvisionnement caisse...">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Référence pièce</label>
                                    <input type="text" class="form-control" name="reference_piece" 
                                           placeholder="Ex: TRANSF-2024-001...">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Annuler
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Effectuer le transfert
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal d'édition du mouvement -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>
                            Modifier le mouvement
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../models/update/update-mouvement.php" method="POST" id="editForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="mouvement_id" id="editMouvementId">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Compte <span class="text-danger">*</span></label>
                                    <select class="form-select" name="compte_id" id="editCompteId" required>
                                        <option value="">Sélectionner un compte</option>
                                        <?php foreach ($comptes as $compte): ?>
                                            <option value="<?= $compte['compte_id'] ?>">
                                                <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type_mouvement" id="editTypeMouvement" required>
                                        <option value="">Sélectionner</option>
                                        <option value="entrée">Entrée</option>
                                        <option value="sortie">Sortie</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Montant <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="montant" id="editMontant" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date du mouvement</label>
                                    <input type="datetime-local" class="form-control" name="date_mouvement" id="editDateMouvement">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Motif <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="motif" id="editMotif" rows="3" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Projet lié</label>
                                    <select class="form-select" name="projet_id" id="editProjetId">
                                        <option value="">Aucun projet</option>
                                        <?php foreach ($projets as $projet): ?>
                                            <option value="<?= $projet['projet_id'] ?>">
                                                <?= htmlspecialchars($projet['reference']) ?> - <?= htmlspecialchars($projet['titre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Contrat lié</label>
                                    <select class="form-select" name="contrat_id" id="editContratId">
                                        <option value="">Aucun contrat</option>
                                        <?php foreach ($contrats as $contrat): ?>
                                            <option value="<?= $contrat['contrat_id'] ?>">
                                                <?= htmlspecialchars($contrat['reference']) ?> - <?= htmlspecialchars($contrat['type_contrat']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Référence pièce</label>
                                    <input type="text" class="form-control" name="reference_piece" id="editReferencePiece" placeholder="Numéro de facture, reçu, etc.">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Enregistrer
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
                        <p>Êtes-vous sûr de vouloir supprimer le mouvement :</p>
                        <h5 class="text-danger" id="deleteMouvementName"></h5>
                        <p class="text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette action est irréversible. Le mouvement sera marqué comme supprimé dans le système et le solde du compte sera ajusté.
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
        // ============================================
        // FONCTIONS POUR LES BONS DE CAISSE
        // ============================================

        /**
         * Imprimer un bon de caisse (format ticket 80mm)
         */
        function imprimerBonTicket(mouvementId, type) {
            Swal.fire({
                title: 'Bénéficiaire',
                input: 'text',
                inputLabel: `Nom du bénéficiaire pour ce bon de ${type === 'entree' ? "d'entrée" : "de sortie"}`,
                inputValue: type === 'entree' ? 'APPORTEUR' : 'BÉNÉFICIAIRE',
                showCancelButton: true,
                confirmButtonText: 'Imprimer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: type === 'entree' ? '#198754' : '#dc3545',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Veuillez saisir le nom du bénéficiaire';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const beneficiaire = result.value;
                    let url = `imprimer-bon-ticket.php?id=${mouvementId}&type=${type}&beneficiaire=${encodeURIComponent(beneficiaire)}&auto_print=1`;
                    window.open(url, '_blank', 'width=500,height=800,scrollbars=yes,resizable=yes');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Bon généré',
                        text: `Le bon de ${type === 'entree' ? 'entrée' : 'sortie'} a été ouvert dans une nouvelle fenêtre`,
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            });
        }

        /**
         * Partager un bon sur WhatsApp
         */
        function partagerBonWhatsApp(mouvementId, type) {
            Swal.fire({
                title: 'Partager sur WhatsApp',
                html: `
                    <div class="text-left">
                        <label class="form-label fw-bold">Numéro WhatsApp</label>
                        <input type="text" id="whatsappNumber" class="form-control form-control-lg mb-3" 
                               placeholder="Ex: 221771234567" value="">
                        <small class="text-muted">Format international sans + (ex: 221771234567)</small>
                        
                        <label class="form-label fw-bold mt-3">Bénéficiaire</label>
                        <input type="text" id="beneficiaireName" class="form-control" 
                               value="${type === 'entree' ? 'APPORTEUR' : 'BÉNÉFICIAIRE'}">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '📱 Partager',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#25D366',
                preConfirm: () => {
                    const number = document.getElementById('whatsappNumber').value;
                    const beneficiaire = document.getElementById('beneficiaireName').value;
                    
                    if (!number) {
                        Swal.showValidationMessage('Veuillez saisir un numéro WhatsApp');
                        return false;
                    }
                    
                    // Nettoyer le numéro (garder seulement les chiffres)
                    const cleanNumber = number.replace(/\D/g, '');
                    
                    if (cleanNumber.length < 9) {
                        Swal.showValidationMessage('Numéro WhatsApp invalide');
                        return false;
                    }
                    
                    return { number: cleanNumber, beneficiaire: beneficiaire };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { number, beneficiaire } = result.value;
                    
                    // Construire le message
                    const typeText = type === 'entree' ? 'ENTRÉE' : 'SORTIE';
                    const date = new Date().toLocaleDateString('fr-FR');
                    const montant = '<?= $mouvement["montant"] ?? "[MONTANT]" ?>';
                    const devise = '<?= $mouvement["compte_devise"] ?? "CFA" ?>';
                    
                    const message = `
*EVOTECH AFRICA - BON DE ${typeText}*
                        
N° Bon: ${type === 'entree' ? 'BEC' : 'BSC'}-${new Date().getFullYear()}-${String(mouvementId).padStart(6, '0')}
Date: ${date}
Bénéficiaire: ${beneficiaire}
Montant: ${devise} ${montant}
                        
👉 Lien vers le bon: ${window.location.origin}/Evotech_Manager/Views/imprimer-bon-ticket.php?id=${mouvementId}&type=${type}&beneficiaire=${encodeURIComponent(beneficiaire)}&auto_print=0
                        
_Édité par ${'<?= htmlspecialchars($_SESSION["prenom"] ?? "") ?>'} ${'<?= htmlspecialchars($_SESSION["nom"] ?? "") ?>'}_
                    `;
                    
                    // Encoder le message
                    const encodedMessage = encodeURIComponent(message.trim());
                    
                    // Ouvrir WhatsApp Web ou l'application
                    const whatsappUrl = `https://wa.me/${number}?text=${encodedMessage}`;
                    window.open(whatsappUrl, '_blank');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Message préparé !',
                        text: 'WhatsApp va s\'ouvrir avec le message pré-rempli.',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            });
        }

        /**
         * Partager en une étape (version rapide)
         */
        function partagerBonWhatsAppRapide(mouvementId, type) {
            const beneficiaire = type === 'entree' ? 'APPORTEUR' : 'BÉNÉFICIAIRE';
            const typeText = type === 'entree' ? 'ENTRÉE' : 'SORTIE';
            const date = new Date().toLocaleDateString('fr-FR');
            
            const message = `EVOTECH - Bon ${typeText} N° ${type === 'entree' ? 'BEC' : 'BSC'}-${new Date().getFullYear()}-${String(mouvementId).padStart(6, '0')}`;
            
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }

        // ============================================
        // GESTION DU SIDEBAR
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebarToggler = document.querySelector('.sidebar-toggler');

            // Toggle sidebar on mobile
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.add('active');
                    overlay.style.display = 'block';
                });
            }

            // Close sidebar when clicking the toggler
            if (sidebarToggler) {
                sidebarToggler.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.style.display = 'none';
                });
            }

            // Close sidebar when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    this.style.display = 'none';
                });
            }

            // Gestion des sous-menus
            const hasSubItems = document.querySelectorAll('.sidebar-item.has-sub');
            hasSubItems.forEach(item => {
                const link = item.querySelector('.sidebar-link');
                if (link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        hasSubItems.forEach(otherItem => {
                            if (otherItem !== item && otherItem.classList.contains('active')) {
                                otherItem.classList.remove('active');
                            }
                        });
                        item.classList.toggle('active');
                    });
                }
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
            initializeFormValidations();
        });

        // ============================================
        // RECHERCHE ET FILTRAGE
        // ============================================
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    filterMouvements();
                });
            }
        }

        function initializeFilters() {
            const filterType = document.getElementById('filterType');
            const filterCompte = document.getElementById('filterCompte');
            const filterDateDebut = document.getElementById('filterDateDebut');
            const filterDateFin = document.getElementById('filterDateFin');
            const filterMontantMin = document.getElementById('filterMontantMin');
            const filterMontantMax = document.getElementById('filterMontantMax');
            const filterMotif = document.getElementById('filterMotif');
            const clearFilterBtn = document.getElementById('clearFilter');

            if (filterType) filterType.addEventListener('change', filterMouvements);
            if (filterDateDebut) filterDateDebut.addEventListener('change', filterMouvements);
            if (filterDateFin) filterDateFin.addEventListener('change', filterMouvements);
            if (filterMontantMin) filterMontantMin.addEventListener('input', filterMouvements);
            if (filterMontantMax) filterMontantMax.addEventListener('input', filterMouvements);
            if (filterMotif) filterMotif.addEventListener('keyup', filterMouvements);
            
            if (filterCompte) {
                filterCompte.addEventListener('change', function() {
                    const compteId = this.value;
                    if (compteId) {
                        window.location.href = `mouvements.php?compte_id=${compteId}`;
                    }
                });
            }
            
            if (clearFilterBtn) {
                clearFilterBtn.addEventListener('click', function() {
                    if (searchInput) searchInput.value = '';
                    if (filterType) filterType.value = '';
                    if (filterCompte) filterCompte.value = '';
                    if (filterDateDebut) filterDateDebut.value = '';
                    if (filterDateFin) filterDateFin.value = '';
                    if (filterMontantMin) filterMontantMin.value = '';
                    if (filterMontantMax) filterMontantMax.value = '';
                    if (filterMotif) filterMotif.value = '';
                    filterMouvements();
                });
            }
        }

        function filterMouvements() {
            const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
            const typeFilter = document.getElementById('filterType')?.value || '';
            const compteFilter = document.getElementById('filterCompte')?.value || '';
            const dateDebutFilter = document.getElementById('filterDateDebut')?.value || '';
            const dateFinFilter = document.getElementById('filterDateFin')?.value || '';
            const montantMin = parseFloat(document.getElementById('filterMontantMin')?.value) || -Infinity;
            const montantMax = parseFloat(document.getElementById('filterMontantMax')?.value) || Infinity;
            const motifFilter = document.getElementById('filterMotif')?.value.toLowerCase() || '';
            
            const tableRows = document.querySelectorAll('.table-row');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            const tableNoResults = document.getElementById('tableNoResults');
            const tableContainer = document.getElementById('tableContainer');
            const mouvementCards = document.querySelectorAll('.col-md-6.col-lg-4');
            
            let visibleRows = 0;
            let visibleCards = 0;
            let activeFilters = [];
            
            if (searchTerm) activeFilters.push(`Recherche: "${searchTerm}"`);
            if (typeFilter) {
                const typeText = typeFilter === 'entrée' ? 'Entrée' : 'Sortie';
                activeFilters.push(`Type: ${typeText}`);
            }
            if (compteFilter) {
                const compteOption = document.querySelector('#filterCompte option:checked');
                const compteText = compteOption ? compteOption.textContent.split('(')[0].trim() : '';
                if (compteText) activeFilters.push(`Compte: ${compteText}`);
            }
            if (dateDebutFilter) activeFilters.push(`Du: ${dateDebutFilter}`);
            if (dateFinFilter) activeFilters.push(`Au: ${dateFinFilter}`);
            if (montantMin !== -Infinity) activeFilters.push(`Min: ${montantMin}`);
            if (montantMax !== Infinity) activeFilters.push(`Max: ${montantMax}`);
            if (motifFilter) activeFilters.push(`Motif: "${motifFilter}"`);
            
            // Filtrer les lignes du tableau
            tableRows.forEach(row => {
                const type = row.getAttribute('data-type');
                const compte = row.getAttribute('data-compte');
                const motif = row.getAttribute('data-motif');
                const montant = parseFloat(row.getAttribute('data-montant'));
                const date = row.getAttribute('data-date');
                const reference = row.getAttribute('data-reference');
                
                let matches = true;
                
                if (searchTerm && !motif.includes(searchTerm) && !reference.includes(searchTerm)) {
                    matches = false;
                }
                if (typeFilter && type !== typeFilter) {
                    matches = false;
                }
                if (compteFilter && compte !== compteFilter.toLowerCase()) {
                    matches = false;
                }
                if (dateDebutFilter && date < dateDebutFilter) {
                    matches = false;
                }
                if (dateFinFilter && date > dateFinFilter) {
                    matches = false;
                }
                if (montant < montantMin || montant > montantMax) {
                    matches = false;
                }
                if (motifFilter && !motif.includes(motifFilter)) {
                    matches = false;
                }
                
                row.style.display = matches ? '' : 'none';
                if (matches) visibleRows++;
            });
            
            // Filtrer les cartes
            mouvementCards.forEach(card => {
                const mouvementCard = card.querySelector('.mouvement-card');
                if (!mouvementCard) return;
                
                const motif = mouvementCard.querySelector('.card-title')?.textContent.toLowerCase() || '';
                const type = mouvementCard.querySelector('.badge')?.textContent.toLowerCase() || '';
                const montantText = mouvementCard.querySelector('h5')?.textContent || '0';
                const montant = parseFloat(montantText.replace(/[^0-9.-]+/g, '')) || 0;
                
                let matches = true;
                
                if (searchTerm && !motif.includes(searchTerm)) {
                    matches = false;
                }
                if (typeFilter && !type.includes(typeFilter.replace('é', 'e'))) {
                    matches = false;
                }
                if (motifFilter && !motif.includes(motifFilter)) {
                    matches = false;
                }
                if (montant < montantMin || montant > montantMax) {
                    matches = false;
                }
                
                card.style.display = matches ? '' : 'none';
                if (matches) visibleCards++;
            });
            
            // Mettre à jour les compteurs
            const tableVisibleCount = document.getElementById('tableVisibleCount');
            if (tableVisibleCount) tableVisibleCount.textContent = visibleRows;
            
            // Afficher/cacher les messages "aucun résultat"
            if (activeFilters.length > 0) {
                if (filterInfo) filterInfo.style.display = 'block';
                if (filterText) filterText.textContent = activeFilters.join(' | ') + ` - ${visibleRows} résultat(s)`;
                
                if (visibleRows === 0) {
                    if (tableNoResults) tableNoResults.style.display = 'block';
                    if (tableContainer) tableContainer.style.display = 'none';
                } else {
                    if (tableNoResults) tableNoResults.style.display = 'none';
                    if (tableContainer) tableContainer.style.display = 'block';
                }
            } else {
                if (filterInfo) filterInfo.style.display = 'none';
                if (tableNoResults) tableNoResults.style.display = 'none';
                if (tableContainer) tableContainer.style.display = 'block';
            }
            
            // Re-numéroter les lignes visibles
            renumberVisibleRows();
        }

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

        // ============================================
        // VALIDATION DES FORMULAIRES
        // ============================================
        function initializeFormValidations() {
            // Gestion devise pour entrée
            const compteSelect = document.querySelector('select[name="compte_id"]');
            if (compteSelect) {
                compteSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const devise = selectedOption.getAttribute('data-devise');
                    const entreeDevise = document.getElementById('entreeDevise');
                    if (entreeDevise) entreeDevise.textContent = devise;
                });
            }
            
            // Gestion devise et solde pour sortie
            const sortieCompteSelect = document.getElementById('sortieCompte');
            const sortieMontantInput = document.getElementById('sortieMontant');
            
            if (sortieCompteSelect && sortieMontantInput) {
                sortieCompteSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const devise = selectedOption.getAttribute('data-devise');
                    const solde = parseFloat(selectedOption.getAttribute('data-solde')) || 0;
                    
                    const sortieDevise = document.getElementById('sortieDevise');
                    const soldeInfo = document.getElementById('soldeInfo');
                    
                    if (sortieDevise) sortieDevise.textContent = devise;
                    if (soldeInfo) soldeInfo.textContent = `Solde disponible: ${devise}${solde.toFixed(2)}`;
                    
                    sortieMontantInput.classList.remove('is-invalid');
                });
                
                sortieMontantInput.addEventListener('input', function() {
                    const selectedOption = sortieCompteSelect.options[sortieCompteSelect.selectedIndex];
                    const solde = parseFloat(selectedOption.getAttribute('data-solde')) || 0;
                    const montant = parseFloat(this.value) || 0;
                    
                    if (montant > solde) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            }
            
            // Gestion transfert
            const compteSourceSelect = document.getElementById('compteSource');
            const compteDestSelect = document.getElementById('compteDestination');
            const transfertMontantInput = document.getElementById('transfertMontant');
            
            if (compteSourceSelect && transfertMontantInput) {
                compteSourceSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const devise = selectedOption.getAttribute('data-devise');
                    const solde = parseFloat(selectedOption.getAttribute('data-solde')) || 0;
                    
                    const transfertDevise = document.getElementById('transfertDevise');
                    const transfertSoldeInfo = document.getElementById('transfertSoldeInfo');
                    
                    if (transfertDevise) transfertDevise.textContent = devise;
                    if (transfertSoldeInfo) transfertSoldeInfo.textContent = `Solde disponible: ${devise}${solde.toFixed(2)}`;
                    
                    transfertMontantInput.classList.remove('is-invalid');
                    
                    if (compteDestSelect) {
                        updateDestinationOptions();
                    }
                });
                
                transfertMontantInput.addEventListener('input', function() {
                    const selectedOption = compteSourceSelect.options[compteSourceSelect.selectedIndex];
                    const solde = parseFloat(selectedOption.getAttribute('data-solde')) || 0;
                    const montant = parseFloat(this.value) || 0;
                    
                    if (montant > solde) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            }
            
            if (compteDestSelect && compteSourceSelect) {
                compteDestSelect.addEventListener('change', function() {
                    const sourceId = compteSourceSelect.value;
                    const destId = this.value;
                    
                    if (sourceId && destId && sourceId === destId) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Le compte source et le compte destination ne peuvent pas être identiques.'
                        });
                        this.value = '';
                    }
                });
            }
        }

        function updateDestinationOptions() {
            const sourceId = document.getElementById('compteSource')?.value;
            const destSelect = document.getElementById('compteDestination');
            
            if (!destSelect) return;
            
            destSelect.value = '';
            
            Array.from(destSelect.options).forEach(option => {
                option.disabled = false;
            });
            
            if (sourceId) {
                const sourceOption = destSelect.querySelector(`option[value="${sourceId}"]`);
                if (sourceOption) {
                    sourceOption.disabled = true;
                }
            }
        }

        // ============================================
        // GESTION DES MOUVEMENTS
        // ============================================
        function loadMouvement(mouvementId) {
            fetch('../models/get/get-mouvement.php?id=' + mouvementId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const mouvement = data.data;
                        document.getElementById('editMouvementId').value = mouvement.mouvement_id;
                        document.getElementById('editCompteId').value = mouvement.compte_id;
                        document.getElementById('editTypeMouvement').value = mouvement.type_mouvement;
                        document.getElementById('editMontant').value = mouvement.montant;
                        document.getElementById('editMotif').value = mouvement.motif;
                        document.getElementById('editProjetId').value = mouvement.projet_id || '';
                        document.getElementById('editContratId').value = mouvement.contrat_id || '';
                        document.getElementById('editReferencePiece').value = mouvement.reference_piece || '';
                        
                        if (mouvement.date_mouvement) {
                            const dateObj = new Date(mouvement.date_mouvement);
                            const localDateTime = dateObj.toISOString().slice(0, 16);
                            document.getElementById('editDateMouvement').value = localDateTime;
                        }
                    } else {
                        Swal.fire('Erreur', 'Impossible de charger le mouvement', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    Swal.fire('Erreur', 'Erreur lors du chargement du mouvement', 'error');
                });
        }

        function setDeleteId(id, description) {
            document.getElementById('deleteMouvementName').textContent = description;
            const deleteUrl = `../models/delete/delete-mouvement.php?id=${id}&csrf_token=<?= $_SESSION['csrf_token'] ?>`;
            document.getElementById('deleteConfirmBtn').href = deleteUrl;
        }

        // ============================================
        // VALIDATION DES FORMULAIRES
        // ============================================
        document.getElementById('addEntreeForm')?.addEventListener('submit', function(e) {
            const montant = parseFloat(this.querySelector('[name="montant"]').value);
            const motif = this.querySelector('[name="motif"]').value.trim();
            
            if (!motif) {
                e.preventDefault();
                Swal.fire('Erreur', 'Le motif est requis', 'error');
                return false;
            }
            
            if (!montant || montant <= 0) {
                e.preventDefault();
                Swal.fire('Erreur', 'Le montant doit être supérieur à 0', 'error');
                return false;
            }
            
            return true;
        });

        document.getElementById('addSortieForm')?.addEventListener('submit', function(e) {
            const compteSelect = this.querySelector('[name="compte_id"]');
            const selectedOption = compteSelect.options[compteSelect.selectedIndex];
            const solde = parseFloat(selectedOption.getAttribute('data-solde')) || 0;
            const montant = parseFloat(this.querySelector('[name="montant"]').value);
            const motif = this.querySelector('[name="motif"]').value.trim();
            
            if (!motif) {
                e.preventDefault();
                Swal.fire('Erreur', 'Le motif est requis', 'error');
                return false;
            }
            
            if (!montant || montant <= 0) {
                e.preventDefault();
                Swal.fire('Erreur', 'Le montant doit être supérieur à 0', 'error');
                return false;
            }
            
            if (montant > solde) {
                e.preventDefault();
                Swal.fire('Erreur', 'Le montant ne peut pas dépasser le solde du compte', 'error');
                return false;
            }
            
            return true;
        });

        document.getElementById('addTransfertForm')?.addEventListener('submit', function(e) {
            const compteSource = this.querySelector('[name="compte_source_id"]').value;
            const compteDest = this.querySelector('[name="compte_destination_id"]').value;
            const montant = parseFloat(this.querySelector('[name="montant"]').value);
            const motif = this.querySelector('[name="motif"]').value.trim();
            
            if (!motif) {
                e.preventDefault();
                Swal.fire('Erreur', 'Le motif est requis', 'error');
                return false;
            }
            
            if (!compteSource || !compteDest) {
                e.preventDefault();
                Swal.fire('Erreur', 'Veuillez sélectionner les comptes source et destination', 'error');
                return false;
            }
            
            if (compteSource === compteDest) {
                e.preventDefault();
                Swal.fire('Erreur', 'Le compte source et le compte destination ne peuvent pas être identiques', 'error');
                return false;
            }
            
            if (!montant || montant <= 0) {
                e.preventDefault();
                Swal.fire('Erreur', 'Le montant doit être supérieur à 0', 'error');
                return false;
            }
            
            return true;
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                try {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch(e) {}
            });
        }, 5000);
    </script>

</body>
</html>