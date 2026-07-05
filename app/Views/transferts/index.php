<?php

use App\Core\Auth;
use App\Core\Helpers;

$comptes = $comptes ?? [];
?>
<div class="finance-tabs">
    <?php if (Auth::can('comptes')): ?><a class="<?= Helpers::currentPath() === 'comptes' ? 'active' : '' ?>" href="<?= Helpers::url('comptes') ?>"><i class="fa-solid fa-wallet"></i> Comptes</a><?php endif; ?>
    <?php if (Auth::can('mouvements')): ?><a class="<?= Helpers::currentPath() === 'mouvements' ? 'active' : '' ?>" href="<?= Helpers::url('mouvements') ?>"><i class="fa-solid fa-right-left"></i> Mouvements</a><?php endif; ?>
    <?php if (Auth::can('transferts')): ?><a class="<?= Helpers::currentPath() === 'transferts' ? 'active' : '' ?>" href="<?= Helpers::url('transferts') ?>"><i class="fa-solid fa-arrow-right-arrow-left"></i> Transferts</a><?php endif; ?>
</div>

<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Transferts internes entre comptes.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead><tr><th>Source</th><th>Destination</th><th>montant</th><th>motif</th><th>reference_transfert</th><th>date_transfert</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= Helpers::e($item['compte_source'] ?? '') ?></td>
                    <td><?= Helpers::e($item['compte_destination'] ?? '') ?></td>
                    <td><?= Helpers::e($item['montant'] ?? '') ?></td>
                    <td><?= Helpers::e($item['motif'] ?? '') ?></td>
                    <td><?= Helpers::e($item['reference_transfert'] ?? '') ?></td>
                    <td><?= Helpers::e($item['date_transfert'] ?? '') ?></td>
                    <td class="table-actions">
                        <a class="action-btn receipt" href="<?= Helpers::url('transferts/recu?id=' . (int) ($item['transfert_id'] ?? 0) . '&print=1') ?>" target="_blank" title="Imprimer le recu" aria-label="Imprimer le recu du transfert">
                            <i class="fa-solid fa-print"></i>
                        </a>
                        <form method="post" action="<?= Helpers::url('transferts/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['transfert_id'] ?? 0) ?>">
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
        <form method="post" action="<?= Helpers::url('transferts/store') ?>">
            <label>compte_source_id<select name="compte_source_id" class="form-control" required>
                    <option value="">Compte source</option>
                    <?php foreach ($comptes as $compte): ?>
                        <option value="<?= (int) ($compte['compte_id'] ?? 0) ?>"><?= Helpers::e($compte['nom'] ?? 'Compte') ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>compte_destination_id<select name="compte_destination_id" class="form-control" required>
                    <option value="">Compte destination</option>
                    <?php foreach ($comptes as $compte): ?>
                        <option value="<?= (int) ($compte['compte_id'] ?? 0) ?>"><?= Helpers::e($compte['nom'] ?? 'Compte') ?></option>
                    <?php endforeach; ?>
                </select></label>
            <label>montant<input name="montant" type="number" step="0.01" class="form-control" required></label>
            <label>motif<input name="motif" class="form-control" required></label>
            <label>reference_transfert<input name="reference_transfert" class="form-control"></label>
            <label>date_transfert<input name="date_transfert" type="datetime-local" class="form-control"></label>
            <div class="actions"><button type="button" onclick="closeModal('modalForm')">Annuler</button><button class="btn-primary">Enregistrer</button></div>
        </form>
    </div>
</div>
