<?php
/**
 * models/traitement/utilisateur-post.php
 * Traitement CRUD pour les utilisateurs
 */

session_start();
require_once __DIR__ . '/../../config/database.php';

// Vérification des droits (admin ou DG)
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['DG', 'admin'])) {
    $_SESSION['error'] = "Accès non autorisé";
    header('Location: ../../views/dashboard.php');
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'add':
            ajouterUtilisateur($pdo);
            break;
        case 'edit':
            modifierUtilisateur($pdo);
            break;
        case 'delete':
            supprimerUtilisateur($pdo);
            break;
        case 'restore':
            restaurerUtilisateur($pdo);
            break;
        default:
            throw new Exception("Action non valide");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../../views/utilisateurs.php'));
    exit();
}

/**
 * Ajoute un nouvel utilisateur
 */
function ajouterUtilisateur($pdo) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirme_passe = $_POST['confirme_passe'] ?? '';
    $roles = $_POST['roles'] ?? [];
    
    // Validation
    if (empty($nom) || empty($prenom)) {
        throw new Exception("Le nom et le prénom sont obligatoires");
    }
    
    if (empty($mot_de_passe) || strlen($mot_de_passe) < 8) {
        throw new Exception("Le mot de passe doit contenir au moins 8 caractères");
    }
    
    if ($mot_de_passe !== $confirme_passe) {
        throw new Exception("Les mots de passe ne correspondent pas");
    }
    
    // Vérification email unique
    $email = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $prenom))) . '-utilisateur@Evo.africa';
    
    $check = $pdo->prepare("SELECT COUNT(*) FROM UTILISATEURS WHERE email = :email AND statut = 0");
    $check->execute([':email' => $email]);
    if ($check->fetchColumn() > 0) {
        throw new Exception("Un utilisateur avec cet email existe déjà");
    }
    
    // Hash du mot de passe
    $passwordHash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
    
    // Insertion
    $sql = "INSERT INTO UTILISATEURS (nom, prenom, email, mot_de_passe_hash) 
            VALUES (:nom, :prenom, :email, :password)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':email' => $email,
        ':password' => $passwordHash
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Mise à jour de l'email avec l'ID
    $emailFinal = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $prenom))) . '-utilisateur' . $userId . '@Evo.africa';
    $update = $pdo->prepare("UPDATE UTILISATEURS SET email = :email WHERE utilisateur_id = :id");
    $update->execute([':email' => $emailFinal, ':id' => $userId]);
    
    // Attribution des rôles
    if (!empty($roles)) {
        foreach ($roles as $roleId) {
            $sql = "INSERT INTO ATTRIBUTIONS (utilisateur_id, role_id) VALUES (:user_id, :role_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId, ':role_id' => $roleId]);
        }
    }
    
    $_SESSION['success'] = "Utilisateur ajouté avec succès. Email: " . $emailFinal;
    header('Location: ../../views/utilisateurs.php');
    exit();
}

/**
 * Modifie un utilisateur existant
 */
function modifierUtilisateur($pdo) {
    $id = (int)($_POST['utilisateur_id'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirme_passe = $_POST['confirme_passe'] ?? '';
    $roles = $_POST['roles'] ?? [];
    $actif = isset($_POST['actif']) ? 1 : 0;
    
    if ($id <= 0) {
        throw new Exception("ID utilisateur invalide");
    }
    
    if (empty($nom) || empty($prenom)) {
        throw new Exception("Le nom et le prénom sont obligatoires");
    }
    
    // Récupération de l'utilisateur
    $user = getUtilisateurById($pdo, $id);
    if (!$user) {
        throw new Exception("Utilisateur non trouvé");
    }
    
    // Mise à jour
    $sql = "UPDATE UTILISATEURS SET 
                nom = :nom, 
                prenom = :prenom, 
                actif = :actif";
    $params = [
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':actif' => $actif,
        ':id' => $id
    ];
    
    // Si nouveau mot de passe
    if (!empty($mot_de_passe)) {
        if (strlen($mot_de_passe) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères");
        }
        if ($mot_de_passe !== $confirme_passe) {
            throw new Exception("Les mots de passe ne correspondent pas");
        }
        $sql .= ", mot_de_passe_hash = :password";
        $params[':password'] = password_hash($mot_de_passe, PASSWORD_DEFAULT);
    }
    
    $sql .= " WHERE utilisateur_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Mise à jour des rôles
    // Supprimer les attributions existantes
    $delete = $pdo->prepare("UPDATE ATTRIBUTIONS SET statut = 1 WHERE utilisateur_id = :user_id");
    $delete->execute([':user_id' => $id]);
    
    // Ajouter les nouvelles
    if (!empty($roles)) {
        foreach ($roles as $roleId) {
            $sql = "INSERT INTO ATTRIBUTIONS (utilisateur_id, role_id) VALUES (:user_id, :role_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $id, ':role_id' => $roleId]);
        }
    }
    
    $_SESSION['success'] = "Utilisateur modifié avec succès";
    header('Location: ../../views/utilisateurs.php');
    exit();
}

/**
 * Suppression soft delete d'un utilisateur
 */
function supprimerUtilisateur($pdo) {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception("ID utilisateur invalide");
    }
    
    // Vérifier si l'utilisateur existe
    $user = getUtilisateurById($pdo, $id);
    if (!$user) {
        throw new Exception("Utilisateur non trouvé");
    }
    
    // Soft delete
    $sql = "UPDATE UTILISATEURS SET statut = 1 WHERE utilisateur_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    // Supprimer également les attributions
    $sql = "UPDATE ATTRIBUTIONS SET statut = 1 WHERE utilisateur_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    $_SESSION['success'] = "Utilisateur supprimé avec succès";
    header('Location: ../../views/utilisateurs.php');
    exit();
}

/**
 * Restaure un utilisateur supprimé
 */
function restaurerUtilisateur($pdo) {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception("ID utilisateur invalide");
    }
    
    $sql = "UPDATE UTILISATEURS SET statut = 0 WHERE utilisateur_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    $_SESSION['success'] = "Utilisateur restauré avec succès";
    header('Location: ../../views/utilisateurs.php');
    exit();
}