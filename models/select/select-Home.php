<?php

/**
 * models/select/select-home.php
 * Requêtes pour la page d'accueil Evotech Africa
 */

/**
 * Récupère les services à afficher sur la page d'accueil
 */
function getServicesHome($pdo, $limit = 3)
{
    // Si vous avez une table SERVICES, utilisez-la
    // Sinon, retournez les services par défaut
    $services = [
        [
            'nom' => 'Solutions Informatiques',
            'icone' => 'fa-laptop-code',
            'couleur' => 'blue',
            'description' => 'Développement de logiciels sur mesure, applications web et mobiles, intégration de systèmes et solutions cloud.',
            'lien' => '#services'
        ],
        [
            'nom' => 'Architecture & Construction',
            'icone' => 'fa-building',
            'couleur' => 'green',
            'description' => 'Conception architecturale, planification de projets, supervision de construction et solutions innovantes pour l\'habitat.',
            'lien' => '#services'
        ],
        [
            'nom' => 'Communication Visuelle',
            'icone' => 'fa-bullhorn',
            'couleur' => 'purple',
            'description' => 'Identité visuelle, design graphique, marketing digital, stratégies de communication et campagnes publicitaires.',
            'lien' => '#services'
        ]
    ];

    // Si vous avez une table SERVICES dans la base de données
    /*
    $sql = "SELECT * FROM SERVICES WHERE statut = 0 AND afficher_accueil = 1 
            ORDER BY ordre ASC LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
    */

    return array_slice($services, 0, $limit);
}

/**
 * Récupère les projets récents pour la page d'accueil
 */
function getProjetsHome($pdo, $limit = 3)
{
    $sql = "SELECT 
                p.projet_id,
                p.reference,
                p.titre,
                p.description_projet as description,
                p.etat,
                p.date_creation,
                CASE 
                    WHEN p.etat = 'Terminé' THEN 'green'
                    WHEN p.etat = 'En cours' THEN 'blue'
                    WHEN p.etat = 'Suspendu' THEN 'orange'
                    ELSE 'gray'
                END as couleur,
                p.etat as categorie
            FROM PROJETS p
            WHERE p.statut = 0
            ORDER BY p.date_creation DESC
            LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $projets = $stmt->fetchAll();

    // Ajouter des images par défaut si besoin
    foreach ($projets as &$projet) {
        $projet['image'] = null; // ou une image par défaut
    }

    return $projets;
}

/**
 * Récupère les membres de l'équipe pour la page d'accueil
 */
function getEquipeHome($pdo, $limit = 4)
{
    // Si vous avez une table AGENTS avec des profils
    $sql = "SELECT 
                a.agent_id,
                CONCAT(a.nom, ' ', a.prenom) as nom,
                a.poste,
                a.photo_profil as photo,
                a.linkedin,
                a.twitter
            FROM AGENTS a
            WHERE a.etat = 'Actif' 
            AND a.statut = 0
            AND a.afficher_accueil = 1
            ORDER BY a.ordre ASC
            LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $membres = $stmt->fetchAll();

    // Si aucun membre n'est trouvé, retourner un tableau vide
    return $membres;
}

/**
 * Récupère les témoignages pour la page d'accueil
 */
function getTemoignagesHome($pdo, $limit = 3)
{
    // Si vous avez une table TEMOIGNAGES
    /*
    $sql = "SELECT 
                t.*,
                CONCAT(t.nom, ' ', t.prenom) as nom_complet
            FROM TEMOIGNAGES t
            WHERE t.statut = 0 AND t.approuve = 1
            ORDER BY t.ordre ASC
            LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
    */

    // Témoignages par défaut
    return [
        [
            'nom' => 'Amadou Diallo',
            'fonction' => 'Directeur, FinTech Solutions',
            'message' => 'L\'équipe d\'Evotech Africa a transformé notre vision en réalité avec professionnalisme et créativité. Notre nouveau système informatique a révolutionné notre façon de travailler.',
            'photo' => null
        ],
        [
            'nom' => 'Fatou Ndiaye',
            'fonction' => 'CEO, Africa Grow Initiative',
            'message' => 'La conception architecturale de nos bureaux par Evotech Africa allie esthétique moderne et fonctionnalité. L\'espace reflète parfaitement notre identité d\'entreprise.',
            'photo' => null
        ],
        [
            'nom' => 'Kofi Mensah',
            'fonction' => 'Directeur Marketing, EcoProducts',
            'message' => 'La campagne marketing conçue par Evotech Africa a considérablement augmenté notre visibilité et nos ventes. Leur approche créative et stratégique a dépassé nos attentes.',
            'photo' => null
        ]
    ];
}

/**
 * Récupère les statistiques pour la page d'accueil
 */
function getStatsHome($pdo)
{
    $stats = [
        'projets' => 0,
        'clients' => 0,
        'employes' => 0,
        'partenaires' => 0
    ];

    // Nombre de projets
    $sql = "SELECT COUNT(*) as total FROM PROJETS WHERE statut = 0";
    $stmt = $pdo->query($sql);
    $stats['projets'] = $stmt->fetchColumn();

    // Nombre de clients (partenaires)
    $sql = "SELECT COUNT(*) as total FROM PARTENAIRES WHERE statut = 0";
    $stmt = $pdo->query($sql);
    $stats['clients'] = $stmt->fetchColumn();

    // Nombre d'employés
    $sql = "SELECT COUNT(*) as total FROM AGENTS WHERE etat = 'Actif' AND statut = 0";
    $stmt = $pdo->query($sql);
    $stats['employes'] = $stmt->fetchColumn();

    // Nombre de partenaires (pourrait être une autre table)
    $stats['partenaires'] = 0;

    return $stats;
}

/**
 * Récupère les coordonnées de l'entreprise
 */
function getContactInfo($pdo)
{
    // Si vous avez une table CONFIGURATION
    /*
    $sql = "SELECT * FROM CONFIGURATION WHERE statut = 0";
    $stmt = $pdo->query($sql);
    return $stmt->fetch();
    */

    return [
        'adresse' => 'Av. Ngulo, Butembo, RDC',
        'telephone' => '+243 000 000 000',
        'email' => 'contact@evotech-africa.net',
        'horaires' => 'Lun-Ven: 8h-18h'
    ];
}
