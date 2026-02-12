<?php
include '../../connexion/connexion.php';

// Vérifier CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF invalide";
    header('Location: ../../Views/disk-stockage.php');
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('Chef_projet', $userRoles)) {
    $_SESSION['error'] = "Permission refusée";
    header('Location: ../../Views/disk-stockage.php');
    exit();
}

// Fonction pour générer le matricule
function genererMatriculeDisque($conn, $prefixe = 'EVO/D', $type = 'HDD') {
    // Nettoyer le préfixe
    $cleanPrefix = str_replace('/', '', $prefixe);
    
    // Déterminer le format en fonction du préfixe
    if ($prefixe === 'EVO/D' || $prefixe === 'EVO') {
        // Format: EVO/D1/HDD
        $basePrefix = 'EVO/D';
    } elseif ($prefixe === 'EVO/HDD' || $prefixe === 'EVO/SSD' || $prefixe === 'EVO/SERVER' || $prefixe === 'EVO/NAS' || $prefixe === 'EVO/ARCHIVE') {
        // Format: EVO/SERVER1/SSD
        $parts = explode('/', $prefixe);
        $basePrefix = $parts[0] . '/' . $parts[1]; // EVO/SERVER
    } else {
        // Format par défaut
        $basePrefix = $cleanPrefix;
    }
    
    // Rechercher le dernier numéro utilisé
    $query = "SELECT matricule FROM DISK_STOCKAGE 
              WHERE matricule LIKE ? AND statut = 0 
              ORDER BY LENGTH(matricule), matricule DESC LIMIT 1";
    
    $stmt = $conn->prepare($query);
    
    // Pattern de recherche selon le format
    if (strpos($basePrefix, '/') !== false) {
        $pattern = $basePrefix . '%/' . $type;
    } else {
        $pattern = $basePrefix . '%/' . $type;
    }
    
    $stmt->execute([$pattern]);
    $lastDisk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $nextNumber = 1;
    
    if ($lastDisk && preg_match('/' . preg_quote($basePrefix, '/') . '(\d+)\/' . preg_quote($type, '/') . '/', $lastDisk['matricule'], $matches)) {
        $nextNumber = intval($matches[1]) + 1;
    } else {
        // Essayer un pattern plus large
        $stmt->execute([$basePrefix . '%']);
        $allDisks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($allDisks as $disk) {
            if (preg_match('/' . preg_quote($basePrefix, '/') . '(\d+)\/[A-Z]+/', $disk['matricule'], $matches)) {
                $num = intval($matches[1]);
                if ($num >= $nextNumber) {
                    $nextNumber = $num + 1;
                }
            }
        }
    }
    
    // Générer le matricule final
    if ($prefixe === 'EVO/D' || $prefixe === 'EVO') {
        $matricule = "EVO/D" . $nextNumber . "/" . $type;
    } elseif (strpos($prefixe, '/') !== false) {
        $matricule = $basePrefix . $nextNumber . "/" . $type;
    } else {
        $matricule = $cleanPrefix . $nextNumber . "/" . $type;
    }
    
    // Vérifier l'unicité
    $checkQuery = "SELECT COUNT(*) FROM DISK_STOCKAGE WHERE matricule = ? AND statut = 0";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$matricule]);
    
    // Si le matricule existe déjà, incrémenter
    $attempts = 0;
    while ($checkStmt->fetchColumn() > 0 && $attempts < 100) {
        $nextNumber++;
        
        if ($prefixe === 'EVO/D' || $prefixe === 'EVO') {
            $matricule = "EVO/D" . $nextNumber . "/" . $type;
        } elseif (strpos($prefixe, '/') !== false) {
            $matricule = $basePrefix . $nextNumber . "/" . $type;
        } else {
            $matricule = $cleanPrefix . $nextNumber . "/" . $type;
        }
        
        $checkStmt->execute([$matricule]);
        $attempts++;
    }
    
    return $matricule;
}

try {
    // Récupérer les données
    $prefixe = trim($_POST['prefixe']);
    $type_stockage = trim($_POST['type_stockage']);
    $capacite_go = intval($_POST['capacite_go']);
    $etat = $_POST['etat'];
    $emplacement_physique = trim($_POST['emplacement_physique'] ?? '');
    $date_acquisition = !empty($_POST['date_acquisition']) ? $_POST['date_acquisition'] : null;

    // Validation basique
    if (empty($prefixe) || empty($type_stockage) || $capacite_go <= 0) {
        throw new Exception("Données invalides. Veuillez vérifier tous les champs obligatoires.");
    }

    // Générer le matricule automatiquement
    $matricule = genererMatriculeDisque($conn, $prefixe, $type_stockage);
    
    // Validation du format du matricule généré
    if (!preg_match('/^EVO\/[A-Z]+\d+\/[A-Z]+$/', $matricule)) {
        throw new Exception("Format de matricule généré invalide: " . $matricule);
    }

    // Double vérification que le matricule n'existe pas déjà
    $checkQuery = "SELECT COUNT(*) FROM DISK_STOCKAGE WHERE matricule = ? AND statut = 0";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$matricule]);
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception("Erreur: Ce matricule est déjà utilisé (" . $matricule . "). Veuillez réessayer.");
    }

    // Insérer le disque
    $query = "INSERT INTO DISK_STOCKAGE (matricule, capacite_go, etat, emplacement_physique, date_acquisition, statut) 
              VALUES (?, ?, ?, ?, ?, 0)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$matricule, $capacite_go, $etat, $emplacement_physique, $date_acquisition]);

    // Récupérer l'ID du disque inséré
    $disk_id = $conn->lastInsertId();
    
    // Log de l'action
    error_log("[" . date('Y-m-d H:i:s') . "] Disk ajouté: " . $matricule . " (" . $capacite_go . " Go) par utilisateur ID " . ($_SESSION['user_id'] ?? 0));

    $_SESSION['msg'] = "Disque " . $matricule . " ajouté avec succès (Capacité: " . $capacite_go . " Go, État: " . $etat . ")";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur base de données: " . $e->getMessage();
    error_log("Erreur PDO add-disk: " . $e->getMessage());
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    error_log("Erreur add-disk: " . $e->getMessage());
}

// Régénérer le token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: ../../Views/disk-stockage.php');
exit();
?>