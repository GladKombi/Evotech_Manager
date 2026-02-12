<?php
// views/aside.php, la page des menus

// Démarrer la session pour vérifier les rôles
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// Définir les permissions pour chaque module
$permissions = [
    // Module RH & Entités Fondamentales
    'utilisateurs' => ['DG', 'ChefDepartement'],
    'roles' => ['DG'],
    'agents' => ['DG', 'ChefDepartement'],
    'departements' => ['DG', 'ChefDepartement'],
    'attributions' => ['DG', 'ChefDepartement'],
    
    // Module Projets
    'projets' => ['DG', 'Chef_projet', 'ChefDepartement'],
    'taches' => ['DG', 'Chef_projet', 'ChefDepartement'],
    'partenaires' => ['DG', 'Chef_projet', 'ChefDepartement'],
    'participation' => ['DG', 'Chef_projet'],
    'production' => ['DG', 'Chef_projet', 'ChefDepartement'],
    'types_production' => ['DG', 'Chef_projet'],
    
    // Module Temps
    'timesheet' => ['DG', 'Chef_projet', 'agent'],
    'validation_timesheet' => ['DG', 'Chef_projet'],
    
    // Module Contrats Communication
    'contrats_com' => ['DG', 'ChefDepartement'],
    'activites_contrat' => ['DG', 'ChefDepartement'],
    'suivi_contrats' => ['DG', 'ChefDepartement'],
    
    // Module Finance
    'comptes' => ['DG', 'comptable'],
    'mouvements' => ['DG', 'comptable'],
    'budgets' => ['DG', 'comptable'],
    'rapports_financiers' => ['DG', 'comptable', 'actionnaire'],
    
    // Module Administration
    'parametres' => ['DG'],
    'logs' => ['DG'],
    'sauvegardes' => ['DG'],
    'securite' => ['DG'],
    'statistiques' => ['DG', 'ChefDepartement', 'Chef_projet'],
    
    // Dashboard spécifiques
    'dashboard_admin' => ['DG'],
    'dashboard_chef' => ['ChefDepartement'],
    'dashboard_projet' => ['Chef_projet'],
    'dashboard_comptable' => ['comptable'],
    'dashboard_actionnaire' => ['actionnaire'],
    'dashboard_agent' => ['agent']
];

// Variables pour le menu actif (à définir dans chaque page)
$ActiveDashboard = $ActiveDashboard ?? 0;
$ActiveUtilisateur = $ActiveUtilisateur ?? 0;
$ActiveRole = $ActiveRole ?? 0;
$ActiveAgent = $ActiveAgent ?? 0;
$ActiveDepartement = $ActiveDepartement ?? 0;
$ActiveAttribution = $ActiveAttribution ?? 0;
$ActiveProjet = $ActiveProjet ?? 0;
$ActiveTache = $ActiveTache ?? 0;
$ActivePartenaire = $ActivePartenaire ?? 0;
$ActiveParticipation = $ActiveParticipation ?? 0;
$ActiveProduction = $ActiveProduction ?? 0;
$ActiveTimesheet = $ActiveTimesheet ?? 0;
$ActiveContrat = $ActiveContrat ?? 0;
$ActiveActivite = $ActiveActivite ?? 0;
$ActiveCompte = $ActiveCompte ?? 0;
$ActiveMouvement = $ActiveMouvement ?? 0;
$ActiveBudget = $ActiveBudget ?? 0;
$ActiveRapport = $ActiveRapport ?? 0;
$ActiveParametre = $ActiveParametre ?? 0;
$ActiveLog = $ActiveLog ?? 0;
$ActiveSauvegarde = $ActiveSauvegarde ?? 0;
$ActiveSecurite = $ActiveSecurite ?? 0;
$ActiveStatistique = $ActiveStatistique ?? 0;
?>

<!-- Chargement des icônes FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
                $profilePic = $_SESSION['photo_profil'] ?? 'default-avatar.png';
                $initials = strtoupper(substr($_SESSION['prenom'] ?? '', 0, 1) . substr($_SESSION['nom'] ?? '', 0, 1));
                ?>
                <div class="avatar-circle" style="background: linear-gradient(135deg, #1e40af, #3b82f6);">
                    <?= $initials ?>
                </div>
            </div>
            <div>
                <strong class="d-block"><?= htmlspecialchars(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')) ?></strong>
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
                
                <li <?= $ActiveDashboard == 1 ? 'class="sidebar-item active"' : 'class="sidebar-item"' ?>>
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
                <?php if (hasPermission($permissions['utilisateurs'], $userRoles) || 
                          hasPermission($permissions['agents'], $userRoles) || 
                          hasPermission($permissions['departements'], $userRoles) || 
                          hasPermission($permissions['attributions'], $userRoles)): ?>
                <li class='sidebar-title'>RH & Entités</li>
                
                <?php if (hasPermission($permissions['utilisateurs'], $userRoles)): ?>
                <li <?= $ActiveUtilisateur == 1 ? 'class="sidebar-item active"' : 'class="sidebar-item"' ?>>
                    <a href="utilisateurs.php" class='sidebar-link'>
                        <i class="fas fa-users-cog text-info"></i>
                        <span>Utilisateurs</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasPermission($permissions['agents'], $userRoles)): ?>
                <li <?= $ActiveAgent == 1 ? 'class="sidebar-item active"' : 'class="sidebar-item"' ?>>
                    <a href="agents.php" class='sidebar-link'>
                        <i class="fas fa-user-tie text-success"></i>
                        <span>Agents</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasPermission($permissions['departements'], $userRoles)): ?>
                <li <?= $ActiveDepartement == 1 ? 'class="sidebar-item active"' : 'class="sidebar-item"' ?>>
                    <a href="departements.php" class='sidebar-link'>
                        <i class="fas fa-building text-warning"></i>
                        <span>Départements</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasPermission($permissions['roles'], $userRoles)): ?>
                <li <?= $ActiveRole == 1 ? 'class="sidebar-item active"' : 'class="sidebar-item"' ?>>
                    <a href="roles.php" class='sidebar-link'>
                        <i class="fas fa-user-tag text-danger"></i>
                        <span>Rôles & Permissions</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasPermission($permissions['attributions'], $userRoles)): ?>
                <li <?= $ActiveAttribution == 1 ? 'class="sidebar-item active"' : 'class="sidebar-item"' ?>>
                    <a href="attributions.php" class='sidebar-link'>
                        <i class="fas fa-user-check text-secondary"></i>
                        <span>Attributions</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
                
                <!-- Module Projets -->
                <?php if (hasPermission($permissions['projets'], $userRoles) || 
                          hasPermission($permissions['taches'], $userRoles) || 
                          hasPermission($permissions['production'], $userRoles)): ?>
                <li class='sidebar-title'>Gestion des Projets</li>
                
                <li class="sidebar-item has-sub <?= $ActiveProjet == 1 || $ActiveTache == 1 || $ActivePartenaire == 1 || $ActiveProduction == 1 ? 'active' : '' ?>">
                    <a href="#" class='sidebar-link'>
                        <i class="fas fa-project-diagram text-info"></i>
                        <span>Projets & Production</span>
                    </a>
                    <ul class="submenu">
                        <?php if (hasPermission($permissions['projets'], $userRoles)): ?>
                        <li <?= $ActiveProjet == 1 ? 'class="active"' : '' ?>>
                            <a href="projets.php">Projets</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['taches'], $userRoles)): ?>
                        <li <?= $ActiveTache == 1 ? 'class="active"' : '' ?>>
                            <a href="taches.php">Tâches</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['partenaires'], $userRoles)): ?>
                        <li <?= $ActivePartenaire == 1 ? 'class="active"' : '' ?>>
                            <a href="partenaires.php">Partenaires</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['participation'], $userRoles)): ?>
                        <li <?= $ActiveParticipation == 1 ? 'class="active"' : '' ?>>
                            <a href="participation.php">Participation</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['production'], $userRoles)): ?>
                        <li <?= $ActiveProduction == 1 ? 'class="active"' : '' ?>>
                            <a href="production.php">Production</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['types_production'], $userRoles)): ?>
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
                <?php if (hasPermission($permissions['timesheet'], $userRoles) || 
                          hasPermission($permissions['validation_timesheet'], $userRoles)): ?>
                <li class='sidebar-title'>Gestion du Temps</li>
                
                <li class="sidebar-item has-sub <?= $ActiveTimesheet == 1 ? 'active' : '' ?>">
                    <a href="#" class='sidebar-link'>
                        <i class="fas fa-clock text-warning"></i>
                        <span>Timesheets</span>
                    </a>
                    <ul class="submenu">
                        <?php if (hasPermission($permissions['timesheet'], $userRoles)): ?>
                        <li <?= $ActiveTimesheet == 1 ? 'class="active"' : '' ?>>
                            <a href="timesheet.php">Mes timesheets</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['validation_timesheet'], $userRoles)): ?>
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
                <?php if (hasPermission($permissions['contrats_com'], $userRoles) || 
                          hasPermission($permissions['activites_contrat'], $userRoles)): ?>
                <li class='sidebar-title'>Communication</li>
                
                <li class="sidebar-item has-sub <?= $ActiveContrat == 1 || $ActiveActivite == 1 ? 'active' : '' ?>">
                    <a href="#" class='sidebar-link'>
                        <i class="fas fa-file-contract text-success"></i>
                        <span>Contrats Communication</span>
                    </a>
                    <ul class="submenu">
                        <?php if (hasPermission($permissions['contrats_com'], $userRoles)): ?>
                        <li <?= $ActiveContrat == 1 ? 'class="active"' : '' ?>>
                            <a href="contrats-com.php">Contrats</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['activites_contrat'], $userRoles)): ?>
                        <li <?= $ActiveActivite == 1 ? 'class="active"' : '' ?>>
                            <a href="activites-contrat.php">Activités</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['suivi_contrats'], $userRoles)): ?>
                        <li>
                            <a href="suivi-contrats.php">Suivi contrats</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Module Finance & Caisse -->
                <?php if (hasPermission($permissions['comptes'], $userRoles) || 
                          hasPermission($permissions['mouvements'], $userRoles) || 
                          hasPermission($permissions['budgets'], $userRoles)): ?>
                <li class='sidebar-title'>Finance & Caisse</li>
                
                <li class="sidebar-item has-sub <?= $ActiveCompte == 1 || $ActiveMouvement == 1 || $ActiveBudget == 1 ? 'active' : '' ?>">
                    <a href="#" class='sidebar-link'>
                        <i class="fas fa-money-bill-wave text-success"></i>
                        <span>Gestion Financière</span>
                    </a>
                    <ul class="submenu">
                        <?php if (hasPermission($permissions['comptes'], $userRoles)): ?>
                        <li <?= $ActiveCompte == 1 ? 'class="active"' : '' ?>>
                            <a href="comptes.php">Comptes</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['mouvements'], $userRoles)): ?>
                        <li <?= $ActiveMouvement == 1 ? 'class="active"' : '' ?>>
                            <a href="mouvements.php">Mouvements</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['budgets'], $userRoles)): ?>
                        <li <?= $ActiveBudget == 1 ? 'class="active"' : '' ?>>
                            <a href="budgets.php">Budgets</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['rapports_financiers'], $userRoles)): ?>
                        <li <?= $ActiveRapport == 1 ? 'class="active"' : '' ?>>
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
                
                <!-- Module Administration Système (DG uniquement) -->
                <?php if (hasPermission($permissions['parametres'], $userRoles) || 
                          hasPermission($permissions['logs'], $userRoles) || 
                          hasPermission($permissions['securite'], $userRoles)): ?>
                <li class='sidebar-title'>Administration</li>
                
                <li class="sidebar-item has-sub <?= $ActiveParametre == 1 || $ActiveLog == 1 || $ActiveSecurite == 1 ? 'active' : '' ?>">
                    <a href="#" class='sidebar-link'>
                        <i class="fas fa-cogs text-secondary"></i>
                        <span>Système</span>
                    </a>
                    <ul class="submenu">
                        <?php if (hasPermission($permissions['parametres'], $userRoles)): ?>
                        <li <?= $ActiveParametre == 1 ? 'class="active"' : '' ?>>
                            <a href="parametres.php">Paramètres</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['logs'], $userRoles)): ?>
                        <li <?= $ActiveLog == 1 ? 'class="active"' : '' ?>>
                            <a href="logs.php">Logs système</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['securite'], $userRoles)): ?>
                        <li <?= $ActiveSecurite == 1 ? 'class="active"' : '' ?>>
                            <a href="securite.php">Sécurité</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($permissions['sauvegardes'], $userRoles)): ?>
                        <li <?= $ActiveSauvegarde == 1 ? 'class="active"' : '' ?>>
                            <a href="sauvegardes.php">Sauvegardes</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <?php if (hasPermission($permissions['statistiques'], $userRoles)): ?>
                <li <?= $ActiveStatistique == 1 ? 'class="sidebar-item active"' : 'class="sidebar-item"' ?>>
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

<style>
    /* Style principal */
    .sidebar-wrapper {
        background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
        box-shadow: 2px 0 15px rgba(0, 0, 0, 0.08);
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        width: 280px;
        overflow-y: auto;
        transition: all 0.3s ease;
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
    
    .sidebar-header a {
        color: #1e40af;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .sidebar-header a:hover {
        color: #3b82f6;
    }
    
    .sidebar-header h4 {
        margin: 0;
        font-weight: 700;
        color: #1e40af;
        font-size: 1.4rem;
    }
    
    .sidebar-header .text-muted {
        color: #6c757d !important;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    /* Profil utilisateur */
    .user-profile {
        padding: 25px 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        background: white;
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
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .user-profile strong {
        color: #343a40;
        font-size: 1.1rem;
        margin-bottom: 5px;
        display: block;
    }
    
    .user-profile .text-muted {
        color: #6c757d !important;
        font-size: 0.9rem;
        background: #f8f9fa;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
    }
    
    /* Titres de sections */
    .sidebar-title {
        color: #6c757d;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        font-weight: 600;
        padding: 20px 25px 10px;
        margin-top: 10px;
        opacity: 0.8;
    }
    
    /* Items du menu */
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
        position: relative;
        overflow: hidden;
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
        transition: all 0.3s ease;
    }
    
    .sidebar-link:hover i {
        transform: scale(1.1);
    }
    
    /* Couleurs des icônes */
    .sidebar-link i.text-primary { color: #1e40af; }
    .sidebar-link i.text-info { color: #17a2b8; }
    .sidebar-link i.text-success { color: #28a745; }
    .sidebar-link i.text-warning { color: #ffc107; }
    .sidebar-link i.text-danger { color: #dc3545; }
    .sidebar-link i.text-secondary { color: #6c757d; }
    
    /* Sous-menus */
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
    
    .submenu li {
        margin: 2px 0;
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
    
    .submenu a::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background: #adb5bd;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .submenu a:hover::before {
        background: #1e40af;
        transform: translateY(-50%) scale(1.2);
    }
    
    /* Bouton toggle */
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
    
    .sidebar-toggler:hover {
        background: #e9ecef;
        color: #495057;
        transform: rotate(90deg);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* Scrollbar personnalisée */
    .sidebar-wrapper::-webkit-scrollbar {
        width: 6px;
    }
    
    .sidebar-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .sidebar-wrapper::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    .sidebar-wrapper::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .sidebar-wrapper {
            transform: translateX(-100%);
            box-shadow: none;
        }
        
        .sidebar-wrapper.active {
            transform: translateX(0);
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
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
        
        .sidebar-wrapper.active + .overlay {
            display: block;
        }
    }
    
    /* Animation d'onde au clic */
    .sidebar-link:active::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 5px;
        height: 5px;
        background: rgba(59, 130, 246, 0.6);
        opacity: 0;
        border-radius: 100%;
        transform: scale(1, 1) translate(-50%);
        transform-origin: 50% 50%;
        animation: ripple 1s ease-out;
    }
    
    @keyframes ripple {
        0% {
            transform: scale(0, 0);
            opacity: 0.5;
        }
        100% {
            transform: scale(40, 40);
            opacity: 0;
        }
    }
    
    /* Style pour les liens de déconnexion */
    .sidebar-link.text-danger {
        color: #dc3545 !important;
    }
    
    .sidebar-link.text-danger:hover {
        color: #bd2130 !important;
        background: linear-gradient(90deg, #fdf2f2 0%, #ffffff 100%);
        border-left-color: #dc3545;
    }
    
    /* Indicateur de notification */
    .sidebar-link .badge-notif {
        position: absolute;
        right: 20px;
        background: #dc3545;
        color: white;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 10px;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
</style>

<script>
    // Gestion des sous-menus
    document.addEventListener('DOMContentLoaded', function() {
        const hasSubItems = document.querySelectorAll('.sidebar-item.has-sub');
        const overlay = document.createElement('div');
        overlay.className = 'overlay';
        document.body.appendChild(overlay);
        
        hasSubItems.forEach(item => {
            const link = item.querySelector('.sidebar-link');
            const submenu = item.querySelector('.submenu');
            
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
        
        // Ouvrir automatiquement les sous-menus qui contiennent un item actif
        const activeSubItems = document.querySelectorAll('.submenu .active');
        activeSubItems.forEach(item => {
            const parent = item.closest('.sidebar-item.has-sub');
            if (parent) {
                parent.classList.add('active');
            }
        });
        
        // Gestion du toggle du sidebar
        const sidebarToggler = document.querySelector('.sidebar-toggler');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggler) {
            sidebarToggler.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
        }
        
        // Fermer le sidebar quand on clique sur l'overlay (mobile)
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            this.classList.remove('active');
        });
        
        // Fermer le sidebar quand on clique sur un lien (mobile)
        const sidebarLinks = document.querySelectorAll('.sidebar-link:not([href="#"])');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
        
        // Raccourci clavier pour basculer le menu (Alt + M)
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.key === 'm') {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
            
            // Échap pour fermer
            if (e.key === 'Escape') {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
        
        // Animation au clic sur les liens
        const links = document.querySelectorAll('.sidebar-link');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!this.getAttribute('href') || this.getAttribute('href') === '#') {
                    return;
                }
                
                // Ajouter une classe de chargement
                this.classList.add('loading');
                
                // Retirer après 1s
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 1000);
            });
        });
        
        // Animation d'entrée pour les items
        const menuItems = document.querySelectorAll('.sidebar-item');
        menuItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            item.style.transition = 'all 0.3s ease ' + (index * 0.05) + 's';
            
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, 100);
        });
    });
</script>