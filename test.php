
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evotech Africa - Contribuer à l'évolution technologique en Afrique</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
            max-width: 100%;
        }

        /* Palette Evotech Africa - Couleurs du logo */
        :root {
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

        .bg-service-blue {
            background: linear-gradient(135deg, #e8f0fe, #d4e4f7);
        }

        .border-blue-light {
            border-color: #b0cce8;
        }

        .text-primary-dark {
            color: var(--color-primary);
        }

        .bg-primary-light {
            background-color: #e8f0fe;
        }

        .bg-secondary-light {
            background-color: #d4e4f7;
        }

        .hover\\:bg-primary-light:hover {
            background-color: #e8f0fe;
        }

        .text-secondary {
            color: var(--color-secondary);
        }

        .border-secondary {
            border-color: var(--color-secondary);
        }

        .bg-gradient-footer {
            background: linear-gradient(135deg, #0a1a3a, #1a3a6a);
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Navigation -->
    <header x-data="{ open: false }" class="bg-white shadow-md fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <a href="index.php" class="flex items-center">
                    <span class="text-2xl font-bold gradient-text">Evotech</span>
                    <span class="ml-1 text-gray-700 font-semibold">Africa</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex space-x-8 items-center">
                <a href="index.php" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Accueil</a>
                <a href="#services" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Services</a>
                <a href="#projets" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Projets</a>
                <a href="#equipe" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Équipe</a>
                <a href="#apropos" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">À propos</a>
                <a href="#contact" class="nav-link text-gray-800 font-medium hover:text-blue-700 transition-colors">Contact</a>
                <a href="<?php echo $loginLink; ?>" class="px-4 py-2 rounded-lg <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true ? 'btn-login-dashboard' : 'btn-login'; ?> font-semibold text-sm transition-colors">
                    <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true ? 'Tableau de Bord' : 'Se connecter'; ?>
                </a>
            </nav>

            <!-- Mobile Navigation Button -->
            <button @click="open = !open" class="md:hidden focus:outline-none">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation Menu -->
        <div x-show="open" @click.away="open = false" class="md:hidden bg-white shadow-lg absolute w-full" x-cloak>
            <div class="container mx-auto px-4 py-3 flex flex-col space-y-3">
                <a href="index.php" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Accueil</a>
                <a href="#services" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Services</a>
                <a href="#projets" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Projets</a>
                <a href="#equipe" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Équipe</a>
                <a href="#apropos" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">À propos</a>
                <a href="#contact" class="text-gray-800 font-medium py-2 hover:text-blue-700 transition-colors">Contact</a>
                <a href="<?php echo $loginLink; ?>" class="btn-primary text-white font-semibold px-4 py-2 rounded-lg text-center">
                    <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true ? 'Tableau de Bord' : 'Se connecter'; ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
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
                    <a href="#contact" class="btn-primary text-white font-semibold px-6 py-3 rounded-lg shadow-md">
                        Nous contacter
                    </a>
                    <a href="#projets" class="bg-white text-gray-800 font-semibold px-6 py-3 rounded-lg shadow-md border border-blue-200 hover:bg-blue-50 transition-colors">
                        Voir nos projets
                    </a>
                </div>
            </div>
            <div class="md:w-1/2" data-aos="fade-left">
                <div class="bg-white rounded-xl shadow-2xl overflow-hidden border-2 border-blue-200">
                    <img src="uploads/logo.jpg" alt="Evotech Africa - Contribuer à l'évolution technologique en Afrique" class="w-full h-auto">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-white border-b border-blue-100">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-number"><?php echo $nbProjets; ?></div>
                    <p class="text-gray-600 font-medium">Projets Réalisés</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-number"><?php echo $nbClients; ?></div>
                    <p class="text-gray-600 font-medium">Clients Satisfaits</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-number"><?php echo $nbEmployes; ?></div>
                    <p class="text-gray-600 font-medium">Experts</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-number"><?php echo $nbPartenaires; ?></div>
                    <p class="text-gray-600 font-medium">Partenaires</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl font-bold mb-4">Nos Services</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Un éventail de solutions pour répondre à vos besoins en technologie, architecture et communication.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $index => $service): ?>
                        <div class="service-card bg-white rounded-xl shadow-lg p-6 border border-blue-100 hover:border-blue-300"
                            data-aos="zoom-in" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                            <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-5">
                                <i class="fas <?php echo $service['icone'] ?? 'fa-laptop-code'; ?> text-2xl text-blue-700"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-blue-900"><?php echo htmlspecialchars($service['nom']); ?></h3>
                            <p class="text-gray-600 mb-4">
                                <?php echo htmlspecialchars($service['description']); ?>
                            </p>
                            <?php if (!empty($service['lien'])): ?>
                                <a href="<?php echo htmlspecialchars($service['lien']); ?>" class="text-blue-600 font-medium flex items-center hover:text-blue-800 transition-colors">
                                    En savoir plus
                                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Services par défaut -->
                    <div class="service-card bg-white rounded-xl shadow-lg p-6 border border-blue-100 hover:border-blue-300" data-aos="zoom-in" data-aos-delay="100">
                        <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-laptop-code text-2xl text-blue-700"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-blue-900">Solutions Informatiques</h3>
                        <p class="text-gray-600 mb-4">Développement de logiciels sur mesure, applications web et mobiles, intégration de systèmes.</p>
                    </div>
                    <div class="service-card bg-white rounded-xl shadow-lg p-6 border border-blue-100 hover:border-blue-300" data-aos="zoom-in" data-aos-delay="200">
                        <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-building text-2xl text-blue-700"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-blue-900">Architecture & Construction</h3>
                        <p class="text-gray-600 mb-4">Conception architecturale, planification de projets, supervision de construction.</p>
                    </div>
                    <div class="service-card bg-white rounded-xl shadow-lg p-6 border border-blue-100 hover:border-blue-300" data-aos="zoom-in" data-aos-delay="300">
                        <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-bullhorn text-2xl text-blue-700"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-blue-900">Communication Visuelle</h3>
                        <p class="text-gray-600 mb-4">Identité visuelle, design graphique, marketing digital, stratégies de communication.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-12">
                <a href="#contact" class="btn-primary text-white font-semibold px-6 py-3 rounded-lg shadow-md inline-block">
                    Demander un devis
                </a>
            </div>
        </div>
    </section>

    <!-- À propos section -->
    <section id="apropos" class="py-20 bg-blue-50">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 mb-10 md:mb-0 md:pr-10" data-aos="fade-right">
                    <h2 class="text-3xl font-bold mb-6 text-blue-900">Notre Mission</h2>
                    <p class="text-gray-700 mb-6">
                        Chez Evotech Africa, nous croyons que la technologie doit être au service du développement durable et de l'innovation sociale en Afrique.
                    </p>
                    <p class="text-gray-700 mb-6">
                        Notre mission est de combiner expertise technique, vision architecturale et communication stratégique pour créer des solutions qui répondent aux défis uniques du continent.
                    </p>
                    <div class="grid grid-cols-2 gap-6 mt-8">
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-leaf text-blue-700"></i>
                            </div>
                            <div>
                                <h4 class="font-bold mb-1 text-blue-900">Innovation Durable</h4>
                                <p class="text-sm text-gray-600">Solutions respectueuses de l'environnement</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-handshake text-blue-700"></i>
                            </div>
                            <div>
                                <h4 class="font-bold mb-1 text-blue-900">Collaboration</h4>
                                <p class="text-sm text-gray-600">Partenariats stratégiques et inclusifs</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-lightbulb text-blue-700"></i>
                            </div>
                            <div>
                                <h4 class="font-bold mb-1 text-blue-900">Créativité</h4>
                                <p class="text-sm text-gray-600">Approches novatrices pour chaque projet</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-chart-line text-blue-700"></i>
                            </div>
                            <div>
                                <h4 class="font-bold mb-1 text-blue-900">Excellence</h4>
                                <p class="text-sm text-gray-600">Engagement envers la qualité et les résultats</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/2" data-aos="fade-left">
                    <div class="bg-white rounded-xl shadow-xl overflow-hidden border-2 border-blue-200">
                        <img src="uploads/logo.jpg" alt="Mission Evotech Africa" class="w-full h-auto">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Projects Preview Section -->
    <section id="projets" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl font-bold mb-4 text-blue-900">Nos Réalisations</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Découvrez quelques-uns de nos projets phares qui démontrent notre expertise et notre engagement envers l'excellence.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (!empty($projetsRecents)): ?>
                    <?php foreach ($projetsRecents as $index => $projet): ?>
                        <div class="bg-white rounded-xl overflow-hidden shadow-lg border border-blue-100 hover:border-blue-300 transition-all duration-300"
                            data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                            <div class="relative">
                                <?php if (!empty($projet['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($projet['image']); ?>" alt="<?php echo htmlspecialchars($projet['titre']); ?>" class="w-full h-56 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-56 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                        <i class="fas fa-project-diagram text-4xl text-blue-400"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="absolute top-4 right-4 text-sm font-semibold text-white bg-blue-700 px-3 py-1 rounded-full">
                                    <?php echo htmlspecialchars($projet['categorie'] ?? 'Projet'); ?>
                                </span>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold mb-2 text-blue-900"><?php echo htmlspecialchars($projet['titre']); ?></h3>
                                <p class="text-gray-600 mb-4">
                                    <?php echo htmlspecialchars($projet['description']); ?>
                                </p>
                                <?php if (!empty($projet['lien'])): ?>
                                    <a href="<?php echo htmlspecialchars($projet['lien']); ?>" class="text-blue-600 font-medium flex items-center hover:text-blue-800 transition-colors">
                                        Voir le projet
                                        <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Projets par défaut -->
                    <div class="bg-white rounded-xl overflow-hidden shadow-lg border border-blue-100 hover:border-blue-300 transition-all duration-300" data-aos="fade-up" data-aos-delay="100">
                        <div class="relative">
                            <div class="w-full h-56 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                <i class="fas fa-code text-4xl text-blue-400"></i>
                            </div>
                            <span class="absolute top-4 right-4 text-sm font-semibold text-white bg-blue-700 px-3 py-1 rounded-full">Technologie</span>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2 text-blue-900">Tech Hub Innovation Center</h3>
                            <p class="text-gray-600 mb-4">Création d'un espace collaboratif intelligent pour les startups technologiques.</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl overflow-hidden shadow-lg border border-blue-100 hover:border-blue-300 transition-all duration-300" data-aos="fade-up" data-aos-delay="200">
                        <div class="relative">
                            <div class="w-full h-56 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                <i class="fas fa-building text-4xl text-blue-400"></i>
                            </div>
                            <span class="absolute top-4 right-4 text-sm font-semibold text-white bg-blue-700 px-3 py-1 rounded-full">Architecture</span>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2 text-blue-900">Éco-Village Durable</h3>
                            <p class="text-gray-600 mb-4">Conception d'un complexe résidentiel écologique intégrant des technologies vertes.</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl overflow-hidden shadow-lg border border-blue-100 hover:border-blue-300 transition-all duration-300" data-aos="fade-up" data-aos-delay="300">
                        <div class="relative">
                            <div class="w-full h-56 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                <i class="fas fa-bullhorn text-4xl text-blue-400"></i>
                            </div>
                            <span class="absolute top-4 right-4 text-sm font-semibold text-white bg-blue-700 px-3 py-1 rounded-full">Communication</span>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2 text-blue-900">Campagne Digitale Nationale</h3>
                            <p class="text-gray-600 mb-4">Stratégie marketing multicanal pour la promotion de produits locaux.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-12">
                <a href="#contact" class="btn-primary text-white font-semibold px-6 py-3 rounded-lg shadow-md inline-block">
                    Discuter de votre projet
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="temoignages" class="py-20 bg-blue-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl font-bold mb-4 text-blue-900">Ce que disent nos clients</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    La satisfaction de nos clients est notre priorité. Voici ce qu'ils pensent de notre collaboration.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (!empty($temoignages)): ?>
                    <?php foreach ($temoignages as $index => $temoignage): ?>
                        <div class="bg-white rounded-xl p-6 shadow-lg border border-blue-100" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                            <div class="flex items-center mb-4">
                                <div class="text-yellow-400 flex">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-gray-700 italic mb-6">
                                "<?php echo htmlspecialchars($temoignage['message']); ?>"
                            </p>
                            <div class="flex items-center">
                                <?php if (!empty($temoignage['photo'])): ?>
                                    <div class="w-12 h-12 rounded-full overflow-hidden mr-4 border-2 border-blue-300">
                                        <img src="<?php echo htmlspecialchars($temoignage['photo']); ?>" alt="<?php echo htmlspecialchars($temoignage['nom']); ?>" class="w-full h-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center mr-4">
                                        <span class="text-lg font-bold text-blue-700"><?php echo substr($temoignage['nom'], 0, 1); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-bold text-blue-900"><?php echo htmlspecialchars($temoignage['nom']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($temoignage['fonction']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Témoignages par défaut -->
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-blue-100" data-aos="fade-up" data-aos-delay="100">
                        <div class="flex items-center mb-4">
                            <div class="text-yellow-400 flex">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-700 italic mb-6">"L'équipe d'Evotech Africa a transformé notre vision en réalité avec professionnalisme et créativité."</p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center mr-4">
                                <span class="text-lg font-bold text-blue-700">AD</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-blue-900">Amadou Diallo</h4>
                                <p class="text-sm text-gray-600">Directeur, FinTech Solutions</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-blue-100" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-center mb-4">
                            <div class="text-yellow-400 flex">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="text-gray-700 italic mb-6">"La conception architecturale de nos bureaux par Evotech Africa allie esthétique moderne et fonctionnalité."</p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center mr-4">
                                <span class="text-lg font-bold text-blue-700">FN</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-blue-900">Fatou Ndiaye</h4>
                                <p class="text-sm text-gray-600">CEO, Africa Grow Initiative</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-blue-100" data-aos="fade-up" data-aos-delay="300">
                        <div class="flex items-center mb-4">
                            <div class="text-yellow-400 flex">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                        <p class="text-gray-700 italic mb-6">"La campagne marketing conçue par Evotech Africa a considérablement augmenté notre visibilité et nos ventes."</p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center mr-4">
                                <span class="text-lg font-bold text-blue-700">KM</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-blue-900">Kofi Mensah</h4>
                                <p class="text-sm text-gray-600">Directeur Marketing, EcoProducts</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Team Preview -->
    <section id="equipe" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl font-bold mb-4 text-blue-900">Notre Équipe</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Des experts passionnés et talentueux qui travaillent ensemble pour donner vie à vos projets.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php if (!empty($membresEquipe)): ?>
                    <?php foreach ($membresEquipe as $index => $membre): ?>
                        <div class="text-center team-card" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                            <div class="relative rounded-xl overflow-hidden mb-4 shadow-lg border-2 border-blue-200">
                                <?php if (!empty($membre['photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($membre['photo']); ?>" alt="<?php echo htmlspecialchars($membre['nom']); ?>" class="w-full h-64 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-64 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                        <i class="fas fa-user-circle text-6xl text-blue-400"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute inset-0 bg-gradient-to-t from-blue-900/60 to-transparent flex items-end">
                                    <div class="p-4 w-full">
                                        <div class="flex justify-center space-x-3 mb-2 team-social">
                                            <?php if (!empty($membre['linkedin'])): ?>
                                                <a href="<?php echo htmlspecialchars($membre['linkedin']); ?>" target="_blank" class="text-white hover:text-blue-300 transition-colors">
                                                    <i class="fab fa-linkedin"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($membre['twitter'])): ?>
                                                <a href="<?php echo htmlspecialchars($membre['twitter']); ?>" target="_blank" class="text-white hover:text-blue-300 transition-colors">
                                                    <i class="fab fa-twitter"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-blue-900"><?php echo htmlspecialchars($membre['nom']); ?></h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($membre['poste']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Équipe par défaut -->
                    <div class="text-center team-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="relative rounded-xl overflow-hidden mb-4 shadow-lg border-2 border-blue-200">
                            <div class="w-full h-64 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                <i class="fas fa-user-circle text-6xl text-blue-400"></i>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-blue-900/60 to-transparent flex items-end">
                                <div class="p-4 w-full">
                                    <div class="flex justify-center space-x-3 mb-2 team-social">
                                        <a href="#" class="text-white hover:text-blue-300 transition-colors"><i class="fab fa-linkedin"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-blue-900">Olivier Nkomo</h3>
                        <p class="text-gray-600">CEO & Fondateur</p>
                    </div>
                    <div class="text-center team-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="relative rounded-xl overflow-hidden mb-4 shadow-lg border-2 border-blue-200">
                            <div class="w-full h-64 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                <i class="fas fa-user-circle text-6xl text-blue-400"></i>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-blue-900/60 to-transparent flex items-end">
                                <div class="p-4 w-full">
                                    <div class="flex justify-center space-x-3 mb-2 team-social">
                                        <a href="#" class="text-white hover:text-blue-300 transition-colors"><i class="fab fa-linkedin"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-blue-900">Amina Touré</h3>
                        <p class="text-gray-600">Directrice Technique</p>
                    </div>
                    <div class="text-center team-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="relative rounded-xl overflow-hidden mb-4 shadow-lg border-2 border-blue-200">
                            <div class="w-full h-64 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                <i class="fas fa-user-circle text-6xl text-blue-400"></i>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-blue-900/60 to-transparent flex items-end">
                                <div class="p-4 w-full">
                                    <div class="flex justify-center space-x-3 mb-2 team-social">
                                        <a href="#" class="text-white hover:text-blue-300 transition-colors"><i class="fab fa-linkedin"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-blue-900">Jean-Marc Koné</h3>
                        <p class="text-gray-600">Directeur Architecture</p>
                    </div>
                    <div class="text-center team-card" data-aos="fade-up" data-aos-delay="400">
                        <div class="relative rounded-xl overflow-hidden mb-4 shadow-lg border-2 border-blue-200">
                            <div class="w-full h-64 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                <i class="fas fa-user-circle text-6xl text-blue-400"></i>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-blue-900/60 to-transparent flex items-end">
                                <div class="p-4 w-full">
                                    <div class="flex justify-center space-x-3 mb-2 team-social">
                                        <a href="#" class="text-white hover:text-blue-300 transition-colors"><i class="fab fa-linkedin"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-blue-900">Aïcha Diop</h3>
                        <p class="text-gray-600">Directrice Marketing</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-12">
                <a href="#contact" class="btn-primary text-white font-semibold px-6 py-3 rounded-lg shadow-md inline-block">
                    Rejoindre l'équipe
                </a>
            </div>
        </div>
    </section>

    <!-- Contact / CTA Section -->
    <section id="contact" class="py-20 bg-gradient-to-br from-blue-700 to-blue-500 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-6" data-aos="fade-up">Prêt à transformer votre vision en réalité ?</h2>
            <p class="text-lg mb-8 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">
                Contactez-nous dès aujourd'hui pour discuter de votre projet et découvrir comment Evotech Africa peut vous aider à atteindre vos objectifs.
            </p>
            <div class="flex flex-wrap justify-center gap-4" data-aos="fade-up" data-aos-delay="200">
                <a href="mailto:contact@evotech-africa.net" class="bg-white text-blue-700 font-semibold px-8 py-3 rounded-lg shadow-md hover:bg-blue-50 transition-colors">
                    <i class="fas fa-envelope mr-2"></i> Nous écrire
                </a>
                <a href="tel:+243000000000" class="bg-transparent text-white font-semibold px-8 py-3 rounded-lg border-2 border-white hover:bg-white/10 transition-colors">
                    <i class="fas fa-phone mr-2"></i> Nous appeler
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-footer text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center mb-6">
                        <span class="text-2xl font-bold text-blue-300">Evotech</span>
                        <span class="ml-1 text-gray-300 font-semibold">Africa</span>
                    </div>
                    <p class="text-gray-400 mb-6">
                        Contribuer à l'évolution technologique en Afrique.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-blue-300 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-300 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-300 transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-300 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-bold mb-6 text-blue-300">Liens Rapides</h3>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="text-gray-400 hover:text-blue-300 transition-colors">Accueil</a></li>
                        <li><a href="#apropos" class="text-gray-400 hover:text-blue-300 transition-colors">À propos</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-blue-300 transition-colors">Services</a></li>
                        <li><a href="#projets" class="text-gray-400 hover:text-blue-300 transition-colors">Projets</a></li>
                        <li><a href="#equipe" class="text-gray-400 hover:text-blue-300 transition-colors">Équipe</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-blue-300 transition-colors">Contact</a></li>
                    </ul>
                </div>

                <!-- Services -->
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

                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-bold mb-6 text-blue-300">Contact</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-blue-300"></i>
                            <span class="text-gray-400">Av. Ngulo<br>Butembo, RDC</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-3 text-blue-300"></i>
                            <span class="text-gray-400">+243 000 000 000</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-blue-300"></i>
                            <span class="text-gray-400">contact@evotech-africa.net</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock mr-3 text-blue-300"></i>
                            <span class="text-gray-400">Lun-Ven: 8h-18h</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-blue-800 mt-12 pt-8 text-center">
                <p class="text-gray-500">
                    &copy; <?php echo date('Y'); ?> Evotech Africa. Tous droits réservés.
                    <span class="block sm:inline">Contribuer à l'évolution technologique en Afrique.</span>
                </p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
        });
    </script>
</body>

</html>