<?php

use App\Core\Auth;
use App\Core\Helpers;
?>
<div class="module-tabs">
    <?php if (Auth::can('productions')): ?><a class="<?= Helpers::currentPath() === 'productions' ? 'active' : '' ?>" href="<?= Helpers::url('productions') ?>"><i class="fa-solid fa-file-lines"></i> Productions</a><?php endif; ?>
    <?php if (Auth::can('types-production')): ?><a class="<?= Helpers::currentPath() === 'types-production' ? 'active' : '' ?>" href="<?= Helpers::url('types-production') ?>"><i class="fa-solid fa-layer-group"></i> Types</a><?php endif; ?>
    <?php if (Auth::can('disks')): ?><a class="<?= Helpers::currentPath() === 'disks' ? 'active' : '' ?>" href="<?= Helpers::url('disks') ?>"><i class="fa-solid fa-hard-drive"></i> Disques</a><?php endif; ?>
</div>

<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Supports de stockage utilisés pour archiver les productions.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Capacité</th>
                <th>Emplacement</th>
                <th>Date acquisition</th>
                <th>État</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $state = $item['etat'] ?? 'Fonctionnel';
                $badgeClass = $state === 'Fonctionnel' ? 'success' : ($state === 'Maintenance' ? 'warning' : 'danger');
                ?>
                <tr data-record-id="<?= (int) ($item['disk_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'matricule' => $item['matricule'] ?? '',
                        'capacite_go' => $item['capacite_go'] ?? '',
                        'emplacement_physique' => $item['emplacement_physique'] ?? '',
                        'date_acquisition' => $item['date_acquisition'] ?? '',
                        'etat' => $item['etat'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td><strong><?= Helpers::e($item['matricule'] ?? '') ?></strong></td>
                    <td><?= Helpers::e(($item['capacite_go'] ?? '') . ' Go') ?></td>
                    <td><?= Helpers::e($item['emplacement_physique'] ?? '-') ?></td>
                    <td><?= Helpers::e($item['date_acquisition'] ?? '-') ?></td>
                    <td><span class="badge <?= Helpers::e($badgeClass) ?>"><?= Helpers::e($state) ?></span></td>
                    <td class="table-actions">
                        <form method="post" action="<?= Helpers::url('disks/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['disk_id'] ?? 0) ?>">
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
        <h2>Ajouter un disque</h2>
        <form method="post" action="<?= Helpers::url('disks/store') ?>">
            <label>Matricule<input name="matricule" class="form-control" required></label>
            <label>Capacité (Go)<input name="capacite_go" type="number" min="1" class="form-control" required></label>
            <label>Emplacement physique<input name="emplacement_physique" class="form-control"></label>
            <label>Date acquisition<input name="date_acquisition" type="date" class="form-control"></label>
            <label>État
                <select name="etat" class="form-control">
                    <option value="Fonctionnel">Fonctionnel</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="HS">HS</option>
                </select>
            </label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
