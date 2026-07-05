<div class="page-head"><div><h1><?= App\Core\Helpers::e($title) ?></h1><p>Gestion du module Departements</p></div><button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button></div>
<div class="card"><table><thead><tr><th>code</th><th>nom</th><th>description</th><th>Actions</th></tr></thead><tbody>
<?php foreach($items as $item): ?><tr><td><?= App\Core\Helpers::e($item['code'] ?? '') ?></td><td><?= App\Core\Helpers::e($item['nom'] ?? '') ?></td><td><?= App\Core\Helpers::e($item['description'] ?? '') ?></td><td><form method="post" action="<?= App\Core\Helpers::url('departements/delete') ?>"><input type="hidden" name="id" value="<?= $item['departement_id'] ?>"><button class="btn-danger">Supprimer</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<div id="modalForm" class="modal"><div class="modal-box"><h2>Ajouter</h2><form method="post" action="<?= App\Core\Helpers::url('departements/store') ?>"><label>code<input name="code" class="form-control"></label>
<label>nom<input name="nom" class="form-control" required></label>
<label>description<textarea name="description" class="form-control"></textarea></label>
<label>responsable_id<input name="responsable_id" type="number" class="form-control"></label><div class="actions"><button type="button" onclick="closeModal('modalForm')">Annuler</button><button class="btn-primary">Enregistrer</button></div></form></div></div>
