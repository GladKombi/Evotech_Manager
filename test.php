<?php
// login.php (racine du projet)

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: Views/dashboard.php');
    exit();
}

// Inclure la connexion à la base de données
require_once 'connexion/connexion.php';

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
                    // Vérifier le mot de passe (dans un cas réel, utiliser password_verify)
                    // Pour le développement, comparer directement si pas encore hashé
                    if ($password === $user['mot_de_passe_hash'] || password_verify($password, $user['mot_de_passe_hash'])) {
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
                            $_SESSION['photo_profil'] = $user['photo_profil'];
                            $_SESSION['last_login'] = date('Y-m-d H:i:s');

                            // Si "se souvenir de moi" est coché
                            if ($remember) {
                                setcookie('evotech_remember', $user['agent_id'], time() + (30 * 24 * 60 * 60), '/');
                            }

                            // Redirection
                            header('Location: Views/dashboard.php');
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
                $query = "SELECT 
                            u.*,
                            d.nom as departement_nom
                          FROM UTILISATEURS u
                          LEFT JOIN DEPARTEMENTS d ON u.departement_id = d.departement_id
                          WHERE u.email = :email 
                          AND u.statut = 0";

                $stmt = $conn->prepare($query);
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Vérifier le mot de passe
                    if ($password === $user['mot_de_passe_hash'] || password_verify($password, $user['mot_de_passe_hash'])) {
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
                            $_SESSION['profil'] = $user['profil'];
                            $_SESSION['roles'] = $userRoles;
                            $_SESSION['role'] = $mainRole;
                            $_SESSION['departement_id'] = $user['departement_id'];
                            $_SESSION['departement_nom'] = $user['departement_nom'];
                            $_SESSION['last_login'] = date('Y-m-d H:i:s');

                            // Si "se souvenir de moi" est coché
                            if ($remember) {
                                setcookie('evotech_remember', $user['utilisateur_id'], time() + (30 * 24 * 60 * 60), '/');
                            }

                            // Redirection
                            header('Location: Views/dashboard.php');
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
            error_log("Erreur de connexion: " . $e->getMessage());
            $error = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}

// Vérifier si l'utilisateur a un cookie "se souvenir de moi"
if (empty($_SESSION['user_id']) && isset($_COOKIE['evotech_remember'])) {
    $userId = $_COOKIE['evotech_remember'];
    // Ici, vous devriez vérifier le token dans la base de données
    // Pour l'instant, on redirige vers le dashboard
    // header('Location: Views/dashboard.php');
    // exit();
}
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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #1e40af;
            --secondary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(30, 64, 175, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-body {
            padding: 40px 30px;
        }

        .company-logo {
            background: white;
            color: #1e40af;
            font-weight: bold;
            font-size: 24px;
            padding: 10px 25px;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(30, 64, 175, 0.2);
        }

        .form-control {
            padding: 12px 20px;
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
            padding: 14px 30px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(30, 64, 175, 0.3);
        }

        .user-type-btn {
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            background: white;
            padding: 20px;
            border-radius: 15px;
            cursor: pointer;
            text-align: center;
            height: 100%;
        }

        .user-type-btn:hover {
            border-color: #3b82f6;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(30, 64, 175, 0.1);
        }

        .user-type-input:checked+.user-type-btn {
            border-color: #3b82f6;
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.05), rgba(59, 130, 246, 0.05));
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 10px 30px rgba(30, 64, 175, 0.15);
        }

        .login-footer {
            background: #f8fafc;
            padding: 20px 30px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
            }

            70% {
                box-shadow: 0 0 0 15px rgba(59, 130, 246, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }

        /* Animation pour le formulaire */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .login-header,
            .login-body {
                padding: 30px 20px;
            }

            .company-logo {
                font-size: 20px;
                padding: 8px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="login-container animate-fade-in">
                    <!-- En-tête -->
                    <div class="login-header">
                        <div class="company-logo pulse">
                            <img src="images/logo.jpg" height="30" width="40" alt="">
                            <!-- <i class="fas fa-rocket me-2"></i>EVOTECH AFRICA -->
                        </div>

                    </div>

                    <!-- Corps du formulaire -->
                    <div class="login-body">
                        <!-- Messages d'erreur -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-3 fs-5"></i>
                                    <div>
                                        <strong>Erreur de connexion</strong>
                                        <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Formulaire de connexion -->
                        <form action="login.php" method="POST" id="loginForm">
                            <!-- Sélection du type d'utilisateur -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-3 d-flex align-items-center">
                                    <i class="fas fa-user-tag me-2 text-primary"></i>
                                    Profil:
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="radio" name="user_type" value="utilisateur" id="typeUtilisateur"
                                            class="user-type-input d-none" <?= $userType === 'utilisateur' ? 'checked' : '' ?>>
                                        <label for="typeUtilisateur" class="user-type-btn">
                                            <div class="w-16 h-16 d-flex align-items-center justify-content-center mx-auto mb-3">
                                                <i class="fas fa-user-shield fs-3 text-primary"></i>
                                            </div>
                                            <h6 class="fw-bold mb-1">Administrateur/Staff</h6>
                                            <small class="text-muted d-block">
                                                DG, Chefs, Comptable, Actionnaire
                                            </small>
                                            <!-- <div class="mt-3">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-cog me-1"></i> Administration
                                                </span>
                                            </div> -->
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" name="user_type" value="agent" id="typeAgent"
                                            class="user-type-input d-none" <?= $userType === 'agent' ? 'checked' : '' ?>>
                                        <label for="typeAgent" class="user-type-btn">
                                            <div class="w-16 h-16 d-flex align-items-center justify-content-center mx-auto mb-3">
                                                <i class="fas fa-user-tie fs-3 text-success"></i>
                                            </div>
                                            <h6 class="fw-bold mb-1">Employé/Agent</h6>
                                            <small class="text-muted d-block">
                                                Collaborateurs, Timesheet, Projets
                                            </small>
                                            <!-- <div class="mt-3">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-tasks me-1"></i> Opérationnel
                                                </span>
                                            </div> -->
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Champ Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold d-flex align-items-center">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    E-mail pro
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-at text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control border-start-0" id="email" name="email"
                                        value="<?= htmlspecialchars($email) ?>"
                                        placeholder="exemple@evo.africa" required>
                                </div>
                                <div class="form-text mt-2">
                                    <i class="fas fa-info-circle me-1 text-primary"></i>
                                    Utilisez l'email fourni par EvoTech Africa
                                </div>
                            </div>

                            <!-- Champ Mot de passe -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold d-flex align-items-center">
                                    <i class="fas fa-lock me-2 text-primary"></i>
                                    Mot de passe
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-key text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="password" name="password"
                                        placeholder="Votre mot de passe sécurisé" required>
                                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                                        <i class="fas fa-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                                <!-- <div class="form-text mt-2">
                                    <i class="fas fa-shield-alt me-1 text-primary"></i>
                                    Votre mot de passe est chiffré et sécurisé
                                </div> -->
                            </div>

                            <!-- Options supplémentaires -->
                            <!-- <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        <i class="fas fa-history me-1"></i>
                                        Se souvenir de moi pendant 30 jours
                                    </label>
                                </div>
                            </div> -->

                            <!-- Bouton de connexion -->
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" class="btn btn-login text-white fw-semibold py-3" id="submitBtn">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Se connecter
                                </button>
                            </div>

                            <!-- Liens supplémentaires -->
                            <div class="text-center">
                                <a href="forgot-password.php" class="text-decoration-none text-muted me-4">
                                    <i class="fas fa-key me-1"></i> Mot de passe oublié ?
                                </a>
                                <a href="contact.php" class="text-decoration-none text-muted">
                                    <i class="fas fa-headset me-1"></i> Support technique
                                </a>
                            </div>
                        </form>

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
                    this.classList.add('active');
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                    this.classList.remove('active');
                }
            });

            // Animation au changement de type d'utilisateur
            const userTypeInputs = document.querySelectorAll('.user-type-input');
            userTypeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Animation sur les boutons
                    document.querySelectorAll('.user-type-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });

                    const label = document.querySelector(`label[for="${this.id}"]`);
                    label.classList.add('active');
                });

                // Initialiser l'état actif
                if (input.checked) {
                    const label = document.querySelector(`label[for="${input.id}"]`);
                    label.classList.add('active');
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

                // Validation simple
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;

                if (!email.includes('@') || !email.includes('.')) {
                    e.preventDefault();
                    showError('Veuillez entrer une adresse email valide.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    return false;
                }

                if (password.length < 6) {
                    e.preventDefault();
                    showError('Le mot de passe doit contenir au moins 6 caractères.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    return false;
                }

                return true;
            });

            // Fonction pour afficher les erreurs
            function showError(message) {
                // Créer une alerte Bootstrap
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3"></i>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

                // Insérer après le formulaire
                const form = document.getElementById('loginForm');
                form.parentNode.insertBefore(alertDiv, form.nextSibling);

                // Auto-suppression après 5 secondes
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }

            // Auto-focus sur le champ email
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    const emailField = document.getElementById('email');
                    if (emailField && !emailField.value) {
                        emailField.focus();
                    } else {
                        document.getElementById('password').focus();
                    }
                }, 300);
            });

            // Raccourcis clavier
            document.addEventListener('keydown', function(e) {
                // Ctrl + P pour aller au mot de passe
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    document.getElementById('password').focus();
                }

                // Alt + 1 pour utilisateur
                if (e.altKey && e.key === '1') {
                    e.preventDefault();
                    document.getElementById('typeUtilisateur').click();
                    document.getElementById('email').focus();
                }

                // Alt + 2 pour agent
                if (e.altKey && e.key === '2') {
                    e.preventDefault();
                    document.getElementById('typeAgent').click();
                    document.getElementById('email').focus();
                }

                // Enter pour soumettre si dans un champ
                if (e.key === 'Enter' &&
                    (document.activeElement === document.getElementById('email') ||
                        document.activeElement === document.getElementById('password'))) {
                    // La soumission se fera automatiquement via le formulaire
                }
            });

            // Effet visuel sur les champs
            const inputs = document.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        setTimeout(() => {
                            this.classList.remove('is-valid');
                        }, 2000);
                    }
                });

                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid', 'is-valid');
                });
            });

            // Prévenir la soumission multiple
            let formSubmitted = false;
            document.getElementById('loginForm').addEventListener('submit', function() {
                if (formSubmitted) {
                    return false;
                }
                formSubmitted = true;
                return true;
            });

            // Animation sur le logo
            const logo = document.querySelector('.company-logo');
            if (logo) {
                logo.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                    this.style.transition = 'transform 0.3s ease';
                });

                logo.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            }

            // Afficher un message de bienvenue dans la console
            console.log('%c🔒 EvoTech Africa - Connexion Sécurisée', 'color: #1e40af; font-size: 16px; font-weight: bold;');
            console.log('%c⚠️  Cette zone est réservée aux développeurs.', 'color: #ef4444;');
        </script>

</body>

</html>