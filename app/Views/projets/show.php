<?php

use App\Core\Helpers;

$project = $project ?? [];
$participants = $participants ?? [];
$availableAgents = $availableAgents ?? [];
$tasks = $tasks ?? [];
?>
<div class="page-head project-detail-head">
    <div>
        <a class="back-link" href="<?= Helpers::url('projets') ?>"><i class="fa-solid fa-arrow-left"></i> Projets</a>
        <h1><?= Helpers::e($project['titre'] ?? 'Projet') ?></h1>
        <p><?= Helpers::e($project['reference'] ?? '') ?></p>
    </div>
    <span class="badge info"><?= Helpers::e($project['etat'] ?? 'Projet') ?></span>
</div>

<div class="project-detail-grid">
    <section class="card project-summary">
        <h2>Détails du projet</h2>
        <dl>
            <div><dt>Priorité</dt><dd><?= Helpers::e($project['priorite'] ?? '-') ?></dd></div>
            <div><dt>Budget estimé</dt><dd><?= Helpers::e($project['budget_estime'] ?? '-') ?></dd></div>
            <div><dt>Date début</dt><dd><?= Helpers::e($project['date_debut'] ?? '-') ?></dd></div>
            <div><dt>Fin prévue</dt><dd><?= Helpers::e($project['date_fin_prevue'] ?? '-') ?></dd></div>
            <div><dt>Fin réelle</dt><dd><?= Helpers::e($project['date_fin_reelle'] ?? '-') ?></dd></div>
            <div><dt>Chef projet ID</dt><dd><?= Helpers::e($project['chef_projet_id'] ?? '-') ?></dd></div>
        </dl>
        <?php if (!empty($project['description_projet'])): ?>
            <div class="project-description">
                <h3>Description</h3>
                <p><?= nl2br(Helpers::e($project['description_projet'])) ?></p>
            </div>
        <?php endif; ?>
    </section>

    <aside class="card participation-panel">
        <h2>Ajouter une participation</h2>
        <form method="post" action="<?= Helpers::url('projets/participations/store') ?>" class="compact-form">
            <input type="hidden" name="projet_id" value="<?= (int) ($project['projet_id'] ?? 0) ?>">
            <label>Agent
                <select name="agent_id" class="form-control" required>
                    <option value="">Sélectionner un agent</option>
                    <?php foreach ($availableAgents as $agent): ?>
                        <?php $name = trim(($agent['nom'] ?? '') . ' ' . ($agent['prenom'] ?? '')); ?>
                        <option value="<?= (int) ($agent['agent_id'] ?? 0) ?>"><?= Helpers::e($name ?: 'Agent #' . ($agent['agent_id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn-primary" <?= !$availableAgents ? 'disabled' : '' ?>>
                <i class="fa-solid fa-user-plus"></i>
                Ajouter
            </button>
        </form>
        <?php if (!$availableAgents): ?>
            <p class="muted-note">Tous les agents actifs participent déjà à ce projet.</p>
        <?php endif; ?>
    </aside>
</div>

<section class="card participants-card">
    <div class="table-card-head">
        <div>
            <h2>Agents participants</h2>
            <p><?= count($participants) ?> participant(s) affecté(s) au projet.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Email professionnel</th>
                    <th>Téléphone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $participant): ?>
                    <?php
                    $fullName = trim(($participant['nom'] ?? '') . ' ' . ($participant['prenom'] ?? ''));
                    $initials = strtoupper(substr((string) ($participant['nom'] ?? 'A'), 0, 1) . substr((string) ($participant['prenom'] ?? ''), 0, 1));
                    ?>
                    <tr>
                        <td>
                            <div class="identity-cell">
                                <?php if (!empty($participant['photo_profil'])): ?>
                                    <img class="identity-avatar" src="<?= Helpers::e(Helpers::url($participant['photo_profil'])) ?>" alt="<?= Helpers::e($fullName ?: 'Agent') ?>">
                                <?php else: ?>
                                    <span class="identity-avatar"><?= Helpers::e($initials ?: 'A') ?></span>
                                <?php endif; ?>
                                <div>
                                    <strong><?= Helpers::e($fullName ?: 'Agent') ?></strong>
                                    <small>ID #<?= (int) ($participant['agent_id'] ?? 0) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= Helpers::e($participant['email_professionnel'] ?? '-') ?></td>
                        <td><?= Helpers::e($participant['telephone_professionnel'] ?? '-') ?></td>
                        <td class="table-actions">
                            <button type="button" class="action-btn task participant-task-button" data-participant-task-button data-project-id="<?= (int) ($project['projet_id'] ?? 0) ?>" data-agent-id="<?= (int) ($participant['agent_id'] ?? 0) ?>" data-agent-name="<?= Helpers::e($fullName ?: 'Agent') ?>" title="Attribuer une tâche" aria-label="Attribuer une tâche à <?= Helpers::e($fullName ?: 'cet agent') ?>">
                                <i class="fa-solid fa-list-check"></i>
                                <span>Tâche</span>
                            </button>
                            <form method="post" action="<?= Helpers::url('projets/participations/delete') ?>">
                                <input type="hidden" name="projet_id" value="<?= (int) ($project['projet_id'] ?? 0) ?>">
                                <input type="hidden" name="participation_id" value="<?= (int) ($participant['participation_id'] ?? 0) ?>">
                                <button class="action-btn delete" title="Retirer" aria-label="Retirer <?= Helpers::e($fullName ?: 'cet agent') ?>">
                                    <i class="fa-solid fa-user-minus"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$participants): ?>
                    <tr>
                        <td colspan="4" class="empty-state">
                            <i class="fa-solid fa-users"></i>
                            <strong>Aucun participant</strong>
                            <span>Ajoutez un agent pour démarrer la gestion de participation.</span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="participantTaskModal" class="modal">
    <div class="modal-box">
        <h2>Attribuer une tâche</h2>
        <p class="modal-context" data-participant-task-label>Participant sélectionné</p>
        <form method="post" action="<?= Helpers::url('projets/taches/store') ?>">
            <input type="hidden" name="projet_id" value="<?= (int) ($project['projet_id'] ?? 0) ?>" data-participant-task-project>
            <input type="hidden" name="assigne_a" data-participant-task-agent>
            <label>Description<input name="description_tache" class="form-control" required></label>
            <label>Début prévu<input name="date_debut_prevue" type="date" class="form-control"></label>
            <label>Fin prévue<input name="date_fin_prevue" type="date" class="form-control"></label>
            <label>Priorité<select name="priorite" class="form-control"><option value="Normale">Normale</option><option value="Basse">Basse</option><option value="Haute">Haute</option><option value="Urgent">Urgent</option></select></label>
            <label>État<select name="etat" class="form-control"><option value="À faire">À faire</option><option value="En cours">En cours</option><option value="En revue">En revue</option><option value="Terminé">Terminé</option><option value="Bloqué">Bloqué</option></select></label>
            <label>Estimation heures<input name="estimation_heures" type="number" step="0.01" class="form-control"></label>
            <div class="actions">
                <button type="button" onclick="closeModal('participantTaskModal')">Annuler</button>
                <button class="btn-primary">Attribuer</button>
            </div>
        </form>
    </div>
</div>

<section class="card participants-card project-tasks-card">
    <div class="table-card-head">
        <div>
            <h2>Tâches du projet</h2>
            <p><?= count($tasks) ?> tâche(s) planifiée(s), assignées aux participants du projet.</p>
        </div>
    </div>
    <p class="muted-note">Utilisez le bouton “Tâche” dans le tableau des participants pour attribuer une tâche à un agent.</p>

    <form method="post" action="<?= Helpers::url('projets/taches/store') ?>" class="project-task-form">
        <input type="hidden" name="projet_id" value="<?= (int) ($project['projet_id'] ?? 0) ?>">
        <label>Description
            <input name="description_tache" class="form-control" required>
        </label>
        <label>Participant
            <select name="assigne_a" class="form-control" required>
                <option value="">Sélectionner un participant</option>
                <?php foreach ($participants as $participant): ?>
                    <?php $name = trim(($participant['nom'] ?? '') . ' ' . ($participant['prenom'] ?? '')); ?>
                    <option value="<?= (int) ($participant['agent_id'] ?? 0) ?>"><?= Helpers::e($name ?: 'Agent #' . ($participant['agent_id'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Début prévu
            <input name="date_debut_prevue" type="date" class="form-control">
        </label>
        <label>Fin prévue
            <input name="date_fin_prevue" type="date" class="form-control">
        </label>
        <label>Priorité
            <select name="priorite" class="form-control">
                <option value="Normale">Normale</option>
                <option value="Basse">Basse</option>
                <option value="Haute">Haute</option>
                <option value="Urgent">Urgent</option>
            </select>
        </label>
        <label>État
            <select name="etat" class="form-control">
                <option value="À faire">À faire</option>
                <option value="En cours">En cours</option>
                <option value="En revue">En revue</option>
                <option value="Terminé">Terminé</option>
                <option value="Bloqué">Bloqué</option>
            </select>
        </label>
        <label>Estimation heures
            <input name="estimation_heures" type="number" step="0.01" class="form-control">
        </label>
        <button class="btn-primary" <?= !$participants ? 'disabled' : '' ?>>
            <i class="fa-solid fa-list-check"></i>
            Ajouter la tâche
        </button>
    </form>
    <?php if (!$participants): ?>
        <p class="muted-note">Ajoutez d'abord un participant avant de créer des tâches pour ce projet.</p>
    <?php endif; ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Assignée à</th>
                    <th>Priorité</th>
                    <th>État</th>
                    <th>Échéance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <?php $taskAgent = trim(($task['agent_nom'] ?? '') . ' ' . ($task['agent_prenom'] ?? '')); ?>
                    <tr>
                        <td><?= Helpers::e($task['description_tache'] ?? '') ?></td>
                        <td><?= Helpers::e($taskAgent ?: '-') ?></td>
                        <td><?= Helpers::e($task['priorite'] ?? '-') ?></td>
                        <td><?= Helpers::e($task['etat'] ?? '-') ?></td>
                        <td><?= Helpers::e($task['date_fin_prevue'] ?? '-') ?></td>
                        <td class="table-actions">
                            <a class="action-btn view" href="<?= Helpers::url('taches/show?id=' . (int) ($task['tache_id'] ?? 0)) ?>" title="Voir les détails" aria-label="Voir les détails de la tâche">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <form method="post" action="<?= Helpers::url('projets/taches/delete') ?>">
                                <input type="hidden" name="projet_id" value="<?= (int) ($project['projet_id'] ?? 0) ?>">
                                <input type="hidden" name="tache_id" value="<?= (int) ($task['tache_id'] ?? 0) ?>">
                                <button class="action-btn delete" title="Retirer" aria-label="Retirer cette tâche">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tasks): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fa-solid fa-list-check"></i>
                            <strong>Aucune tâche</strong>
                            <span>Ajoutez une première tâche et assignez-la à un participant du projet.</span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
