<?php

use App\Core\Auth;
use App\Core\Helpers;

$account = $account ?? [];
$movements = $movements ?? [];
?>
<div class="finance-tabs">
    <?php if (Auth::can('comptes')): ?><a class="<?= Helpers::currentPath() === 'comptes' ? 'active' : '' ?>" href="<?= Helpers::url('comptes') ?>"><i class="fa-solid fa-wallet"></i> Comptes</a><?php endif; ?>
    <?php if (Auth::can('mouvements')): ?><a class="<?= Helpers::currentPath() === 'mouvements' ? 'active' : '' ?>" href="<?= Helpers::url('mouvements') ?>"><i class="fa-solid fa-right-left"></i> Mouvements</a><?php endif; ?>
    <?php if (Auth::can('transferts')): ?><a class="<?= Helpers::currentPath() === 'transferts' ? 'active' : '' ?>" href="<?= Helpers::url('transferts') ?>"><i class="fa-solid fa-arrow-right-arrow-left"></i> Transferts</a><?php endif; ?>
</div>

<div class="page-head project-detail-head">
    <div>
        <a class="back-link" href="<?= Helpers::url('comptes') ?>"><i class="fa-solid fa-arrow-left"></i> Comptes</a>
        <h1><?= Helpers::e($account['nom'] ?? 'Compte') ?></h1>
        <p><?= Helpers::e(($account['type'] ?? '') . ' - ' . ($account['numero_compte'] ?? '')) ?></p>
    </div>
    <span class="badge info"><?= Helpers::e(($account['solde_actuel'] ?? '0') . ' ' . ($account['devise'] ?? '')) ?></span>
</div>

<section class="card participants-card">
    <div class="table-card-head">
        <div>
            <h2>Mouvements du compte</h2>
            <p><?= count($movements) ?> mouvement(s) enregistré(s).</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Type</th><th>Montant</th><th>Motif</th><th>Projet</th><th>Contrat</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($movements as $movement): ?>
                    <tr>
                        <td><?= Helpers::e($movement['type_mouvement'] ?? '') ?></td>
                        <td><?= Helpers::e($movement['montant'] ?? '') ?></td>
                        <td><?= Helpers::e($movement['motif'] ?? '') ?></td>
                        <td><?= Helpers::e($movement['projet_reference'] ?? '-') ?></td>
                        <td><?= Helpers::e($movement['contrat_reference'] ?? '-') ?></td>
                        <td><?= Helpers::e($movement['date_mouvement'] ?? '') ?></td>
                        <td class="table-actions">
                            <a class="action-btn receipt" href="<?= Helpers::url('mouvements/recu?id=' . (int) ($movement['mouvement_id'] ?? 0) . '&print=1') ?>" target="_blank" title="Imprimer le recu" aria-label="Imprimer le recu du mouvement">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
