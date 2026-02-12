<?php
include '../connexion.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit();
}

// Vérifier les permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('Chef_projet', $userRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission refusée']);
    exit();
}

// Vérifier que l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de production manquant']);
    exit();
}

try {
    $production_id = intval($_GET['id']);
    
    $query = "SELECT p.*, 
              DATE_FORMAT(p.date_upload, '%d/%m/%Y %H:%i') as date_upload_formatted,
              DATE_FORMAT(p.date_modification, '%d/%m/%Y %H:%i') as date_modification_formatted,
              pr.chef_projet_id,
              pr.reference as projet_reference,
              pr.titre as projet_titre
              FROM PRODUCTION p
              LEFT JOIN PROJETS pr ON p.projet_id = pr.projet_id
              WHERE p.production_id = ? AND p.statut = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$production_id]);
    $production = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($production) {
        // Vérifier que le chef de projet a le droit de modifier cette production
        if (in_array('Chef_projet', $userRoles) && !in_array('DG', $userRoles)) {
            if ($production['chef_projet_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Vous n\'êtes pas autorisé à modifier cette production']);
                exit();
            }
        }
        
        echo json_encode(['success' => true, 'production' => $production]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Production non trouvée']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur récupération production: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}
?>