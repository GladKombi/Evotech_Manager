<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Agent;
use App\Models\Tache;
use App\Models\Timesheet;

class TimesheetController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('timesheets/index', [
            'title' => 'Timesheets',
            'items' => (new Timesheet())->allWithDetails(),
            'tasks' => (new Tache())->allWithDetails(),
            'agents' => (new Agent())->all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $model = new Timesheet();
        $data = $_POST;
        $redirect = $data['_redirect'] ?? 'timesheets';
        unset($data['_csrf'], $data['_redirect']);

        if (empty($data['date_travail'])) {
            $data['date_travail'] = date('Y-m-d');
        }

        if (($data['etat'] ?? '') === 'Soumise' && empty($data['date_soumission'])) {
            $data['date_soumission'] = date('Y-m-d H:i:s');
        }

        $model->create($data);
        Session::flash('success', 'Temps enregistré avec succès');
        Helpers::redirect($redirect);
    }

    public function validate(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? 0);
        $user = Session::user();

        if ($id > 0) {
            (new Timesheet())->update($id, [
                'etat' => 'Validée',
                'valide_par' => $user['utilisateur_id'] ?? null,
                'date_validation' => date('Y-m-d H:i:s'),
            ]);
            Session::flash('success', 'Timesheet validée');
        }

        Helpers::redirect('timesheets');
    }

    public function refuse(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? 0);
        $user = Session::user();

        if ($id > 0) {
            (new Timesheet())->update($id, [
                'etat' => 'Refusée',
                'valide_par' => $user['utilisateur_id'] ?? null,
                'date_validation' => date('Y-m-d H:i:s'),
            ]);
            Session::flash('success', 'Timesheet refusée');
        }

        Helpers::redirect('timesheets');
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new Timesheet())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('timesheets');
    }
}
