<?php
/**
 * Impression directe des bons d'entrée/sortie de caisse
 * Version sans NumberFormatter - Compatible XAMPP standard
 * Dev Senior - Evotech Africa
 */

require_once '../connexion/connexion.php';

// Vérification authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Vérification permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('comptable', $userRoles)) {
    die("Accès non autorisé");
}

$mouvement_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$beneficiaire = isset($_GET['beneficiaire']) ? trim($_GET['beneficiaire']) : '';

if (!$mouvement_id || !in_array($type, ['entree', 'sortie'])) {
    die("Paramètres invalides");
}

// Récupérer les informations du mouvement
try {
    $query = "
        SELECT 
            m.*,
            c.nom as compte_nom,
            c.type as compte_type,
            c.devise,
            p.reference as projet_reference,
            p.titre as projet_titre,
            cc.reference as contrat_reference,
            cc.type_contrat as contrat_type,
            u.nom as cree_par_nom,
            u.prenom as cree_par_prenom
        FROM MOUVEMENT_COMPTE m
        LEFT JOIN COMPTES c ON m.compte_id = c.compte_id
        LEFT JOIN PROJETS p ON m.projet_id = p.projet_id
        LEFT JOIN CONTRATS_COM cc ON m.contrat_id = cc.contrat_id
        LEFT JOIN UTILISATEURS u ON m.cree_par = u.utilisateur_id
        WHERE m.mouvement_id = ? AND m.statut = 0
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$mouvement_id]);
    $mouvement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mouvement) {
        die("Mouvement introuvable");
    }
    
    // Générer un numéro de bon unique pour l'affichage
    $prefixe = ($type === 'entree') ? 'BEC' : 'BSC';
    $annee = date('Y');
    $numero_bon = $prefixe . '-' . $annee . '-' . str_pad($mouvement_id, 6, '0', STR_PAD_LEFT);
    
    /**
     * FONCTION DE CONVERSION MONTANT EN LETTRES (FRANÇAIS)
     * Sans dépendance à NumberFormatter
     */
    function nombreEnLettres($nombre) {
        $unites = [
            0 => 'ZÉRO', 1 => 'UN', 2 => 'DEUX', 3 => 'TROIS', 4 => 'QUATRE',
            5 => 'CINQ', 6 => 'SIX', 7 => 'SEPT', 8 => 'HUIT', 9 => 'NEUF',
            10 => 'DIX', 11 => 'ONZE', 12 => 'DOUZE', 13 => 'TREIZE', 14 => 'QUATORZE',
            15 => 'QUINZE', 16 => 'SEIZE', 17 => 'DIX-SEPT', 18 => 'DIX-HUIT', 19 => 'DIX-NEUF'
        ];
        
        $dizaines = [
            2 => 'VINGT', 3 => 'TRENTE', 4 => 'QUARANTE', 5 => 'CINQUANTE',
            6 => 'SOIXANTE', 7 => 'SOIXANTE-DIX', 8 => 'QUATRE-VINGTS', 9 => 'QUATRE-VINGT-DIX'
        ];
        
        if ($nombre < 20) {
            return $unites[$nombre];
        } elseif ($nombre < 100) {
            $dizaine = floor($nombre / 10);
            $unite = $nombre % 10;
            
            if ($dizaine == 7 || $dizaine == 9) {
                $dizaine--;
                $unite += 10;
            }
            
            $texte = $dizaines[$dizaine];
            
            if ($unite == 1 && $dizaine < 8) {
                $texte .= ' ET ' . $unites[$unite];
            } elseif ($unite > 0) {
                $texte .= '-' . $unites[$unite];
            } elseif ($dizaine == 8) {
                $texte = 'QUATRE-VINGTS';
            }
            
            return $texte;
        } elseif ($nombre < 1000) {
            $centaines = floor($nombre / 100);
            $reste = $nombre % 100;
            $texte = '';
            
            if ($centaines == 1) {
                $texte .= 'CENT';
            } else {
                $texte .= $unites[$centaines] . ' CENT';
            }
            
            if ($reste > 0) {
                $texte .= ' ' . nombreEnLettres($reste);
            } elseif ($centaines > 1) {
                $texte .= 'S';
            }
            
            return $texte;
        } elseif ($nombre < 1000000) {
            $milliers = floor($nombre / 1000);
            $reste = $nombre % 1000;
            $texte = '';
            
            if ($milliers == 1) {
                $texte .= 'MILLE';
            } else {
                $texte .= nombreEnLettres($milliers) . ' MILLE';
            }
            
            if ($reste > 0) {
                $texte .= ' ' . nombreEnLettres($reste);
            }
            
            return $texte;
        } elseif ($nombre < 1000000000) {
            $millions = floor($nombre / 1000000);
            $reste = $nombre % 1000000;
            $texte = '';
            
            if ($millions == 1) {
                $texte .= 'UN MILLION';
            } else {
                $texte .= nombreEnLettres($millions) . ' MILLIONS';
            }
            
            if ($reste > 0) {
                $texte .= ' ' . nombreEnLettres($reste);
            }
            
            return $texte;
        }
        
        return (string)$nombre;
    }
    
    function montantEnLettres($montant, $devise = 'FRANCS CFA') {
        // Séparer la partie entière et les centimes
        $entier = floor($montant);
        $centimes = round(($montant - $entier) * 100);
        
        // Convertir la partie entière
        $lettres = nombreEnLettres($entier) . ' ' . $devise;
        
        // Ajouter les centimes si nécessaire
        if ($centimes > 0) {
            $lettres .= ' ET ' . nombreEnLettres($centimes) . ' CENTIMES';
        }
        
        return $lettres;
    }
    
    $montant_lettres = montantEnLettres($mouvement['montant']);
    
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    die("Erreur lors de la récupération des données");
}

// Déterminer le bénéficiaire par défaut si non fourni
if (empty($beneficiaire)) {
    $beneficiaire = ($type === 'entree') ? 'Client / Apporteur' : 'Fournisseur / Bénéficiaire';
}

// Informations de l'entreprise
$entreprise = [
    'nom' => 'EVOTECH AFRICA',
    'slogan' => 'Innovation & Technologies',
    'siege' => 'Dakar, Sénégal',
    'telephone' => '+221 33 123 45 67',
    'email' => 'contact@evotech.africa',
    'site' => 'www.evotech.africa',
    'ninea' => 'SN-DKR-2024-A-12345',
    'rc' => 'RC DKR-2024-B-6789'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de <?= ucfirst($type) ?> - <?= $numero_bon ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* MÊMES STYLES QUE PRÉCÉDEMMENT */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: #f8fafc;
            padding: 40px 20px;
            color: #0f172a;
        }
        
        .bon-caisse {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        
        .bon-header {
            background: <?= $type === 'entree' 
                ? 'linear-gradient(135deg, #059669 0%, #10b981 100%)' 
                : 'linear-gradient(135deg, #b91c1c 0%, #dc2626 100%)' ?>;
            padding: 35px 35px 25px;
            color: white;
            position: relative;
        }
        
        .bon-header::after {
            content: "<?= $type === 'entree' ? '💰' : '💸' ?>";
            position: absolute;
            right: 35px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 90px;
            opacity: 0.15;
            color: white;
        }
        
        .bon-title {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 8px;
            text-transform: uppercase;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .bon-subtitle {
            font-size: 18px;
            font-weight: 500;
            opacity: 0.95;
            margin-bottom: 15px;
        }
        
        .bon-numero {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-block;
            font-weight: 600;
            font-size: 16px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .badge-original {
            background: rgba(255,255,255,0.25);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.4);
            margin-left: 15px;
        }
        
        .entreprise-info {
            background: #f1f5f9;
            padding: 20px 35px;
            border-bottom: 2px dashed #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .entreprise-nom {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .entreprise-details {
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .bon-body {
            padding: 35px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 35px;
        }
        
        .info-item {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
        }
        
        .montant-chiffres {
            text-align: center;
            margin: 30px 0 20px;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 16px;
        }
        
        .montant-label {
            font-size: 14px;
            color: #475569;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        
        .montant-valeur {
            font-size: 42px;
            font-weight: 800;
            color: <?= $type === 'entree' ? '#059669' : '#b91c1c' ?>;
            line-height: 1.2;
        }
        
        .montant-devise {
            font-size: 24px;
            font-weight: 600;
            color: <?= $type === 'entree' ? '#10b981' : '#dc2626' ?>;
        }
        
        .montant-lettres {
            background: #fef9c3;
            border-left: 8px solid <?= $type === 'entree' ? '#059669' : '#b91c1c' ?>;
            padding: 25px;
            border-radius: 12px;
            margin: 30px 0;
            font-size: 18px;
            font-weight: 500;
            color: #1e293b;
            font-style: italic;
            position: relative;
        }
        
        .montant-lettres::before {
            content: "“";
            font-size: 70px;
            color: <?= $type === 'entree' ? '#059669' : '#b91c1c' ?>;
            opacity: 0.2;
            position: absolute;
            left: 10px;
            top: -15px;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px dashed #cbd5e1;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-titre {
            font-weight: 700;
            color: #334155;
            margin-bottom: 10px;
            font-size: 15px;
        }
        
        .signature-nom {
            color: #475569;
            font-size: 14px;
            min-height: 40px;
        }
        
        .signature-ligne {
            margin-top: 30px;
            border-top: 1px solid #94a3b8;
            padding-top: 10px;
            font-size: 12px;
            color: #64748b;
        }
        
        .footer-info {
            margin-top: 35px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        
        .qr-simule {
            width: 70px;
            height: 70px;
            background: repeating-linear-gradient(45deg, #e2e8f0 0px, #e2e8f0 5px, #cbd5e1 5px, #cbd5e1 10px);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #1e293b;
            font-weight: bold;
            text-align: center;
        }
        
        .bon-actions {
            padding: 25px 35px;
            background: #f8fafc;
            display: flex;
            gap: 15px;
            justify-content: center;
            border-top: 2px solid #e2e8f0;
        }
        
        .btn-print {
            background: #0f172a;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .btn-print:hover {
            background: #1e293b;
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-retour {
            background: white;
            color: #334155;
            padding: 14px 30px;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .btn-retour:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .bon-caisse {
                box-shadow: none;
                border-radius: 0;
            }
            .bon-actions {
                display: none;
            }
            .bon-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge-original {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .montant-lettres {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .bon-header {
                padding: 25px;
            }
            .bon-header::after {
                font-size: 60px;
                right: 25px;
            }
            .bon-title {
                font-size: 26px;
            }
            .bon-body {
                padding: 25px;
            }
            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .montant-valeur {
                font-size: 32px;
            }
            .signatures {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .entreprise-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .bon-actions {
                flex-direction: column;
            }
            .btn-print, .btn-retour {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="bon-caisse">
        <!-- HEADER : Titre et référence -->
        <div class="bon-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="bon-title">BON DE <?= strtoupper($type) ?> DE CAISSE</div>
                    <div class="bon-subtitle"><?= $entreprise['slogan'] ?></div>
                    <div class="bon-numero mt-3">
                        <i class="bi bi-upc-scan me-2"></i> N° <?= $numero_bon ?>
                    </div>
                    <span class="badge-original">
                        <i class="bi bi-check-circle me-1"></i> ORIGINAL
                    </span>
                </div>
                <div class="text-end">
                    <div style="font-size: 15px; font-weight: 600; opacity: 0.9;">DATE D'OPÉRATION</div>
                    <div style="font-size: 24px; font-weight: 800;"><?= date('d/m/Y', strtotime($mouvement['date_mouvement'])) ?></div>
                    <div style="font-size: 15px; opacity: 0.9; margin-top: 5px;">
                        <i class="bi bi-clock me-1"></i> <?= date('H:i', strtotime($mouvement['date_mouvement'])) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- INFOS ENTREPRISE -->
        <div class="entreprise-info">
            <div>
                <div class="entreprise-nom"><?= $entreprise['nom'] ?></div>
                <div class="entreprise-details mt-1">
                    <i class="bi bi-geo-alt-fill me-1"></i> <?= $entreprise['siege'] ?><br>
                    <i class="bi bi-telephone-fill me-1"></i> <?= $entreprise['telephone'] ?> | <i class="bi bi-envelope-fill me-1"></i> <?= $entreprise['email'] ?>
                </div>
            </div>
            <div class="text-md-end">
                <div class="entreprise-details">
                    <strong>NINEA:</strong> <?= $entreprise['ninea'] ?><br>
                    <strong>RC:</strong> <?= $entreprise['rc'] ?>
                </div>
            </div>
        </div>
        
        <!-- CORPS DU BON -->
        <div class="bon-body">
            <!-- Grille d'informations -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">RÉFÉRENCE PIÈCE</div>
                    <div class="info-value"><?= !empty($mouvement['reference_piece']) ? htmlspecialchars($mouvement['reference_piece']) : 'N/A' ?></div>
                    <small class="text-muted">ID MVT: MVT-<?= str_pad($mouvement['mouvement_id'], 5, '0', STR_PAD_LEFT) ?></small>
                </div>
                <div class="info-item">
                    <div class="info-label">COMPTE CONCERNÉ</div>
                    <div class="info-value"><?= htmlspecialchars($mouvement['compte_nom']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($mouvement['compte_type'] ?? 'Caisse') ?></small>
                </div>
                <div class="info-item">
                    <div class="info-label">BÉNÉFICIAIRE</div>
                    <div class="info-value"><?= htmlspecialchars($beneficiaire) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">MOTIF DE L'OPÉRATION</div>
                    <div class="info-value"><?= htmlspecialchars($mouvement['motif']) ?></div>
                </div>
            </div>
            
            <!-- MONTANT EN CHIFFRES -->
            <div class="montant-chiffres">
                <div class="montant-label">MONTANT DE L'OPÉRATION</div>
                <div class="montant-valeur">
                    <?= $mouvement['devise'] ?? 'CFA' ?> <?= number_format($mouvement['montant'], 0, ',', ' ') ?>
                    <span class="montant-devise">F</span>
                </div>
            </div>
            
            <!-- MONTANT EN LETTRES -->
            <div class="montant-lettres">
                <strong>Arrêté le présent bon à la somme de :</strong><br>
                <?= $montant_lettres ?>
            </div>
            
            <!-- PROJET / CONTRAT LIÉ (si applicable) -->
            <?php if (!empty($mouvement['projet_id'])): ?>
            <div class="alert alert-info mt-4 d-flex align-items-center" style="background: #e0f2fe; border: none; border-radius: 12px;">
                <i class="bi bi-folder2-open fs-4 me-3" style="color: #0369a1;"></i>
                <div>
                    <strong>Projet lié :</strong> 
                    <?= htmlspecialchars($mouvement['projet_reference']) ?> - <?= htmlspecialchars($mouvement['projet_titre']) ?>
                </div>
            </div>
            <?php elseif (!empty($mouvement['contrat_id'])): ?>
            <div class="alert alert-warning mt-4 d-flex align-items-center" style="background: #fef3c7; border: none; border-radius: 12px;">
                <i class="bi bi-file-earmark-text fs-4 me-3" style="color: #b45309;"></i>
                <div>
                    <strong>Contrat lié :</strong> 
                    <?= htmlspecialchars($mouvement['contrat_reference']) ?> - <?= htmlspecialchars($mouvement['contrat_type']) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- SIGNATURES -->
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-titre">CAISSIER / COMPTABLE</div>
                    <div class="signature-nom">
                        <?= htmlspecialchars($mouvement['cree_par_prenom'] ?? '') ?> <?= htmlspecialchars($mouvement['cree_par_nom'] ?? '') ?>
                    </div>
                    <div class="signature-ligne">Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-titre">BÉNÉFICIAIRE</div>
                    <div class="signature-nom"><?= htmlspecialchars($beneficiaire) ?></div>
                    <div class="signature-ligne">Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-titre">DIRECTION GÉNÉRALE</div>
                    <div class="signature-nom">DG EVOTECH AFRICA</div>
                    <div class="signature-ligne">Cachet & Signature</div>
                </div>
            </div>
            
            <!-- FOOTER : Traçabilité et QR Code simulé -->
            <div class="footer-info">
                <div>
                    <i class="bi bi-printer-fill me-1"></i> 
                    Imprimé le <?= date('d/m/Y à H:i:s') ?> par 
                    <strong><?= htmlspecialchars($_SESSION['prenom'] ?? '') ?> <?= htmlspecialchars($_SESSION['nom'] ?? '') ?></strong>
                </div>
                <div class="qr-simule">
                    EVOTECH<br>AFRICA
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px; font-size: 11px; color: #94a3b8; font-style: italic;">
                Ce document fait office de pièce justificative - Evotech Africa Gestion Intégrée
            </div>
        </div>
        
        <!-- ACTIONS -->
        <div class="bon-actions">
            <button onclick="window.print()" class="btn-print">
                <i class="bi bi-printer-fill"></i>
                Imprimer le bon
            </button>
            <a href="javascript:window.close()" class="btn-retour">
                <i class="bi bi-x-lg"></i>
                Fermer
            </a>
        </div>
    </div>
    
    <script>
        // Auto-print si demandé
        <?php if (isset($_GET['auto_print']) && $_GET['auto_print'] == 1): ?>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
        <?php endif; ?>
        
        // Raccourci clavier Ctrl+P
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>