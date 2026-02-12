<?php
session_start();
include '../connexion/connexion.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

// Vérifier les permissions - DG ou Chef de projet
$userId = $_SESSION['user_id'];
$userRoles = $_SESSION['roles'] ?? [];
$isDG = in_array('DG', $userRoles);
$isChefProjet = in_array('Chef_projet', $userRoles);

if (!$isDG && !$isChefProjet) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Permission refusée']);
    exit();
}

try {
    // Construire la requête selon les permissions
    if ($isDG) {
        // Le DG peut voir tous les projets
        $query = "SELECT projet_id, reference, titre, etat 
                  FROM PROJETS 
                  WHERE statut = 0 
                  AND etat IN ('En cours', 'Suspendu')
                  ORDER BY reference";
        $stmt = $conn->prepare($query);
        $stmt->execute();
    } else {
        // Le Chef de projet ne peut voir que ses propres projets
        $query = "SELECT projet_id, reference, titre, etat 
                  FROM PROJETS 
                  WHERE statut = 0 
                  AND etat IN ('En cours', 'Suspendu')
                  AND chef_projet_id = ?
                  ORDER BY reference";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
    }
    
    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['projets' => $projets]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des projets: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur serveur']);
}
?>