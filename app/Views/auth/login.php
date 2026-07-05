<?php

use App\Core\Helpers;
use App\Core\Session;

$error = Session::flash('error');
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Helpers::e($title ?? 'Connexion') ?> - Evotech Africa</title>
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
    <link rel="stylesheet" href="<?= Helpers::asset('assets/css/auth.css') ?>">
</head>

<body>
    <main class="login-shell">
        <form class="login-card" method="post" action="<?= Helpers::url('login') ?>">
            <a class="close-home" href="<?= Helpers::url() ?>" aria-label="Retour à l'accueil" title="Retour à l'accueil">
                <i class="fa-solid fa-xmark"></i>
            </a>
            <button type="button" class="theme-toggle-login" data-theme-toggle aria-label="Changer le thème">
                <i class="fa-solid fa-moon"></i>
            </button>

            <a class="brand" href="<?= Helpers::url() ?>">
                <span class="brand-mark">EA</span>
                <span><span class="gradient-text">Evotech</span> Africa</span>
            </a>

            <div class="card-head">
                <h1>Connexion</h1>
                <p>Entrez vos identifiants pour rejoindre le tableau de bord.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= Helpers::e($error) ?></span>
                </div>
            <?php endif; ?>

            <label class="field">
                Email
                <span class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input name="email" type="email" autocomplete="email" placeholder="nom@evotech-africa.net" required>
                </span>
            </label>

            <label class="field">
                Mot de passe
                <span class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input name="password" type="password" autocomplete="current-password" placeholder="Votre mot de passe" required>
                </span>
            </label>

            <button class="btn-primary" type="submit">Se connecter</button>

            <div class="meta">
                <span>Accès réservé à l'équipe Evotech.</span>
                <a href="mailto:contact@evotech-africa.net">Besoin d'aide ?</a>
            </div>
        </form>
    </main>
    <script src="<?= Helpers::asset('assets/js/theme.js') ?>"></script>
</body>

</html>
