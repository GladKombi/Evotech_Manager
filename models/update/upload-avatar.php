<?php
session_start();
require_once('../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
    exit();
}

try {
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
        exit();
    }
    
    // Vérifier que l'utilisateur existe
    $verify = $conn->prepare("SELECT utilisateur_id, profil FROM UTILISATEURS WHERE utilisateur_id = ? AND statut = 0");
    $verify->execute([$user_id]);
    $user = $verify->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit();
    }
    
    // Vérifier s'il y a un fichier
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement']);
        exit();
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($_FILES['file']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Format d\'image non autorisé']);
        exit();
    }
    
    if ($_FILES['file']['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'L\'image est trop grande (max 2MB)']);
        exit();
    }
    
    $uploadDir = '../../images/profil/utilisateurs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Supprimer l'ancienne photo
    if (!empty($user['profil']) && file_exists($uploadDir . $user['profil'])) {
        unlink($uploadDir . $user['profil']);
    }
    
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . time() . '_' . uniqid() . '.' . $ext;
    $uploadPath = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
        // Mettre à jour la base de données
        $update = $conn->prepare("
            UPDATE UTILISATEURS 
            SET profil = ?
            WHERE utilisateur_id = ?
        ");
        
        if ($update->execute([$filename, $user_id])) {
            // Mettre à jour la session
            $_SESSION['profil'] = $filename;
            
            echo json_encode([
                'success' => true,
                'message' => 'Photo mise à jour avec succès',
                'image_url' => '../images/profil/utilisateurs/' . $filename
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la base de données']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement du fichier']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur upload avatar: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
