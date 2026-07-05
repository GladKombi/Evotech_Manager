<?php

use App\Core\Auth;
use App\Core\Helpers;

$agents = $agents ?? [];
?>
<div class="finance-tabs">
    <?php if (Auth::can('comptes')): ?><a class="<?= Helpers::currentPath() === 'comptes' ? 'active' : '' ?>" href="<?= Helpers::url('comptes') ?>"><i class="fa-solid fa-wallet"></i> Comptes</a><?php endif; ?>
    <?php if (Auth::can('mouvements')): ?><a class="<?= Helpers::currentPath() === 'mouvements' ? 'active' : '' ?>" href="<?= Helpers::url('mouvements') ?>"><i class="fa-solid fa-right-left"></i> Mouvements</a><?php endif; ?>
    <?php if (Auth::can('transferts')): ?><a class="<?= Helpers::currentPath() === 'transferts' ? 'active' : '' ?>" href="<?= Helpers::url('transferts') ?>"><i class="fa-solid fa-arrow-right-arrow-left"></i> Transferts</a><?php endif; ?>
</div>

<div class="page-head">
    <div>
        <h1><?= Helpers::e($title) ?></h1>
        <p>Gestion des comptes bancaires et caisses.</p>
    </div>
    <button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>nom</th>
                <th>type</th>
                <th>numero_compte</th>
                <th>solde_actuel</th>
                <th>devise</th>
                <th>responsable</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php $responsable = trim(($item['responsable_nom'] ?? '') . ' ' . ($item['responsable_prenom'] ?? '')); ?>
                <tr data-record-id="<?= (int) ($item['compte_id'] ?? 0) ?>"
                    data-edit-payload="<?= Helpers::e(json_encode([
                        'nom' => $item['nom'] ?? '',
                        'type' => $item['type'] ?? '',
                        'numero_compte' => $item['numero_compte'] ?? '',
                        'solde_initial' => $item['solde_initial'] ?? '',
                        'solde_actuel' => $item['solde_actuel'] ?? '',
                        'devise' => $item['devise'] ?? '',
                        'responsable_id' => $item['responsable_id'] ?? '',
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                    <td><?= Helpers::e($item['nom'] ?? '') ?></td>
                    <td><?= Helpers::e($item['type'] ?? '') ?></td>
                    <td><?= Helpers::e($item['numero_compte'] ?? '') ?></td>
                    <td><?= Helpers::e($item['solde_actuel'] ?? '') ?></td>
                    <td><?= Helpers::e($item['devise'] ?? '') ?></td>
                    <td><?= Helpers::e($responsable ?: '-') ?></td>
                    <td class="table-actions">
                        <a class="action-btn view" href="<?= Helpers::url('comptes/show?id=' . (int) ($item['compte_id'] ?? 0)) ?>" title="Voir les détails" aria-label="Voir les détails du compte">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <form method="post" action="<?= Helpers::url('comptes/delete') ?>">
                            <input type="hidden" name="id" value="<?= (int) ($item['compte_id'] ?? 0) ?>">
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
        <form method="post" action="<?= Helpers::url('comptes/store') ?>">
            <label>nom<input name="nom" class="form-control" required></label>
            <label>type<select name="type" class="form-control"><option value="Banque">Banque</option><option value="Caisse">Caisse</option></select></label>
            <label>numero_compte<input name="numero_compte" class="form-control"></label>
            <label>solde_initial<input name="solde_initial" type="number" step="0.01" class="form-control"></label>
            <label>solde_actuel<input name="solde_actuel" type="number" step="0.01" class="form-control"></label>
            <label>devise<input name="devise" maxlength="3" class="form-control" value="USD"></label>
            <label>responsable_id<select name="responsable_id" class="form-control">
                    <option value="">Sélectionner un responsable</option>
                    <?php foreach ($agents as $agent): ?>
                        <?php $name = trim(($agent['nom'] ?? '') . ' ' . ($agent['prenom'] ?? '')); ?>
                        <option value="<?= (int) ($agent['agent_id'] ?? 0) ?>"><?= Helpers::e($name ?: 'Agent #' . ($agent['agent_id'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
