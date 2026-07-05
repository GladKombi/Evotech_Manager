<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\ActiviteContrat;
use App\Models\Agent;
use App\Models\ContratCom;
use App\Models\Partenaire;

class ContratComController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('contrats-com/index', [
            'title' => 'Contrats Com',
            'items' => (new ContratCom())->allWithDetails(),
            'partenaires' => (new Partenaire())->all(),
            'agents' => (new Agent())->all(),
        ]);
    }

    public function show(): void
    {
        Auth::requireAuth();

        $contractId = (int) ($_GET['id'] ?? 0);
        $contract = (new ContratCom())->findWithDetails($contractId);

        if (!$contract) {
            Session::flash('error', 'Contrat introuvable');
            Helpers::redirect('contrats-com');
        }

        $this->view('contrats-com/show', [
            'title' => 'Détails du Contrat',
            'contract' => $contract,
            'activities' => (new ActiviteContrat())->byContract($contractId),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $model = new ContratCom();
        $data = $_POST;
        unset($data['_csrf']);

        $model->create($data);
        Session::flash('success', 'Enregistrement effectué avec succès');
        Helpers::redirect('contrats-com');
    }

    public function addActivity(): void
    {
        Auth::requireAuth();

        $contractId = (int) ($_POST['contrat_id'] ?? 0);
        $data = $_POST;
        unset($data['_csrf']);

        if ($contractId <= 0) {
            Session::flash('error', 'Contrat invalide');
            Helpers::redirect('contrats-com');
        }

        (new ActiviteContrat())->create($data);
        Session::flash('success', 'Activité ajoutée au contrat');
        Helpers::redirect('contrats-com/show?id=' . $contractId);
    }

    public function removeActivity(): void
    {
        Auth::requireAuth();

        $contractId = (int) ($_POST['contrat_id'] ?? 0);
        $activityId = (int) ($_POST['activite_id'] ?? 0);

        if ($activityId > 0) {
            (new ActiviteContrat())->softDelete($activityId);
            Session::flash('success', 'Activité retirée');
        }

        Helpers::redirect('contrats-com/show?id=' . $contractId);
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new ContratCom())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('contrats-com');
    }
}
