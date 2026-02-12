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
$canAccessRoles = hasPermission(['DG', 'comptable', 'actionnaire'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Définir le menu actif pour le sidebar
$ActiveRapportsFinanciers = 1;

// Récupérer les paramètres de filtre
$annee = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');
$mois = isset($_GET['mois']) ? intval($_GET['mois']) : date('n');
$trimestre = isset($_GET['trimestre']) ? intval($_GET['trimestre']) : ceil($mois / 3);
$compte_id = isset($_GET['compte_id']) ? intval($_GET['compte_id']) : 0;
$type_rapport = isset($_GET['type_rapport']) ? $_GET['type_rapport'] : 'mensuel';

// Dates pour les filtres
$date_debut = '';
$date_fin = '';

switch ($type_rapport) {
    case 'mensuel':
        $date_debut = date('Y-m-01', strtotime("$annee-$mois-01"));
        $date_fin = date('Y-m-t', strtotime($date_debut));
        break;
    case 'trimestriel':
        $mois_debut = (($trimestre - 1) * 3) + 1;
        $date_debut = date('Y-m-01', strtotime("$annee-$mois_debut-01"));
        $mois_fin = $mois_debut + 2;
        $date_fin = date('Y-m-t', strtotime("$annee-$mois_fin-01"));
        break;
    case 'annuel':
        $date_debut = date('Y-01-01', strtotime("$annee-01-01"));
        $date_fin = date('Y-12-31', strtotime("$annee-12-31"));
        break;
    case 'personnalise':
        $date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : date('Y-m-01');
        $date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : date('Y-m-t');
        break;
}

try {
    // Récupérer les comptes pour les filtres
    $queryComptes = "SELECT compte_id, nom, type, devise FROM COMPTES WHERE statut = 0 ORDER BY type, nom";
    $stmtComptes = $conn->prepare($queryComptes);
    $stmtComptes->execute();
    $comptes = $stmtComptes->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques globales
    $statsQuery = "
        SELECT 
            COUNT(DISTINCT m.compte_id) as nb_comptes,
            SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) as total_entrees,
            SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END) as total_sorties,
            COUNT(*) as nb_mouvements,
            COUNT(DISTINCT m.projet_id) as nb_projets,
            COUNT(DISTINCT m.contrat_id) as nb_contrats,
            (SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) - 
             SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END)) as solde_net
        FROM MOUVEMENT_COMPTE m
        WHERE m.statut = 0 
        AND DATE(m.date_mouvement) BETWEEN ? AND ?
    ";
    
    $params = [$date_debut, $date_fin];
    if ($compte_id > 0) {
        $statsQuery .= " AND m.compte_id = ?";
        $params[] = $compte_id;
    }
    
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Statistiques par type de mouvement
    $statsParTypeQuery = "
        SELECT 
            m.type_mouvement,
            COUNT(*) as nb_mouvements,
            SUM(m.montant) as total_montant,
            AVG(m.montant) as moyenne_montant
        FROM MOUVEMENT_COMPTE m
        WHERE m.statut = 0 
        AND DATE(m.date_mouvement) BETWEEN ? AND ?
    ";
    
    $paramsType = [$date_debut, $date_fin];
    if ($compte_id > 0) {
        $statsParTypeQuery .= " AND m.compte_id = ?";
        $paramsType[] = $compte_id;
    }
    
    $statsParTypeQuery .= " GROUP BY m.type_mouvement";
    $statsParTypeStmt = $conn->prepare($statsParTypeQuery);
    $statsParTypeStmt->execute($paramsType);
    $statsParType = $statsParTypeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques par compte
    $statsParCompteQuery = "
        SELECT 
            c.nom,
            c.type,
            c.devise,
            COUNT(m.mouvement_id) as nb_mouvements,
            SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) as entrees,
            SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END) as sorties,
            (SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) - 
             SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END)) as solde_periode,
            c.solde_actuel
        FROM COMPTES c
        LEFT JOIN MOUVEMENT_COMPTE m ON c.compte_id = m.compte_id 
            AND m.statut = 0 
            AND DATE(m.date_mouvement) BETWEEN ? AND ?
        WHERE c.statut = 0
    ";
    
    $paramsCompte = [$date_debut, $date_fin];
    if ($compte_id > 0) {
        $statsParCompteQuery .= " AND c.compte_id = ?";
        $paramsCompte[] = $compte_id;
    }
    
    $statsParCompteQuery .= " GROUP BY c.compte_id ORDER BY c.type, c.nom";
    $statsParCompteStmt = $conn->prepare($statsParCompteQuery);
    $statsParCompteStmt->execute($paramsCompte);
    $statsParCompte = $statsParCompteStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mouvements récents
    $mouvementsRecentsQuery = "
        SELECT 
            m.*,
            c.nom as compte_nom,
            c.type as compte_type,
            p.reference as projet_reference,
            cc.reference as contrat_reference
        FROM MOUVEMENT_COMPTE m
        LEFT JOIN COMPTES c ON m.compte_id = c.compte_id
        LEFT JOIN PROJETS p ON m.projet_id = p.projet_id
        LEFT JOIN CONTRATS_COM cc ON m.contrat_id = cc.contrat_id
        WHERE m.statut = 0 
        AND DATE(m.date_mouvement) BETWEEN ? AND ?
    ";
    
    $paramsRecents = [$date_debut, $date_fin];
    if ($compte_id > 0) {
        $mouvementsRecentsQuery .= " AND m.compte_id = ?";
        $paramsRecents[] = $compte_id;
    }
    
    $mouvementsRecentsQuery .= " ORDER BY m.date_mouvement DESC LIMIT 10";
    $mouvementsRecentsStmt = $conn->prepare($mouvementsRecentsQuery);
    $mouvementsRecentsStmt->execute($paramsRecents);
    $mouvementsRecents = $mouvementsRecentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques mensuelles pour le graphique
    $statsMensuellesQuery = "
        SELECT 
            YEAR(m.date_mouvement) as annee,
            MONTH(m.date_mouvement) as mois,
            DATE_FORMAT(m.date_mouvement, '%Y-%m') as periode,
            SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) as entrees,
            SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END) as sorties
        FROM MOUVEMENT_COMPTE m
        WHERE m.statut = 0 
        AND YEAR(m.date_mouvement) = ?
    ";
    
    $paramsMensuel = [$annee];
    if ($compte_id > 0) {
        $statsMensuellesQuery .= " AND m.compte_id = ?";
        $paramsMensuel[] = $compte_id;
    }
    
    $statsMensuellesQuery .= " GROUP BY YEAR(m.date_mouvement), MONTH(m.date_mouvement) 
                              ORDER BY YEAR(m.date_mouvement), MONTH(m.date_mouvement)";
    $statsMensuellesStmt = $conn->prepare($statsMensuellesQuery);
    $statsMensuellesStmt->execute($paramsMensuel);
    $statsMensuelles = $statsMensuellesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques par projet
    $statsParProjetQuery = "
        SELECT 
            p.reference,
            p.titre,
            COUNT(m.mouvement_id) as nb_mouvements,
            SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) as revenus,
            SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END) as depenses,
            (SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) - 
             SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END)) as benefice
        FROM PROJETS p
        LEFT JOIN MOUVEMENT_COMPTE m ON p.projet_id = m.projet_id 
            AND m.statut = 0 
            AND DATE(m.date_mouvement) BETWEEN ? AND ?
        WHERE p.statut = 0
        GROUP BY p.projet_id
        HAVING nb_mouvements > 0
        ORDER BY benefice DESC
        LIMIT 10
    ";
    
    $paramsProjet = [$date_debut, $date_fin];
    $statsParProjetStmt = $conn->prepare($statsParProjetQuery);
    $statsParProjetStmt->execute($paramsProjet);
    $statsParProjet = $statsParProjetStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques par contrat
    $statsParContratQuery = "
        SELECT 
            cc.reference,
            cc.type_contrat,
            SUM(CASE WHEN m.type_mouvement = 'entrée' THEN m.montant ELSE 0 END) as revenus,
            SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.montant ELSE 0 END) as depenses
        FROM CONTRATS_COM cc
        LEFT JOIN MOUVEMENT_COMPTE m ON cc.contrat_id = m.contrat_id 
            AND m.statut = 0 
            AND DATE(m.date_mouvement) BETWEEN ? AND ?
        WHERE cc.statut = 0 AND cc.etat = 'Actif'
        GROUP BY cc.contrat_id
        HAVING revenus > 0 OR depenses > 0
        ORDER BY revenus DESC
        LIMIT 10
    ";
    
    $paramsContrat = [$date_debut, $date_fin];
    $statsParContratStmt = $conn->prepare($statsParContratQuery);
    $statsParContratStmt->execute($paramsContrat);
    $statsParContrat = $statsParContratStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer le nom du compte filtré
    $compte_filtre = null;
    if ($compte_id > 0) {
        $queryCompte = "SELECT nom, type, devise FROM COMPTES WHERE compte_id = ? AND statut = 0";
        $stmtCompte = $conn->prepare($queryCompte);
        $stmtCompte->execute([$compte_id]);
        $compte_filtre = $stmtCompte->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des rapports: " . $e->getMessage());
    $stats = [];
    $statsParType = [];
    $statsParCompte = [];
    $mouvementsRecents = [];
    $statsMensuelles = [];
    $statsParProjet = [];
    $statsParContrat = [];
    $comptes = [];
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
    <title>Rapports Financiers - Evotech Africa</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-blue: #0d6efd;
            --primary-blue-dark: #0b5ed7;
            --success-green: #198754;
            --danger-red: #dc3545;
            --warning-yellow: #ffc107;
            --info-blue: #0dcaf0;
            --purple: #6f42c1;
            --pink: #d63384;
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

        /* Styles spécifiques aux rapports */
        .report-header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #1e40af;
        }
        
        .report-card {
            border-left: 4px solid;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .report-card-entrees {
            border-left-color: var(--success-green);
        }
        
        .report-card-sorties {
            border-left-color: var(--danger-red);
        }
        
        .report-card-benefice {
            border-left-color: var(--info-blue);
        }
        
        .report-card-comptes {
            border-left-color: var(--purple);
        }
        
        .badge-entree {
            background: linear-gradient(135deg, var(--success-green), #20c997);
            color: white;
        }
        
        .badge-sortie {
            background: linear-gradient(135deg, var(--danger-red), #e35d6a);
            color: white;
        }
        
        .badge-benefice {
            background: linear-gradient(135deg, var(--info-blue), #6ec6ff);
            color: white;
        }
        
        .progress-bar-entrees {
            background: linear-gradient(90deg, var(--success-green), #20c997);
        }
        
        .progress-bar-sorties {
            background: linear-gradient(90deg, var(--danger-red), #e35d6a);
        }
        
        .table-rapport th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .export-buttons {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .export-btn {
            padding: 10px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .export-pdf {
            background: linear-gradient(135deg, #dc3545, #e35d6a);
            color: white;
        }
        
        .export-excel {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
        }
        
        .export-print {
            background: linear-gradient(135deg, #6c757d, #adb5bd);
            color: white;
        }
        
        .montant-positif {
            color: var(--success-green) !important;
            font-weight: bold;
        }
        
        .montant-negatif {
            color: var(--danger-red) !important;
            font-weight: bold;
        }
        
        .montant-neutre {
            color: #6c757d !important;
        }
        
        .filtre-actif {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 1px solid #90caf9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
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

                                <?php if (hasPermission(['DG', 'comptable', 'actionnaire'], $userRoles)): ?>
                                    <li class="active">
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
        <div class="report-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-chart-line me-2"></i>
                        Rapports Financiers
                    </h1>
                    <p class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Période du <?= date('d/m/Y', strtotime($date_debut)) ?> au <?= date('d/m/Y', strtotime($date_fin)) ?>
                        <?php if ($compte_filtre): ?>
                            - <i class="fas fa-university me-2"></i>Compte: <?= htmlspecialchars($compte_filtre['nom']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="text-end">
                    <h3 class="mb-0">
                        <?php 
                        $solde = $stats['solde_net'] ?? 0;
                        $soldeClass = $solde > 0 ? 'montant-positif' : ($solde < 0 ? 'montant-negatif' : 'montant-neutre');
                        ?>
                        <span class="<?= $soldeClass ?>">
                            $<?= number_format($solde, 2, ',', ' ') ?>
                        </span>
                    </h3>
                    <small class="text-white-50">Solde net de la période</small>
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

        <!-- Filtres -->
        <div class="filter-section">
            <form method="GET" action="" id="filtreForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Type de rapport</label>
                        <select class="form-select" name="type_rapport" id="typeRapport">
                            <option value="mensuel" <?= $type_rapport == 'mensuel' ? 'selected' : '' ?>>Mensuel</option>
                            <option value="trimestriel" <?= $type_rapport == 'trimestriel' ? 'selected' : '' ?>>Trimestriel</option>
                            <option value="annuel" <?= $type_rapport == 'annuel' ? 'selected' : '' ?>>Annuel</option>
                            <option value="personnalise" <?= $type_rapport == 'personnalise' ? 'selected' : '' ?>>Personnalisé</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2" id="anneeField">
                        <label class="form-label fw-semibold">Année</label>
                        <select class="form-select" name="annee">
                            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                <option value="<?= $i ?>" <?= $annee == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2" id="moisField" style="display: <?= $type_rapport == 'mensuel' ? 'block' : 'none' ?>;">
                        <label class="form-label fw-semibold">Mois</label>
                        <select class="form-select" name="mois">
                            <?php 
                            $mois_noms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                                         'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                            for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $mois == $i ? 'selected' : '' ?>>
                                    <?= $mois_noms[$i] ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2" id="trimestreField" style="display: <?= $type_rapport == 'trimestriel' ? 'block' : 'none' ?>;">
                        <label class="form-label fw-semibold">Trimestre</label>
                        <select class="form-select" name="trimestre">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?= $i ?>" <?= $trimestre == $i ? 'selected' : '' ?>>T<?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3" id="datePersoField" style="display: <?= $type_rapport == 'personnalise' ? 'block' : 'none' ?>;">
                        <label class="form-label fw-semibold">Date début</label>
                        <input type="date" class="form-control" name="date_debut" value="<?= $date_debut ?>">
                    </div>
                    
                    <div class="col-md-3" id="dateFinField" style="display: <?= $type_rapport == 'personnalise' ? 'block' : 'none' ?>;">
                        <label class="form-label fw-semibold">Date fin</label>
                        <input type="date" class="form-control" name="date_fin" value="<?= $date_fin ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Compte</label>
                        <select class="form-select" name="compte_id">
                            <option value="0">Tous les comptes</option>
                            <?php foreach ($comptes as $compte): ?>
                                <option value="<?= $compte['compte_id'] ?>" <?= $compte_id == $compte['compte_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($compte['nom']) ?> (<?= htmlspecialchars($compte['type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12 mt-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Appliquer les filtres
                                </button>
                                <a href="rapports-financiers.php" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-redo me-1"></i> Réinitialiser
                                </a>
                            </div>
                            <div class="export-buttons">
                                <button type="button" class="btn export-btn export-pdf me-2" onclick="exportPDF()">
                                    <i class="fas fa-file-pdf me-1"></i> PDF
                                </button>
                                <button type="button" class="btn export-btn export-excel me-2" onclick="exportExcel()">
                                    <i class="fas fa-file-excel me-1"></i> Excel
                                </button>
                                <button type="button" class="btn export-btn export-print" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i> Imprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistiques principales -->
        <div class="row mb-4 g-3">
            <div class="col-md-3">
                <div class="card stat-card report-card-entrees">
                    <div class="card-body text-center">
                        <div class="stat-number text-success">
                            $<?= number_format($stats['total_entrees'] ?? 0, 2, ',', ' ') ?>
                        </div>
                        <div class="stat-label">Total Entrées</div>
                        <div class="mt-2">
                            <span class="badge badge-entree">Revenus</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card report-card-sorties">
                    <div class="card-body text-center">
                        <div class="stat-number text-danger">
                            $<?= number_format($stats['total_sorties'] ?? 0, 2, ',', ' ') ?>
                        </div>
                        <div class="stat-label">Total Sorties</div>
                        <div class="mt-2">
                            <span class="badge badge-sortie">Dépenses</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card report-card-benefice">
                    <div class="card-body text-center">
                        <div class="stat-number <?= ($stats['solde_net'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                            $<?= number_format($stats['solde_net'] ?? 0, 2, ',', ' ') ?>
                        </div>
                        <div class="stat-label">Bénéfice Net</div>
                        <div class="mt-2">
                            <span class="badge badge-benefice">
                                <?= ($stats['solde_net'] ?? 0) >= 0 ? 'Profit' : 'Perte' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card report-card-comptes">
                    <div class="card-body text-center">
                        <div class="stat-number text-purple">
                            <?= $stats['nb_mouvements'] ?? 0 ?>
                        </div>
                        <div class="stat-label">Mouvements</div>
                        <div class="mt-2">
                            <small class="text-muted">Sur <?= $stats['nb_comptes'] ?? 0 ?> compte(s)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row mb-4 g-3">
            <!-- Graphique Évolution mensuelle -->
            <div class="col-md-8">
                <div class="chart-container">
                    <h4 class="chart-title">
                        <i class="fas fa-chart-line me-2"></i>
                        Évolution Mensuelle <?= $annee ?>
                    </h4>
                    <canvas id="evolutionChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- Graphique Répartition -->
            <div class="col-md-4">
                <div class="chart-container">
                    <h4 class="chart-title">
                        <i class="fas fa-chart-pie me-2"></i>
                        Répartition des Mouvements
                    </h4>
                    <canvas id="repartitionChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Tableaux détaillés -->
        <div class="row mb-4 g-3">
            <!-- Par compte -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h4 class="chart-title">
                        <i class="fas fa-university me-2"></i>
                        Performance par Compte
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover table-rapport">
                            <thead>
                                <tr>
                                    <th>Compte</th>
                                    <th>Entrées</th>
                                    <th>Sorties</th>
                                    <th>Solde</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statsParCompte as $compte): 
                                    $solde = $compte['solde_periode'] ?? 0;
                                    $soldeClass = $solde > 0 ? 'montant-positif' : ($solde < 0 ? 'montant-negatif' : 'montant-neutre');
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($compte['nom']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($compte['type']) ?></small>
                                    </td>
                                    <td class="text-success">
                                        <?= $compte['devise'] ?><?= number_format($compte['entrees'] ?? 0, 2, ',', ' ') ?>
                                    </td>
                                    <td class="text-danger">
                                        <?= $compte['devise'] ?><?= number_format($compte['sorties'] ?? 0, 2, ',', ' ') ?>
                                    </td>
                                    <td class="<?= $soldeClass ?>">
                                        <?= $compte['devise'] ?><?= number_format($solde, 2, ',', ' ') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Par projet -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h4 class="chart-title">
                        <i class="fas fa-project-diagram me-2"></i>
                        Performance par Projet (Top 10)
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover table-rapport">
                            <thead>
                                <tr>
                                    <th>Projet</th>
                                    <th>Revenus</th>
                                    <th>Dépenses</th>
                                    <th>Bénéfice</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statsParProjet as $projet): 
                                    $benefice = $projet['benefice'] ?? 0;
                                    $beneficeClass = $benefice > 0 ? 'montant-positif' : ($benefice < 0 ? 'montant-negatif' : 'montant-neutre');
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($projet['reference']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($projet['titre']) ?></small>
                                    </td>
                                    <td class="text-success">
                                        $<?= number_format($projet['revenus'] ?? 0, 2, ',', ' ') ?>
                                    </td>
                                    <td class="text-danger">
                                        $<?= number_format($projet['depenses'] ?? 0, 2, ',', ' ') ?>
                                    </td>
                                    <td class="<?= $beneficeClass ?>">
                                        $<?= number_format($benefice, 2, ',', ' ') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mouvements récents -->
        <div class="chart-container">
            <h4 class="chart-title">
                <i class="fas fa-history me-2"></i>
                Derniers Mouvements (10 plus récents)
            </h4>
            <div class="table-responsive">
                <table class="table table-hover table-rapport">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Compte</th>
                            <th>Type</th>
                            <th>Motif</th>
                            <th>Montant</th>
                            <th>Projet/Contrat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mouvementsRecents as $mouvement): 
                            $type = $mouvement['type_mouvement'];
                            $typeClass = $type == 'entrée' ? 'badge-entree' : 'badge-sortie';
                            $montantClass = $type == 'entrée' ? 'text-success' : 'text-danger';
                            $date = date('d/m/Y H:i', strtotime($mouvement['date_mouvement']));
                        ?>
                        <tr>
                            <td><?= $date ?></td>
                            <td><?= htmlspecialchars($mouvement['compte_nom']) ?></td>
                            <td>
                                <span class="badge <?= $typeClass ?>">
                                    <?= ucfirst($type) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($mouvement['motif']) ?></td>
                            <td class="fw-bold <?= $montantClass ?>">
                                <?php if ($type == 'entrée'): ?>+<?php else: ?>-<?php endif; ?>
                                $<?= number_format($mouvement['montant'], 2, ',', ' ') ?>
                            </td>
                            <td>
                                <?php if ($mouvement['projet_reference']): ?>
                                    <span class="badge bg-info">Projet: <?= htmlspecialchars($mouvement['projet_reference']) ?></span>
                                <?php elseif ($mouvement['contrat_reference']): ?>
                                    <span class="badge bg-warning">Contrat: <?= htmlspecialchars($mouvement['contrat_reference']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

            // Gestion des champs de filtre dynamiques
            initializeFilterFields();
            
            // Initialiser les graphiques
            initializeCharts();
        });

        // Gestion des champs de filtre dynamiques
        function initializeFilterFields() {
            const typeRapportSelect = document.getElementById('typeRapport');
            const moisField = document.getElementById('moisField');
            const trimestreField = document.getElementById('trimestreField');
            const anneeField = document.getElementById('anneeField');
            const datePersoField = document.getElementById('datePersoField');
            const dateFinField = document.getElementById('dateFinField');
            
            typeRapportSelect.addEventListener('change', function() {
                const type = this.value;
                
                // Masquer tous les champs
                moisField.style.display = 'none';
                trimestreField.style.display = 'none';
                anneeField.style.display = 'none';
                datePersoField.style.display = 'none';
                dateFinField.style.display = 'none';
                
                // Afficher les champs appropriés
                switch(type) {
                    case 'mensuel':
                        moisField.style.display = 'block';
                        anneeField.style.display = 'block';
                        break;
                    case 'trimestriel':
                        trimestreField.style.display = 'block';
                        anneeField.style.display = 'block';
                        break;
                    case 'annuel':
                        anneeField.style.display = 'block';
                        break;
                    case 'personnalise':
                        datePersoField.style.display = 'block';
                        dateFinField.style.display = 'block';
                        break;
                }
            });
        }

        // Initialiser les graphiques
        function initializeCharts() {
            // Données pour le graphique d'évolution
            const statsMensuelles = <?= json_encode($statsMensuelles) ?>;
            
            const moisLabels = [];
            const entreeData = [];
            const sortieData = [];
            const beneficeData = [];
            
            // Créer des données pour tous les mois de l'année
            for (let i = 1; i <= 12; i++) {
                const moisNoms = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 
                                'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
                moisLabels.push(moisNoms[i]);
                
                // Chercher les données pour ce mois
                const statsMois = statsMensuelles.find(s => s.mois == i);
                if (statsMois) {
                    entreeData.push(parseFloat(statsMois.entrees) || 0);
                    sortieData.push(parseFloat(statsMois.sorties) || 0);
                    beneficeData.push((parseFloat(statsMois.entrees) || 0) - (parseFloat(statsMois.sorties) || 0));
                } else {
                    entreeData.push(0);
                    sortieData.push(0);
                    beneficeData.push(0);
                }
            }
            
            // Graphique d'évolution
            const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
            new Chart(evolutionCtx, {
                type: 'line',
                data: {
                    labels: moisLabels,
                    datasets: [
                        {
                            label: 'Entrées',
                            data: entreeData,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Sorties',
                            data: sortieData,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Bénéfice',
                            data: beneficeData,
                            borderColor: '#0dcaf0',
                            backgroundColor: 'rgba(13, 202, 240, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Évolution Mensuelle <?= $annee ?>'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('fr-FR');
                                }
                            }
                        }
                    }
                }
            });
            
            // Données pour le graphique de répartition
            const statsParType = <?= json_encode($statsParType) ?>;
            
            let totalEntrees = 0;
            let totalSorties = 0;
            
            statsParType.forEach(stat => {
                if (stat.type_mouvement === 'entrée') {
                    totalEntrees = parseFloat(stat.total_montant) || 0;
                } else if (stat.type_mouvement === 'sortie') {
                    totalSorties = parseFloat(stat.total_montant) || 0;
                }
            });
            
            // Graphique de répartition
            const repartitionCtx = document.getElementById('repartitionChart').getContext('2d');
            new Chart(repartitionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Entrées', 'Sorties'],
                    datasets: [{
                        data: [totalEntrees, totalSorties],
                        backgroundColor: [
                            'rgba(25, 135, 84, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ],
                        borderColor: [
                            'rgba(25, 135, 84, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: $${value.toLocaleString('fr-FR', {minimumFractionDigits: 2})} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Fonctions d'export
        function exportPDF() {
            alert('Fonction d\'export PDF en développement. Un rapport sera généré.');
            // Ici, vous pourriez appeler un script PHP pour générer un PDF
            // window.open('../models/export/export-pdf.php?' + new URLSearchParams(window.location.search), '_blank');
        }

        function exportExcel() {
            alert('Fonction d\'export Excel en développement. Un fichier sera téléchargé.');
            // Ici, vous pourriez appeler un script PHP pour générer un Excel
            // window.open('../models/export/export-excel.php?' + new URLSearchParams(window.location.search), '_blank');
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