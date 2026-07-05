<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Compte;
use App\Models\ContratCom;
use App\Models\MouvementCompte;
use App\Models\Projet;

class MouvementCompteController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('mouvements/index', [
            'title' => 'Mouvements',
            'items' => (new MouvementCompte())->allWithDetails(),
            'comptes' => (new Compte())->all(),
            'projets' => (new Projet())->all(),
            'contrats' => (new ContratCom())->allWithDetails(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $model = new MouvementCompte();
        $data = $_POST;
        $redirect = $data['_redirect'] ?? 'mouvements';
        unset($data['_csrf'], $data['_redirect']);

        if (empty($data['cree_par'])) {
            $data['cree_par'] = Session::user()['utilisateur_id'] ?? 1;
        }

        if (empty($data['date_mouvement'])) {
            $data['date_mouvement'] = date('Y-m-d H:i:s');
        }

        $model->create($data);
        $amount = (float) ($data['montant'] ?? 0);
        $signedAmount = ($data['type_mouvement'] ?? '') === 'sortie' ? -$amount : $amount;
        (new Compte())->adjustBalance((int) ($data['compte_id'] ?? 0), $signedAmount);

        Session::flash('success', 'Mouvement enregistré avec succès');
        Helpers::redirect($redirect);
    }

    public function receipt(): void
    {
        Auth::requireAuth();

        $id = (int) ($_GET['id'] ?? 0);
        $operation = null;

        foreach ((new MouvementCompte())->allWithDetails() as $movement) {
            if ((int) ($movement['mouvement_id'] ?? 0) === $id) {
                $operation = $movement;
                break;
            }
        }

        if (!$operation) {
            Session::flash('error', 'Mouvement introuvable');
            Helpers::redirect('mouvements');
        }

        $this->authView('finance/recu', [
            'title' => 'Reçu de mouvement',
            'type' => 'mouvement',
            'operation' => $operation,
        ]);
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new MouvementCompte())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('mouvements');
    }
}
