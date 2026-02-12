<?php
// login.php (racine du projet)

// Inclure la connexion à la base de données
require_once 'connexion/connexion.php';
// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    // Rediriger selon le rôle
    redirectBasedOnRole();
    exit();
}

// Activer le mode erreur pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// Vérifier si la connexion est établie
if (!$conn) {
    die("Erreur de connexion à la base de données.");
}

// Variables pour le formulaire
$email = '';
$error = '';
$userType = 'utilisateur'; // Par défaut

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? 'utilisateur';
    $remember = isset($_POST['remember']);

    // Validation des champs
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            if ($userType === 'agent') {
                // Connexion comme AGENT
                $query = "SELECT 
                            a.*,
                            d.nom as departement_nom,
                            d.code as departement_code
                          FROM AGENTS a
                          LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id
                          WHERE a.email_professionnel = :email 
                          AND a.statut = 0";

                $stmt = $conn->prepare($query);
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Pour le développement : vérifier si le mot de passe est hashé ou en clair
                    $passwordValid = false;

                    // Vérifier si c'est un hash bcrypt/Argon2
                    if (password_verify($password, $user['mot_de_passe_hash'])) {
                        $passwordValid = true;
                    }
                    // Sinon, vérifier en texte clair (pour le développement seulement)
                    elseif ($password === $user['mot_de_passe_hash']) {
                        $passwordValid = true;
                        // Recommander de hacher le mot de passe
                        error_log("ATTENTION: Mot de passe en clair pour l'agent ID: " . $user['agent_id']);
                    }

                    if ($passwordValid) {
                        // Vérifier si le compte est actif
                        if ($user['etat'] === 'Actif') {
                            // Créer la session
                            $_SESSION['agent_id'] = $user['agent_id'];
                            $_SESSION['user_id'] = $user['agent_id'];
                            $_SESSION['user_type'] = 'agent';
                            $_SESSION['nom'] = $user['nom'];
                            $_SESSION['prenom'] = $user['prenom'];
                            $_SESSION['email'] = $user['email_professionnel'];
                            $_SESSION['departement_id'] = $user['departement_id'];
                            $_SESSION['departement_nom'] = $user['departement_nom'];
                            $_SESSION['role'] = 'Agent';
                            $_SESSION['photo_profil'] = $user['photo_profil'] ?? '';
                            $_SESSION['last_login'] = date('Y-m-d H:i:s');

                            // Si "se souvenir de moi" est coché
                            if ($remember) {
                                setcookie('evotech_remember', $user['agent_id'], time() + (30 * 24 * 60 * 60), '/');
                            }

                            // Redirection vers le dashboard des agents
                            header('Location: Views/dashboard-agent.php');
                            exit();
                        } else {
                            $error = "Votre compte est " . $user['etat'] . ". Contactez l'administrateur.";
                        }
                    } else {
                        $error = "Email ou mot de passe incorrect.";
                    }
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            } else {
                // Connexion comme UTILISATEUR (admin, chef, etc.)
                $query = "SELECT * FROM UTILISATEURS WHERE email = :email AND statut = 0";
                $stmt = $conn->prepare($query);
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Pour le développement : vérifier si le mot de passe est hashé ou en clair
                    $passwordValid = false;

                    // Vérifier si c'est un hash bcrypt/Argon2
                    if (password_verify($password, $user['mot_de_passe_hash'])) {
                        $passwordValid = true;
                    }
                    // Sinon, vérifier en texte clair (pour le développement seulement)
                    elseif ($password === $user['mot_de_passe_hash']) {
                        $passwordValid = true;
                        error_log("ATTENTION: Mot de passe en clair pour l'utilisateur ID: " . $user['utilisateur_id']);
                    }

                    if ($passwordValid) {
                        // Vérifier si le compte est actif
                        if ($user['actif']) {
                            // Récupérer les rôles de l'utilisateur
                            $rolesQuery = "SELECT r.fonction 
                                         FROM ROLES r
                                         JOIN ATTRIBUTIONS a ON r.role_id = a.role_id
                                         WHERE a.utilisateur_id = :user_id 
                                         AND a.statut = 0 
                                         AND r.statut = 0";
                            $rolesStmt = $conn->prepare($rolesQuery);
                            $rolesStmt->execute([':user_id' => $user['utilisateur_id']]);
                            $userRoles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN, 0);

                            // Déterminer le rôle principal
                            $mainRole = 'Utilisateur';
                            if (in_array('DG', $userRoles)) {
                                $mainRole = 'DG';
                            } elseif (in_array('ChefDepartement', $userRoles)) {
                                $mainRole = 'ChefDepartement';
                            } elseif (in_array('Chef_projet', $userRoles)) {
                                $mainRole = 'Chef_projet';
                            } elseif (in_array('comptable', $userRoles)) {
                                $mainRole = 'comptable';
                            } elseif (in_array('actionnaire', $userRoles)) {
                                $mainRole = 'actionnaire';
                            }

                            // Créer la session
                            $_SESSION['user_id'] = $user['utilisateur_id'];
                            $_SESSION['user_type'] = 'utilisateur';
                            $_SESSION['nom'] = $user['nom'];
                            $_SESSION['prenom'] = $user['prenom'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['profil'] = $user['profil'] ?? '';
                            $_SESSION['roles'] = $userRoles;
                            $_SESSION['role'] = $mainRole;
                            $_SESSION['last_login'] = date('Y-m-d H:i:s');

                            // Si "se souvenir de moi" est coché
                            if ($remember) {
                                setcookie('evotech_remember', $user['utilisateur_id'], time() + (30 * 24 * 60 * 60), '/');
                            }

                            // Redirection basée sur le rôle
                            redirectBasedOnRole($userRoles);
                            exit();
                            
                        } else {
                            $error = "Votre compte est désactivé. Contactez l'administrateur.";
                        }
                    } else {
                        $error = "Email ou mot de passe incorrect.";
                    }
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            }
        } catch (Exception $e) {
            $error = "Erreur technique: " . $e->getMessage();
            error_log("Erreur de connexion: " . $e->getMessage());
        }
    }
}

// Fonction de redirection basée sur le rôle
function redirectBasedOnRole($roles = null) {
    if ($roles === null && isset($_SESSION['roles'])) {
        $roles = $_SESSION['roles'];
    }
    
    if (in_array('DG', $roles)) {
        header('Location: Views/dashboard-admin.php');
    } elseif (in_array('ChefDepartement', $roles)) {
        header('Location: Views/dashboard-chef.php');
    } elseif (in_array('Chef_projet', $roles)) {
        header('Location: Views/dashboard-chef-projet.php');
    } elseif (in_array('comptable', $roles)) {
        header('Location: Views/dashboard-comptable.php');
    } else {
        header('Location: Views/dashboard.php');
    }
    exit();
}

// Vérifier si l'utilisateur a un cookie "se souvenir de moi"
if (empty($_SESSION['user_id']) && isset($_COOKIE['evotech_remember'])) {
    $userId = $_COOKIE['evotech_remember'];
    // Rediriger vers le dashboard général
    header('Location: Views/dashboard.php');
    exit();
}

// Désactiver l'affichage des erreurs après le débogage
ini_set('display_errors', 0);
?>

<!DOCTYPE html>
<html lang="fr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EvoTech Africa Manager</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1e40af;
            --secondary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            max-width: 450px;
            margin: 0 auto;
        }
        
        .login-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .login-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: white;
            border-radius: 2px;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .logo-container {
            margin-bottom: 20px;
        }
        
        .logo-img {
            max-height: 70px;
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: 700;
            color: white;
            letter-spacing: 1px;
        }
        
        .form-control {
            padding: 14px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            border: none;
            padding: 16px 30px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(30, 64, 175, 0.3);
        }
        
        .user-type-btn {
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            background: white;
            padding: 15px;
            border-radius: 12px;
            cursor: pointer;
            text-align: center;
            height: 100%;
        }
        
        .user-type-btn:hover {
            border-color: #3b82f6;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(30, 64, 175, 0.1);
        }
        
        .user-type-input:checked + .user-type-btn {
            border-color: #3b82f6;
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.05), rgba(59, 130, 246, 0.05));
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 10px 20px rgba(30, 64, 175, 0.15);
        }
        
        .type-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 20px;
        }
        
        .admin-icon {
            background: rgba(30, 64, 175, 0.1);
            color: #1e40af;
        }
        
        .agent-icon {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .security-badge {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 12px;
            color: #10b981;
            display: inline-flex;
            align-items: center;
        }
        
        .security-badge i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .login-container {
                max-width: 100%;
            }
            
            .login-header,
            .login-body {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="login-container">
                    <!-- En-tête -->
                    <div class="login-header">
                        <div class="logo-container">
                            <img src="images/logo.jpg" alt="EvoTech Africa" class="logo-img">
                            <div class="company-name">EVOTECH AFRICA</div>
                        </div>
                        <h5 class="mb-0">Système de Gestion Intégré</h5>
                        <small class="opacity-90">Accès sécurisé aux modules</small>
                    </div>

                    <!-- Corps du formulaire -->
                    <div class="login-body">
                        <!-- Messages d'erreur -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-3"></i>
                                    <div>
                                        <strong>Erreur de connexion</strong>
                                        <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Information sur les rôles -->
                        <div class="alert alert-info mb-4">
                            <strong><i class="fas fa-info-circle me-2"></i>Rôles et accès</strong>
                            <p class="mb-0 small">
                                • <strong>DG/Admin</strong>: Accès complet à tous les modules<br>
                                • <strong>Chef de Département</strong>: Gestion de département & contrats<br>
                                • <strong>Chef de Projet</strong>: Gestion des projets & productions<br>
                                • <strong>Comptable</strong>: Module finance & caisse<br>
                                • <strong>Agent</strong>: Timesheet & tâches
                            </p>
                        </div>

                        <!-- Formulaire de connexion -->
                        <form action="login.php" method="POST" id="loginForm">
                            <!-- Sélection du type d'utilisateur -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-3">
                                    <i class="fas fa-user-tag me-2 text-primary"></i>
                                    Type de compte
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="radio" name="user_type" value="utilisateur" id="typeUtilisateur"
                                            class="user-type-input d-none" <?= $userType === 'utilisateur' ? 'checked' : '' ?>>
                                        <label for="typeUtilisateur" class="user-type-btn">
                                            <div class="type-icon admin-icon">
                                                <i class="fas fa-user-shield"></i>
                                            </div>
                                            <h6 class="fw-bold mb-1">Administrateur/Staff</h6>
                                            <small class="text-muted d-block">
                                                DG, Chefs, Comptables
                                            </small>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" name="user_type" value="agent" id="typeAgent"
                                            class="user-type-input d-none" <?= $userType === 'agent' ? 'checked' : '' ?>>
                                        <label for="typeAgent" class="user-type-btn">
                                            <div class="type-icon agent-icon">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <h6 class="fw-bold mb-1">Employé/Agent</h6>
                                            <small class="text-muted d-block">
                                                Collaborateurs, Équipes
                                            </small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Champ Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    Adresse email professionnelle
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-at text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control border-start-0" id="email" name="email"
                                        value="<?= htmlspecialchars($email) ?>"
                                        placeholder="prenom.nom@evo.africa" required>
                                </div>
                            </div>

                            <!-- Champ Mot de passe -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2 text-primary"></i>
                                    Mot de passe
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-key text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="password" name="password"
                                        placeholder="Votre mot de passe" required>
                                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                                        <i class="fas fa-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Options supplémentaires -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        <i class="fas fa-history me-1"></i>
                                        Se souvenir de moi (30 jours)
                                    </label>
                                </div>
                            </div>

                            <!-- Bouton de connexion -->
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" class="btn btn-login text-white fw-semibold" id="submitBtn">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Se connecter
                                </button>
                            </div>

                            <!-- Information sécurité -->
                            <div class="text-center mb-4">
                                <span class="security-badge">
                                    <i class="fas fa-shield-alt"></i>
                                    Connexion sécurisée SSL/TLS
                                </span>
                            </div>
                        </form>

                        <!-- Support -->
                        <div class="text-center">
                            <p class="text-muted small mb-2">
                                <i class="fas fa-headset me-1"></i>
                                Support technique: support@evotech.africa
                            </p>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-copyright me-1"></i>
                                <?= date('Y') ?> EvoTech Africa • v1.0.0
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle affichage mot de passe
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });

        // Gestion de la soumission du formulaire
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            // Désactiver le bouton et montrer un indicateur de chargement
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Connexion en cours...
            `;

            return true;
        });

        // Auto-focus sur le champ email
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.getElementById('email').focus();
            }, 300);
        });

        // Animation sur les boutons de type
        document.querySelectorAll('.user-type-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                if (!this.previousElementSibling.checked) {
                    this.style.transform = 'translateY(-3px)';
                }
            });
            
            btn.addEventListener('mouseleave', function() {
                if (!this.previousElementSibling.checked) {
                    this.style.transform = 'translateY(0)';
                }
            });
        });

        // Vérification des champs
        document.getElementById('email').addEventListener('blur', function() {
            if (this.value && !this.value.includes('@evo.africa') && !this.value.includes('@')) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    </script>
</body>
</html>