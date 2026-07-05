<?php

use App\Core\Helpers;

$contract = $contract ?? [];
$activities = $activities ?? [];
$responsable = trim(($contract['responsable_nom'] ?? '') . ' ' . ($contract['responsable_prenom'] ?? ''));
?>
<div class="page-head project-detail-head">
    <div>
        <a class="back-link" href="<?= Helpers::url('contrats-com') ?>"><i class="fa-solid fa-arrow-left"></i> Contrats Com</a>
        <h1><?= Helpers::e($contract['reference'] ?? 'Contrat') ?></h1>
        <p><?= Helpers::e($contract['partenaire_nom'] ?? '') ?></p>
    </div>
    <span class="badge info"><?= Helpers::e($contract['etat'] ?? 'Contrat') ?></span>
</div>

<div class="project-detail-grid">
    <section class="card project-summary">
        <h2>Détails du contrat</h2>
        <dl>
            <div><dt>Partenaire</dt><dd><?= Helpers::e($contract['partenaire_nom'] ?? '-') ?></dd></div>
            <div><dt>Responsable</dt><dd><?= Helpers::e($responsable ?: '-') ?></dd></div>
            <div><dt>Type</dt><dd><?= Helpers::e($contract['type_contrat'] ?? '-') ?></dd></div>
            <div><dt>Montant</dt><dd><?= Helpers::e($contract['montant'] ?? '-') ?></dd></div>
            <div><dt>Périodicité</dt><dd><?= Helpers::e($contract['periodicite'] ?? '-') ?></dd></div>
            <div><dt>Début</dt><dd><?= Helpers::e($contract['date_debut'] ?? '-') ?></dd></div>
            <div><dt>Fin</dt><dd><?= Helpers::e($contract['date_fin'] ?? '-') ?></dd></div>
            <div><dt>Email partenaire</dt><dd><?= Helpers::e($contract['partenaire_email'] ?? '-') ?></dd></div>
        </dl>
        <?php if (!empty($contract['description'])): ?>
            <div class="project-description">
                <h3>Description</h3>
                <p><?= nl2br(Helpers::e($contract['description'])) ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($contract['conditions_particulieres'])): ?>
            <div class="project-description">
                <h3>Conditions particulières</h3>
                <p><?= nl2br(Helpers::e($contract['conditions_particulieres'])) ?></p>
            </div>
        <?php endif; ?>
    </section>

    <aside class="card participation-panel">
        <h2>Ajouter une activité</h2>
        <form method="post" action="<?= Helpers::url('contrats-com/activites/store') ?>" class="compact-form">
            <input type="hidden" name="contrat_id" value="<?= (int) ($contract['contrat_id'] ?? 0) ?>">
            <label>description_activite<input name="description_activite" class="form-control" required></label>
            <label>date_activite<input name="date_activite" type="date" class="form-control"></label>
            <label>cout<input name="cout" type="number" step="0.01" class="form-control"></label>
            <label>statut_activite<select name="statut_activite" class="form-control"><option value="Planifiée">Planifiée</option><option value="Réalisée">Réalisée</option><option value="Annulée">Annulée</option></select></label>
            <label>lien_url<input name="lien_url" type="url" class="form-control"></label>
            <label>details<textarea name="details" class="form-control"></textarea></label>
            <button class="btn-primary"><i class="fa-solid fa-plus"></i> Ajouter</button>
        </form>
    </aside>
</div>

<section class="card participants-card">
    <div class="table-card-head">
        <div>
            <h2>Activités du contrat</h2>
            <p><?= count($activities) ?> activité(s) enregistrée(s).</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Coût</th>
                    <th>Statut</th>
                    <th>Lien</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?= Helpers::e($activity['description_activite'] ?? '') ?></td>
                        <td><?= Helpers::e($activity['date_activite'] ?? '-') ?></td>
                        <td><?= Helpers::e($activity['cout'] ?? '-') ?></td>
                        <td><?= Helpers::e($activity['statut_activite'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($activity['lien_url'])): ?>
                                <a href="<?= Helpers::e($activity['lien_url']) ?>" target="_blank" rel="noopener">Ouvrir</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <form method="post" action="<?= Helpers::url('contrats-com/activites/delete') ?>">
                                <input type="hidden" name="contrat_id" value="<?= (int) ($contract['contrat_id'] ?? 0) ?>">
                                <input type="hidden" name="activite_id" value="<?= (int) ($activity['activite_id'] ?? 0) ?>">
                                <button class="action-btn delete" title="Retirer" aria-label="Retirer cette activité">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$activities): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fa-solid fa-calendar-check"></i>
                            <strong>Aucune activité</strong>
                            <span>Ajoutez une première activité pour suivre l'exécution du contrat.</span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
