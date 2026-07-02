<?php

/**
 * views/dashboard.php
 * Tableau de bord principal - Evotech Africa
 * Version avec les couleurs du logo (bleu clair et bleu foncé)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/select/select-dash.php';

session_start();

// Vérifier si l'utilisateur est connecté
requireLogin();

$userName = $_SESSION['user_nom'];
$userEmail = $_SESSION['user_email'];
$userRole = $_SESSION['user_role'];
$userInitials = strtoupper(substr($userName, 0, 2));

try {
    $pdo = getDBConnection();

    // Récupérer les données spécifiques à Evotech Africa
    $projetsActifs = getProjetsActifs($pdo);
    $totalEmployes = getTotalEmployes($pdo);
    $contratsActifs = getContratsActifs($pdo);
    $chiffreAffaires = getChiffreAffaires($pdo);
    $tauxEvolution = getTauxEvolution($pdo);
    $projetsParDepartement = getProjetsParDepartement($pdo, 5);
    $evolutionProjets = getEvolutionProjets($pdo, 7);
    $dernieresActivites = getDernieresActivites($pdo, 10);
    $alertesProjets = getAlertesProjets($pdo, 10);
    $statsGlobales = getGlobalStats($pdo);

    // Calcul de l'évolution
    $evolution = 0;
    if ($tauxEvolution['precedent'] > 0) {
        $evolution = (($chiffreAffaires['total'] - $tauxEvolution['precedent']) / $tauxEvolution['precedent']) * 100;
    }

    // Formater les données pour les graphiques
    $joursLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    $projetsData = array_values($evolutionProjets);
    if (count($projetsData) < 7) {
        $projetsData = array_pad($projetsData, 7, 0);
    }

    // Départements pour le graphique
    $departementsLabels = [];
    $departementsData = [];
    foreach ($projetsParDepartement as $dep) {
        $departementsLabels[] = $dep['departement'];
        $departementsData[] = (float)$dep['total_projets'];
    }
} catch (PDOException $e) {
    error_log("Erreur dashboard: " . $e->getMessage());
    $projetsActifs = ['total' => 0, 'nb' => 0];
    $totalEmployes = 0;
    $contratsActifs = 0;
    $chiffreAffaires = ['total' => 0];
    $evolution = 0;
    $alertesProjets = [];
    $dernieresActivites = [];
    $projetsData = [5, 3, 7, 4, 6, 8, 5];
    $departementsLabels = ['Informatique', 'Architecture', 'Communication', 'Design', 'Marketing'];
    $departementsData = [12, 8, 6, 4, 3];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evotech Africa - Tableau de Bord</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* ========== RESET & BASE ========== */
        *,
        ::before,
        ::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Palette Evotech Africa - Couleurs du logo */
            --color-bg: #f5f7fa;
            --color-surface: #ffffff;
            --color-primary: #1a3a7a;
            /* Bleu foncé du logo */
            --color-primary-light: #2a5a9a;
            --color-primary-dark: #0a2a5a;
            --color-secondary: #4a8fc7;
            /* Bleu clair du logo */
            --color-secondary-light: #6aafd7;
            --color-secondary-dark: #2a6fa7;
            --color-accent: #4a8fc7;
            --color-gradient-start: #1a3a7a;
            --color-gradient-end: #4a8fc7;
            --color-primary-soft: #e8f0fe;
            --color-primary-border: #b0cce8;
            --color-text: #1a2a3a;
            --color-text-secondary: #4a5a6a;
            --color-text-muted: #8a9aaa;
            --color-border: #dce4ec;
            --color-success: #27ae60;
            --color-warning: #f39c12;
            --color-danger: #e74c3c;
            --color-info: #4a8fc7;
            --shadow-sm: 0 1px 2px 0 rgb(26 58 122 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(26 58 122 / 0.1), 0 2px 4px -2px rgb(26 58 122 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(26 58 122 / 0.1), 0 4px 6px -4px rgb(26 58 122 / 0.1);
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.25rem;
            --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
            --font-display: 'Outfit', sans-serif;
            --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark {
            --color-bg: #0a1428;
            --color-surface: #1a2a4a;
            --color-primary-soft: #1a2a4a;
            --color-primary-border: #2a3a5a;
            --color-text: #e8ecf4;
            --color-text-secondary: #a8b8c8;
            --color-text-muted: #6a7a8a;
            --color-border: #2a3a5a;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--color-bg);
            color: var(--color-text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s, color 0.3s;
        }

        /* ========== SCROLLBAR ========== */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--color-text-muted);
            border-radius: 8px;
        }

        /* ========== LAYOUT PRINCIPAL ========== */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 16rem;
            background: linear-gradient(180deg, var(--color-primary), var(--color-secondary));
            z-index: 40;
            transition: transform var(--transition);
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            box-shadow: 2px 0 20px rgba(26, 58, 122, 0.15);
        }

        .sidebar-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo .logo-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: rgba(255, 255, 255, 0.15);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            font-family: var(--font-display);
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(4px);
        }

        .sidebar-logo h1 {
            font-family: var(--font-display);
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            letter-spacing: -0.025em;
            text-align: center;
        }

        .sidebar-logo span {
            font-size: 0.7rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.7);
            display: block;
            text-align: center;
            letter-spacing: 0.05em;
        }

        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            overflow-y: auto;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 3px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .nav-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255, 255, 255, 0.4);
            padding: 0.5rem 0.75rem;
            margin-top: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all var(--transition);
            white-space: nowrap;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 600;
            box-shadow: inset 3px 0 0 rgba(255, 255, 255, 0.5);
        }

        .nav-link.active .nav-badge {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }

        .user-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.15);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-info p {
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            line-height: 1.3;
        }

        .user-info span {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .logout-btn {
            margin-left: auto;
            color: rgba(255, 255, 255, 0.5);
            background: none;
            border: none;
            cursor: pointer;
            transition: color var(--transition);
            display: flex;
        }

        .logout-btn:hover {
            color: #ff6b6b;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            margin-left: 16rem;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-bar {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--color-border);
            padding: 1rem 2rem;
        }

        .dark .top-bar {
            background: rgba(10, 20, 40, 0.85);
        }

        .dashboard-content {
            flex: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* ========== TYPOGRAPHIE ========== */
        .page-title {
            font-family: var(--font-display);
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--color-text);
        }

        .page-subtitle {
            font-size: 0.875rem;
            color: var(--color-text-muted);
            font-weight: 400;
        }

        /* ========== CARTES STATISTIQUES ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .stat-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: default;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card:hover {
            border-color: var(--color-primary-border);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-card-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text-muted);
        }

        .stat-card-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card-value {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--color-text);
            margin-bottom: 0.25rem;
        }

        .stat-card-trend {
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .trend-up {
            color: var(--color-success);
        }

        .trend-down {
            color: var(--color-danger);
        }

        /* ========== GRILLE DE CONTENU ========== */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }

        .chart-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
        }

        .chart-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .chart-title {
            font-family: var(--font-display);
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--color-text);
        }

        .chart-subtitle {
            font-size: 0.8rem;
            color: var(--color-text-muted);
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 280px;
        }

        .legend-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
        }

        .legend-dot {
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        /* ========== TABLEAUX ========== */
        .table-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
        }

        .table-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: #fff;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all var(--transition);
            letter-spacing: 0.02em;
            box-shadow: 0 2px 8px rgba(26, 58, 122, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26, 58, 122, 0.35);
        }

        .btn-secondary {
            background: var(--color-secondary);
            color: #fff;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all var(--transition);
        }

        .btn-secondary:hover {
            background: var(--color-secondary-dark);
            transform: translateY(-1px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0.75rem 1.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--color-text-muted);
            background: var(--color-bg);
            border-bottom: 1px solid var(--color-border);
        }

        td {
            padding: 1rem 1.5rem;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text-secondary);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: var(--color-primary-soft);
        }

        .badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            display: inline-block;
            letter-spacing: 0.03em;
        }

        .badge-success {
            background: #e8f5e9;
            color: #1b5e20;
        }

        .badge-warning {
            background: #fff3e0;
            color: #e65100;
        }

        .badge-danger {
            background: #ffebee;
            color: #b71c1c;
        }

        .badge-primary {
            background: var(--color-primary-soft);
            color: var(--color-primary);
        }

        .badge-info {
            background: #e3f2fd;
            color: #0d47a1;
        }

        .dark .badge-success {
            background: #1b5e20;
            color: #a5d6a7;
        }

        .dark .badge-warning {
            background: #e65100;
            color: #ffe0b2;
        }

        .dark .badge-danger {
            background: #b71c1c;
            color: #ef9a9a;
        }

        .dark .badge-primary {
            background: #1a2a6c;
            color: #b8c4d8;
        }

        .dark .badge-info {
            background: #0d47a1;
            color: #90caf9;
        }

        /* ========== ALERTES ========== */
        .alert-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--color-border);
        }

        .alert-item:last-child {
            border-bottom: none;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--color-border);
            border-radius: 4px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: var(--color-danger);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--color-text-muted);
        }

        .empty-state svg {
            opacity: 0.5;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: var(--shadow-lg);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-content {
                padding: 1rem;
            }

            .top-bar {
                padding: 0.75rem 1rem;
            }

            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--color-text);
            cursor: pointer;
        }

        @media (max-width: 1024px) {
            .mobile-menu-btn {
                display: flex;
            }
        }
    </style>
</head>

<body>
    <!-- Bannière hors ligne -->
    <div id="offline-banner" style="display:none; position:fixed; top:0; left:0; right:0; background:#e74c3c; color:white; text-align:center; padding:6px; font-size:0.8rem; z-index:1000;">
        Mode hors ligne - Données potentiellement obsolètes
    </div>

    <div class="app-container">
        <!-- Overlay mobile -->
        <div id="sidebar-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:35;" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">EA</div>
                <div>
                    <h1>Evotech Africa</h1>
                    <span>Contribuer à l'évolution technologique</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <!-- MENU PRINCIPAL -->
                <span class="nav-label">Navigation</span>

                <a href="dashboard.php" class="nav-link active">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Tableau de Bord
                </a>
                <a href="projets.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Projets
                    <span class="nav-badge"><?php echo $projetsActifs['nb'] ?? 0; ?></span>
                </a>
                <a href="taches.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Tâches
                </a>
                <a href="timesheet.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Timesheet
                </a>

                <span class="nav-label">Ressources</span>

                <a href="agents.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Agents
                    <span class="nav-badge"><?php echo $totalEmployes ?? 0; ?></span>
                </a>
                <a href="departements.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Départements
                </a>
                <a href="contrats.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Contrats
                    <span class="nav-badge"><?php echo $contratsActifs ?? 0; ?></span>
                </a>
                <a href="partenaires.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Partenaires
                </a>

                <span class="nav-label">Finance</span>

                <a href="finance.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Finance
                </a>
                <a href="comptes.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Comptes
                </a>

                <span class="nav-label">Production</span>

                <a href="productions.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Productions
                </a>
                <a href="disques.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                    Disques
                </a>

                <?php if ($userRole === 'DG' || $userRole === 'admin'): ?>
                    <span class="nav-label">Administration</span>
                    <a href="utilisateurs.php" class="nav-link">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Utilisateurs
                    </a>
                    <a href="roles.php" class="nav-link">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Rôles
                    </a>
                    <a href="configuration.php" class="nav-link">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Configuration
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-user">
                <div class="user-avatar"><?php echo htmlspecialchars($userInitials); ?></div>
                <div class="user-info">
                    <p><?php echo htmlspecialchars($userName); ?></p>
                    <span><?php echo htmlspecialchars($userEmail); ?></span>
                </div>
                <a href="../models/logout.php" class="logout-btn" title="Déconnexion">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <button class="mobile-menu-btn" onclick="toggleSidebar()">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div>
                            <h2 class="page-title">Bonjour, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?> 👋</h2>
                            <p class="page-subtitle"><?php echo date('l d F Y'); ?> · <span id="last-update"><?php echo date('H:i'); ?></span></p>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <button onclick="refreshData()" style="background:var(--color-surface); border:1px solid var(--color-border); padding:0.5rem; border-radius:var(--radius-md); cursor:pointer; color:var(--color-text-secondary);">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                        <button onclick="toggleDarkMode()" style="background:var(--color-surface); border:1px solid var(--color-border); padding:0.5rem; border-radius:var(--radius-md); cursor:pointer; color:var(--color-text-secondary);">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Cartes Statistiques -->
                <div class="stats-grid">
                    <!-- Projets Actifs -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Projets Actifs</span>
                            <div class="stat-card-icon" style="background:var(--color-primary-soft); color:var(--color-primary);">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $projetsActifs['nb'] ?? 0; ?></div>
                        <div class="stat-card-trend">
                            <span style="font-weight:400; color:var(--color-text-muted);">En cours</span>
                        </div>
                    </div>

                    <!-- Équipe -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Équipe</span>
                            <div class="stat-card-icon" style="background:#e8f5e9; color:#27ae60;">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $totalEmployes ?? 0; ?></div>
                        <div style="font-size:0.8rem; color:var(--color-text-muted);">Employés actifs</div>
                    </div>

                    <!-- Contrats -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Contrats Actifs</span>
                            <div class="stat-card-icon" style="background:#fff3e0; color:#f39c12;">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $contratsActifs ?? 0; ?></div>
                        <div style="font-size:0.8rem; color:var(--color-text-muted);">En cours</div>
                    </div>

                    <!-- Chiffre d'Affaires -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Chiffre d'Affaires</span>
                            <div class="stat-card-icon" style="background:var(--color-primary-soft); color:var(--color-primary);">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($chiffreAffaires['total'] ?? 0, 0, ',', ' '); ?> <span style="font-size:0.9rem; font-weight:400; color:var(--color-text-muted);">$</span></div>
                        <div class="stat-card-trend <?php echo $evolution >= 0 ? 'trend-up' : 'trend-down'; ?>">
                            <span><?php echo $evolution >= 0 ? '↑' : '↓'; ?> <?php echo number_format(abs($evolution), 1); ?>%</span>
                            <span style="font-weight:400; color:var(--color-text-muted);">vs mois dernier</span>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="content-grid">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div>
                                <div class="chart-title">Évolution des Projets</div>
                                <div class="chart-subtitle">7 derniers jours</div>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="projects-chart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div>
                                <div class="chart-title">Par Département</div>
                                <div class="chart-subtitle">Répartition des projets</div>
                            </div>
                        </div>
                        <div class="chart-container" style="height:220px;">
                            <canvas id="department-chart"></canvas>
                        </div>
                        <div id="department-legend" class="legend-list"></div>
                    </div>
                </div>

                <!-- Alertes Projets -->
                <div class="table-card">
                    <div class="table-header">
                        <div>
                            <div class="chart-title" style="margin-bottom:0;">Projets en Alerte</div>
                            <div class="chart-subtitle"><?php echo count($alertesProjets); ?> projet(s) nécessitant une attention</div>
                        </div>
                        <button class="btn-primary">+ Nouveau Projet</button>
                    </div>
                    <?php if (count($alertesProjets) > 0): ?>
                        <?php foreach ($alertesProjets as $alerte): ?>
                            <div class="alert-item">
                                <div style="flex:1;">
                                    <div style="display:flex; justify-content:space-between;">
                                        <span style="font-weight:600; font-size:0.9rem;"><?php echo htmlspecialchars($alerte['titre']); ?></span>
                                        <span style="font-weight:700; color:var(--color-danger);"><?php echo $alerte['delai']; ?> jours</span>
                                    </div>
                                    <div style="font-size:0.8rem; color:var(--color-text-muted); margin-top:0.25rem;">
                                        Réf: <?php echo htmlspecialchars($alerte['reference']); ?> · Chef: <?php echo htmlspecialchars($alerte['chef_projet']); ?>
                                    </div>
                                    <div class="progress-bar">
                                        <?php $pourcentage = ($alerte['avancement'] ?? 50); ?>
                                        <div class="progress-fill" style="width:<?php echo min($pourcentage, 100); ?>%; background:<?php echo $pourcentage > 70 ? 'var(--color-warning)' : 'var(--color-danger)'; ?>;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg width="48" height="48" fill="none" stroke="#27ae60" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p style="margin-top:0.5rem; font-weight:500;">Tous les projets sont sur la bonne voie</p>
                            <p style="font-size:0.85rem;">Aucune alerte à signaler.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Dernières Activités -->
                <div class="table-card">
                    <div class="table-header">
                        <div>
                            <div class="chart-title" style="margin-bottom:0;">Dernières Activités</div>
                            <div class="chart-subtitle">Les actions récentes dans l'entreprise</div>
                        </div>
                        <button class="btn-secondary">Voir tout</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Projet</th>
                                <th>Activité</th>
                                <th>Agent</th>
                                <th style="text-align:right;">Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($dernieresActivites) > 0): ?>
                                <?php foreach ($dernieresActivites as $activite): ?>
                                    <tr>
                                        <td style="font-weight:600; color:var(--color-primary);"><?php echo htmlspecialchars($activite['projet_titre'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($activite['description']); ?></td>
                                        <td><?php echo htmlspecialchars($activite['agent_nom'] ?? 'N/A'); ?></td>
                                        <td style="text-align:right;"><?php echo date('d/m H:i', strtotime($activite['date_creation'])); ?></td>
                                        <td>
                                            <?php if ($activite['statut'] === 'Terminé'): ?>
                                                <span class="badge badge-success">Terminé</span>
                                            <?php elseif ($activite['statut'] === 'En cours'): ?>
                                                <span class="badge badge-warning">En cours</span>
                                            <?php elseif ($activite['statut'] === 'Planifiée'): ?>
                                                <span class="badge badge-primary">Planifiée</span>
                                            <?php elseif ($activite['statut'] === 'Validée'): ?>
                                                <span class="badge badge-success">Validée</span>
                                            <?php elseif ($activite['statut'] === 'Soumise'): ?>
                                                <span class="badge badge-info">Soumise</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger"><?php echo htmlspecialchars($activite['statut']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:3rem;">Aucune activité récente.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <footer style="padding:1rem 2rem; border-top:1px solid var(--color-border); font-size:0.8rem; color:var(--color-text-muted); display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                <span>© <?php echo date('Y'); ?> Evotech Africa · Contribuer à l'évolution technologique en Afrique</span>
                <span>Version 1.0 · PHP 8.0+</span>
            </footer>
        </div>
    </div>

    <script>
        // Données PHP -> JS
        const projetsData = <?php echo json_encode($projetsData); ?>;
        const joursLabels = <?php echo json_encode($joursLabels); ?>;
        const deptLabels = <?php echo json_encode($departementsLabels); ?>;
        const deptData = <?php echo json_encode($departementsData); ?>;
        const deptColors = ['#1a3a7a', '#4a8fc7', '#2a5a9a', '#6aafd7', '#0a2a5a', '#2a6fa7'];

        // Initialisation des graphiques
        function initCharts() {
            const isDark = document.body.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(51,65,85,0.5)' : 'rgba(226,232,240,0.8)';

            // Graphique Projets (ligne)
            const projectsCtx = document.getElementById('projects-chart').getContext('2d');
            const gradient = projectsCtx.createLinearGradient(0, 0, 0, 280);
            gradient.addColorStop(0, 'rgba(26,58,122,0.2)');
            gradient.addColorStop(1, 'rgba(74,143,199,0)');

            new Chart(projectsCtx, {
                type: 'line',
                data: {
                    labels: joursLabels,
                    datasets: [{
                        data: projetsData,
                        borderColor: '#1a3a7a',
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#1a3a7a',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: gridColor,
                                borderDash: [4, 4]
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            // Graphique Départements (anneau)
            const deptCtx = document.getElementById('department-chart').getContext('2d');
            new Chart(deptCtx, {
                type: 'doughnut',
                data: {
                    labels: deptLabels,
                    datasets: [{
                        data: deptData,
                        backgroundColor: deptColors,
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Légende départements
            const total = deptData.reduce((a, b) => a + b, 0);
            const legendHtml = deptLabels.map((label, i) => {
                const pct = total > 0 ? ((deptData[i] / total) * 100).toFixed(1) : 0;
                return `
                    <div class="legend-item">
                        <span style="display:flex; align-items:center; gap:0.5rem;">
                            <span class="legend-dot" style="background:${deptColors[i % deptColors.length]};"></span> ${label}
                        </span>
                        <span style="font-weight:600;">${pct}%</span>
                    </div>`;
            }).join('');
            document.getElementById('department-legend').innerHTML = legendHtml;
        }

        // Thème sombre
        function toggleDarkMode() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
            location.reload();
        }

        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark');

        // Sidebar mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('open');
            overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
        }

        // Actualisation
        async function refreshData() {
            try {
                const res = await fetch('../api/dashboard_data.php');
                const data = await res.json();
                if (data.success) location.reload();
            } catch (e) {
                console.error('Erreur de rafraîchissement', e);
            }
        }

        // Hors ligne
        window.addEventListener('online', () => document.getElementById('offline-banner').style.display = 'none');
        window.addEventListener('offline', () => document.getElementById('offline-banner').style.display = 'block');
        if (!navigator.onLine) document.getElementById('offline-banner').style.display = 'block';

        document.addEventListener('DOMContentLoaded', initCharts);
    </script>
</body>

</html>