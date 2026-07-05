<?php

use App\Core\Helpers;

$contracts = $contracts ?? [];
?>
<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Suivi des activités liées aux contrats de communication.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Contrat</th>
                <th>Partenaire</th>
                <th>description_activite</th>
                <th>date_activite</th>
                <th>cout</th>
                <th>statut_activite</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr data-record-id="<?= (int) ($item['activite_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'contrat_id' => $item['contrat_id'] ?? '',
                        'date_activite' => $item['date_activite'] ?? '',
                        'description_activite' => $item['description_activite'] ?? '',
                        'details' => $item['details'] ?? '',
                        'lien_url' => $item['lien_url'] ?? '',
                        'cout' => $item['cout'] ?? '',
                        'statut_activite' => $item['statut_activite'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td><?= Helpers::e($item['reference'] ?? '') ?></td>
                    <td><?= Helpers::e($item['partenaire_nom'] ?? '-') ?></td>
                    <td><?= Helpers::e($item['description_activite'] ?? '') ?></td>
                    <td><?= Helpers::e($item['date_activite'] ?? '') ?></td>
                    <td><?= Helpers::e($item['cout'] ?? '') ?></td>
                    <td><?= Helpers::e($item['statut_activite'] ?? '') ?></td>
                    <td>
                        <form method="post" action="<?= Helpers::url('activites-contrat/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['activite_id'] ?? 0) ?>">
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
        <form method="post" action="<?= Helpers::url('activites-contrat/store') ?>">
            <label>contrat_id<select name="contrat_id" class="form-control" required>
                    <option value="">Sélectionner un contrat</option>
                    <?php foreach ($contracts as $contract): ?>
                        <option value="<?= (int) ($contract['contrat_id'] ?? 0) ?>">
                            <?= Helpers::e(trim(($contract['reference'] ?? '') . ' - ' . ($contract['partenaire_nom'] ?? ''), ' -')) ?>
                        </option>
                    <?php endforeach; ?>
                </select></label>
            <label>date_activite<input name="date_activite" type="date" class="form-control"></label>
            <label>description_activite<input name="description_activite" class="form-control" required></label>
            <label>details<textarea name="details" class="form-control"></textarea></label>
            <label>lien_url<input name="lien_url" type="url" class="form-control"></label>
            <label>cout<input name="cout" type="number" step="0.01" class="form-control"></label>
            <label>statut_activite<select name="statut_activite" class="form-control"><option value="Planifiée">Planifiée</option><option value="Réalisée">Réalisée</option><option value="Annulée">Annulée</option></select></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
