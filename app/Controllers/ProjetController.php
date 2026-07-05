<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Agent;
use App\Models\Participation;
use App\Models\Partenaire;
use App\Models\Projet;
use App\Models\Tache;

class ProjetController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $model = new Projet();
        $items = $model->all();
        $participationModel = new Participation();
        $projectParticipants = [];

        foreach ($items as $project) {
            $projectId = (int) ($project['projet_id'] ?? 0);
            $projectParticipants[$projectId] = $projectId > 0 ? $participationModel->byProject($projectId) : [];
        }

        $this->view('projets/index', [
            'title' => 'Projets',
            'items' => $items,
            'agents' => (new Agent())->all(),
            'partenaires' => (new Partenaire())->all(),
            'projectParticipants' => $projectParticipants,
        ]);
    }

    public function show(): void
    {
        Auth::requireAuth();

        $projectId = (int) ($_GET['id'] ?? 0);
        $project = (new Projet())->find($projectId);

        if (!$project) {
            Session::flash('error', 'Projet introuvable');
            Helpers::redirect('projets');
        }

        $participationModel = new Participation();
        $participants = $participationModel->byProject($projectId);
        $participantIds = array_map(static fn ($participant) => (int) $participant['agent_id'], $participants);
        $availableAgents = array_values(array_filter((new Agent())->all(), static function ($agent) use ($participantIds) {
            return !in_array((int) ($agent['agent_id'] ?? 0), $participantIds, true);
        }));

        $this->view('projets/show', [
            'title' => 'Détails du Projet',
            'project' => $project,
            'participants' => $participants,
            'availableAgents' => $availableAgents,
            'tasks' => (new Tache())->byProject($projectId),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $model = new Projet();
        $data = $_POST;
        unset($data['_csrf']);

        $model->create($data);
        Session::flash('success', 'Enregistrement effectué avec succès');
        Helpers::redirect('projets');
    }

    public function addParticipation(): void
    {
        Auth::requireAuth();

        $projectId = (int) ($_POST['projet_id'] ?? 0);
        $agentId = (int) ($_POST['agent_id'] ?? 0);

        if ($projectId <= 0 || $agentId <= 0) {
            Session::flash('error', 'Agent ou projet invalide');
            Helpers::redirect('projets/show?id=' . $projectId);
        }

        $participationModel = new Participation();
        if ($participationModel->agentAlreadyAssigned($projectId, $agentId)) {
            Session::flash('error', 'Cet agent participe déjà à ce projet');
            Helpers::redirect('projets/show?id=' . $projectId);
        }

        $participationModel->create([
            'projet_id' => $projectId,
            'agent_id' => $agentId,
        ]);

        Session::flash('success', 'Agent ajouté au projet');
        Helpers::redirect('projets/show?id=' . $projectId);
    }

    public function removeParticipation(): void
    {
        Auth::requireAuth();

        $projectId = (int) ($_POST['projet_id'] ?? 0);
        $participationId = (int) ($_POST['participation_id'] ?? 0);

        if ($participationId > 0) {
            (new Participation())->softDelete($participationId);
            Session::flash('success', 'Participation retirée');
        }

        Helpers::redirect('projets/show?id=' . $projectId);
    }

    public function addTask(): void
    {
        Auth::requireAuth();

        $projectId = (int) ($_POST['projet_id'] ?? 0);
        $agentId = (int) ($_POST['assigne_a'] ?? 0);
        $data = $_POST;
        unset($data['_csrf']);

        if ($projectId <= 0) {
            Session::flash('error', 'Projet invalide');
            Helpers::redirect('projets');
        }

        if ($agentId <= 0 || !(new Participation())->agentAlreadyAssigned($projectId, $agentId)) {
            Session::flash('error', 'La tâche doit être assignée à un participant du projet');
            Helpers::redirect('projets/show?id=' . $projectId);
        }

        (new Tache())->create($data);
        Session::flash('success', 'Tâche ajoutée au projet');
        Helpers::redirect('projets/show?id=' . $projectId);
    }

    public function removeTask(): void
    {
        Auth::requireAuth();

        $projectId = (int) ($_POST['projet_id'] ?? 0);
        $taskId = (int) ($_POST['tache_id'] ?? 0);

        if ($taskId > 0) {
            (new Tache())->softDelete($taskId);
            Session::flash('success', 'Tâche retirée');
        }

        Helpers::redirect('projets/show?id=' . $projectId);
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new Projet())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('projets');
    }
}
