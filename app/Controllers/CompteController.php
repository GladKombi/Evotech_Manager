<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Agent;
use App\Models\Compte;
use App\Models\MouvementCompte;

class CompteController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('comptes/index', [
            'title' => 'Comptes',
            'items' => (new Compte())->allWithResponsable(),
            'agents' => (new Agent())->all(),
        ]);
    }

    public function show(): void
    {
        Auth::requireAuth();

        $id = (int) ($_GET['id'] ?? 0);
        $account = (new Compte())->find($id);

        if (!$account) {
            Session::flash('error', 'Compte introuvable');
            Helpers::redirect('comptes');
        }

        $movements = array_values(array_filter((new MouvementCompte())->allWithDetails(), static function ($movement) use ($id) {
            return (int) ($movement['compte_id'] ?? 0) === $id;
        }));

        $this->view('comptes/show', [
            'title' => 'Détails du Compte',
            'account' => $account,
            'movements' => $movements,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $model = new Compte();
        $data = $_POST;
        unset($data['_csrf']);

        if (($data['solde_actuel'] ?? '') === '') {
            $data['solde_actuel'] = $data['solde_initial'] ?? 0;
        }

        $model->create($data);
        Session::flash('success', 'Enregistrement effectué avec succès');
        Helpers::redirect('comptes');
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new Compte())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('comptes');
    }
}
