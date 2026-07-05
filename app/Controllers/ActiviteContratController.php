<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\ActiviteContrat;
use App\Models\ContratCom;

class ActiviteContratController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('activites-contrat/index', [
            'title' => 'Activites Contrat',
            'items' => (new ActiviteContrat())->allWithDetails(),
            'contracts' => (new ContratCom())->allWithDetails(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $model = new ActiviteContrat();
        $data = $_POST;
        unset($data['_csrf']);

        $model->create($data);
        Session::flash('success', 'Enregistrement effectué avec succès');
        Helpers::redirect('activites-contrat');
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new ActiviteContrat())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('activites-contrat');
    }
}
