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
$canAccessRoles = hasPermission(['DG', 'Chef_projet', 'ChefDepartement', 'agent'], $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
    header('Location: dashboard.php');
    exit();
}

// Vérifier l'ID du projet
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de projet invalide.";
    header('Location: projets.php');
    exit();
}

$projet_id = intval($_GET['id']);

// Définir le menu actif pour le sidebar
$ActiveProjets = 1;

# Récupérer les détails du projet
try {
    // Récupérer les informations principales du projet
    $query = "
        SELECT 
            p.*,
            pa.nom as partenaire_nom,
            pa.type as partenaire_type,
            pa.telephone as partenaire_telephone,
            pa.email as partenaire_email,
            pa.adresse as partenaire_adresse,
            pa.secteur_activite as partenaire_secteur,
            CONCAT(u.prenom, ' ', u.nom) as chef_projet_nom,
            u.email as chef_projet_email,
            u.profil as chef_projet_photo
        FROM PROJETS p
        LEFT JOIN PARTENAIRES pa ON p.partenaire_id = pa.partenaire_id AND pa.statut = 0
        LEFT JOIN UTILISATEURS u ON p.chef_projet_id = u.utilisateur_id AND u.statut = 0
        WHERE p.projet_id = ? AND p.statut = 0
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$projet_id]);
    $projet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$projet) {
        $_SESSION['error'] = "Projet non trouvé ou supprimé.";
        header('Location: projets.php');
        exit();
    }
    
    // Récupérer les participants
    $queryParticipants = "
        SELECT 
            a.agent_id,
            CONCAT(a.prenom, ' ', a.nom) as nom_complet,
            a.email_professionnel,
            a.telephone_professionnel,
            d.nom as departement_nom,
            a.photo_profil
        FROM PARTICIPATION p
        JOIN AGENTS a ON p.agent_id = a.agent_id AND a.statut = 0
        LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id AND d.statut = 0
        WHERE p.projet_id = ? AND p.statut = 0
        ORDER BY a.nom, a.prenom
    ";
    $stmtParticipants = $conn->prepare($queryParticipants);
    $stmtParticipants->execute([$projet_id]);
    $participants = $stmtParticipants->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les tâches
    $queryTaches = "
        SELECT 
            t.*,
            CONCAT(a.prenom, ' ', a.nom) as assigne_nom,
            a.photo_profil as assigne_photo,
            COUNT(ts.timesheet_id) as nb_timesheets,
            SUM(ts.worked_heures) as total_heures
        FROM TACHES t
        LEFT JOIN AGENTS a ON t.assigne_a = a.agent_id AND a.statut = 0
        LEFT JOIN TIMESHEET ts ON t.tache_id = ts.tache_id AND ts.statut = 0 AND ts.etat = 'Validée'
        WHERE t.projet_id = ? AND t.statut = 0
        GROUP BY t.tache_id
        ORDER BY 
            CASE t.priorite 
                WHEN 'Urgent' THEN 1
                WHEN 'Haute' THEN 2
                WHEN 'Normale' THEN 3
                WHEN 'Basse' THEN 4
                ELSE 5
            END,
            t.date_debut_prevue
    ";
    $stmtTaches = $conn->prepare($queryTaches);
    $stmtTaches->execute([$projet_id]);
    $taches = $stmtTaches->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques des tâches
    $statsTaches = [
        'total' => count($taches),
        'a_faire' => 0,
        'en_cours' => 0,
        'en_revue' => 0,
        'termine' => 0,
        'bloque' => 0,
        'total_heures' => 0
    ];
    
    foreach ($taches as $tache) {
        $statsTaches[$tache['etat'] == 'À faire' ? 'a_faire' : 
                     ($tache['etat'] == 'En cours' ? 'en_cours' :
                     ($tache['etat'] == 'En revue' ? 'en_revue' :
                     ($tache['etat'] == 'Terminé' ? 'termine' : 'bloque')))]++;
        $statsTaches['total_heures'] += $tache['total_heures'] ?? 0;
    }
    
    // Récupérer les productions
    $queryProductions = "
        SELECT 
            pr.*,
            tp.nom as type_nom,
            tp.description as type_description,
            tp.extension_autorisee,
            ds.matricule as disk_matricule,
            ds.capacite_go,
            ds.emplacement_physique
        FROM PRODUCTION pr
        JOIN TYPES_PRODUCTION tp ON pr.type_prod_id = tp.type_prod_id AND tp.statut = 0
        JOIN DISK_STOCKAGE ds ON pr.disk_id = ds.disk_id AND ds.statut = 0
        WHERE pr.projet_id = ? AND pr.statut = 0
        ORDER BY pr.date_upload DESC
    ";
    $stmtProductions = $conn->prepare($queryProductions);
    $stmtProductions->execute([$projet_id]);
    $productions = $stmtProductions->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les timesheets récents
    $queryTimesheets = "
        SELECT 
            ts.*,
            t.description_tache,
            CONCAT(a.prenom, ' ', a.nom) as agent_nom,
            a.photo_profil
        FROM TIMESHEET ts
        JOIN TACHES t ON ts.tache_id = t.tache_id AND t.statut = 0
        JOIN AGENTS a ON ts.agent_id = a.agent_id AND a.statut = 0
        WHERE t.projet_id = ? AND ts.statut = 0
        ORDER BY ts.date_travail DESC, ts.date_creation DESC
        LIMIT 10
    ";
    $stmtTimesheets = $conn->prepare($queryTimesheets);
    $stmtTimesheets->execute([$projet_id]);
    $timesheets = $stmtTimesheets->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les agents disponibles pour ajouter comme participants
    $queryAgentsDisponibles = "
        SELECT 
            a.agent_id,
            CONCAT(a.prenom, ' ', a.nom) as nom_complet,
            a.email_professionnel,
            d.nom as departement_nom,
            a.photo_profil
        FROM AGENTS a
        LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id AND d.statut = 0
        WHERE a.statut = 0 
        AND a.etat = 'Actif'
        AND a.agent_id NOT IN (
            SELECT agent_id 
            FROM PARTICIPATION 
            WHERE projet_id = ? AND statut = 0
        )
        ORDER BY a.nom, a.prenom
    ";
    $stmtAgentsDisponibles = $conn->prepare($queryAgentsDisponibles);
    $stmtAgentsDisponibles->execute([$projet_id]);
    $agentsDisponibles = $stmtAgentsDisponibles->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer la progression globale
    $progression = 0;
    if ($statsTaches['total'] > 0) {
        $progression = round(($statsTaches['termine'] / $statsTaches['total']) * 100);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des détails du projet: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors du chargement des données du projet.";
    header('Location: projets.php');
    exit();
}

# Messages de session
$success_msg = $_SESSION['msg'] ?? null;
$error_msg = $_SESSION['error'] ?? null;
unset($_SESSION['msg'], $_SESSION['error']);

# Générer un token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Classes CSS pour l'état et priorité
$etatClass = 'badge-etat-' . strtolower(str_replace(' ', '-', $projet['etat']));
$prioriteClass = 'badge-priorite-' . strtolower($projet['priorite']);

// Formater les dates
$dateDebut = $projet['date_debut'] ? date('d/m/Y', strtotime($projet['date_debut'])) : '-';
$dateFinPrevue = $projet['date_fin_prevue'] ? date('d/m/Y', strtotime($projet['date_fin_prevue'])) : '-';
$dateFinReelle = $projet['date_fin_reelle'] ? date('d/m/Y', strtotime($projet['date_fin_reelle'])) : '-';
$dateCreation = $projet['date_creation'] ? date('d/m/Y H:i', strtotime($projet['date_creation'])) : '-';

// Calculer les jours écoulés et restants
$joursEcoules = 0;
$joursRestants = 0;
$retard = false;

if ($projet['date_debut']) {
    $dateDebutObj = new DateTime($projet['date_debut']);
    $today = new DateTime();
    
    if ($projet['etat'] === 'Terminé' && $projet['date_fin_reelle']) {
        $dateFinObj = new DateTime($projet['date_fin_reelle']);
    } elseif ($projet['date_fin_prevue']) {
        $dateFinObj = new DateTime($projet['date_fin_prevue']);
    }
    
    if (isset($dateFinObj)) {
        $joursTotal = $dateDebutObj->diff($dateFinObj)->days;
        $joursEcoules = min($joursTotal, $dateDebutObj->diff($today)->days);
        
        if ($projet['etat'] !== 'Terminé' && $projet['date_fin_prevue']) {
            $joursRestants = $today->diff($dateFinObj)->days;
            $retard = $dateFinObj < $today;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Projet - Evotech Africa</title>
    
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

        /* Badges spécifiques aux projets */
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
        
        .badge-priorite-haute {
            background: linear-gradient(135deg, #fd7e14, #ff922b);
            color: white;
        }
        
        .badge-priorite-urgent {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .badge-priorite-normale {
            background: linear-gradient(135deg, #0d6efd, #3d8bfd);
            color: white;
        }
        
        .badge-priorite-basse {
            background: linear-gradient(135deg, #6c757d, #adb5bd);
            color: white;
        }
        
        /* Badges pour les états de tâches */
        .badge-etat-a-faire {
            background: linear-gradient(135deg, #6c757d, #adb5bd);
            color: white;
        }
        
        .badge-etat-en-cours-tache {
            background: linear-gradient(135deg, #0d6efd, #3d8bfd);
            color: white;
        }
        
        .badge-etat-en-revue {
            background: linear-gradient(135deg, #ffc107, #ffca2c);
            color: #000;
        }
        
        .badge-etat-termine-tache {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
        }
        
        .badge-etat-bloque {
            background: linear-gradient(135deg, #dc3545, #e35d6a);
            color: white;
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
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        /* Progression */
        .progress {
            height: 12px;
            border-radius: 6px;
        }
        
        .progress-bar {
            border-radius: 6px;
            background: linear-gradient(90deg, #0d6efd, #3d8bfd);
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
            margin-left: 20px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #0d6efd, #3d8bfd);
            border-radius: 3px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -33px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: white;
            border: 3px solid #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
        }
        
        .timeline-date {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        /* Card metrics */
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .metric-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .metric-card .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        /* Participant avatar */
        .participant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .participant-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Tâche card */
        .tache-card {
            border-left: 4px solid;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .tache-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .tache-card.priorite-urgent { border-left-color: #dc3545; }
        .tache-card.priorite-haute { border-left-color: #fd7e14; }
        .tache-card.priorite-normale { border-left-color: #0d6efd; }
        .tache-card.priorite-basse { border-left-color: #6c757d; }
        
        /* Production card */
        .production-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .production-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Nav tabs personnalisées */
        .nav-tabs-custom {
            border-bottom: 2px solid #dee2e6;
        }
        
        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            position: relative;
        }
        
        .nav-tabs-custom .nav-link:hover {
            color: #0d6efd;
        }
        
        .nav-tabs-custom .nav-link.active {
            color: #0d6efd;
            background: none;
            border: none;
        }
        
        .nav-tabs-custom .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #0d6efd, #3d8bfd);
            border-radius: 3px 3px 0 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .metric-card .number {
                font-size: 2rem;
            }
            
            .nav-tabs-custom .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
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
        <!-- Header avec bouton retour -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-4 mb-3 border-bottom">
            <div>
                <a href="projets.php" class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="fas fa-arrow-left me-1"></i> Retour aux projets
                </a>
                <h1 class="h3 fw-bold text-primary">
                    <i class="fas fa-project-diagram me-2"></i>
                    Détails du Projet
                </h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                    <a href="edit-projet.php?id=<?= $projet_id ?>" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-1"></i> Modifier
                    </a>
                <?php endif; ?>
                <a href="projets.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Fermer
                </a>
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

        <!-- En-tête du projet -->
        <div class="card border-0 shadow mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1 text-white">
                            <i class="fas fa-info-circle me-2"></i>
                            Informations générales
                        </h5>
                        <small class="text-white-50">Référence: <?= htmlspecialchars($projet['reference']) ?></small>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge <?= $etatClass ?> fs-6">
                            <?= htmlspecialchars($projet['etat']) ?>
                        </span>
                        <span class="badge <?= $prioriteClass ?> fs-6">
                            <?= htmlspecialchars($projet['priorite']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h3 class="text-primary mb-3"><?= htmlspecialchars($projet['titre']) ?></h3>
                        
                        <?php if (!empty($projet['description_projet'])): ?>
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Description</h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($projet['description_projet'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Progression -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <h6 class="text-muted mb-0">Progression du projet</h6>
                                <strong class="text-primary"><?= $progression ?>%</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?= $progression ?>%"
                                     aria-valuenow="<?= $progression ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted"><?= $joursEcoules ?> jours écoulés</small>
                                <?php if ($joursRestants > 0): ?>
                                    <small class="<?= $retard ? 'text-danger' : 'text-success' ?>">
                                        <?= $retard ? 'En retard de ' . $joursRestants . ' jours' : $joursRestants . ' jours restants' ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Métriques rapides -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="metric-card">
                                    <div class="number text-primary"><?= count($participants) ?></div>
                                    <div class="label">Participants</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card">
                                    <div class="number text-info"><?= $statsTaches['total'] ?></div>
                                    <div class="label">Tâches</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card">
                                    <div class="number text-success"><?= count($productions) ?></div>
                                    <div class="label">Productions</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card">
                                    <div class="number text-warning"><?= number_format($statsTaches['total_heures'], 1) ?></div>
                                    <div class="label">Heures</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Budget -->
                        <?php if ($projet['budget_estime'] > 0): ?>
                        <div class="alert alert-light border">
                            <h6 class="text-muted mb-2">Budget estimé</h6>
                            <h4 class="text-success mb-0">
                                $<?= number_format($projet['budget_estime'], 2, ',', ' ') ?>
                            </h4>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Informations détaillées -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-date">Date de début</div>
                                <div class="fw-bold"><?= $dateDebut ?></div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-date">Date de fin prévue</div>
                                <div class="fw-bold <?= $retard ? 'text-danger' : '' ?>">
                                    <?= $dateFinPrevue ?>
                                </div>
                            </div>
                            <?php if ($projet['date_fin_reelle']): ?>
                            <div class="timeline-item">
                                <div class="timeline-date">Date de fin réelle</div>
                                <div class="fw-bold text-success"><?= $dateFinReelle ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="timeline-item">
                                <div class="timeline-date">Créé le</div>
                                <div class="fw-bold"><?= $dateCreation ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Chef de projet</h6>
                                <div class="d-flex align-items-center">
                                    <div class="participant-avatar me-3">
                                        <?php if (!empty($projet['chef_projet_photo'])): ?>
                                            <img src="../uploads/profiles/<?= htmlspecialchars($projet['chef_projet_photo']) ?>" 
                                                 alt="<?= htmlspecialchars($projet['chef_projet_nom']) ?>">
                                        <?php else: 
                                            $initials = strtoupper(
                                                substr($projet['chef_projet_nom'] ?? '', 0, 1) .
                                                substr($projet['chef_projet_nom'] ?? '', strpos($projet['chef_projet_nom'] ?? '', ' ') + 1, 1)
                                            );
                                        ?>
                                            <?= htmlspecialchars($initials) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong class="d-block"><?= htmlspecialchars($projet['chef_projet_nom']) ?></strong>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?= htmlspecialchars($projet['chef_projet_email'] ?? '') ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation par onglets -->
        <ul class="nav nav-tabs nav-tabs-custom mb-4" id="projetTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="taches-tab" data-bs-toggle="tab" data-bs-target="#taches" type="button" role="tab">
                    <i class="fas fa-tasks me-2"></i>Tâches
                    <span class="badge bg-primary ms-2"><?= $statsTaches['total'] ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="participants-tab" data-bs-toggle="tab" data-bs-target="#participants" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>Participants
                    <span class="badge bg-primary ms-2"><?= count($participants) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="productions-tab" data-bs-toggle="tab" data-bs-target="#productions" type="button" role="tab">
                    <i class="fas fa-file-alt me-2"></i>Productions
                    <span class="badge bg-primary ms-2"><?= count($productions) ?></span>
                </button>
            </li>
            <?php if ($projet['partenaire_nom']): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="partenaire-tab" data-bs-toggle="tab" data-bs-target="#partenaire" type="button" role="tab">
                    <i class="fas fa-handshake me-2"></i>Partenaire
                </button>
            </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="activite-tab" data-bs-toggle="tab" data-bs-target="#activite" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Activité récente
                </button>
            </li>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content" id="projetTabsContent">
            <!-- Onglet Tâches -->
            <div class="tab-pane fade show active" id="taches" role="tabpanel">
                <div class="row">
                    <div class="col-md-3">
                        <!-- Statistiques des tâches -->
                        <div class="card border-0 shadow mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Statistiques
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted">À faire</span>
                                        <span class="badge bg-secondary"><?= $statsTaches['a_faire'] ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted">En cours</span>
                                        <span class="badge bg-primary"><?= $statsTaches['en_cours'] ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted">En revue</span>
                                        <span class="badge bg-warning text-dark"><?= $statsTaches['en_revue'] ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted">Terminées</span>
                                        <span class="badge bg-success"><?= $statsTaches['termine'] ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted">Bloquées</span>
                                        <span class="badge bg-danger"><?= $statsTaches['bloque'] ?></span>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <small class="text-muted">Total heures travaillées</small>
                                    <h4 class="text-primary"><?= number_format($statsTaches['total_heures'], 1) ?>h</h4>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                        <div class="d-grid gap-2">
                            <a href="taches.php?projet_id=<?= $projet_id ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nouvelle tâche
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-9">
                        <!-- Liste des tâches -->
                        <div class="card border-0 shadow">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-list-check me-2"></i>Liste des tâches
                                    </h6>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="filterTaches('all')">
                                            Toutes
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="filterTaches('en_cours')">
                                            En cours
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="filterTaches('termine')">
                                            Terminées
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($taches)): ?>
                                <div class="no-results">
                                    <i class="fas fa-tasks text-muted"></i>
                                    <h5 class="mt-3">Aucune tâche</h5>
                                    <p class="text-muted">Aucune tâche n'a été créée pour ce projet.</p>
                                    <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                    <a href="taches.php?projet_id=<?= $projet_id ?>" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i> Créer une tâche
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="row g-3" id="tachesList">
                                    <?php foreach ($taches as $tache): 
                                        $tacheEtatClass = 'badge-etat-' . strtolower(str_replace(' ', '-', $tache['etat']));
                                        $tacheEtatClass = str_replace('à', 'a', $tacheEtatClass); // Pour "À faire"
                                        $tachePrioriteClass = 'badge-priorite-' . strtolower($tache['priorite']);
                                        $dateDebutTache = $tache['date_debut_prevue'] ? date('d/m/Y', strtotime($tache['date_debut_prevue'])) : '-';
                                        $dateFinTache = $tache['date_fin_prevue'] ? date('d/m/Y', strtotime($tache['date_fin_prevue'])) : '-';
                                    ?>
                                    <div class="col-12 tache-item" data-etat="<?= htmlspecialchars($tache['etat']) ?>">
                                        <div class="tache-card priorite-<?= strtolower($tache['priorite']) ?> card p-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="badge <?= $tacheEtatClass ?> me-2">
                                                            <?= htmlspecialchars($tache['etat']) ?>
                                                        </span>
                                                        <span class="badge <?= $tachePrioriteClass ?>">
                                                            <?= htmlspecialchars($tache['priorite']) ?>
                                                        </span>
                                                    </div>
                                                    <h6 class="mb-2"><?= htmlspecialchars($tache['description_tache']) ?></h6>
                                                    
                                                    <?php if ($tache['assigne_nom']): ?>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <small class="text-muted me-2">Assignée à:</small>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($tache['assigne_photo'])): ?>
                                                            <img src="../uploads/profiles/<?= htmlspecialchars($tache['assigne_photo']) ?>" 
                                                                 alt="<?= htmlspecialchars($tache['assigne_nom']) ?>" 
                                                                 class="rounded-circle me-1" width="20" height="20">
                                                            <?php endif; ?>
                                                            <small class="fw-bold"><?= htmlspecialchars($tache['assigne_nom']) ?></small>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="row g-2">
                                                        <div class="col-auto">
                                                            <small class="text-muted">
                                                                <i class="fas fa-play-circle me-1"></i> <?= $dateDebutTache ?>
                                                            </small>
                                                        </div>
                                                        <div class="col-auto">
                                                            <small class="text-muted">
                                                                <i class="fas fa-flag-checkered me-1"></i> <?= $dateFinTache ?>
                                                            </small>
                                                        </div>
                                                        <div class="col-auto">
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i> <?= $tache['estimation_heures'] ?>h estimées
                                                            </small>
                                                        </div>
                                                        <?php if ($tache['total_heures']): ?>
                                                        <div class="col-auto">
                                                            <small class="text-success">
                                                                <i class="fas fa-check-circle me-1"></i> <?= $tache['total_heures'] ?>h travaillées
                                                            </small>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="btn-group">
                                                    <a href="edit-tache.php?id=<?= $tache['tache_id'] ?>" 
                                                       class="btn btn-sm btn-outline-secondary"
                                                       title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteTache(<?= $tache['tache_id'] ?>, '<?= htmlspecialchars(addslashes($tache['description_tache'])) ?>')"
                                                            title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Onglet Participants -->
            <div class="tab-pane fade" id="participants" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card border-0 shadow">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-users me-2"></i>Équipe du projet
                                    </h6>
                                    <span class="badge bg-primary"><?= count($participants) ?> participant(s)</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($participants)): ?>
                                <div class="no-results">
                                    <i class="fas fa-users text-muted"></i>
                                    <h5 class="mt-3">Aucun participant</h5>
                                    <p class="text-muted">Aucun agent n'a été assigné à ce projet.</p>
                                </div>
                                <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($participants as $participant): 
                                        $initials = strtoupper(
                                            substr($participant['nom_complet'] ?? '', 0, 1) .
                                            substr($participant['nom_complet'] ?? '', strpos($participant['nom_complet'] ?? '', ' ') + 1, 1)
                                        );
                                    ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card border">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="participant-avatar me-3">
                                                        <?php if (!empty($participant['photo_profil'])): ?>
                                                            <img src="../uploads/profiles/<?= htmlspecialchars($participant['photo_profil']) ?>" 
                                                                 alt="<?= htmlspecialchars($participant['nom_complet']) ?>">
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($initials) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <strong class="d-block"><?= htmlspecialchars($participant['nom_complet']) ?></strong>
                                                        <small class="text-muted d-block">
                                                            <?= htmlspecialchars($participant['departement_nom'] ?? 'Non assigné') ?>
                                                        </small>
                                                        <div class="mt-2">
                                                            <?php if ($participant['email_professionnel']): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-envelope me-1"></i>
                                                                <?= htmlspecialchars($participant['email_professionnel']) ?>
                                                            </small>
                                                            <?php endif; ?>
                                                            <?php if ($participant['telephone_professionnel']): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-phone me-1"></i>
                                                                <?= htmlspecialchars($participant['telephone_professionnel']) ?>
                                                            </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="removeParticipant(<?= $participant['agent_id'] ?>, '<?= htmlspecialchars(addslashes($participant['nom_complet'])) ?>')"
                                                            title="Retirer">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                        <div class="card border-0 shadow">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-user-plus me-2"></i>Ajouter un participant
                                </h6>
                            </div>
                            <div class="card-body">
                                <form id="addParticipantForm" action="../models/add/add-participation-post.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="projet_id" value="<?= $projet_id ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Sélectionner un agent</label>
                                        <select class="form-select" name="agent_id" required>
                                            <option value="">Choisir un agent...</option>
                                            <?php foreach ($agentsDisponibles as $agent): ?>
                                            <option value="<?= $agent['agent_id'] ?>">
                                                <?= htmlspecialchars($agent['nom_complet']) ?> 
                                                (<?= htmlspecialchars($agent['departement_nom'] ?? 'Non assigné') ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <?php if (empty($agentsDisponibles)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Tous les agents actifs sont déjà participants à ce projet.
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary" <?= empty($agentsDisponibles) ? 'disabled' : '' ?>>
                                            <i class="fas fa-plus me-1"></i> Ajouter au projet
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Statistiques participants -->
                        <div class="card border-0 shadow mt-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Répartition par département
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $departements = [];
                                foreach ($participants as $participant) {
                                    $dept = $participant['departement_nom'] ?? 'Non assigné';
                                    $departements[$dept] = ($departements[$dept] ?? 0) + 1;
                                }
                                ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($departements as $dept => $count): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted"><?= htmlspecialchars($dept) ?></span>
                                        <span class="badge bg-primary rounded-pill"><?= $count ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Onglet Productions -->
            <div class="tab-pane fade" id="productions" role="tabpanel">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card border-0 shadow">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-file-alt me-2"></i>Productions du projet
                                    </h6>
                                    <?php if (hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles)): ?>
                                    <a href="production.php?projet_id=<?= $projet_id ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Ajouter une production
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($productions)): ?>
                                <div class="no-results">
                                    <i class="fas fa-file-alt text-muted"></i>
                                    <h5 class="mt-3">Aucune production</h5>
                                    <p class="text-muted">Aucune production n'a été uploadée pour ce projet.</p>
                                    <?php if (hasPermission(['DG', 'Chef_projet', 'ChefDepartement'], $userRoles)): ?>
                                    <a href="production.php?projet_id=<?= $projet_id ?>" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i> Ajouter une production
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($productions as $production): 
                                        $dateUpload = date('d/m/Y H:i', strtotime($production['date_upload']));
                                        $dateModif = $production['date_modification'] ? date('d/m/Y H:i', strtotime($production['date_modification'])) : null;
                                        $confidentialiteClass = [
                                            'Public' => 'bg-success',
                                            'Interne' => 'bg-primary',
                                            'Confidentiel' => 'bg-danger'
                                        ][$production['confidentialite']] ?? 'bg-secondary';
                                    ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="production-card">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <span class="badge bg-light text-dark mb-1">
                                                        <?= htmlspecialchars($production['type_nom']) ?>
                                                    </span>
                                                    <span class="badge <?= $confidentialiteClass ?>">
                                                        <?= htmlspecialchars($production['confidentialite']) ?>
                                                    </span>
                                                </div>
                                                <div class="btn-group">
                                                    <?php if (!empty($production['cloud_url'])): ?>
                                                    <a href="<?= htmlspecialchars($production['cloud_url']) ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Voir en ligne">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission(['DG', 'Chef_projet'], $userRoles)): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteProduction(<?= $production['production_id'] ?>, '<?= htmlspecialchars(addslashes($production['nom_fichier'])) ?>')"
                                                            title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <h6 class="mb-2"><?= htmlspecialchars($production['nom_fichier']) ?></h6>
                                            
                                            <?php if (!empty($production['type_description'])): ?>
                                            <p class="small text-muted mb-2">
                                                <?= htmlspecialchars($production['type_description']) ?>
                                            </p>
                                            <?php endif; ?>
                                            
                                            <div class="small text-muted mb-2">
                                                <div><i class="fas fa-hdd me-1"></i> Stockage: <?= htmlspecialchars($production['disk_matricule']) ?></div>
                                                <div><i class="fas fa-upload me-1"></i> Upload: <?= $dateUpload ?></div>
                                                <?php if ($dateModif): ?>
                                                <div><i class="fas fa-edit me-1"></i> Modifié: <?= $dateModif ?></div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="d-grid mt-2">
                                                <a href="<?= htmlspecialchars($production['chemin_stockage']) ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   download="<?= htmlspecialchars($production['nom_fichier']) ?>">
                                                    <i class="fas fa-download me-1"></i> Télécharger
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Onglet Partenaire -->
            <?php if ($projet['partenaire_nom']): ?>
            <div class="tab-pane fade" id="partenaire" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card border-0 shadow">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-handshake me-2"></i>Informations du partenaire
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 class="text-primary mb-3"><?= htmlspecialchars($projet['partenaire_nom']) ?></h4>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <h6 class="text-muted mb-2">Type</h6>
                                                <p class="fw-bold"><?= htmlspecialchars($projet['partenaire_type']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-muted mb-2">Secteur d'activité</h6>
                                                <p class="fw-bold"><?= htmlspecialchars($projet['partenaire_secteur'] ?? 'Non spécifié') ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6 class="text-muted mb-2">Contact</h6>
                                            <div class="row">
                                                <?php if ($projet['partenaire_telephone']): ?>
                                                <div class="col-md-6 mb-2">
                                                    <i class="fas fa-phone text-primary me-2"></i>
                                                    <?= htmlspecialchars($projet['partenaire_telephone']) ?>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($projet['partenaire_email']): ?>
                                                <div class="col-md-6 mb-2">
                                                    <i class="fas fa-envelope text-primary me-2"></i>
                                                    <?= htmlspecialchars($projet['partenaire_email']) ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($projet['partenaire_adresse']): ?>
                                        <div class="mb-3">
                                            <h6 class="text-muted mb-2">Adresse</h6>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($projet['partenaire_adresse'])) ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card border">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <i class="fas fa-building fa-3x text-primary"></i>
                                                </div>
                                                <h6 class="text-muted">Partenaire du projet</h6>
                                                <p class="small text-muted">Client ou collaborateur associé</p>
                                                <a href="partenaires.php?view=<?= $projet['partenaire_id'] ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i> Voir détails
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Contrats avec ce partenaire -->
                        <?php
                        try {
                            $queryContrats = "
                                SELECT contrat_id, reference, type_contrat, etat, date_debut, montant
                                FROM CONTRATS_COM 
                                WHERE partenaire_id = ? AND statut = 0
                                ORDER BY date_debut DESC
                                LIMIT 5
                            ";
                            $stmtContrats = $conn->prepare($queryContrats);
                            $stmtContrats->execute([$projet['partenaire_id']]);
                            $contrats = $stmtContrats->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $contrats = [];
                        }
                        ?>
                        
                        <div class="card border-0 shadow">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-file-contract me-2"></i>Contrats avec ce partenaire
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($contrats)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-file-contract text-muted fa-2x mb-2"></i>
                                    <p class="text-muted small">Aucun contrat</p>
                                </div>
                                <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($contrats as $contrat): 
                                        $dateDebut = date('d/m/Y', strtotime($contrat['date_debut']));
                                        $etatClass = 'bg-' . ($contrat['etat'] == 'Actif' ? 'success' : ($contrat['etat'] == 'En attente' ? 'warning' : 'secondary'));
                                    ?>
                                    <a href="contrats-com.php?view=<?= $contrat['contrat_id'] ?>" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="d-block"><?= htmlspecialchars($contrat['reference']) ?></strong>
                                                <small class="text-muted"><?= htmlspecialchars($contrat['type_contrat']) ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge <?= $etatClass ?>"><?= htmlspecialchars($contrat['etat']) ?></span>
                                                <div class="small text-muted mt-1"><?= $dateDebut ?></div>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <a href="contrats-com.php?partenaire_id=<?= $projet['partenaire_id'] ?>" 
                                       class="btn btn-outline-primary btn-sm w-100">
                                        <i class="fas fa-list me-1"></i> Voir tous les contrats
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Onglet Activité -->
            <div class="tab-pane fade" id="activite" role="tabpanel">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card border-0 shadow">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Activité récente
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($timesheets)): ?>
                                <div class="no-results">
                                    <i class="fas fa-clock text-muted"></i>
                                    <h5 class="mt-3">Aucune activité</h5>
                                    <p class="text-muted">Aucun timesheet n'a été enregistré pour ce projet.</p>
                                </div>
                                <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($timesheets as $ts): 
                                        $dateTravail = date('d/m/Y', strtotime($ts['date_travail']));
                                        $heure = date('H:i', strtotime($ts['date_creation']));
                                        $etatClass = [
                                            'Brouillon' => 'bg-secondary',
                                            'Soumise' => 'bg-warning text-dark',
                                            'Validée' => 'bg-success',
                                            'Refusée' => 'bg-danger'
                                        ][$ts['etat']] ?? 'bg-secondary';
                                    ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between">
                                            <div class="timeline-date"><?= $dateTravail ?> à <?= $heure ?></div>
                                            <span class="badge <?= $etatClass ?>"><?= htmlspecialchars($ts['etat']) ?></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <?php if (!empty($ts['photo_profil'])): ?>
                                            <img src="../uploads/profiles/<?= htmlspecialchars($ts['photo_profil']) ?>" 
                                                 alt="<?= htmlspecialchars($ts['agent_nom']) ?>" 
                                                 class="rounded-circle me-2" width="30" height="30">
                                            <?php endif; ?>
                                            <strong><?= htmlspecialchars($ts['agent_nom']) ?></strong>
                                        </div>
                                        <div class="mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-tasks me-1"></i>
                                                <?= htmlspecialchars($ts['description_tache']) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <span class="badge bg-info">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= $ts['worked_heures'] ?> heures
                                            </span>
                                            <?php if (!empty($ts['description_travail'])): ?>
                                            <p class="mb-0 mt-2 small"><?= nl2br(htmlspecialchars($ts['description_travail'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <a href="timesheet.php?projet_id=<?= $projet_id ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-list me-1"></i> Voir tous les timesheets
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
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
        });

        // Filtrage des tâches
        function filterTaches(etat) {
            const tacheItems = document.querySelectorAll('.tache-item');
            
            tacheItems.forEach(item => {
                const itemEtat = item.getAttribute('data-etat');
                let matches = true;
                
                if (etat === 'all') {
                    matches = true;
                } else if (etat === 'en_cours') {
                    matches = itemEtat === 'En cours';
                } else if (etat === 'termine') {
                    matches = itemEtat === 'Terminé';
                } else {
                    matches = itemEtat === etat;
                }
                
                item.style.display = matches ? '' : 'none';
            });
        }

        // Supprimer une tâche
        function deleteTache(tacheId, tacheDescription) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer la tâche : "${tacheDescription}" ?`)) {
                window.location.href = `../models/delete/delete-tache.php?id=${tacheId}&csrf_token=<?= $_SESSION['csrf_token'] ?>&return_to=detail-projet.php?id=<?= $projet_id ?>`;
            }
        }

        // Supprimer une production
        function deleteProduction(productionId, nomFichier) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer la production : "${nomFichier}" ?`)) {
                window.location.href = `../models/delete/delete-production.php?id=${productionId}&csrf_token=<?= $_SESSION['csrf_token'] ?>&return_to=detail-projet.php?id=<?= $projet_id ?>`;
            }
        }

        // Retirer un participant
        function removeParticipant(agentId, agentNom) {
            if (confirm(`Êtes-vous sûr de vouloir retirer "${agentNom}" du projet ?`)) {
                window.location.href = `../models/delete/delete-participation.php?agent_id=${agentId}&projet_id=<?= $projet_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>&return_to=detail-projet.php?id=<?= $projet_id ?>`;
            }
        }

        // Validation du formulaire d'ajout de participant
        document.getElementById('addParticipantForm')?.addEventListener('submit', function(e) {
            const agentId = this.querySelector('[name="agent_id"]').value;
            
            if (!agentId) {
                e.preventDefault();
                alert('Veuillez sélectionner un agent');
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

        // Garder l'onglet actif après rechargement
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const tabTrigger = document.querySelector(`[data-bs-target="${hash}"]`);
                if (tabTrigger) {
                    new bootstrap.Tab(tabTrigger).show();
                }
            }
        });
    </script>
</body>
</html>