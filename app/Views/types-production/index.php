<?php

use App\Core\Auth;
use App\Core\Helpers;

$departements = $departements ?? [];
?>
<div class="module-tabs">
    <?php if (Auth::can('productions')): ?><a class="<?= Helpers::currentPath() === 'productions' ? 'active' : '' ?>" href="<?= Helpers::url('productions') ?>"><i class="fa-solid fa-file-lines"></i> Productions</a><?php endif; ?>
    <?php if (Auth::can('types-production')): ?><a class="<?= Helpers::currentPath() === 'types-production' ? 'active' : '' ?>" href="<?= Helpers::url('types-production') ?>"><i class="fa-solid fa-layer-group"></i> Types</a><?php endif; ?>
    <?php if (Auth::can('disks')): ?><a class="<?= Helpers::currentPath() === 'disks' ? 'active' : '' ?>" href="<?= Helpers::url('disks') ?>"><i class="fa-solid fa-hard-drive"></i> Disques</a><?php endif; ?>
</div>

<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Catégories de livrables, extensions autorisées et règles de taille.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Département</th>
                <th>Extensions</th>
                <th>Taille max</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr data-record-id="<?= (int) ($item['type_prod_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'nom' => $item['nom'] ?? '',
                        'description' => $item['description'] ?? '',
                        'departement_id' => $item['departement_id'] ?? '',
                        'extension_autorisee' => $item['extension_autorisee'] ?? '',
                        'taille_max_mo' => $item['taille_max_mo'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td><strong><?= Helpers::e($item['nom'] ?? '') ?></strong></td>
                    <td><?= Helpers::e($item['departement_nom'] ?? '-') ?></td>
                    <td><?= Helpers::e($item['extension_autorisee'] ?? '-') ?></td>
                    <td><?= Helpers::e(($item['taille_max_mo'] ?? '100') . ' Mo') ?></td>
                    <td><?= Helpers::e($item['description'] ?? '-') ?></td>
                    <td class="table-actions">
                        <form method="post" action="<?= Helpers::url('types-production/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['type_prod_id'] ?? 0) ?>">
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
        <h2>Ajouter un type</h2>
        <form method="post" action="<?= Helpers::url('types-production/store') ?>">
            <label>Nom<input name="nom" class="form-control" required></label>
            <label>Département
                <select name="departement_id" class="form-control">
                    <option value="">Aucun département</option>
                    <?php foreach ($departements as $departement): ?>
                        <option value="<?= (int) ($departement['departement_id'] ?? 0) ?>"><?= Helpers::e($departement['nom'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Extensions autorisées<input name="extension_autorisee" class="form-control" placeholder="pdf, docx, xlsx, png"></label>
            <label>Taille max (Mo)<input name="taille_max_mo" type="number" min="1" class="form-control" value="100"></label>
            <label class="file-preview-field">Description<textarea name="description" class="form-control" rows="4"></textarea></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
