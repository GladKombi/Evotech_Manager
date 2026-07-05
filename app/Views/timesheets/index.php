<?php

use App\Core\Helpers;

$tasks = $tasks ?? [];
$agents = $agents ?? [];
?>
<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Suivi des heures travaillées par tâche, agent et projet.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Projet</th>
                <th>Tâche</th>
                <th>Agent</th>
                <th>Date</th>
                <th>Heures</th>
                <th>État</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $agentName = trim(($item['agent_nom'] ?? '') . ' ' . ($item['agent_prenom'] ?? ''));
                ?>
                <tr data-record-id="<?= (int) ($item['timesheet_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'tache_id' => $item['tache_id'] ?? '',
                        'agent_id' => $item['agent_id'] ?? '',
                        'date_travail' => $item['date_travail'] ?? '',
                        'worked_heures' => $item['worked_heures'] ?? '',
                        'description_travail' => $item['description_travail'] ?? '',
                        'etat' => $item['etat'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td>
                        <strong><?= Helpers::e($item['projet_titre'] ?? 'Projet') ?></strong>
                        <small><?= Helpers::e($item['projet_reference'] ?? '') ?></small>
                    </td>
                    <td><?= Helpers::e($item['description_tache'] ?? '') ?></td>
                    <td><?= Helpers::e($agentName ?: '-') ?></td>
                    <td><?= Helpers::e($item['date_travail'] ?? '') ?></td>
                    <td><?= Helpers::e($item['worked_heures'] ?? '') ?></td>
                    <td><?= Helpers::e($item['etat'] ?? '') ?></td>
                    <td class="table-actions">
                        <form method="post" action="<?= Helpers::url('timesheets/validate') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['timesheet_id'] ?? 0) ?>">
                            <button class="action-btn validate" title="Valider" aria-label="Valider cette timesheet">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </form>
                        <form method="post" action="<?= Helpers::url('timesheets/refuse') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['timesheet_id'] ?? 0) ?>">
                            <button class="action-btn refuse" title="Refuser" aria-label="Refuser cette timesheet">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                        <form method="post" action="<?= Helpers::url('timesheets/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['timesheet_id'] ?? 0) ?>">
                            <button class="action-btn delete" title="Supprimer" aria-label="Supprimer cette timesheet">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
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
        <form method="post" action="<?= Helpers::url('timesheets/store') ?>">
            <label>tache_id<select name="tache_id" class="form-control" required>
                    <option value="">Sélectionner une tâche</option>
                    <?php foreach ($tasks as $task): ?>
                        <option value="<?= (int) ($task['tache_id'] ?? 0) ?>" data-agent-id="<?= (int) ($task['assigne_a'] ?? 0) ?>">
                            <?= Helpers::e(trim(($task['projet_reference'] ?? '') . ' - ' . ($task['description_tache'] ?? ''), ' -')) ?>
                        </option>
                    <?php endforeach; ?>
                </select></label>
            <label>agent_id<select name="agent_id" class="form-control" required>
                    <option value="">Sélectionner un agent</option>
                    <?php foreach ($agents as $agent): ?>
                        <?php $name = trim(($agent['nom'] ?? '') . ' ' . ($agent['prenom'] ?? '')); ?>
                        <option value="<?= (int) ($agent['agent_id'] ?? 0) ?>"><?= Helpers::e($name ?: 'Agent #' . ($agent['agent_id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>date_travail<input name="date_travail" type="date" class="form-control"></label>
            <label>worked_heures<input name="worked_heures" type="number" step="0.01" class="form-control" required></label>
            <label>description_travail<textarea name="description_travail" class="form-control"></textarea></label>
            <label>etat<select name="etat" class="form-control"><option value="Brouillon">Brouillon</option><option value="Soumise">Soumise</option><option value="Validée">Validée</option><option value="Refusée">Refusée</option></select></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
