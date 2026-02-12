<?php
include 'connexion/connexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userRoles = $_SESSION['roles'] ?? [];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de production manquant.";
    header('Location: production.php');
    exit();
}

$production_id = intval($_GET['id']);

try {
    $query = "SELECT p.*, pr.chef_projet_id, pr.reference as projet_reference, pr.titre as projet_titre
              FROM PRODUCTION p
              LEFT JOIN PROJETS pr ON p.projet_id = pr.projet_id
              WHERE p.production_id = ? AND p.statut = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$production_id]);
    $production = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$production) {
        $_SESSION['error'] = "Fichier non trouvé.";
        header('Location: production.php');
        exit();
    }
    
    // Vérifier les permissions de téléchargement
    $canDownload = true;
    
    if ($production['confidentialite'] === 'Confidentiel') {
        if (!in_array('DG', $userRoles) && !in_array('Chef_projet', $userRoles)) {
            $canDownload = false;
        } else if (in_array('Chef_projet', $userRoles) && !in_array('DG', $userRoles)) {
            // Le chef de projet ne peut télécharger que les productions confidentielles de ses projets
            if ($production['chef_projet_id'] != $_SESSION['user_id']) {
                $canDownload = false;
            }
        }
    }
    
    if (!$canDownload) {
        $_SESSION['error'] = "Vous n'avez pas l'autorisation de télécharger ce fichier confidentiel.";
        header('Location: production.php');
        exit();
    }
    
    // Vérifier que le fichier existe
    if (!file_exists($production['chemin_stockage'])) {
        error_log("Fichier introuvable: " . $production['chemin_stockage']);
        $_SESSION['error'] = "Le fichier n'existe plus sur le serveur.";
        header('Location: production.php');
        exit();
    }
    
    // Vérifier que le fichier est lisible
    if (!is_readable($production['chemin_stockage'])) {
        error_log("Fichier illisible: " . $production['chemin_stockage']);
        $_SESSION['error'] = "Le fichier n'est pas accessible en lecture.";
        header('Location: production.php');
        exit();
    }
    
    // Déterminer le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $production['chemin_stockage']);
    finfo_close($finfo);
    
    if (!$mime_type) {
        $mime_type = 'application/octet-stream';
    }
    
    // Définir le nom de téléchargement
    $download_name = $production['nom_fichier'];
    
    // Nettoyer le nom du fichier pour le téléchargement
    $download_name = preg_replace('/[^\x20-\x7E]/', '', $download_name);
    
    // Désactiver la compression pour les gros fichiers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers pour le téléchargement
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($production['chemin_stockage']));
    
    // Lire et envoyer le fichier
    readfile($production['chemin_stockage']);
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur PDO téléchargement production: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors du téléchargement du fichier.";
    header('Location: production.php');
    exit();
} catch (Exception $e) {
    error_log("Erreur téléchargement production: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors du téléchargement du fichier.";
    header('Location: production.php');
    exit();
}
?>