<?php

use App\Core\Auth;
use App\Core\Helpers;

$projets = $projets ?? [];
$typesProduction = $typesProduction ?? [];
$disks = $disks ?? [];
?>
<div class="module-tabs">
    <?php if (Auth::can('productions')): ?><a class="<?= Helpers::currentPath() === 'productions' ? 'active' : '' ?>" href="<?= Helpers::url('productions') ?>"><i class="fa-solid fa-file-lines"></i> Productions</a><?php endif; ?>
    <?php if (Auth::can('types-production')): ?><a class="<?= Helpers::currentPath() === 'types-production' ? 'active' : '' ?>" href="<?= Helpers::url('types-production') ?>"><i class="fa-solid fa-layer-group"></i> Types</a><?php endif; ?>
    <?php if (Auth::can('disks')): ?><a class="<?= Helpers::currentPath() === 'disks' ? 'active' : '' ?>" href="<?= Helpers::url('disks') ?>"><i class="fa-solid fa-hard-drive"></i> Disques</a><?php endif; ?>
</div>

<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Documents, livrables et fichiers produits dans les projets.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Fichier</th>
                <th>Projet</th>
                <th>Type</th>
                <th>Disque</th>
                <th>Confidentialité</th>
                <th>Date upload</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $filePath = (string) ($item['chemin_stockage'] ?? '');
                $fileUrl = preg_match('/^https?:\/\//', $filePath) ? $filePath : Helpers::url($filePath);
                $confidentiality = $item['confidentialite'] ?? 'Interne';
                $badgeClass = $confidentiality === 'Public' ? 'success' : ($confidentiality === 'Confidentiel' ? 'danger' : 'info');
                ?>
                <tr data-record-id="<?= (int) ($item['production_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'projet_id' => $item['projet_id'] ?? '',
                        'type_prod_id' => $item['type_prod_id'] ?? '',
                        'disk_id' => $item['disk_id'] ?? '',
                        'cloud_url' => $item['cloud_url'] ?? '',
                        'confidentialite' => $item['confidentialite'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td>
                        <div class="document-cell">
                            <i class="fa-solid fa-file-lines"></i>
                            <div>
                                <strong><?= Helpers::e($item['nom_fichier'] ?? '') ?></strong>
                                <small><?= Helpers::e($filePath) ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="identity-cell">
                            <strong><?= Helpers::e($item['projet_reference'] ?? '-') ?></strong>
                            <small><?= Helpers::e($item['projet_titre'] ?? '') ?></small>
                        </div>
                    </td>
                    <td><?= Helpers::e($item['type_nom'] ?? '-') ?></td>
                    <td>
                        <div class="identity-cell">
                            <strong><?= Helpers::e($item['disk_matricule'] ?? '-') ?></strong>
                            <small><?= Helpers::e($item['disk_etat'] ?? '') ?></small>
                        </div>
                    </td>
                    <td><span class="badge <?= Helpers::e($badgeClass) ?>"><?= Helpers::e($confidentiality) ?></span></td>
                    <td><?= Helpers::e($item['date_upload'] ?? '') ?></td>
                    <td class="table-actions">
                        <a class="action-btn view" href="<?= Helpers::e($fileUrl) ?>" target="_blank" title="Ouvrir le fichier" aria-label="Ouvrir le fichier">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                        <?php if (!empty($item['cloud_url'])): ?>
                            <a class="action-btn receipt" href="<?= Helpers::e($item['cloud_url']) ?>" target="_blank" title="Lien cloud" aria-label="Ouvrir le lien cloud">
                                <i class="fa-solid fa-cloud-arrow-down"></i>
                            </a>
                        <?php endif; ?>
                        <form method="post" action="<?= Helpers::url('productions/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['production_id'] ?? 0) ?>">
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
        <h2>Ajouter une production</h2>
        <form method="post" action="<?= Helpers::url('productions/store') ?>" enctype="multipart/form-data">
            <label>Projet
                <select name="projet_id" class="form-control" required>
                    <option value="">Sélectionner un projet</option>
                    <?php foreach ($projets as $projet): ?>
                        <option value="<?= (int) ($projet['projet_id'] ?? 0) ?>">
                            <?= Helpers::e(($projet['reference'] ?? '') . ' - ' . ($projet['titre'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Type de production
                <select name="type_prod_id" class="form-control" required>
                    <option value="">Sélectionner un type</option>
                    <?php foreach ($typesProduction as $type): ?>
                        <option value="<?= (int) ($type['type_prod_id'] ?? 0) ?>">
                            <?= Helpers::e(($type['nom'] ?? 'Type') . ' (' . ($type['extension_autorisee'] ?? 'tout fichier') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Disque de stockage
                <select name="disk_id" class="form-control" required>
                    <option value="">Sélectionner un disque</option>
                    <?php foreach ($disks as $disk): ?>
                        <option value="<?= (int) ($disk['disk_id'] ?? 0) ?>">
                            <?= Helpers::e(($disk['matricule'] ?? 'Disque') . ' - ' . ($disk['etat'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="file-preview-field">Fichier de production
                <input name="document" type="file" class="form-control" required>
                <small class="field-hint">Le nom et le chemin de stockage sont générés automatiquement.</small>
            </label>
            <label>Lien cloud
                <input name="cloud_url" type="url" class="form-control" placeholder="https://...">
            </label>
            <label>Confidentialité
                <select name="confidentialite" class="form-control">
                    <option value="Interne">Interne</option>
                    <option value="Public">Public</option>
                    <option value="Confidentiel">Confidentiel</option>
                </select>
            </label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
