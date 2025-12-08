<?php
// $ActiveClasse=0;
// $ActiveOption=0;
include_once('../connexion/connexion.php');
?>
<div id="sidebar" class='active'>
    <div class="sidebar-wrapper active">
        <div class="sidebar-header text-center">
            <img src="../images/logo.jpg" alt="" srcset="">
            <a href="Accueil.php">
                <h4>Evotech_Africa</h4>
            </a>
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
                        <i class="bi bi-people-fill" width="25"></i>
                        <span>Agents</span>
                    </a>
                </li>
                <li <?php if ($ActiveAtribut == 1) { ?> class="sidebar-item active" <?php } ?>>
                    <a href="attribution.php" class='sidebar-link'>
                        <i class="bi bi-calendar-day-fill" width="25"></i>
                        <span>Attribution</span>
                    </a>
                </li>
                <li class="sidebar-item  has-sub">
                    <a href="#" class='sidebar-link'>
                        <i data-feather="triangle" width="20"></i>
                        <span>Menu Projet</span>
                    </a>
                    <ul class="submenu ">
                        <li <?php if ($ActivePartenaire == 1) { ?> class="sidebar-item active" <?php } ?>>
                            <a href="partenaire.php">Partenaires </a>
                        </li>
                        <li <?php if ($ActiveType_projet == 1) { ?> class="sidebar-item active" <?php } ?>>
                            <a href="type_projet.php">Types de projets </a>
                        </li>
                        <li <?php if ($ActiveProjet == 1) { ?> class="sidebar-item active" <?php } ?>>
                            <a href="project.php">Projets </a>
                        </li>
                        <li <?php if ($ActiveProjet == 1) { ?> class="sidebar-item active" <?php } ?>>
                            <a href="production.php">Productions </a>
                        </li>
                    </ul>

                </li>
                <li class="sidebar-item  has-sub">

                    <a href="#" class='sidebar-link'>
                        <i data-feather="triangle" width="20"></i>
                        <span>Mouvements caisse</span>
                    </a>
                    <ul class="submenu ">
                        <li <?php if ($ActiveEntree == 1) { ?> class="sidebar-item active" <?php } ?>>
                            <a href="entree.php">Entrées en caisse </a>
                        </li>
                        <li <?php if ($ActiveSortie == 1) { ?> class="sidebar-item active" <?php } ?>>
                            <a href="sortie.php">Sortie en caisse </a>
                        </li>
                        <li <?php if ($ActiveCloture == 1) { ?> class="sidebar-item active" <?php } ?>>
                            <a href="cloture.php">Cloture </a>
                        </li>
                    </ul>

                </li>
                <li class="sidebar-item  has-sub">
                    <a href="#" class='sidebar-link'>
                        <i data-feather="triangle" width="20"></i>
                        <span>Materiels</span>
                    </a>
                    <ul class="submenu ">
                        <li <?php if ($ActiveMateriel == 1) { ?> class="sidebar-item active" <?php } ?>>
                            <a href="disk.php">Disk de stockage </a>
                        </li>
                        <li <?php if ($ActiveMatos == 1) { ?> class="sidebar-item active" <?php } ?>>
                            <a href="materiels-Details.php">Equipements </a>
                        </li>
                    </ul>
                </li>
               
                <?php  ?>

            </ul>
        </div>
        <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
    </div>
</div>