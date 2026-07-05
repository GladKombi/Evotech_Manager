<?php

use App\Core\Auth;
use App\Core\Helpers;
use App\Core\Session;

$user = Session::user();
$currentPath = Helpers::currentPath();
$navItems = [
    ['path' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
    ['path' => 'utilisateurs', 'label' => 'Utilisateurs', 'icon' => 'fa-user-shield'],
    ['path' => 'agents', 'label' => 'Agents', 'icon' => 'fa-users'],
    ['path' => 'departements', 'label' => 'Départements', 'icon' => 'fa-sitemap'],
    ['path' => 'partenaires', 'label' => 'Partenaires', 'icon' => 'fa-handshake'],
    ['path' => 'realisations', 'label' => 'Réalisations', 'icon' => 'fa-images'],
    ['path' => 'projets', 'label' => 'Projets', 'icon' => 'fa-diagram-project'],
    ['path' => 'taches', 'label' => 'Tâches', 'icon' => 'fa-list-check'],
    ['path' => 'timesheets', 'label' => 'Timesheets', 'icon' => 'fa-clock'],
    ['path' => 'contrats-com', 'label' => 'Contrats Com', 'icon' => 'fa-file-signature'],
    ['path' => 'comptes', 'label' => 'Finance', 'icon' => 'fa-wallet'],
    ['path' => 'productions', 'label' => 'Productions', 'icon' => 'fa-chart-line'],
];
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Helpers::e($title ?? 'Evotech Africa') ?></title>
    <script>
        (function() {
            var saved = localStorage.getItem('evotech-theme');
            var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= Helpers::asset('assets/css/app.css') ?>">
    <script>
        window.EvotechConfig = {
            updateUrl: <?= json_encode(Helpers::url('records/update')) ?>,
            currentPath: <?= json_encode($currentPath) ?>
        };
    </script>
</head>

<body class="app-body">
    <aside class="sidebar">
        <a class="brand" href="<?= Helpers::url('dashboard') ?>">
            <span class="logo">EA</span>
            <span>
                <strong>Evotech Africa</strong>
                <small>Gestion intégrée</small>
            </span>
        </a>

        <nav class="sidebar-nav" aria-label="Navigation principale">
            <?php foreach ($navItems as $item): ?>
                <?php if (!Auth::can($item['path'])) continue; ?>
                <?php $isActive = $currentPath === $item['path']; ?>
                <a class="<?= $isActive ? 'active' : '' ?>" href="<?= Helpers::url($item['path']) ?>">
                    <i class="fa-solid <?= Helpers::e($item['icon']) ?>"></i>
                    <span><?= Helpers::e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="user-panel">
            <div class="avatar"><?= strtoupper(substr((string) ($user['nom'] ?? 'A'), 0, 1)) ?></div>
            <div>
                <strong><?= Helpers::e($user['nom'] ?? 'Admin') ?></strong>
                <?php if (!empty($user['roles'])): ?>
                    <small><?= Helpers::e($user['roles']) ?></small>
                <?php endif; ?>
                <a href="<?= Helpers::url('logout') ?>">Déconnexion</a>
            </div>
        </div>
    </aside>

    <main class="main-shell">
        <header class="topbar">
            <div>
                <span class="topbar-kicker">Evotech Manager</span>
                <h1><?= Helpers::e($title ?? '') ?></h1>
            </div>
            <div class="topbar-actions">
                <button type="button" class="icon-button" data-theme-toggle aria-label="Changer le thème">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <a class="icon-button" href="<?= Helpers::url() ?>" title="Voir l'accueil">
                    <i class="fa-solid fa-house"></i>
                </a>
            </div>
        </header>

        <section class="content">
            <?php if ($message = Session::flash('success')): ?>
                <div class="alert success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= Helpers::e($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($message = Session::flash('error')): ?>
                <div class="alert danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= Helpers::e($message) ?></span>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </section>
    </main>

    <script src="<?= Helpers::asset('assets/js/theme.js') ?>"></script>
    <script src="<?= Helpers::asset('assets/js/app.js') ?>"></script>
</body>

</html>
