<?php

use App\Core\Auth;
use App\Core\Helpers;
?>
<div class="module-tabs">
    <?php if (Auth::can('realisations')): ?><a class="<?= Helpers::currentPath() === 'realisations' ? 'active' : '' ?>" href="<?= Helpers::url('realisations') ?>"><i class="fa-solid fa-images"></i> Réalisations</a><?php endif; ?>
    <?php if (Auth::can('categories')): ?><a class="<?= Helpers::currentPath() === 'categories' ? 'active' : '' ?>" href="<?= Helpers::url('categories') ?>"><i class="fa-solid fa-tags"></i> Catégories</a><?php endif; ?>
</div>

<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Catégories utilisées pour classer les réalisations affichées sur la page d’accueil.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Description</th>
                <th>Date création</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr data-record-id="<?= (int) ($item['categorie_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'nom' => $item['nom'] ?? '',
                        'description' => $item['description'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td><strong><?= Helpers::e($item['nom'] ?? '') ?></strong></td>
                    <td><?= Helpers::e($item['description'] ?? '-') ?></td>
                    <td><?= Helpers::e($item['date_creation'] ?? '') ?></td>
                    <td class="table-actions">
                        <form method="post" action="<?= Helpers::url('categories/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['categorie_id'] ?? 0) ?>">
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
        <h2>Ajouter une catégorie</h2>
        <form method="post" action="<?= Helpers::url('categories/store') ?>">
            <label>Nom<input name="nom" class="form-control" required></label>
            <label class="file-preview-field">Description<textarea name="description" class="form-control" rows="4"></textarea></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
