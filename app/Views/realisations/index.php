<?php

use App\Core\Auth;
use App\Core\Helpers;

$categories = $categories ?? [];
$videoExtensions = ['mp4', 'webm', 'ogg', 'mov'];
?>
<div class="module-tabs">
    <?php if (Auth::can('realisations')): ?><a class="<?= Helpers::currentPath() === 'realisations' ? 'active' : '' ?>" href="<?= Helpers::url('realisations') ?>"><i class="fa-solid fa-images"></i> Réalisations</a><?php endif; ?>
    <?php if (Auth::can('categories')): ?><a class="<?= Helpers::currentPath() === 'categories' ? 'active' : '' ?>" href="<?= Helpers::url('categories') ?>"><i class="fa-solid fa-tags"></i> Catégories</a><?php endif; ?>
</div>

<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Gestion des réalisations publiées sur la page d’accueil.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Aperçu</th>
                <th>Titre</th>
                <th>Catégorie</th>
                <th>Statut</th>
                <th>Médias</th>
                <th>Date création</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $media = $item['first_image'] ?? '';
                $extension = strtolower(pathinfo((string) $media, PATHINFO_EXTENSION));
                $isVideo = in_array($extension, $videoExtensions, true);
                $isPublished = (int) ($item['statut'] ?? 0) === 1;
                $mediaList = array_values(array_filter(explode('||', (string) ($item['medias'] ?? ''))));
                $detailPayload = [
                    'id' => (int) ($item['realisation_id'] ?? 0),
                    'titre' => $item['titre'] ?? '',
                    'description' => $item['description'] ?? '',
                    'categorie' => $item['categorie_nom'] ?? '-',
                    'statut' => $isPublished ? 'Publié' : 'Brouillon',
                    'date' => $item['date_creation'] ?? '',
                    'medias' => array_map(static fn ($path) => [
                        'url' => Helpers::url($path),
                        'type' => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg', 'mov'], true) ? 'video' : 'image',
                    ], $mediaList),
                ];
                ?>
                <tr data-record-id="<?= (int) ($item['realisation_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'titre' => $item['titre'] ?? '',
                        'description' => $item['description'] ?? '',
                        'categorie' => $item['categorie'] ?? '',
                        'statut' => $item['statut'] ?? 0,
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td>
                        <?php if ($media && !$isVideo): ?>
                            <img class="realisation-thumb" src="<?= Helpers::url($media) ?>" alt="<?= Helpers::e($item['titre'] ?? 'Réalisation') ?>">
                        <?php elseif ($media && $isVideo): ?>
                            <span class="realisation-thumb placeholder"><i class="fa-solid fa-video"></i></span>
                        <?php else: ?>
                            <span class="realisation-thumb placeholder"><i class="fa-solid fa-image"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="identity-cell">
                            <strong><?= Helpers::e($item['titre'] ?? '') ?></strong>
                            <small><?= Helpers::e($item['description'] ?? '') ?></small>
                        </div>
                    </td>
                    <td><?= Helpers::e($item['categorie_nom'] ?? '-') ?></td>
                    <td><span class="badge <?= $isPublished ? 'success' : 'warning' ?>"><?= $isPublished ? 'Publié' : 'Brouillon' ?></span></td>
                    <td><?= count($mediaList) ?> / 3</td>
                    <td><?= Helpers::e($item['date_creation'] ?? '') ?></td>
                    <td class="table-actions">
                        <button type="button" class="action-btn view realisation-detail-button" data-realisation-detail="<?= Helpers::e(json_encode($detailPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>" title="Voir les détails" aria-label="Voir les détails">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button type="button" class="action-btn media realisation-media-button" data-realisation-id="<?= (int) ($item['realisation_id'] ?? 0) ?>" data-realisation-title="<?= Helpers::e($item['titre'] ?? '') ?>" data-media-count="<?= count($mediaList) ?>" title="Ajouter des médias" aria-label="Ajouter des médias">
                            <i class="fa-solid fa-photo-film"></i>
                        </button>
                        <form method="post" action="<?= Helpers::url('realisations/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['realisation_id'] ?? 0) ?>">
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
        <h2>Ajouter une réalisation</h2>
        <form method="post" action="<?= Helpers::url('realisations/store') ?>" enctype="multipart/form-data">
            <label>Titre<input name="titre" class="form-control" required></label>
            <label>Catégorie
                <select name="categorie" class="form-control">
                    <option value="">Sélectionner une catégorie</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?= (int) ($categorie['categorie_id'] ?? 0) ?>"><?= Helpers::e($categorie['nom'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Statut
                <select name="statut" class="form-control">
                    <option value="0">Brouillon</option>
                    <option value="1">Publié</option>
                </select>
            </label>
            <label>Médias
                <input name="images[]" type="file" accept="image/*,video/*" multiple class="form-control">
                <small class="field-hint">Maximum 3 médias par réalisation. Photos: 3 MB max. Vidéos: mp4, webm, ogg ou mov.</small>
            </label>
            <label class="file-preview-field">Description<textarea name="description" class="form-control" rows="4"></textarea></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div id="realisationDetailsModal" class="modal">
    <div class="modal-box realisation-details-modal">
        <div class="modal-titlebar">
            <div>
                <h2 data-detail-title>Détails de la réalisation</h2>
                <p data-detail-meta></p>
            </div>
            <button type="button" class="modal-x" onclick="closeModal('realisationDetailsModal')" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="realisation-detail-layout">
            <section><div class="detail-media-grid" data-detail-medias></div></section>
            <aside class="detail-summary-panel">
                <span class="badge info" data-detail-status></span>
                <p data-detail-description></p>
                <div class="detail-counter" data-detail-counter></div>
            </aside>
        </div>
    </div>
</div>

<div id="realisationMediaModal" class="modal">
    <div class="modal-box">
        <div class="modal-titlebar">
            <div>
                <h2>Ajouter des médias</h2>
                <p data-media-modal-title></p>
            </div>
            <button type="button" class="modal-x" onclick="closeModal('realisationMediaModal')" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="post" action="<?= Helpers::url('realisations/media/store') ?>" enctype="multipart/form-data">
            <input type="hidden" name="realisation_id" data-media-realisation-id>
            <label class="file-preview-field">Médias
                <input name="images[]" type="file" accept="image/*,video/*" multiple class="form-control" data-media-input>
                <small class="field-hint" data-media-hint>Photos: 3 MB max. Maximum 3 médias au total.</small>
            </label>
            <div class="actions">
                <button type="button" onclick="closeModal('realisationMediaModal')">Annuler</button>
                <button class="btn-primary" data-media-submit>Ajouter</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function fillDetails(data) {
            var modal = document.getElementById('realisationDetailsModal');
            var medias = Array.isArray(data.medias) ? data.medias : [];
            var mediaGrid = modal.querySelector('[data-detail-medias]');

            modal.querySelector('[data-detail-title]').textContent = data.titre || 'Réalisation';
            modal.querySelector('[data-detail-meta]').textContent = (data.categorie || '-') + ' • ' + (data.date || '');
            modal.querySelector('[data-detail-status]').textContent = data.statut || 'Brouillon';
            modal.querySelector('[data-detail-description]').textContent = data.description || 'Aucune description enregistrée.';
            modal.querySelector('[data-detail-counter]').textContent = medias.length + ' / 3 média(s)';

            mediaGrid.innerHTML = '';
            if (medias.length === 0) {
                mediaGrid.innerHTML = '<div class="detail-empty-media"><i class="fa-solid fa-image"></i><span>Aucun média enregistré.</span></div>';
            } else {
                medias.forEach(function(media) {
                    var item = document.createElement('div');
                    item.className = 'detail-media-item';
                    item.innerHTML = media.type === 'video'
                        ? '<video src="' + media.url + '" controls muted playsinline></video>'
                        : '<img src="' + media.url + '" alt="">';
                    mediaGrid.appendChild(item);
                });
            }

            openModal('realisationDetailsModal');
        }

        document.querySelectorAll('.realisation-detail-button').forEach(function(button) {
            button.addEventListener('click', function() {
                fillDetails(JSON.parse(button.dataset.realisationDetail || '{}'));
            });
        });

        document.querySelectorAll('.realisation-media-button').forEach(function(button) {
            button.addEventListener('click', function() {
                var modal = document.getElementById('realisationMediaModal');
                var count = parseInt(button.dataset.mediaCount || '0', 10);
                var remaining = Math.max(0, 3 - count);

                modal.querySelector('[data-media-modal-title]').textContent = button.dataset.realisationTitle || 'Réalisation';
                modal.querySelector('[data-media-realisation-id]').value = button.dataset.realisationId || '';
                modal.querySelector('[data-media-input]').value = '';
                modal.querySelector('[data-media-input]').disabled = remaining === 0;
                modal.querySelector('[data-media-submit]').disabled = remaining === 0;
                modal.querySelector('[data-media-hint]').textContent = remaining === 0
                    ? 'La limite de 3 médias est atteinte.'
                    : 'Vous pouvez encore ajouter ' + remaining + ' média(s). Photos: 3 MB max.';

                openModal('realisationMediaModal');
            });
        });
    });
</script>
