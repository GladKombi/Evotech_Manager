<?php
session_start();
include '../connexion/connexion.php';

// Vérifier les permissions
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Récupérer les rôles de l'utilisateur
$userRoles = $_SESSION['roles'] ?? [];

// Vérifier les permissions
function hasPermission($requiredRoles, $userRoles) {
    if (empty($requiredRoles)) return true;
    foreach ($requiredRoles as $role) {
        if (in_array($role, $userRoles)) return true;
    }
    return false;
}

try {
    $production_id = intval($_GET['id'] ?? 0);
    
    if ($production_id <= 0) {
        throw new Exception("Identifiant de production invalide.");
    }
    
    // Récupérer les informations de la production
    $query = "
        SELECT pr.*, p.reference as projet_reference
        FROM PRODUCTION pr
        LEFT JOIN PROJETS p ON pr.projet_id = p.projet_id
        WHERE pr.production_id = ? AND pr.statut = 0
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$production_id]);
    $production = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$production) {
        throw new Exception("Production non trouvée.");
    }
    
    // Vérifier la confidentialité
    if ($production['confidentialite'] === 'Confidentiel') {
        if (!hasPermission(['DG', 'Chef_projet'], $userRoles)) {
            throw new Exception("Accès refusé. Cette production est confidentielle.");
        }
    }
    
    // Vérifier que le fichier existe
    if (!file_exists($production['chemin_stockage'])) {
        throw new Exception("Le fichier n'existe plus sur le serveur.");
    }
    
    // Téléchargement du fichier
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($production['nom_fichier']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($production['chemin_stockage']));
    flush(); // Flush system output buffer
    readfile($production['chemin_stockage']);
    exit();
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
    header('Location: ../views/projets/production.php');
    exit();
} catch (PDOException $e) {
    error_log("Erreur PDO lors du téléchargement: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur technique est survenue.";
    header('Location: ../views/projets/production.php');
    exit();
}
?>