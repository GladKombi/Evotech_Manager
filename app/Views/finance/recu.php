<?php

use App\Core\Helpers;

$operation = $operation ?? [];
$type = $type ?? 'mouvement';
$isTransfer = $type === 'transfert';

$receiptNumber = $isTransfer
    ? 'TRF-' . (int) ($operation['transfert_id'] ?? 0)
    : 'MVT-' . (int) ($operation['mouvement_id'] ?? 0);

$date = $isTransfer
    ? ($operation['date_transfert'] ?? '')
    : ($operation['date_mouvement'] ?? '');

$amount = number_format((float) ($operation['montant'] ?? 0), 2, ',', ' ');
$currency = $operation['devise'] ?? '';
$operator = $isTransfer
    ? trim(($operation['valideur_nom'] ?? '') . ' ' . ($operation['valideur_prenom'] ?? ''))
    : trim(($operation['createur_nom'] ?? '') . ' ' . ($operation['createur_prenom'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($title ?? 'Recu') ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #1a3a7a;
            --secondary: #4a8fc7;
            --soft: #e8f0fe;
            --border: #d9e6f5;
            --text: #172033;
            --muted: #64748b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', Arial, sans-serif;
            color: var(--text);
            background: linear-gradient(135deg, #e8f0fe 0%, #f7fbff 52%, #d4e4f7 100%);
            padding: 34px;
        }

        .print-bar {
            max-width: 900px;
            margin: 0 auto 18px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--primary);
            background: #fff;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .btn.primary {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
        }

        .receipt {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 24px 60px rgba(26, 58, 122, .14);
            overflow: hidden;
        }

        .receipt-head {
            display: flex;
            justify-content: space-between;
            gap: 22px;
            padding: 34px;
            color: #fff;
            background: linear-gradient(135deg, #0a2a5a, #1a3a7a 58%, #4a8fc7);
        }

        .brand h1,
        .receipt-title h2 {
            margin: 0;
            letter-spacing: 0;
        }

        .brand h1 { font-size: 28px; }
        .brand p,
        .receipt-title p {
            margin: 8px 0 0;
            opacity: .86;
        }

        .receipt-title {
            text-align: right;
        }

        .receipt-title h2 {
            font-size: 22px;
            text-transform: uppercase;
        }

        .receipt-body {
            padding: 34px;
            display: grid;
            gap: 24px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .summary-item,
        .detail-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #f8fbff;
            padding: 16px;
        }

        .label {
            display: block;
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .value {
            font-weight: 800;
            color: var(--primary);
        }

        .amount {
            font-size: 28px;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .detail-card.full {
            grid-column: 1 / -1;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-top: 18px;
        }

        .signature {
            min-height: 108px;
            border: 1px dashed #b0cce8;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            align-items: flex-end;
            color: var(--muted);
            font-weight: 700;
        }

        .receipt-foot {
            padding: 18px 34px;
            color: var(--muted);
            background: var(--soft);
            border-top: 1px solid var(--border);
            font-size: 13px;
        }

        @media (max-width: 720px) {
            body { padding: 18px; }
            .receipt-head,
            .summary,
            .details,
            .signatures {
                grid-template-columns: 1fr;
            }
            .receipt-head { display: grid; }
            .receipt-title { text-align: left; }
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }
            .print-bar { display: none; }
            .receipt {
                max-width: none;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <a class="btn" href="<?= Helpers::url($isTransfer ? 'transferts' : 'mouvements') ?>"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        <button class="btn primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer</button>
    </div>

    <main class="receipt">
        <header class="receipt-head">
            <div class="brand">
                <h1>Evotech Africa</h1>
                <p>Contribuer a l'evolution technologique en Afrique.</p>
            </div>
            <div class="receipt-title">
                <h2><?= Helpers::e($isTransfer ? 'Recu de transfert' : 'Recu de mouvement') ?></h2>
                <p><?= Helpers::e($receiptNumber) ?></p>
            </div>
        </header>

        <section class="receipt-body">
            <div class="summary">
                <div class="summary-item">
                    <span class="label">Date</span>
                    <span class="value"><?= Helpers::e($date ?: '-') ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Montant</span>
                    <span class="value amount"><?= Helpers::e(trim($amount . ' ' . $currency)) ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Reference</span>
                    <span class="value"><?= Helpers::e($isTransfer ? ($operation['reference_transfert'] ?? '-') : ($operation['reference_piece'] ?? '-')) ?></span>
                </div>
            </div>

            <?php if ($isTransfer): ?>
                <div class="details">
                    <div class="detail-card">
                        <span class="label">Compte source</span>
                        <span class="value"><?= Helpers::e($operation['compte_source'] ?? '-') ?></span>
                    </div>
                    <div class="detail-card">
                        <span class="label">Compte destination</span>
                        <span class="value"><?= Helpers::e($operation['compte_destination'] ?? '-') ?></span>
                    </div>
                    <div class="detail-card">
                        <span class="label">Mouvement sortie</span>
                        <span class="value">#<?= (int) ($operation['mouvement_sortie_id'] ?? 0) ?></span>
                    </div>
                    <div class="detail-card">
                        <span class="label">Mouvement entree</span>
                        <span class="value">#<?= (int) ($operation['mouvement_entree_id'] ?? 0) ?></span>
                    </div>
                    <div class="detail-card full">
                        <span class="label">Motif</span>
                        <span class="value"><?= Helpers::e($operation['motif'] ?? '-') ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="details">
                    <div class="detail-card">
                        <span class="label">Compte</span>
                        <span class="value"><?= Helpers::e($operation['compte_nom'] ?? '-') ?></span>
                    </div>
                    <div class="detail-card">
                        <span class="label">Type d'operation</span>
                        <span class="value"><?= Helpers::e($operation['type_mouvement'] ?? '-') ?></span>
                    </div>
                    <div class="detail-card">
                        <span class="label">Projet</span>
                        <span class="value"><?= Helpers::e($operation['projet_reference'] ?? '-') ?></span>
                    </div>
                    <div class="detail-card">
                        <span class="label">Contrat</span>
                        <span class="value"><?= Helpers::e($operation['contrat_reference'] ?? '-') ?></span>
                    </div>
                    <div class="detail-card full">
                        <span class="label">Motif</span>
                        <span class="value"><?= Helpers::e($operation['motif'] ?? '-') ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="details">
                <div class="detail-card">
                    <span class="label"><?= Helpers::e($isTransfer ? 'Valide par' : 'Cree par') ?></span>
                    <span class="value"><?= Helpers::e($operator ?: '-') ?></span>
                </div>
                <div class="detail-card">
                    <span class="label">Imprime le</span>
                    <span class="value"><?= date('d/m/Y H:i') ?></span>
                </div>
            </div>

            <div class="signatures">
                <div class="signature">Signature Evotech Africa</div>
                <div class="signature">Signature beneficiaire</div>
            </div>
        </section>

        <footer class="receipt-foot">
            Ce document confirme l'enregistrement de l'operation dans le module finance.
        </footer>
    </main>

    <script>
        window.addEventListener('load', function () {
            if (new URLSearchParams(window.location.search).get('print') === '1') {
                window.print();
            }
        });
    </script>
</body>
</html>
