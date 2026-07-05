<?php

use App\Core\Helpers;

$partenaires = $partenaires ?? [];
$agents = $agents ?? [];
?>
<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Gestion des contrats de communication</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>reference</th>
                <th>partenaire</th>
                <th>type_contrat</th>
                <th>montant</th>
                <th>periodicite</th>
                <th>etat</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr data-record-id="<?= (int) ($item['contrat_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'reference' => $item['reference'] ?? '',
                        'partenaire_id' => $item['partenaire_id'] ?? '',
                        'type_contrat' => $item['type_contrat'] ?? '',
                        'description' => $item['description'] ?? '',
                        'date_debut' => $item['date_debut'] ?? '',
                        'date_fin' => $item['date_fin'] ?? '',
                        'montant' => $item['montant'] ?? '',
                        'periodicite' => $item['periodicite'] ?? '',
                        'responsable_id' => $item['responsable_id'] ?? '',
                        'etat' => $item['etat'] ?? '',
                        'conditions_particulieres' => $item['conditions_particulieres'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td><?= Helpers::e($item['reference'] ?? '') ?></td>
                    <td><?= Helpers::e($item['partenaire_nom'] ?? '-') ?></td>
                    <td><?= Helpers::e($item['type_contrat'] ?? '') ?></td>
                    <td><?= Helpers::e($item['montant'] ?? '') ?></td>
                    <td><?= Helpers::e($item['periodicite'] ?? '') ?></td>
                    <td><?= Helpers::e($item['etat'] ?? '') ?></td>
                    <td class="table-actions">
                        <a class="action-btn view" href="<?= Helpers::url('contrats-com/show?id=' . (int) ($item['contrat_id'] ?? 0)) ?>" title="Voir les détails" aria-label="Voir les détails du contrat">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <form method="post" action="<?= Helpers::url('contrats-com/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['contrat_id'] ?? 0) ?>">
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
        <form method="post" action="<?= Helpers::url('contrats-com/store') ?>">
            <label>reference<input name="reference" class="form-control" required></label>
            <label>partenaire_id<select name="partenaire_id" class="form-control" required>
                    <option value="">Sélectionner un partenaire</option>
                    <?php foreach ($partenaires as $partenaire): ?>
                        <option value="<?= (int) ($partenaire['partenaire_id'] ?? 0) ?>"><?= Helpers::e($partenaire['nom'] ?? 'Partenaire') ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>type_contrat<input name="type_contrat" class="form-control" required></label>
            <label>description<textarea name="description" class="form-control"></textarea></label>
            <label>date_debut<input name="date_debut" type="date" class="form-control" required></label>
            <label>date_fin<input name="date_fin" type="date" class="form-control"></label>
            <label>montant<input name="montant" type="number" step="0.01" class="form-control" required></label>
            <label>periodicite<select name="periodicite" class="form-control"><option value="Mensuel">Mensuel</option><option value="Trimestriel">Trimestriel</option><option value="Annuel">Annuel</option><option value="Forfait">Forfait</option><option value="Horaire">Horaire</option><option value="Hebdomadaire">Hebdomadaire</option></select></label>
            <label>responsable_id<select name="responsable_id" class="form-control">
                    <option value="">Sélectionner un responsable</option>
                    <?php foreach ($agents as $agent): ?>
                        <?php $agentName = trim(($agent['nom'] ?? '') . ' ' . ($agent['prenom'] ?? '')); ?>
                        <option value="<?= (int) ($agent['agent_id'] ?? 0) ?>"><?= Helpers::e($agentName ?: 'Agent #' . ($agent['agent_id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>etat<select name="etat" class="form-control"><option value="Actif">Actif</option><option value="En attente">En attente</option><option value="Terminé">Terminé</option><option value="Résilié">Résilié</option></select></label>
            <label>conditions_particulieres<textarea name="conditions_particulieres" class="form-control"></textarea></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
