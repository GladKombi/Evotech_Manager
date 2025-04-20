<?php
// $ActiveClasse=0;
// $ActiveOption=0;
include_once('../connexion/connexion.php');
?>
<div id="sidebar" class='active'>
    <div class="sidebar-wrapper active">
        <div class="sidebar-header text-center">
            <img src="../images/logo.jpg" alt="" srcset="">
            <a href="Accueil.php"><h4>Evotech_Africa</h4></a>
        </div>
        <div class="sidebar-menu">
            <ul class="menu">
                <li class='sidebar-title'>Menus</li>
                <li <?php if ($ActiveDeparte == 1) { ?> class="sidebar-item active" <?php } ?>>
                    <a href="departement.php" class='sidebar-link'>
                        <i class="bi bi-people-fill" width="25"></i>
                        <span>Département</span>
                    </a>
                </li>
                <li <?php if ($ActiveRole == 1) { ?> class="sidebar-item active" <?php } ?>>
                    <a href="roles.php" class='sidebar-link'>
                        <i class="bi bi-calculator" width="25"></i>
                        <span>Roles/Fonctions</span>
                    </a>
                </li>
                <li <?php if ($ActiveAgent == 1) { ?> class="sidebar-item active" <?php } ?>>
                    <a href="Agent.php" class='sidebar-link'>
                        <i class="bi bi-person" width="25"></i>
                        <span>Agents</span>
                    </a>
                </li>
                <li <?php if ($ActiveInscription == 1) { ?> class="sidebar-item active" <?php } ?>>
                    <a href="inscription.php" class='sidebar-link'>
                        <i class="bi bi-calendar-day-fill" width="25"></i>
                        <span>Inscription</span>
                    </a>
                </li>
                <li <?php if ($ActivePayement == 1) { ?> class="sidebar-item active" <?php } ?>>
                    <a href="payement.php" class='sidebar-link'>
                        <i class="bi bi-calculator" width="25"></i>
                        <span>Payement</span>
                    </a>
                </li>
                <li <?php if ($ActiveAnee == 1) { ?> class="sidebar-item active" <?php } ?>>
                    <a href="AnneeScolaire.php" class='sidebar-link'>
                        <i class="bi bi-calendar-day-fill" width="25"></i>
                        <span>Année Academiques</span>
                    </a>
                </li>
                <li <?php if ($ActivePromo == 1) { ?> class="sidebar-item active" <?php } ?>>
                    <a href="promotion.php" class='sidebar-link'>
                        <i class="bi bi-calendar-day-fill" width="25"></i>
                        <span>Promotion</span>
                    </a>
                </li>
                <li <?php if ($ActiveUser == 1) { ?> class="sidebar-item active" <?php } ?>>
                    <a href="utilisateurs.php" class='sidebar-link'>
                        <i data-feather="user" width="25"></i>
                        <span>Utilisateur</span>
                    </a>
                </li>
                <?php  ?>

            </ul>
        </div>
        <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
    </div>
</div>