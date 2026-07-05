<?php

use App\Core\Helpers;

$task = $task ?? [];
$agentName = trim(($task['agent_nom'] ?? '') . ' ' . ($task['agent_prenom'] ?? ''));
?>
<div class="page-head project-detail-head">
    <div>
        <a class="back-link" href="<?= Helpers::url('taches') ?>"><i class="fa-solid fa-arrow-left"></i> Tâches</a>
        <h1><?= Helpers::e($task['description_tache'] ?? 'Tâche') ?></h1>
        <p><?= Helpers::e(($task['projet_reference'] ?? '') . ' - ' . ($task['projet_titre'] ?? '')) ?></p>
    </div>
    <span class="badge info"><?= Helpers::e($task['etat'] ?? 'Tâche') ?></span>
</div>

<section class="card project-summary">
    <h2>Détails de la tâche</h2>
    <dl>
        <div><dt>Projet</dt><dd><?= Helpers::e($task['projet_titre'] ?? '-') ?></dd></div>
        <div><dt>Assignée à</dt><dd><?= Helpers::e($agentName ?: '-') ?></dd></div>
        <div><dt>Priorité</dt><dd><?= Helpers::e($task['priorite'] ?? '-') ?></dd></div>
        <div><dt>État</dt><dd><?= Helpers::e($task['etat'] ?? '-') ?></dd></div>
        <div><dt>Début prévu</dt><dd><?= Helpers::e($task['date_debut_prevue'] ?? '-') ?></dd></div>
        <div><dt>Fin prévue</dt><dd><?= Helpers::e($task['date_fin_prevue'] ?? '-') ?></dd></div>
        <div><dt>Estimation</dt><dd><?= Helpers::e($task['estimation_heures'] ?? '-') ?> h</dd></div>
        <div><dt>Email agent</dt><dd><?= Helpers::e($task['email_professionnel'] ?? '-') ?></dd></div>
    </dl>
</section>
