<?php

use App\Core\Auth;
use App\Core\Helpers;

$homeUrl = Helpers::url();
$dashboardUrl = Helpers::url('dashboard');
$loginUrl = Helpers::url('login');
$primaryUrl = Auth::check() ? $dashboardUrl : $loginUrl;
$primaryLabel = Auth::check() ? 'Retour au dashboard' : 'Se connecter';
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Page introuvable | Evotech Africa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #1a3a7a;
            --primary-dark: #0a2a5a;
            --secondary: #4a8fc7;
            --soft: #e8f0fe;
            --border: #d9e6f5;
            --text: #172033;
            --muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            color: var(--text);
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #e8f0fe 0%, #f8fbff 52%, #d4e4f7 100%);
        }

        .error-shell {
            width: min(920px, 100%);
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 0;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 26px 70px rgba(26, 58, 122, .16);
        }

        .error-code {
            min-height: 430px;
            display: grid;
            place-items: center;
            padding: 36px;
            color: #fff;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary) 58%, var(--secondary));
        }

        .error-code strong {
            font-size: clamp(92px, 15vw, 170px);
            line-height: .9;
            letter-spacing: 0;
        }

        .error-content {
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 24px;
        }

        .brand span {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
        }

        h1 {
            margin: 0;
            color: var(--primary);
            font-size: clamp(30px, 5vw, 46px);
            line-height: 1.08;
        }

        p {
            margin: 18px 0 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 18px;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--primary);
            background: #fff;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .btn.primary {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            box-shadow: 0 14px 28px rgba(26, 58, 122, .22);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px rgba(26, 58, 122, .14);
        }

        .hint {
            margin-top: 26px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 14px;
            color: var(--muted);
            background: var(--soft);
            font-size: 13px;
        }

        @media (max-width: 760px) {
            body {
                padding: 16px;
            }

            .error-shell {
                grid-template-columns: 1fr;
                border-radius: 18px;
            }

            .error-code {
                min-height: 220px;
            }

            .error-content {
                padding: 32px 24px;
            }

            .actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="error-shell" role="main">
        <section class="error-code" aria-label="Erreur 404">
            <strong>404</strong>
        </section>

        <section class="error-content">
            <div class="brand">
                <span>EA</span>
                Evotech Africa
            </div>

            <h1>Page introuvable</h1>
            <p>Le lien demandé n’existe pas, a été déplacé ou n’est plus disponible dans Evotech Manager.</p>

            <div class="actions">
                <a class="btn primary" href="<?= Helpers::e($primaryUrl) ?>">
                    <i class="fa-solid <?= Auth::check() ? 'fa-gauge-high' : 'fa-right-to-bracket' ?>"></i>
                    <?= Helpers::e($primaryLabel) ?>
                </a>
                <a class="btn" href="<?= Helpers::e($homeUrl) ?>">
                    <i class="fa-solid fa-house"></i>
                    Accueil
                </a>
                <button class="btn" type="button" onclick="history.back()">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour
                </button>
            </div>

            <div class="hint">
                Si le problème persiste, vérifiez l’adresse saisie ou revenez au menu principal.
            </div>
        </section>
    </main>
</body>

</html>
