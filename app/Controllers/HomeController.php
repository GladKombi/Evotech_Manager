<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Realisation;

class HomeController extends Controller
{
    public function index(): void
    {
        $stats = [
            'projets' => 0,
            'clients' => 0,
            'agents' => 0,
            'partenaires' => 0,
        ];

        try {
            $db = Database::conn();
            $tables = [
                'projets' => 'PROJETS',
                'agents' => 'AGENTS',
                'partenaires' => 'PARTENAIRES',
            ];

            foreach ($tables as $key => $table) {
                $stats[$key] = (int) $db->query("SELECT COUNT(*) FROM $table WHERE statut=0")->fetchColumn();
            }

            $stats['clients'] = $stats['partenaires'];
        } catch (\Throwable $e) {
            error_log('Erreur chargement accueil: ' . $e->getMessage());
        }

        $services = [
            [
                'nom' => 'Solutions Informatiques',
                'icone' => 'fa-laptop-code',
                'description' => 'Applications web, logiciels de gestion, automatisation et integrations adaptees aux realites de votre organisation.',
            ],
            [
                'nom' => 'Architecture & Construction',
                'icone' => 'fa-building',
                'description' => 'Conception, planification, suivi technique et pilotage de projets de construction avec une approche structuree.',
            ],
            [
                'nom' => 'Communication Visuelle',
                'icone' => 'fa-bullhorn',
                'description' => 'Identite visuelle, supports graphiques, strategie digitale et contenus pour rendre votre marque plus lisible.',
            ],
        ];

        $projets = [];

        try {
            $projets = (new Realisation())->publishedForHome(6);
        } catch (\Throwable $e) {
            error_log('Erreur chargement réalisations accueil: ' . $e->getMessage());
        }

        $temoignages = [
            ['nom' => 'Amadou Diallo', 'fonction' => 'Directeur, FinTech Solutions', 'message' => "L'equipe d'Evotech Africa a transforme notre vision en realite avec professionnalisme et creativite."],
            ['nom' => 'Fatou Ndiaye', 'fonction' => 'CEO, Africa Grow Initiative', 'message' => 'La conception architecturale de nos bureaux allie esthetique moderne et fonctionnalite.'],
            ['nom' => 'Kofi Mensah', 'fonction' => 'Directeur Marketing, EcoProducts', 'message' => 'La campagne marketing concue par Evotech Africa a considerablement augmente notre visibilite.'],
        ];

        $membresEquipe = [
            ['nom' => 'Olivier Nkomo', 'poste' => 'CEO & Fondateur'],
            ['nom' => 'Amina Toure', 'poste' => 'Directrice Technique'],
            ['nom' => 'Jean-Marc Kone', 'poste' => 'Directeur Architecture'],
            ['nom' => 'Aicha Diop', 'poste' => 'Directrice Marketing'],
        ];

        $this->authView('home/index', [
            'title' => "Evotech Africa - Contribuer a l'evolution technologique en Afrique",
            'stats' => $stats,
            'services' => $services,
            'projets' => $projets,
            'projetsRecents' => $projets,
            'temoignages' => $temoignages,
            'membresEquipe' => $membresEquipe,
            'nbProjets' => $stats['projets'],
            'nbClients' => $stats['clients'],
            'nbEmployes' => $stats['agents'],
            'nbPartenaires' => $stats['partenaires'],
            'isLoggedIn' => Session::user() !== null,
        ]);
    }
}
