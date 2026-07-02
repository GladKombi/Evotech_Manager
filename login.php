<?php
/**
 * Login.php
 * Page de connexion Evotech Africa
 */

session_start();

// Rediriger vers le dashboard si déjà connecté
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: views/dashboard.php');
    exit();
}

// Récupérer les messages d'erreur
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$success = $_SESSION['login_success'] ?? null;
unset($_SESSION['login_success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evotech Africa - Connexion</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f7fa;
            padding: 1.5rem;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
            border: 1px solid #eef2f6;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            padding: 2.5rem 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid #eef2f6;
        }

        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .logo-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #1a3a7a, #4a8fc7);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 12px rgba(26, 58, 122, 0.15);
        }

        .login-header h1 {
            color: #1a2a3a;
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 0.75rem;
        }

        .login-header p {
            color: #6a7a8a;
            font-size: 0.85rem;
            font-weight: 400;
        }

        .login-body {
            padding: 2rem 2rem 2rem;
        }

        .login-body .welcome {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a2a3a;
            margin-bottom: 0.25rem;
        }

        .login-body .subtitle {
            color: #6a7a8a;
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
        }

        /* Alert Messages */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #065f46;
            border: 1px solid #bbf7d0;
        }

        .alert i {
            font-size: 1rem;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #1a2a3a;
            margin-bottom: 0.4rem;
        }

        .form-group label .required {
            color: #dc2626;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8a9aaa;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.8rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fafbfc;
            color: #1a2a3a;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #4a8fc7;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(74, 143, 199, 0.08);
        }

        .input-wrapper input::placeholder {
            color: #aab8c8;
        }

        .input-wrapper input:focus + i,
        .input-wrapper input:focus ~ i {
            color: #4a8fc7;
        }

        .input-wrapper .input-icon {
            color: #8a9aaa;
            transition: color 0.3s ease;
        }

        .input-wrapper:focus-within .input-icon {
            color: #4a8fc7;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #8a9aaa;
            cursor: pointer;
            font-size: 0.95rem;
            padding: 0.25rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #1a3a7a;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.85rem;
            color: #4a5a6a;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 17px;
            height: 17px;
            accent-color: #4a8fc7;
            cursor: pointer;
            border-radius: 4px;
        }

        .forgot-link {
            color: #4a8fc7;
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #1a3a7a;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #1a3a7a, #4a8fc7);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 2px 8px rgba(26, 58, 122, 0.15);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(26, 58, 122, 0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-login.loading .spinner {
            display: block;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: #6a7a8a;
        }

        .login-footer a {
            color: #4a8fc7;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #1a3a7a;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            color: #8a9aaa;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .social-login {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .social-btn {
            width: 44px;
            height: 44px;
            border-radius: 0.75rem;
            border: 1.5px solid #e2e8f0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #4a5a6a;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            border-color: #4a8fc7;
            color: #4a8fc7;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 143, 199, 0.1);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-body {
                padding: 1.5rem 1.25rem 1.5rem;
            }
            
            .login-header {
                padding: 2rem 1.25rem 1.25rem;
            }

            .login-header h1 {
                font-size: 1.25rem;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .login-body .welcome {
                font-size: 1.1rem;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f1724;
            }

            .login-container {
                background: #1a2438;
                border-color: #2a3a4a;
            }

            .login-header {
                border-bottom-color: #2a3a4a;
            }

            .login-header h1 {
                color: #e8ecf0;
            }

            .login-header p {
                color: #8a9aaa;
            }

            .login-body .welcome {
                color: #e8ecf0;
            }

            .login-body .subtitle {
                color: #8a9aaa;
            }

            .input-wrapper input {
                background: #0f1724;
                border-color: #2a3a4a;
                color: #e8ecf0;
            }

            .input-wrapper input:focus {
                background: #1a2438;
                border-color: #4a8fc7;
            }

            .input-wrapper input::placeholder {
                color: #5a6a7a;
            }

            .input-wrapper i {
                color: #5a6a7a;
            }

            .form-group label {
                color: #c8d8e8;
            }

            .checkbox-wrapper {
                color: #8a9aaa;
            }

            .forgot-link {
                color: #6aafd7;
            }

            .forgot-link:hover {
                color: #8ab8e8;
            }

            .login-footer {
                color: #6a8aaa;
            }

            .login-footer a {
                color: #6aafd7;
            }

            .login-footer a:hover {
                color: #8ab8e8;
            }

            .social-btn {
                background: #0f1724;
                border-color: #2a3a4a;
                color: #8a9aaa;
            }

            .social-btn:hover {
                border-color: #4a8fc7;
                color: #6aafd7;
            }

            .divider::before,
            .divider::after {
                background: #2a3a4a;
            }

            .divider span {
                color: #5a6a7a;
            }

            .alert-error {
                background: #1a0a0a;
                color: #fca5a5;
                border-color: #3a1a1a;
            }

            .alert-success {
                background: #0a1a0a;
                color: #6ee7b7;
                border-color: #1a3a1a;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-container">
                <div class="logo-icon">EA</div>
                <h1>Evotech Africa</h1>
                <p>Contribuer à l'évolution technologique en Afrique</p>
            </div>
        </div>

        <!-- Body -->
        <div class="login-body">
            <div class="welcome">Bienvenue 👋</div>
            <p class="subtitle">Connectez-vous pour accéder à votre espace de travail</p>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form id="loginForm" method="POST" action="models/login.php" x-data="loginForm()" @submit.prevent="submit">
                <div class="form-group">
                    <label for="email">
                        Adresse email <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="exemple@evotech-africa.net" 
                            required
                            x-model="email"
                        >
                    </div>
                    <template x-if="errors.email">
                        <p style="color: #dc2626; font-size: 0.8rem; margin-top: 0.3rem;" x-text="errors.email"></p>
                    </template>
                </div>

                <div class="form-group">
                    <label for="password">
                        Mot de passe <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            :type="showPassword ? 'text' : 'password'"
                            id="password" 
                            name="mot_de_passe" 
                            placeholder="••••••••" 
                            required
                            minlength="6"
                            x-model="password"
                        >
                        <button 
                            type="button" 
                            class="password-toggle"
                            @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        >
                            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                    <template x-if="errors.password">
                        <p style="color: #dc2626; font-size: 0.8rem; margin-top: 0.3rem;" x-text="errors.password"></p>
                    </template>
                </div>

                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember" x-model="remember">
                        <span>Se souvenir de moi</span>
                    </label>
                    <a href="#" class="forgot-link">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn-login" :disabled="loading" :class="{ 'loading': loading }">
                    <span class="spinner"></span>
                    <span class="btn-text">
                        Se connecter
                    </span>
                </button>
            </form>

            <!-- Footer -->
            <div class="login-footer">
                <p>
                    Vous n'avez pas de compte ? 
                    <a href="#">Contacter l'administrateur</a>
                </p>
                <p style="margin-top: 0.5rem; font-size: 0.75rem; color: #8a9aaa;">
                    &copy; <?php echo date('Y'); ?> Evotech Africa
                </p>
            </div>
        </div>
    </div>

    <script>
        function loginForm() {
            return {
                email: '',
                password: '',
                remember: false,
                showPassword: false,
                loading: false,
                errors: {},
                
                submit() {
                    // Réinitialiser les erreurs
                    this.errors = {};
                    this.loading = true;

                    // Validation
                    if (!this.email) {
                        this.errors.email = 'L\'adresse email est requise';
                    } else if (!this.isValidEmail(this.email)) {
                        this.errors.email = 'Veuillez entrer une adresse email valide';
                    }

                    if (!this.password) {
                        this.errors.password = 'Le mot de passe est requis';
                    } else if (this.password.length < 6) {
                        this.errors.password = 'Le mot de passe doit contenir au moins 6 caractères';
                    }

                    // Si erreurs, arrêter
                    if (Object.keys(this.errors).length > 0) {
                        this.loading = false;
                        return;
                    }

                    // Soumettre le formulaire
                    document.getElementById('loginForm').submit();
                },

                isValidEmail(email) {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                }
            }
        }
    </script>
</body>
</html>