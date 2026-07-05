<?php

use App\Core\Helpers;

$agents = $agents ?? [];
$partenaires = $partenaires ?? [];
$projectParticipants = $projectParticipants ?? [];
?>
<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Gestion du module Projets</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>reference</th>
                <th>titre</th>
                <th>etat</th>
                <th>priorite</th>
                <th>budget_estime</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr data-record-id="<?= (int) ($item['projet_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'reference' => $item['reference'] ?? '',
                        'partenaire_id' => $item['partenaire_id'] ?? '',
                        'titre' => $item['titre'] ?? '',
                        'description_projet' => $item['description_projet'] ?? '',
                        'chef_projet_id' => $item['chef_projet_id'] ?? '',
                        'date_debut' => $item['date_debut'] ?? '',
                        'date_fin_prevue' => $item['date_fin_prevue'] ?? '',
                        'date_fin_reelle' => $item['date_fin_reelle'] ?? '',
                        'budget_estime' => $item['budget_estime'] ?? '',
                        'etat' => $item['etat'] ?? '',
                        'priorite' => $item['priorite'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td><?= Helpers::e($item['reference'] ?? '') ?></td>
                    <td><?= Helpers::e($item['titre'] ?? '') ?></td>
                    <td><?= Helpers::e($item['etat'] ?? '') ?></td>
                    <td><?= Helpers::e($item['priorite'] ?? '') ?></td>
                    <td><?= Helpers::e($item['budget_estime'] ?? '') ?></td>
                    <td class="table-actions">
                        <button type="button" class="action-btn task project-task-button" data-project-task-button data-project-id="<?= (int) ($item['projet_id'] ?? 0) ?>" data-project-title="<?= Helpers::e($item['titre'] ?? 'Projet') ?>" title="Assigner une tâche" aria-label="Assigner une tâche au projet">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Tâche</span>
                        </button>
                        <a class="action-btn view" href="<?= Helpers::url('projets/show?id=' . (int) ($item['projet_id'] ?? 0)) ?>" title="Voir les détails" aria-label="Voir les détails du projet">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <form method="post" action="<?= Helpers::url('projets/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['projet_id'] ?? 0) ?>">
                            <button class="btn-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalForm" class="modal">
    <div class="modal-box">
        <h2>Ajouter</h2>
        <form method="post" action="<?= Helpers::url('projets/store') ?>">
            <label>reference<input name="reference" class="form-control" required></label>
            <label>partenaire_id<select name="partenaire_id" class="form-control">
                    <option value="">Sélectionner un partenaire</option>
                    <?php foreach ($partenaires as $partenaire): ?>
                        <option value="<?= (int) ($partenaire['partenaire_id'] ?? 0) ?>">
                            <?= Helpers::e($partenaire['nom'] ?? 'Partenaire') ?>
                        </option>
                    <?php endforeach; ?>
                </select></label>
            <label>titre<input name="titre" class="form-control" required></label>
            <label>description_projet<textarea name="description_projet" class="form-control"></textarea></label>
            <label>chef_projet_id<select name="chef_projet_id" class="form-control">
                    <option value="">Sélectionner un chef de projet</option>
                    <?php foreach ($agents as $agent): ?>
                        <?php $agentName = trim(($agent['nom'] ?? '') . ' ' . ($agent['prenom'] ?? '')); ?>
                        <option value="<?= (int) ($agent['agent_id'] ?? 0) ?>">
                            <?= Helpers::e($agentName ?: 'Agent #' . ($agent['agent_id'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select></label>
            <label>date_debut<input name="date_debut" type="date" class="form-control"></label>
            <label>date_fin_prevue<input name="date_fin_prevue" type="date" class="form-control"></label>
            <label>date_fin_reelle<input name="date_fin_reelle" type="date" class="form-control"></label>
            <label>budget_estime<input name="budget_estime" type="number" step="0.01" class="form-control"></label>
            <label>etat<select name="etat" class="form-control"><option value="En cours">En cours</option><option value="Suspendu">Suspendu</option><option value="Terminé">Terminé</option><option value="Annulé">Annulé</option></select></label>
            <label>priorite<select name="priorite" class="form-control"><option value="Normale">Normale</option><option value="Basse">Basse</option><option value="Haute">Haute</option><option value="Urgent">Urgent</option></select></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div id="taskModal" class="modal">
    <div class="modal-box">
        <h2>Assigner une tâche</h2>
        <p class="modal-context" data-task-project-label>Projet sélectionné</p>
        <form method="post" action="<?= Helpers::url('projets/taches/store') ?>">
            <input type="hidden" name="projet_id" data-task-project-input>
            <label>description_tache<input name="description_tache" class="form-control" required></label>
            <label>Participant<select name="assigne_a" class="form-control" required data-task-agent-select>
                    <option value="">Sélectionner un participant</option>
                    <?php foreach ($projectParticipants as $projectId => $participants): ?>
                        <?php foreach ($participants as $participant): ?>
                            <?php $participantName = trim(($participant['nom'] ?? '') . ' ' . ($participant['prenom'] ?? '')); ?>
                            <option value="<?= (int) ($participant['agent_id'] ?? 0) ?>" data-project-id="<?= (int) $projectId ?>">
                                <?= Helpers::e($participantName ?: 'Agent #' . ($participant['agent_id'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select></label>
            <label>date_debut_prevue<input name="date_debut_prevue" type="date" class="form-control"></label>
            <label>date_fin_prevue<input name="date_fin_prevue" type="date" class="form-control"></label>
            <label>priorite<select name="priorite" class="form-control"><option value="Normale">Normale</option><option value="Basse">Basse</option><option value="Haute">Haute</option><option value="Urgent">Urgent</option></select></label>
            <label>etat<select name="etat" class="form-control"><option value="À faire">À faire</option><option value="En cours">En cours</option><option value="En revue">En revue</option><option value="Terminé">Terminé</option><option value="Bloqué">Bloqué</option></select></label>
            <label>estimation_heures<input name="estimation_heures" type="number" step="0.01" class="form-control"></label>
            <p class="muted-note" data-task-empty-note hidden>Ce projet n'a pas encore de participant. Ajoutez d'abord une participation dans les détails du projet.</p>
            <div class="actions">
                <button type="button" onclick="closeModal('taskModal')">Annuler</button>
                <button class="btn-primary" data-task-submit>Assigner</button>
            </div>
        </form>
    </div>
</div>
