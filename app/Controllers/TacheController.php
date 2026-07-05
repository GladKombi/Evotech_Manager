<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Agent;
use App\Models\Projet;
use App\Models\Tache;

class TacheController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('taches/index', [
            'title' => 'Taches',
            'items' => (new Tache())->allWithDetails(),
            'projets' => (new Projet())->all(),
            'agents' => (new Agent())->all(),
        ]);
    }

    public function show(): void
    {
        Auth::requireAuth();

        $taskId = (int) ($_GET['id'] ?? 0);
        $task = (new Tache())->findWithDetails($taskId);

        if (!$task) {
            Session::flash('error', 'Tâche introuvable');
            Helpers::redirect('taches');
        }

        $this->view('taches/show', [
            'title' => 'Détails de la Tâche',
            'task' => $task,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $model = new Tache();
        $data = $_POST;
        unset($data['_csrf']);

        $model->create($data);
        Session::flash('success', 'Enregistrement effectué avec succès');
        Helpers::redirect('taches');
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new Tache())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('taches');
    }
}
