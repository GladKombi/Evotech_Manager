<?php

/**
 * views/utilisateurs.php
 * Gestion des utilisateurs, rôles et attributions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/select/select-utilisateurs.php';

session_start();

// Vérification des droits
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../Login.php');
    exit();
}

if (!in_array($_SESSION['user_role'], ['DG', 'admin'])) {
    $_SESSION['error'] = "Accès non autorisé";
    header('Location: dashboard.php');
    exit();
}

$userName = $_SESSION['user_nom'] ?? 'Utilisateur';
$userEmail = $_SESSION['user_email'] ?? '';
$userInitials = strtoupper(substr($userName, 0, 2));

try {
    $pdo = getDBConnection();
    $utilisateurs = getUtilisateurs($pdo);
    $roles = getRoles($pdo);
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    $_SESSION['error'] = "Erreur de chargement des données";
    $utilisateurs = [];
    $roles = [];
}

// Message de succès/erreur
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evotech Africa - Gestion des Utilisateurs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* Styles identiques au dashboard */
        *,
        ::before,
        ::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --color-bg: #f0f2f5;
            --color-surface: #ffffff;
            --color-primary: #1a2a6c;
            --color-primary-light: #2a3a7c;
            --color-secondary: #c0392b;
            --color-accent: #f39c12;
            --color-gradient-start: #1a2a6c;
            --color-gradient-end: #c0392b;
            --color-text: #1a2a3a;
            --color-text-secondary: #4a5a6a;
            --color-text-muted: #8a9aaa;
            --color-border: #dce0e8;
            --color-success: #27ae60;
            --color-warning: #f39c12;
            --color-danger: #e74c3c;
            --shadow-md: 0 4px 6px -1px rgb(26 42 108 / 0.1);
            --radius-md: 0.75rem;
            --radius-xl: 1.25rem;
            --font-sans: 'Inter', system-ui, sans-serif;
            --font-display: 'Outfit', sans-serif;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--color-bg);
            color: var(--color-text);
        }

        /* Sidebar et layout identiques au dashboard */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 16rem;
            background: linear-gradient(180deg, var(--color-gradient-start), var(--color-gradient-end));
            z-index: 40;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            box-shadow: 2px 0 20px rgba(26, 42, 108, 0.15);
        }

        .sidebar-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo .logo-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: rgba(255, 255, 255, 0.15);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            font-family: var(--font-display);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-logo h1 {
            font-family: var(--font-display);
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            text-align: center;
        }

        .sidebar-logo span {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
        }

        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 200ms;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 600;
            box-shadow: inset 3px 0 0 var(--color-accent);
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }

        .user-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.15);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-info p {
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }

        .user-info span {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .logout-btn {
            margin-left: auto;
            color: rgba(255, 255, 255, 0.5);
            background: none;
            border: none;
            cursor: pointer;
        }

        .logout-btn:hover {
            color: var(--color-secondary);
        }

        .main-content {
            flex: 1;
            margin-left: 16rem;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-bar {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(240, 242, 245, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--color-border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
        }

        .page-subtitle {
            font-size: 0.875rem;
            color: var(--color-text-muted);
        }

        .content {
            padding: 2rem;
        }

        /* Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #e8f5e9;
            color: #1b5e20;
            border-left: 4px solid #27ae60;
        }

        .alert-danger {
            background: #ffebee;
            color: #b71c1c;
            border-left: 4px solid #e74c3c;
        }

        /* Boutons */
        .btn-primary {
            background: linear-gradient(135deg, var(--color-gradient-start), var(--color-gradient-end));
            color: #fff;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 200ms;
            box-shadow: 0 2px 8px rgba(26, 42, 108, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26, 42, 108, 0.35);
        }

        .btn-secondary {
            background: var(--color-secondary);
            color: #fff;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: #a93226;
        }

        .btn-sm {
            padding: 0.25rem 0.6rem;
            font-size: 0.7rem;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: #3498db;
            color: white;
        }

        .btn-edit:hover {
            background: #2980b9;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .btn-restore {
            background: #f39c12;
            color: white;
        }

        .btn-restore:hover {
            background: #d68910;
        }

        /* Table */
        .table-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
        }

        .table-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-header h3 {
            font-family: var(--font-display);
            font-size: 1.1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0.75rem 1.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--color-text-muted);
            background: var(--color-bg);
            border-bottom: 1px solid var(--color-border);
        }

        td {
            padding: 0.75rem 1.5rem;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text-secondary);
        }

        tr:hover td {
            background: #f8f9fa;
        }

        .badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            display: inline-block;
        }

        .badge-success {
            background: #e8f5e9;
            color: #1b5e20;
        }

        .badge-danger {
            background: #ffebee;
            color: #b71c1c;
        }

        .badge-primary {
            background: #e8ecf4;
            color: #1a2a6c;
        }

        .badge-warning {
            background: #fff3e0;
            color: #e65100;
        }

        .badge-info {
            background: #e3f2fd;
            color: #0d47a1;
        }

        .role-tag {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: var(--color-primary-soft, #e8ecf4);
            color: var(--color-primary);
            margin: 0.1rem;
        }

        .status-dot {
            display: inline-block;
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            margin-right: 0.4rem;
        }

        .status-dot.active {
            background: #27ae60;
        }

        .status-dot.inactive {
            background: #e74c3c;
        }

        .actions {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 100;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2rem;
            max-width: 600px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            font-family: var(--font-display);
            font-size: 1.3rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--color-text-muted);
        }

        .modal-close:hover {
            color: var(--color-text);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: var(--color-text);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            transition: border-color 200ms;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn-cancel {
            background: #e2e8f0;
            color: var(--color-text);
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background: #cbd5e1;
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--color-text);
            cursor: pointer;
            font-size: 1.5rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: stretch;
            }

            td,
            th {
                padding: 0.5rem 0.8rem;
                font-size: 0.75rem;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">EA</div>
                <div>
                    <h1>Evotech Africa</h1>
                    <span>Contribuer à l'évolution technologique</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Tableau de Bord
                </a>
                <a href="utilisateurs.php" class="nav-link active">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Utilisateurs
                </a>
                <a href="agents.php" class="nav-link">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Agents
                </a>
            </nav>

            <div class="sidebar-user">
                <div class="user-avatar"><?php echo htmlspecialchars($userInitials); ?></div>
                <div class="user-info">
                    <p><?php echo htmlspecialchars($userName); ?></p>
                    <span><?php echo htmlspecialchars($userEmail); ?></span>
                </div>
                <a href="../models/logout.php" class="logout-btn" title="Déconnexion">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div style="display:flex; align-items:center; gap:1rem;">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
                    <div>
                        <h2 class="page-title">Gestion des Utilisateurs</h2>
                        <p class="page-subtitle">Gérez les utilisateurs, rôles et attributions</p>
                    </div>
                </div>
                <button class="btn-primary" onclick="openModal('add')">+ Nouvel Utilisateur</button>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Tableau des utilisateurs -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Liste des Utilisateurs</h3>
                        <span style="font-size:0.85rem; color:var(--color-text-muted);">
                            Total: <?php echo count($utilisateurs); ?>
                        </span>
                    </div>
                    <table>
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
                            <?php if (empty($utilisateurs)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:2rem; color:var(--color-text-muted);">
                                        Aucun utilisateur trouvé
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($utilisateurs as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if ($user['roles']): ?>
                                                <?php foreach (explode(', ', $user['roles']) as $role): ?>
                                                    <span class="role-tag"><?php echo htmlspecialchars($role); ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span style="color:var(--color-text-muted); font-size:0.8rem;">Aucun rôle</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-dot <?php echo $user['actif'] ? 'active' : 'inactive'; ?>"></span>
                                            <?php echo $user['actif'] ? 'Actif' : 'Inactif'; ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <button class="btn-sm btn-edit" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">✏️</button>
                                                <button class="btn-sm btn-delete" onclick="deleteUser(<?php echo $user['utilisateur_id']; ?>)">🗑️</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout/Modification -->
    <div id="userModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">Nouvel Utilisateur</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <form id="userForm" method="POST" action="../models/traitement/utilisateur-post.php">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="utilisateur_id" id="userId" value="0">

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe <?php echo isset($_GET['edit']) ? '(laisser vide pour conserver)' : '*'; ?></label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" <?php echo isset($_GET['edit']) ? '' : 'required'; ?>>
                </div>

                <div class="form-group">
                    <label for="confirme_passe">Confirmer le mot de passe <?php echo isset($_GET['edit']) ? '(laisser vide pour conserver)' : '*'; ?></label>
                    <input type="password" id="confirme_passe" name="confirme_passe" <?php echo isset($_GET['edit']) ? '' : 'required'; ?>>
                </div>

                <div class="form-group">
                    <label>Rôles</label>
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem; padding-top:0.3rem;">
                        <?php foreach ($roles as $role): ?>
                            <label style="display:flex; align-items:center; gap:0.3rem; font-size:0.85rem; cursor:pointer;">
                                <input type="checkbox" name="roles[]" value="<?php echo $role['role_id']; ?>" class="role-checkbox">
                                <?php echo htmlspecialchars($role['fonction']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                        <input type="checkbox" name="actif" value="1" checked>
                        Utilisateur actif
                    </label>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal" style="max-width:400px;">
            <div class="modal-header">
                <h2>Confirmer la suppression</h2>
                <button class="modal-close" onclick="closeDeleteModal()">×</button>
            </div>
            <p style="margin-bottom:1.5rem;">Êtes-vous sûr de vouloir supprimer cet utilisateur ?</p>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Annuler</button>
                <a id="deleteLink" href="#" class="btn-primary" style="text-decoration:none; background:#e74c3c;">Supprimer</a>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function openModal(action, data = null) {
            const modal = document.getElementById('userModal');
            const form = document.getElementById('userForm');
            const title = document.getElementById('modalTitle');
            const actionInput = document.getElementById('formAction');
            const userId = document.getElementById('userId');

            modal.classList.add('active');

            if (action === 'add') {
                title.textContent = 'Nouvel Utilisateur';
                actionInput.value = 'add';
                userId.value = '0';
                form.reset();
                document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false);
                document.querySelector('input[name="actif"]').checked = true;
                document.getElementById('mot_de_passe').required = true;
                document.getElementById('confirme_passe').required = true;
                document.querySelector('label[for="mot_de_passe"]').textContent = 'Mot de passe *';
                document.querySelector('label[for="confirme_passe"]').textContent = 'Confirmer le mot de passe *';
            } else {
                title.textContent = 'Modifier l\'utilisateur';
                actionInput.value = 'edit';
                userId.value = data.utilisateur_id;
                document.getElementById('nom').value = data.nom || '';
                document.getElementById('prenom').value = data.prenom || '';
                document.getElementById('mot_de_passe').required = false;
                document.getElementById('confirme_passe').required = false;
                document.querySelector('label[for="mot_de_passe"]').textContent = 'Mot de passe (laisser vide pour conserver)';
                document.querySelector('label[for="confirme_passe"]').textContent = 'Confirmer le mot de passe (laisser vide pour conserver)';
                document.querySelector('input[name="actif"]').checked = data.actif == 1;
            }
        }

        function editUser(data) {
            // Charger les rôles actuels
            fetch('../models/select/select-utilisateurs.php?action=get_roles&user_id=' + data.utilisateur_id)
                .then(r => r.json())
                .then(roles => {
                    document.querySelectorAll('.role-checkbox').forEach(cb => {
                        cb.checked = roles.includes(parseInt(cb.value));
                    });
                })
                .catch(() => {
                    document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false);
                });

            openModal('edit', data);
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        function deleteUser(id) {
            document.getElementById('deleteLink').href = '../models/traitement/utilisateur-post.php?action=delete&id=' + id;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        // Fermer les modals en cliquant en dehors
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>

</html>