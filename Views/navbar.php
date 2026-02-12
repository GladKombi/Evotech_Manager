<nav class="navbar navbar-header navbar-expand navbar-light">
    <!-- Bouton hamburger pour mobile -->
    <button class="sidebar-toggle d-lg-none" aria-label="Ouvrir le menu">
        <i class="fas fa-bars fa-lg"></i>
    </button>
    
    <a class="sidebar-toggler d-none d-lg-block" href="#"><span class="navbar-toggler-icon"></span></a>
    
    <button class="btn navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav d-flex align-items-center navbar-light ms-auto">
            <!-- Notification Bell -->
            <li class="dropdown nav-icon">
                <a href="#" data-bs-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                    <div class="d-lg-inline-block">
                        <i data-feather="bell"></i>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-large">
                    <h6 class='py-2 px-4'>Notifications</h6>
                    <ul class="list-group rounded-none">
                        <li class="list-group-item border-0 align-items-start">
                            <div class="avatar bg-success me-3">
                                <span class="avatar-content"><i data-feather="shopping-cart"></i></span>
                            </div>
                            <div>
                                <h6 class='text-bold'>Nouvelle Notification</h6>
                                <p class='text-xs'>
                                    Tableau de bord DG mis à jour
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </li>
            
            <!-- Messages -->
            <li class="dropdown nav-icon me-2">
                <a href="#" data-bs-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                    <div class="d-lg-inline-block">
                        <i data-feather="mail"></i>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="#"><i data-feather="user"></i> Mon Compte</a>
                    <a class="dropdown-item active" href="#"><i data-feather="mail"></i> Messages</a>
                    <a class="dropdown-item" href="#"><i data-feather="settings"></i> Paramètres</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="../logout.php"><i data-feather="log-out"></i> Déconnexion</a>
                </div>
            </li>
            
            <!-- Profil utilisateur -->
            <li class="dropdown">
                <a href="#" data-bs-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                    <div class="avatar me-1">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">
                            <?php 
                            $initials = strtoupper(substr($_SESSION['prenom'] ?? '', 0, 1) . substr($_SESSION['nom'] ?? '', 0, 1));
                            echo $initials;
                            ?>
                        </div>
                    </div>
                    <div class="d-none d-md-block d-lg-inline-block">Bonjour, <?= htmlspecialchars($_SESSION['prenom'] ?? 'Utilisateur') ?></div>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="mon-profil.php"><i data-feather="user"></i> Mon profil</a>
                    <a class="dropdown-item" href="#"><i data-feather="settings"></i> Paramètres</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="../logout.php"><i data-feather="log-out"></i> Déconnexion</a>
                </div>
            </li>
        </ul>
    </div>
</nav>

<style>
    .navbar {
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 10px 15px;
        top: 0;
        z-index: 1030;
        width: 100%;
        min-height: 60px;
    }
    
    .sidebar-toggle {
        background: none;
        border: none;
        color: #333;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 8px 12px;
        margin-right: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sidebar-toggle:hover {
        color: #1e40af;
        background: #f8f9fa;
        border-radius: 4px;
    }
    
    .sidebar-toggler {
        margin-right: 15px;
        color: #333;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    
    .sidebar-toggler:hover {
        background: #f8f9fa;
        color: #1e40af;
    }
    
    .navbar-toggler {
        border: none;
        padding: 8px 12px;
    }
    
    .navbar-toggler:focus {
        box-shadow: none;
        outline: 2px solid #1e40af;
    }
    
    .nav-link-user {
        display: flex;
        align-items: center;
        color: #333;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    
    .nav-link-user:hover {
        background: #f8f9fa;
        color: #1e40af;
    }
    
    .nav-icon {
        margin-right: 10px;
    }
    
    .nav-icon .nav-link {
        color: #6c757d;
        padding: 8px 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    
    .nav-icon .nav-link:hover {
        background: #f8f9fa;
        color: #1e40af;
    }
    
    .dropdown-menu {
        border: 1px solid #e9ecef;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-radius: 8px;
        padding: 10px 0;
        min-width: 200px;
    }
    
    .dropdown-item {
        padding: 8px 20px;
        color: #495057;
        transition: all 0.2s ease;
    }
    
    .dropdown-item:hover {
        background: #f8f9fa;
        color: #1e40af;
    }
    
    .dropdown-item.active {
        background: #f0f7ff;
        color: #1e40af;
    }
    
    .dropdown-divider {
        margin: 8px 0;
        border-color: #e9ecef;
    }
    
    .avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        color: white;
        font-weight: bold;
        font-size: 0.9rem;
    }
    
    .avatar-content {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }
    
    .dropdown-menu-large {
        min-width: 300px;
    }
    
    .list-group-item {
        border: none;
        padding: 15px;
        transition: all 0.2s ease;
    }
    
    .list-group-item:hover {
        background: #f8f9fa;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .navbar {
            padding: 8px 10px;
        }
        
        .sidebar-toggle {
            margin-right: 5px;
            padding: 6px 10px;
        }
        
        .nav-link-user {
            padding: 6px 10px;
        }
        
        .d-none.d-md-block.d-lg-inline-block {
            display: none !important;
        }
    }
    
    @media (max-width: 576px) {
        .dropdown-menu-large {
            min-width: 250px;
            right: -50px;
        }
        
        .nav-icon {
            margin-right: 5px;
        }
        
        .nav-icon .nav-link {
            padding: 6px 8px;
        }
    }
</style>

<script>
    // Initialiser Feather Icons
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>