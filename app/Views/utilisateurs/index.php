<?php

use App\Core\Helpers;

$roles = $roles ?? [];
$items = $items ?? [];
?>
<section class="module-shell">
    <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <a class="breadcrumb-item" href="<?= Helpers::url('dashboard') ?>">Dashboard</a>
        <span class="breadcrumb-separator">›</span>
        <span class="breadcrumb-item active">Utilisateurs</span>
    </nav>

    <div class="module-head">
        <div>
            <span class="module-kicker">Administration</span>
            <h1>Gestion des Utilisateurs</h1>
            <p>Gérez les comptes, accès et rôles de l'équipe Evotech.</p>
        </div>
        <button class="btn-primary" onclick="openModal('modalForm')">Nouvel utilisateur</button>
    </div>

    <div class="toolbar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input class="search-input" type="search" data-table-search="#usersTable" placeholder="Rechercher un nom, email ou rôle...">
        </div>
        <div class="toolbar-summary">
            <span><?= count($items) ?> utilisateur(s)</span>
            <span><?= count($roles) ?> rôle(s)</span>
        </div>
    </div>

    <div class="module-grid">
        <div class="table-card">
            <div class="table-card-head">
                <div>
                    <h2>Comptes utilisateurs</h2>
                    <p>Liste des accès actifs dans le système.</p>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôles</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $user): ?>
                            <?php
                            $fullName = trim(($user['nom'] ?? '') . ' ' . ($user['prenom'] ?? ''));
                            $initials = strtoupper(substr((string) ($user['nom'] ?? 'U'), 0, 1) . substr((string) ($user['prenom'] ?? ''), 0, 1));
                            $profilePhoto = $user['profil'] ?? '';
                            $search = strtolower($fullName . ' ' . ($user['email'] ?? '') . ' ' . ($user['roles'] ?? ''));
                            ?>
                            <tr data-record-id="<?= (int) ($user['utilisateur_id'] ?? 0) ?>"
                                data-edit-payload="<?= Helpers::e(json_encode([
                                    'nom' => $user['nom'] ?? '',
                                    'prenom' => $user['prenom'] ?? '',
                                    'email' => $user['email'] ?? '',
                                    'profil' => $user['profil'] ?? '',
                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>"
                                data-search="<?= Helpers::e($search) ?>">
                                <td>
                                    <div class="identity-cell">
                                        <?php if ($profilePhoto): ?>
                                            <img class="identity-avatar" src="<?= Helpers::e(Helpers::url($profilePhoto)) ?>" alt="<?= Helpers::e($fullName ?: 'Utilisateur') ?>">
                                        <?php else: ?>
                                            <span class="identity-avatar"><?= Helpers::e($initials ?: 'U') ?></span>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= Helpers::e($fullName ?: 'Utilisateur') ?></strong>
                                            <small>ID #<?= (int) ($user['utilisateur_id'] ?? 0) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= Helpers::e($user['email'] ?? '') ?></td>
                                <td><span class="badge info"><?= Helpers::e($user['roles'] ?? 'Aucun rôle') ?></span></td>
                                <td><span class="badge <?= !empty($user['actif']) ? 'success' : 'muted' ?>"><?= !empty($user['actif']) ? 'Actif' : 'Inactif' ?></span></td>
                                <td>
                                    <form method="post" action="<?= Helpers::url('utilisateurs/delete') ?>" class="inline-form">
                                        <input type="hidden" name="id" value="<?= (int) ($user['utilisateur_id'] ?? 0) ?>">
                                        <button class="action-btn delete" title="Supprimer" aria-label="Supprimer <?= Helpers::e($fullName ?: 'cet utilisateur') ?>">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="side-panel">
            <div class="side-panel-head">
                <i class="fa-solid fa-user-shield"></i>
                <div>
                    <h2>Rôles</h2>
                    <p>Répartition disponible</p>
                </div>
            </div>
            <div class="role-list">
                <?php foreach ($roles as $role): ?>
                    <div class="role-item">
                        <span><?= Helpers::e($role['fonction'] ?? 'Rôle') ?></span>
                        <small>#<?= (int) ($role['role_id'] ?? 0) ?></small>
                    </div>
                <?php endforeach; ?>
                <?php if (!$roles): ?>
                    <div class="empty-mini">Aucun rôle enregistré.</div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<div id="modalForm" class="modal">
    <div class="modal-box">
        <div class="modal-titlebar">
            <div>
                <h2>Nouvel utilisateur</h2>
                <p>Créez un accès pour un membre de l'équipe.</p>
            </div>
            <button type="button" class="modal-x" onclick="closeModal('modalForm')" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="post" action="<?= Helpers::url('utilisateurs/store') ?>" enctype="multipart/form-data">
            <label>Nom<input name="nom" required></label>
            <label>Prénom<input name="prenom" required></label>
            <label>Email<input name="email" type="email" required></label>
            <label class="file-preview-field">Photo de profil<input name="profil" type="file" accept="image/*"><img class="upload-preview" alt="Prévisualisation photo de profil" hidden></label>
            <label>Mot de passe<input name="password" type="password"></label>
            <div class="actions">
                <button type="button" onclick="closeModal('modalForm')">Annuler</button>
                <button class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
