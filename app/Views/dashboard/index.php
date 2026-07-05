<?php use App\Core\Helpers; ?>

<section class="dash-page">
    <div class="dash-hero">
        <div class="dash-hero-copy">
            <span class="dash-kicker">Vue d'ensemble</span>
            <h2>Tableau de bord Evotech</h2>
            <p>Un résumé clair de vos modules actifs, avec accès rapide aux espaces de gestion les plus importants.</p>
        </div>
        <div class="dash-total">
            <span>Total données actives</span>
            <strong><?= (int) $totalRecords ?></strong>
        </div>
    </div>

    <div class="dash-section-head">
        <div>
            <h3>Modules principaux</h3>
            <p>Surveillez les volumes et ouvrez rapidement chaque module.</p>
        </div>
    </div>

    <div class="dash-stat-grid">
        <?php foreach ($modules as $module): ?>
            <a class="dash-stat-card" href="<?= Helpers::url($module['url']) ?>">
                <span class="dash-stat-icon"><i class="fa-solid <?= Helpers::e($module['icon']) ?>"></i></span>
                <span class="dash-stat-label"><?= Helpers::e($module['label']) ?></span>
                <strong><?= (int) $module['value'] ?></strong>
                <span class="dash-stat-link">Ouvrir le module <i class="fa-solid fa-arrow-right"></i></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="dash-lower-grid">
        <article class="dash-panel">
            <div class="dash-panel-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div>
                <h3>Organisation</h3>
                <p>Les modules sont séparés par domaine pour garder une lecture simple : équipe, projets, partenaires, finance et production.</p>
            </div>
        </article>

        <article class="dash-panel">
            <div class="dash-panel-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <h3>Données maîtrisées</h3>
                <p>Chaque écran utilise les mêmes repères visuels : recherche, tableaux, actions compactes et formulaires en modal.</p>
            </div>
        </article>
    </div>
</section>
