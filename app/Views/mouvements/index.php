<?php

use App\Core\Auth;
use App\Core\Helpers;

$comptes = $comptes ?? [];
$projets = $projets ?? [];
$contrats = $contrats ?? [];
?>
<div class="finance-tabs">
    <?php if (Auth::can('comptes')): ?><a class="<?= Helpers::currentPath() === 'comptes' ? 'active' : '' ?>" href="<?= Helpers::url('comptes') ?>"><i class="fa-solid fa-wallet"></i> Comptes</a><?php endif; ?>
    <?php if (Auth::can('mouvements')): ?><a class="<?= Helpers::currentPath() === 'mouvements' ? 'active' : '' ?>" href="<?= Helpers::url('mouvements') ?>"><i class="fa-solid fa-right-left"></i> Mouvements</a><?php endif; ?>
    <?php if (Auth::can('transferts')): ?><a class="<?= Helpers::currentPath() === 'transferts' ? 'active' : '' ?>" href="<?= Helpers::url('transferts') ?>"><i class="fa-solid fa-arrow-right-arrow-left"></i> Transferts</a><?php endif; ?>
</div>

<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Entrées et sorties financières par compte.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead><tr><th>Compte</th><th>type_mouvement</th><th>montant</th><th>motif</th><th>Projet</th><th>Contrat</th><th>date_mouvement</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr data-record-id="<?= (int) ($item['mouvement_id'] ?? 0) ?>">
                    <td><?= Helpers::e($item['compte_nom'] ?? '') ?></td>
                    <td><?= Helpers::e($item['type_mouvement'] ?? '') ?></td>
                    <td><?= Helpers::e(($item['montant'] ?? '') . ' ' . ($item['devise'] ?? '')) ?></td>
                    <td><?= Helpers::e($item['motif'] ?? '') ?></td>
                    <td><?= Helpers::e($item['projet_reference'] ?? '-') ?></td>
                    <td><?= Helpers::e($item['contrat_reference'] ?? '-') ?></td>
                    <td><?= Helpers::e($item['date_mouvement'] ?? '') ?></td>
                    <td class="table-actions">
                        <a class="action-btn receipt" href="<?= Helpers::url('mouvements/recu?id=' . (int) ($item['mouvement_id'] ?? 0) . '&print=1') ?>" target="_blank" title="Imprimer le recu" aria-label="Imprimer le recu du mouvement">
                            <i class="fa-solid fa-print"></i>
                        </a>
                        <form method="post" action="<?= Helpers::url('mouvements/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['mouvement_id'] ?? 0) ?>">
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
        <form method="post" action="<?= Helpers::url('mouvements/store') ?>">
            <label>compte_id<select name="compte_id" class="form-control" required>
                    <option value="">Sélectionner un compte</option>
                    <?php foreach ($comptes as $compte): ?>
                        <option value="<?= (int) ($compte['compte_id'] ?? 0) ?>"><?= Helpers::e(($compte['nom'] ?? 'Compte') . ' - ' . ($compte['devise'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>type_mouvement<select name="type_mouvement" class="form-control" required><option value="entrée">Entrée</option><option value="sortie">Sortie</option></select></label>
            <label>montant<input name="montant" type="number" step="0.01" class="form-control" required></label>
            <label>motif<input name="motif" class="form-control" required></label>
            <label>projet_id<select name="projet_id" class="form-control">
                    <option value="">Aucun projet</option>
                    <?php foreach ($projets as $projet): ?>
                        <option value="<?= (int) ($projet['projet_id'] ?? 0) ?>"><?= Helpers::e(($projet['reference'] ?? '') . ' - ' . ($projet['titre'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>contrat_id<select name="contrat_id" class="form-control">
                    <option value="">Aucun contrat</option>
                    <?php foreach ($contrats as $contrat): ?>
                        <option value="<?= (int) ($contrat['contrat_id'] ?? 0) ?>"><?= Helpers::e(($contrat['reference'] ?? '') . ' - ' . ($contrat['partenaire_nom'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>reference_piece<input name="reference_piece" class="form-control"></label>
            <label>date_mouvement<input name="date_mouvement" type="datetime-local" class="form-control"></label>
            <div class="actions"><button type="button" onclick="closeModal('modalForm')">Annuler</button><button class="btn-primary">Enregistrer</button></div>
        </form>
    </div>
</div>
