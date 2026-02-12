<?php
include '../connexion.php';

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token_production']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../production.php');
    exit();
}

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour effectuer cette action.";
    header('Location: ../../login.php');
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('Chef_projet', $userRoles)) {
    $_SESSION['error'] = "Accès non autorisé.";
    header('Location: ../../production.php');
    exit();
}

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header('Location: ../../production.php');
    exit();
}

try {
    $conn->beginTransaction();
    
    // Récupérer et valider les données
    $production_id = isset($_POST['production_id']) ? intval($_POST['production_id']) : 0;
    $projet_id = isset($_POST['projet_id']) ? intval($_POST['projet_id']) : 0;
    $type_prod_id = isset($_POST['type_prod_id']) ? intval($_POST['type_prod_id']) : 0;
    $disk_id = isset($_POST['disk_id']) ? intval($_POST['disk_id']) : 0;
    $confidentialite = isset($_POST['confidentialite']) ? $_POST['confidentialite'] : 'Interne';
    $cloud_url = isset($_POST['cloud_url']) && !empty($_POST['cloud_url']) ? $_POST['cloud_url'] : null;
    $chemin_stockage = isset($_POST['chemin_stockage']) ? $_POST['chemin_stockage'] : null;
    $nom_fichier = isset($_POST['nom_fichier']) ? trim($_POST['nom_fichier']) : null;
    
    // Vérifier les données obligatoires
    if (empty($production_id) || empty($projet_id) || empty($type_prod_id) || empty($disk_id) || empty($nom_fichier)) {
        $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
        header('Location: ../../production.php');
        exit();
    }
    
    // Vérifier que la production existe et récupérer les infos
    $queryProd = "SELECT p.*, pr.chef_projet_id FROM PRODUCTION p
                  LEFT JOIN PROJETS pr ON p.projet_id = pr.projet_id
                  WHERE p.production_id = ? AND p.statut = 0";
    $stmtProd = $conn->prepare($queryProd);
    $stmtProd->execute([$production_id]);
    $production = $stmtProd->fetch(PDO::FETCH_ASSOC);
    
    if (!$production) {
        $_SESSION['error'] = "Production non trouvée.";
        header('Location: ../../production.php');
        exit();
    }
    
    // Vérifier les permissions
    if (in_array('Chef_projet', $userRoles) && !in_array('DG', $userRoles)) {
        if ($production['chef_projet_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "Vous n'êtes pas autorisé à modifier cette production.";
            header('Location: ../../production.php');
            exit();
        }
    }
    
    // Vérifier que le nouveau projet existe
    $queryProjet = "SELECT projet_id FROM PROJETS WHERE projet_id = ? AND statut = 0";
    $stmtProjet = $conn->prepare($queryProjet);
    $stmtProjet->execute([$projet_id]);
    if (!$stmtProjet->fetch()) {
        $_SESSION['error'] = "Le projet sélectionné n'existe pas ou a été supprimé.";
        header('Location: ../../production.php');
        exit();
    }
    
    // Si le chemin de stockage change, vérifier qu'il est valide
    if ($chemin_stockage && $chemin_stockage !== $production['chemin_stockage']) {
        // Vérifier que le fichier existe
        if (!file_exists($chemin_stockage)) {
            $_SESSION['error'] = "Le chemin de stockage spécifié n'existe pas.";
            header('Location: ../../production.php');
            exit();
        }
    } else {
        $chemin_stockage = $production['chemin_stockage'];
    }
    
    // Mettre à jour la production
    $query = "UPDATE PRODUCTION SET 
              projet_id = ?, 
              type_prod_id = ?, 
              disk_id = ?, 
              nom_fichier = ?, 
              chemin_stockage = ?, 
              cloud_url = ?, 
              confidentialite = ?,
              date_modification = NOW()
              WHERE production_id = ? AND statut = 0";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        $projet_id,
        $type_prod_id,
        $disk_id,
        $nom_fichier,
        $chemin_stockage,
        $cloud_url,
        $confidentialite,
        $production_id
    ]);
    
    if (!$result) {
        throw new Exception("Erreur lors de la mise à jour en base de données.");
    }
    
    $conn->commit();
    
    $_SESSION['msg'] = "Production mise à jour avec succès!";
    header('Location: ../../production.php');
    exit();
    
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Erreur PDO lors de la mise à jour production: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur de base de données est survenue. Veuillez réessayer.";
    header('Location: ../../production.php');
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Erreur lors de la mise à jour production: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../../production.php');
    exit();
}
?>