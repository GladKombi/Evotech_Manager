<?php

use App\Core\Helpers;

$loginLabel = $isLoggedIn ? 'Tableau de Bord' : 'Se connecter';
$loginUrl = $isLoggedIn ? Helpers::url('dashboard') : Helpers::url('login');
$homeUrl = Helpers::url();
$uploadRoot = dirname(__DIR__, 3) . '/public/uploads';
$logoPath = $uploadRoot . '/logos/logo.jpg';
$logoUrl = Helpers::url('uploads/logos/logo.jpg');
if (!is_file($logoPath) && is_file($uploadRoot . '/logo.jpg')) {
    $logoPath = $uploadRoot . '/logo.jpg';
    $logoUrl = Helpers::url('uploads/logo.jpg');
}
$hasLogo = is_file($logoPath);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($title ?? "Evotech Africa - Contribuer à l'évolution technologique en Afrique") ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js" defer></script>
    <style>
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            scroll-behavior: smooth;
            max-width: 100%;
        }

        :root {
            --color-primary: #1a3a7a;
            --color-primary-light: #2a5a9a;
            --color-primary-dark: #0a2a5a;
            --color-secondary: #4a8fc7;
            --color-secondary-light: #6aafd7;
            --color-secondary-dark: #2a6fa7;
            --color-accent: #4a8fc7;
        }

        .gradient-text {
            background: linear-gradient(45deg, var(--color-primary), var(--color-secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--color-primary), var(--color-secondary));
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(26, 58, 122, 0.3);
        }

        .service-card {
            transition: all 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .nav-link {
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background: linear-gradient(45deg, var(--color-primary), var(--color-secondary));
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .btn-login {
            background: transparent;
            border: 2px solid var(--color-primary);
            color: var(--color-primary);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--color-primary);
            color: white;
        }

        .btn-login-dashboard {
            background: linear-gradient(45deg, var(--color-primary), var(--color-secondary));
            color: white;
            transition: all 0.3s ease;
        }

        .btn-login-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(26, 58, 122, 0.3);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--color-primary), var(--color-secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .team-card:hover .team-social {
            opacity: 1;
        }

        .team-social {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        [x-cloak] {
            display: none !important;
        }

        .bg-hero {
            background: linear-gradient(135deg, #e8f0fe 0%, #d4e4f7 50%, #c0d8f0 100%);
        }

        .bg-gradient-footer {
            background: linear-gradient(135deg, #0a1a3a, #1a3a6a);
        }

        .realisation-carousel {
            position: relative;
            height: 14rem;
            background: linear-gradient(135deg, #e8f0fe, #d4e4f7);
        }

        .realisation-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
            transition: opacity .35s ease;
        }

        .realisation-slide.active {
            opacity: 1;
            pointer-events: auto;
        }

        .realisation-media {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .realisation-empty {
            height: 14rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a8fc7;
            background: linear-gradient(135deg, #e8f0fe, #d4e4f7);
        }

        .carousel-button {
            position: absolute;
            top: 50%;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 999px;
            color: white;
            background: rgba(10, 42, 90, .74);
            transform: translateY(-50%);
            transition: background .2s ease;
        }

        .carousel-button:hover {
            background: rgba(10, 42, 90, .92);
        }

        .carousel-button.prev {
            left: 12px;
        }

        .carousel-button.next {
            right: 12px;
        }

        .carousel-dots {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 12px;
            display: flex;
            justify-content: center;
            gap: 6px;
        }

        .carousel-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .68);
        }

        .carousel-dot.active {
            width: 22px;
            background: white;
        }

        .home-detail-button {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: var(--color-primary);
            font-weight: 700;
            transition: color .2s ease;
        }

        .home-detail-button:hover {
            color: var(--color-secondary);
        }

        .home-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(10, 26, 58, .56);
        }

        .home-modal.active {
            display: flex;
        }

        .home-modal-box {
            width: min(980px, 100%);
            max-height: 88vh;
            overflow: auto;
            border-radius: 1rem;
            background: white;
            box-shadow: 0 24px 70px rgba(10, 26, 58, .28);
        }

        .home-modal-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #d9e6f5;
        }

        .home-modal-head h3 {
            margin: 0;
            color: var(--color-primary);
            font-size: 1.5rem;
            font-weight: 800;
        }

        .home-modal-close {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: .75rem;
            color: var(--color-primary);
            background: #e8f0fe;
        }

        .home-modal-body {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(260px, .8fr);
            gap: 1rem;
            padding: 1.5rem;
        }

        .home-modal-medias {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: .75rem;
        }

        .home-modal-medias img,
        .home-modal-medias video {
            width: 100%;
            height: 190px;
            object-fit: cover;
            border-radius: .875rem;
            border: 1px solid #d9e6f5;
            background: #e8f0fe;
        }

        .home-modal-copy p {
            color: #64748b;
            line-height: 1.7;
        }

        @media (max-width: 768px) {
            .home-modal-body {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <header x-data="{ open: false }" class="bg-white shadow-md fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="<?= $homeUrl ?>" class="flex items-center">
                <span class="text-2xl font-bold gradient-text">Evotech</span>
                <span class="ml-1 text-gray-700 font-semibold">Africa</span>
            </a>

            <nav class="hidden md:flex space-x-8 items-center">
                <a href="<?= $homeUrl ?>" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Accueil</a>
                <a href="#services" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Services</a>
                <a href="#projets" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Projets</a>
                <a href="#equipe" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Équipe</a>
                <a href="#apropos" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">À propos</a>
                <a href="#contact" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Contact</a>
                <a href="<?= $loginUrl ?>" class="px-4 py-2 rounded-lg <?= $isLoggedIn ? 'btn-login-dashboard' : 'btn-login' ?> font-semibold text-sm transition-colors">
                    <?= Helpers::e($loginLabel) ?>
                </a>
            </nav>

            <button @click="open = !open" class="md:hidden focus:outline-none" aria-label="Menu">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <div x-show="open" @click.away="open = false" class="md:hidden bg-white shadow-lg absolute w-full" x-cloak>
            <div class="container mx-auto px-4 py-3 flex flex-col space-y-3">
                <a href="<?= $homeUrl ?>" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Accueil</a>
                <a href="#services" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Services</a>
                <a href="#projets" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Projets</a>
                <a href="#equipe" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Équipe</a>
                <a href="#apropos" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">À propos</a>
                <a href="#contact" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Contact</a>
                <a href="<?= $loginUrl ?>" class="btn-primary text-white font-semibold px-4 py-2 rounded-lg text-center">
                    <?= Helpers::e($loginLabel) ?>
                </a>
            </div>
        </div>
    </header>

    <section class="pt-32 pb-20 bg-hero">
        <div class="container mx-auto px-4 flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-10 md:mb-0" data-aos="fade-right">
                <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">
                    Contribuer à l'évolution<br>
                    <span class="gradient-text">technologique en Afrique</span>
                </h1>
                <p class="text-lg text-gray-700 mb-8">
                    Evotech Africa combine expertise technologique, vision architecturale et créativité marketing pour transformer vos projets en réalités exceptionnelles.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="#contact" class="btn-primary text-white font-semibold px-6 py-3 rounded-lg shadow-md">Nous contacter</a>
                    <a href="#projets" class="bg-white text-gray-800 font-semibold px-6 py-3 rounded-lg shadow-md border border-blue-200 hover:bg-blue-50 transition-colors">Voir nos projets</a>
                </div>
            </div>
            <div class="md:w-1/2" data-aos="fade-left">
                <div class="bg-white rounded-xl shadow-2xl overflow-hidden border-2 border-blue-200">
                    <?php if ($hasLogo): ?>
                        <img src="<?= $logoUrl ?>" alt="Evotech Africa" class="w-full h-auto">
                    <?php else: ?>
                        <div class="h-80 bg-gradient-to-br from-blue-100 to-blue-200 flex flex-col items-center justify-center text-center px-8">
                            <span class="text-6xl font-bold gradient-text">EA</span>
                            <p class="mt-4 text-xl font-semibold text-blue-900">Evotech Africa</p>
                            <p class="mt-2 text-gray-600">Contribuer à l'évolution technologique en Afrique</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 bg-white border-b border-blue-100">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-number"><?= (int) $nbProjets ?></div>
                    <p class="text-gray-600 font-medium">Projets Réalisés</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-number"><?= (int) $nbClients ?></div>
                    <p class="text-gray-600 font-medium">Clients Satisfaits</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-number"><?= (int) $nbEmployes ?></div>
                    <p class="text-gray-600 font-medium">Experts</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-number"><?= (int) $nbPartenaires ?></div>
                    <p class="text-gray-600 font-medium">Partenaires</p>
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl font-bold mb-4">Nos Services</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Un éventail de solutions pour répondre à vos besoins en technologie, architecture et communication.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($services as $index => $service): ?>
                    <div class="service-card bg-white rounded-xl shadow-lg p-6 border border-blue-100 hover:border-blue-300" data-aos="zoom-in" data-aos-delay="<?= ($index + 1) * 100 ?>">
                        <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas <?= Helpers::e($service['icone'] ?? 'fa-laptop-code') ?> text-2xl text-blue-700"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-blue-900"><?= Helpers::e($service['nom'] ?? '') ?></h3>
                        <p class="text-gray-600 mb-4"><?= Helpers::e($service['description'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-12">
                <a href="#contact" class="btn-primary text-white font-semibold px-6 py-3 rounded-lg shadow-md inline-block">Demander un devis</a>
            </div>
        </div>
    </section>

    <section id="apropos" class="py-20 bg-blue-50">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 mb-10 md:mb-0 md:pr-10" data-aos="fade-right">
                    <h2 class="text-3xl font-bold mb-6 text-blue-900">Notre Mission</h2>
                    <p class="text-gray-700 mb-6">Chez Evotech Africa, nous croyons que la technologie doit être au service du développement durable et de l'innovation sociale en Afrique.</p>
                    <p class="text-gray-700 mb-6">Notre mission est de combiner expertise technique, vision architecturale et communication stratégique pour créer des solutions qui répondent aux défis uniques du continent.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-8">
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center mr-4"><i class="fas fa-leaf text-blue-700"></i></div>
                            <div>
                                <h4 class="font-bold mb-1 text-blue-900">Innovation Durable</h4>
                                <p class="text-sm text-gray-600">Solutions respectueuses de l'environnement</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center mr-4"><i class="fas fa-handshake text-blue-700"></i></div>
                            <div>
                                <h4 class="font-bold mb-1 text-blue-900">Collaboration</h4>
                                <p class="text-sm text-gray-600">Partenariats stratégiques et inclusifs</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center mr-4"><i class="fas fa-lightbulb text-blue-700"></i></div>
                            <div>
                                <h4 class="font-bold mb-1 text-blue-900">Créativité</h4>
                                <p class="text-sm text-gray-600">Approches novatrices pour chaque projet</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center mr-4"><i class="fas fa-chart-line text-blue-700"></i></div>
                            <div>
                                <h4 class="font-bold mb-1 text-blue-900">Excellence</h4>
                                <p class="text-sm text-gray-600">Engagement envers la qualité et les résultats</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/2" data-aos="fade-left">
                    <div class="bg-white rounded-xl shadow-xl overflow-hidden border-2 border-blue-200">
                        <?php if ($hasLogo): ?>
                            <img src="<?= $logoUrl ?>" alt="Mission Evotech Africa" class="w-full h-auto">
                        <?php else: ?>
                            <div class="h-72 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                <i class="fas fa-lightbulb text-6xl text-blue-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="projets" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl font-bold mb-4 text-blue-900">Nos Réalisations</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Découvrez quelques-uns de nos projets phares qui démontrent notre expertise et notre engagement envers l'excellence.</p>
            </div>

            <?php if (!empty($projetsRecents)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($projetsRecents as $index => $projet): ?>
                        <?php
                        $medias = array_values(array_filter(explode('||', (string) ($projet['medias'] ?? ''))));
                        $homeDetailPayload = [
                            'titre' => $projet['titre'] ?? '',
                            'description' => $projet['description'] ?? '',
                            'categorie' => $projet['categorie'] ?? 'Réalisation',
                            'medias' => array_map(static fn ($path) => [
                                'url' => Helpers::url($path),
                                'type' => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg', 'mov'], true) ? 'video' : 'image',
                            ], $medias),
                        ];
                        ?>
                        <div class="bg-white rounded-xl overflow-hidden shadow-lg border border-blue-100 hover:border-blue-300 transition-all duration-300" data-aos="fade-up" data-aos-delay="<?= ($index + 1) * 100 ?>">
                            <div class="relative">
                                <?php if ($medias): ?>
                                    <div class="realisation-carousel" data-realisation-carousel>
                                        <?php foreach ($medias as $mediaIndex => $media): ?>
                                            <?php
                                            $extension = strtolower(pathinfo($media, PATHINFO_EXTENSION));
                                            $isVideo = in_array($extension, ['mp4', 'webm', 'ogg', 'mov'], true);
                                            ?>
                                            <div class="realisation-slide <?= $mediaIndex === 0 ? 'active' : '' ?>">
                                                <?php if ($isVideo): ?>
                                                    <video class="realisation-media" src="<?= Helpers::url($media) ?>" controls muted playsinline></video>
                                                <?php else: ?>
                                                    <img class="realisation-media" src="<?= Helpers::url($media) ?>" alt="<?= Helpers::e($projet['titre'] ?? 'Réalisation') ?>">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($medias) > 1): ?>
                                            <button type="button" class="carousel-button prev" data-carousel-prev aria-label="Média précédent"><i class="fas fa-chevron-left"></i></button>
                                            <button type="button" class="carousel-button next" data-carousel-next aria-label="Média suivant"><i class="fas fa-chevron-right"></i></button>
                                            <div class="carousel-dots">
                                                <?php foreach ($medias as $mediaIndex => $media): ?>
                                                    <span class="carousel-dot <?= $mediaIndex === 0 ? 'active' : '' ?>"></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="realisation-empty">
                                        <i class="fas fa-image text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="absolute top-4 right-4 text-sm font-semibold text-white bg-blue-700 px-3 py-1 rounded-full"><?= Helpers::e($projet['categorie'] ?? 'Réalisation') ?></span>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold mb-2 text-blue-900"><?= Helpers::e($projet['titre'] ?? '') ?></h3>
                                <p class="text-gray-600 mb-4"><?= Helpers::e($projet['description'] ?? '') ?></p>
                                <button type="button" class="home-detail-button" data-home-realisation-detail="<?= Helpers::e(json_encode($homeDetailPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                                    Voir les détails <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-8 text-center text-gray-600">
                    Aucune réalisation publiée pour le moment.
                </div>
            <?php endif; ?>

            <div class="text-center mt-12">
                <a href="#contact" class="btn-primary text-white font-semibold px-6 py-3 rounded-lg shadow-md inline-block">Discuter de votre projet</a>
            </div>
        </div>
    </section>

    <section id="temoignages" class="py-20 bg-blue-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl font-bold mb-4 text-blue-900">Ce que disent nos clients</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">La satisfaction de nos clients est notre priorité. Voici ce qu'ils pensent de notre collaboration.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($temoignages as $index => $temoignage): ?>
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-blue-100" data-aos="fade-up" data-aos-delay="<?= ($index + 1) * 100 ?>">
                        <div class="text-yellow-400 flex mb-4">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-gray-700 italic mb-6">"<?= Helpers::e($temoignage['message'] ?? '') ?>"</p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center mr-4">
                                <span class="text-lg font-bold text-blue-700"><?= Helpers::e(mb_substr($temoignage['nom'] ?? 'EA', 0, 1)) ?></span>
                            </div>
                            <div>
                                <h4 class="font-bold text-blue-900"><?= Helpers::e($temoignage['nom'] ?? '') ?></h4>
                                <p class="text-sm text-gray-600"><?= Helpers::e($temoignage['fonction'] ?? '') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="equipe" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl font-bold mb-4 text-blue-900">Notre Équipe</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Des experts passionnés et talentueux qui travaillent ensemble pour donner vie à vos projets.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($membresEquipe as $index => $membre): ?>
                    <div class="text-center team-card" data-aos="fade-up" data-aos-delay="<?= ($index + 1) * 100 ?>">
                        <div class="relative rounded-xl overflow-hidden mb-4 shadow-lg border-2 border-blue-200">
                            <div class="w-full h-64 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                <i class="fas fa-user-circle text-6xl text-blue-400"></i>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-blue-900 to-transparent opacity-70 flex items-end">
                                <div class="p-4 w-full">
                                    <div class="flex justify-center space-x-3 mb-2 team-social">
                                        <a href="#" class="text-white hover:text-blue-300 transition-colors"><i class="fab fa-linkedin"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-blue-900"><?= Helpers::e($membre['nom'] ?? '') ?></h3>
                        <p class="text-gray-600"><?= Helpers::e($membre['poste'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-12">
                <a href="#contact" class="btn-primary text-white font-semibold px-6 py-3 rounded-lg shadow-md inline-block">Rejoindre l'équipe</a>
            </div>
        </div>
    </section>

    <section id="contact" class="py-20 bg-gradient-to-br from-blue-700 to-blue-500 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-6" data-aos="fade-up">Prêt à transformer votre vision en réalité ?</h2>
            <p class="text-lg mb-8 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">Contactez-nous dès aujourd'hui pour discuter de votre projet et découvrir comment Evotech Africa peut vous aider à atteindre vos objectifs.</p>
            <div class="flex flex-wrap justify-center gap-4" data-aos="fade-up" data-aos-delay="200">
                <a href="mailto:contact@evotech-africa.net" class="bg-white text-blue-700 font-semibold px-8 py-3 rounded-lg shadow-md hover:bg-blue-50 transition-colors">
                    <i class="fas fa-envelope mr-2"></i> Nous écrire
                </a>
                <a href="tel:+243000000000" class="bg-transparent text-white font-semibold px-8 py-3 rounded-lg border-2 border-white hover:bg-white transition-colors hover:text-blue-700">
                    <i class="fas fa-phone mr-2"></i> Nous appeler
                </a>
            </div>
        </div>
    </section>

    <footer class="bg-gradient-footer text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-6">
                        <span class="text-2xl font-bold text-blue-300">Evotech</span>
                        <span class="ml-1 text-gray-300 font-semibold">Africa</span>
                    </div>
                    <p class="text-gray-400 mb-6">Contribuer à l'évolution technologique en Afrique.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-blue-300 transition-colors"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-blue-300 transition-colors"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-blue-300 transition-colors"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-gray-400 hover:text-blue-300 transition-colors"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-6 text-blue-300">Liens Rapides</h3>
                    <ul class="space-y-3">
                        <li><a href="<?= $homeUrl ?>" class="text-gray-400 hover:text-blue-300 transition-colors">Accueil</a></li>
                        <li><a href="#apropos" class="text-gray-400 hover:text-blue-300 transition-colors">À propos</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-blue-300 transition-colors">Services</a></li>
                        <li><a href="#projets" class="text-gray-400 hover:text-blue-300 transition-colors">Projets</a></li>
                        <li><a href="#equipe" class="text-gray-400 hover:text-blue-300 transition-colors">Équipe</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-blue-300 transition-colors">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-6 text-blue-300">Nos Services</h3>
                    <ul class="space-y-3">
                        <li><a href="#services" class="text-gray-400 hover:text-blue-300 transition-colors">Solutions Informatiques</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-blue-300 transition-colors">Architecture & Construction</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-blue-300 transition-colors">Communication Visuelle</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-blue-300 transition-colors">Conseil en Innovation</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-blue-300 transition-colors">Formation Professionnelle</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-6 text-blue-300">Contact</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start"><i class="fas fa-map-marker-alt mt-1 mr-3 text-blue-300"></i><span class="text-gray-400">Av. Ngulo<br>Butembo, RDC</span></li>
                        <li class="flex items-center"><i class="fas fa-phone mr-3 text-blue-300"></i><span class="text-gray-400">+243 000 000 000</span></li>
                        <li class="flex items-center"><i class="fas fa-envelope mr-3 text-blue-300"></i><span class="text-gray-400">contact@evotech-africa.net</span></li>
                        <li class="flex items-center"><i class="fas fa-clock mr-3 text-blue-300"></i><span class="text-gray-400">Lun-Ven: 8h-18h</span></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-blue-800 mt-12 pt-8 text-center">
                <p class="text-gray-500">
                    &copy; <?= date('Y') ?> Evotech Africa. Tous droits réservés.
                    <span class="block sm:inline">Contribuer à l'évolution technologique en Afrique.</span>
                </p>
            </div>
        </div>
    </footer>

    <div id="homeRealisationModal" class="home-modal">
        <div class="home-modal-box">
            <div class="home-modal-head">
                <div>
                    <h3 data-home-detail-title>Réalisation</h3>
                    <p class="text-sm text-gray-500 mt-1" data-home-detail-category></p>
                </div>
                <button type="button" class="home-modal-close" data-home-modal-close aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="home-modal-body">
                <div class="home-modal-medias" data-home-detail-medias></div>
                <div class="home-modal-copy">
                    <p data-home-detail-description></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.AOS) {
                AOS.init({
                    duration: 800,
                    easing: 'ease-in-out',
                    once: true
                });
            }

            document.querySelectorAll('[data-realisation-carousel]').forEach(function(carousel) {
                var slides = Array.prototype.slice.call(carousel.querySelectorAll('.realisation-slide'));
                var dots = Array.prototype.slice.call(carousel.querySelectorAll('.carousel-dot'));
                var current = 0;

                function show(index) {
                    current = (index + slides.length) % slides.length;
                    slides.forEach(function(slide, slideIndex) {
                        slide.classList.toggle('active', slideIndex === current);
                        var video = slide.querySelector('video');
                        if (video && slideIndex !== current) {
                            video.pause();
                        }
                    });
                    dots.forEach(function(dot, dotIndex) {
                        dot.classList.toggle('active', dotIndex === current);
                    });
                }

                var prev = carousel.querySelector('[data-carousel-prev]');
                var next = carousel.querySelector('[data-carousel-next]');

                if (prev) {
                    prev.addEventListener('click', function() {
                        show(current - 1);
                    });
                }

                if (next) {
                    next.addEventListener('click', function() {
                        show(current + 1);
                    });
                }
            });

            var homeModal = document.getElementById('homeRealisationModal');
            document.querySelectorAll('[data-home-realisation-detail]').forEach(function(button) {
                button.addEventListener('click', function() {
                    var data = JSON.parse(button.dataset.homeRealisationDetail || '{}');
                    var medias = Array.isArray(data.medias) ? data.medias : [];
                    var grid = homeModal.querySelector('[data-home-detail-medias]');

                    homeModal.querySelector('[data-home-detail-title]').textContent = data.titre || 'Réalisation';
                    homeModal.querySelector('[data-home-detail-category]').textContent = data.categorie || '';
                    homeModal.querySelector('[data-home-detail-description]').textContent = data.description || 'Aucune description disponible.';
                    grid.innerHTML = '';

                    if (medias.length === 0) {
                        grid.innerHTML = '<div class="realisation-empty"><i class="fas fa-image text-4xl"></i></div>';
                    } else {
                        medias.forEach(function(media) {
                            var wrapper = document.createElement('div');
                            wrapper.innerHTML = media.type === 'video'
                                ? '<video src="' + media.url + '" controls muted playsinline></video>'
                                : '<img src="' + media.url + '" alt="">';
                            grid.appendChild(wrapper.firstChild);
                        });
                    }

                    homeModal.classList.add('active');
                });
            });

            document.querySelectorAll('[data-home-modal-close]').forEach(function(button) {
                button.addEventListener('click', function() {
                    homeModal.classList.remove('active');
                });
            });

            homeModal?.addEventListener('click', function(event) {
                if (event.target === homeModal) {
                    homeModal.classList.remove('active');
                }
            });
        });
    </script>
</body>

</html>
