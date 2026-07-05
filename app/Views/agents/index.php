<?php $departements = $departements ?? []; ?>
<div class="page-head">
    <div>
        <h1><?= App\Core\Helpers::e($title) ?></h1>
        <p>Gestion du module Agents</p>
    </div><button class="btn-primary" onclick="openModal('modalForm')">+ Ajouter</button>
</div>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>nom</th>
                <th>prenom</th>
                <th>etat</th>
                <th>date_embauche</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?><tr>
                    <td><?= App\Core\Helpers::e($item['nom'] ?? '') ?></td>
                    <td><?= App\Core\Helpers::e($item['prenom'] ?? '') ?></td>
                    <td><?= App\Core\Helpers::e($item['etat'] ?? '') ?></td>
                    <td><?= App\Core\Helpers::e($item['date_embauche'] ?? '') ?></td>
                    <td>
                        <form method="post" action="<?= App\Core\Helpers::url('agents/delete') ?>"><input type="hidden" name="id" value="<?= $item['agent_id'] ?>"><button class="btn-danger">Supprimer</button></form>
                    </td>
                </tr><?php endforeach; ?>
        </tbody>
    </table>
</div>
<div id="modalForm" class="modal">
    <div class="modal-box">
        <h2>Ajouter</h2>
        <form method="post" action="<?= App\Core\Helpers::url('agents/store') ?>" enctype="multipart/form-data">
            <label>nom<input name="nom" class="form-control" required></label>
            <label>prenom<input name="prenom" class="form-control" required></label>
            <label>date_naissance<input name="date_naissance" type="date" class="form-control"></label>
            <label>adresse<textarea name="adresse" class="form-control"></textarea></label>
            <label>telephone_personnel<input name="telephone_personnel" class="form-control"></label>
            <label>telephone_professionnel<input name="telephone_professionnel" class="form-control"></label>
            <label>email_personnel<input name="email_personnel" type="email" class="form-control"></label>
            <label>email_professionnel<input name="email_professionnel" type="email" class="form-control"></label>
            <label>password<input name="password" type="password" class="form-control"></label>
            <label>departement_id<select name="departement_id" class="form-control">
                    <option value="">Sélectionner un département</option>
                    <?php foreach ($departements as $departement): ?>
                        <option value="<?= (int) ($departement['departement_id'] ?? 0) ?>">
                            <?= App\Core\Helpers::e(trim(($departement['code'] ?? '') . ' - ' . ($departement['nom'] ?? ''), ' -')) ?>
                        </option>
                    <?php endforeach; ?>
                </select></label>
            <label>situation_matrimoniale<input name="situation_matrimoniale" class="form-control"></label>
            <label>nombre_enfants<input name="nombre_enfants" type="number" class="form-control"></label>
            <label>urgence_nom<input name="urgence_nom" class="form-control"></label>
            <label>urgence_telephone<input name="urgence_telephone" class="form-control"></label>
            <label class="file-preview-field">photo_profil<input name="photo_profil" type="file" accept="image/*" class="form-control"><img class="upload-preview" alt="Prévisualisation photo de profil" hidden></label>
            <label>cv_path<input name="cv_path" class="form-control"></label>
            <label>etat<select name="etat" class="form-control"><option value="Actif">Actif</option><option value="Inactif">Inactif</option><option value="Suspendu">Suspendu</option></select></label>
            <label>date_embauche<input name="date_embauche" type="date" class="form-control"></label>
            <label>date_depart<input name="date_depart" type="date" class="form-control"></label>
            <div class="actions"><button type="button" onclick="closeModal('modalForm')">Annuler</button><button class="btn-primary">Enregistrer</button></div>
        </form>
    </div>
</div>
