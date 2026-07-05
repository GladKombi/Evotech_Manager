<?php

use App\Core\Helpers;

$projets = $projets ?? [];
$agents = $agents ?? [];
?>
<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Gestion des tâches par projet et par participant</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Projet</th>
                <th>description_tache</th>
                <th>assigné_a</th>
                <th>priorite</th>
                <th>etat</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php $agentName = trim(($item['agent_nom'] ?? '') . ' ' . ($item['agent_prenom'] ?? '')); ?>
                <tr data-record-id="<?= (int) ($item['tache_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'projet_id' => $item['projet_id'] ?? '',
                        'description_tache' => $item['description_tache'] ?? '',
                        'assigne_a' => $item['assigne_a'] ?? '',
                        'date_debut_prevue' => $item['date_debut_prevue'] ?? '',
                        'date_fin_prevue' => $item['date_fin_prevue'] ?? '',
                        'priorite' => $item['priorite'] ?? '',
                        'etat' => $item['etat'] ?? '',
                        'estimation_heures' => $item['estimation_heures'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td>
                        <strong><?= Helpers::e($item['projet_titre'] ?? 'Projet') ?></strong>
                        <small><?= Helpers::e($item['projet_reference'] ?? '') ?></small>
                    </td>
                    <td><?= Helpers::e($item['description_tache'] ?? '') ?></td>
                    <td><?= Helpers::e($agentName ?: '-') ?></td>
                    <td><?= Helpers::e($item['priorite'] ?? '') ?></td>
                    <td><?= Helpers::e($item['etat'] ?? '') ?></td>
                    <td>
                        <button type="button" class="action-btn time task-time-button" data-task-time-button data-task-id="<?= (int) ($item['tache_id'] ?? 0) ?>" data-agent-id="<?= (int) ($item['assigne_a'] ?? 0) ?>" data-task-label="<?= Helpers::e($item['description_tache'] ?? 'Tâche') ?>" title="Ajouter un temps" aria-label="Ajouter un temps pour cette tâche">
                            <i class="fa-solid fa-clock"></i>
                            <span>Temps</span>
                        </button>
                        <a class="action-btn view" href="<?= Helpers::url('taches/show?id=' . (int) ($item['tache_id'] ?? 0)) ?>" title="Voir les détails" aria-label="Voir les détails de la tâche">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <form method="post" action="<?= Helpers::url('taches/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['tache_id'] ?? 0) ?>">
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
        <form method="post" action="<?= Helpers::url('taches/store') ?>">
            <label>projet_id<select name="projet_id" class="form-control" required>
                    <option value="">Sélectionner un projet</option>
                    <?php foreach ($projets as $projet): ?>
                        <option value="<?= (int) ($projet['projet_id'] ?? 0) ?>">
                            <?= Helpers::e(trim(($projet['reference'] ?? '') . ' - ' . ($projet['titre'] ?? ''), ' -')) ?>
                        </option>
                    <?php endforeach; ?>
                </select></label>
            <label>description_tache<input name="description_tache" class="form-control" required></label>
            <label>assigne_a<select name="assigne_a" class="form-control">
                    <option value="">Non assignée</option>
                    <?php foreach ($agents as $agent): ?>
                        <?php $name = trim(($agent['nom'] ?? '') . ' ' . ($agent['prenom'] ?? '')); ?>
                        <option value="<?= (int) ($agent['agent_id'] ?? 0) ?>"><?= Helpers::e($name ?: 'Agent #' . ($agent['agent_id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>date_debut_prevue<input name="date_debut_prevue" type="date" class="form-control"></label>
            <label>date_fin_prevue<input name="date_fin_prevue" type="date" class="form-control"></label>
            <label>priorite<select name="priorite" class="form-control"><option value="Normale">Normale</option><option value="Basse">Basse</option><option value="Haute">Haute</option><option value="Urgent">Urgent</option></select></label>
            <label>etat<select name="etat" class="form-control"><option value="À faire">À faire</option><option value="En cours">En cours</option><option value="En revue">En revue</option><option value="Terminé">Terminé</option><option value="Bloqué">Bloqué</option></select></label>
            <label>estimation_heures<input name="estimation_heures" type="number" step="0.01" class="form-control"></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div id="taskTimeModal" class="modal">
    <div class="modal-box">
        <h2>Ajouter un temps</h2>
        <p class="modal-context" data-task-time-label>Tâche sélectionnée</p>
        <form method="post" action="<?= Helpers::url('timesheets/store') ?>">
            <input type="hidden" name="_redirect" value="taches">
            <input type="hidden" name="tache_id" data-task-time-task>
            <input type="hidden" name="agent_id" data-task-time-agent>
            <label>date_travail<input name="date_travail" type="date" class="form-control"></label>
            <label>worked_heures<input name="worked_heures" type="number" step="0.01" class="form-control" required></label>
            <label>description_travail<textarea name="description_travail" class="form-control"></textarea></label>
            <label>etat<select name="etat" class="form-control"><option value="Brouillon">Brouillon</option><option value="Soumise">Soumise</option></select></label>
            <p class="muted-note" data-task-time-empty hidden>Cette tâche n'est pas assignée à un agent. Assignez-la avant d'ajouter un timesheet.</p>
            <div class="actions">
                <button type="button" onclick="closeModal('taskTimeModal')">Annuler</button>
                <button class="btn-primary" data-task-time-submit>Enregistrer</button>
            </div>
        </form>
    </div>
</div>
