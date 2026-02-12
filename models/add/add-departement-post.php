<?php
require_once('../../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide";
    header("Location: ../../Views/departements.php");
    exit();
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        // Ajouter un nouveau département
        $code = trim($_POST['code']);
        $nom = trim($_POST['nom']);
        $description = trim($_POST['description'] ?? '');
        $responsable_id = !empty($_POST['responsable_id']) ? intval($_POST['responsable_id']) : null;
        
        // Validation
        if (empty($code) || empty($nom)) {
            $_SESSION['error'] = "Le code et le nom sont obligatoires";
            header("Location: ../../Views/departements.php");
            exit();
        }
        
        // Vérifier si le code existe déjà
        $checkCode = $conn->prepare("SELECT departement_id FROM DEPARTEMENTS WHERE code = ? AND statut = 0");
        $checkCode->execute([$code]);
        
        if ($checkCode->rowCount() > 0) {
            $_SESSION['error'] = "Ce code de département existe déjà";
            header("Location: ../../Views/departements.php");
            exit();
        }
        
        // Vérifier si le nom existe déjà
        $checkNom = $conn->prepare("SELECT departement_id FROM DEPARTEMENTS WHERE nom = ? AND statut = 0");
        $checkNom->execute([$nom]);
        
        if ($checkNom->rowCount() > 0) {
            $_SESSION['error'] = "Ce nom de département existe déjà";
            header("Location: ../../Views/departements.php");
            exit();
        }
        
        // Vérifier le responsable si spécifié
        if ($responsable_id) {
            $checkResponsable = $conn->prepare("SELECT utilisateur_id FROM UTILISATEURS WHERE utilisateur_id = ? AND statut = 0 AND actif = 1");
            $checkResponsable->execute([$responsable_id]);
            
            if ($checkResponsable->rowCount() === 0) {
                $_SESSION['error'] = "Le responsable sélectionné n'existe pas ou n'est pas actif";
                header("Location: ../../Views/departements.php");
                exit();
            }
        }
        
        // Insérer le nouveau département
        $insert = $conn->prepare("
            INSERT INTO DEPARTEMENTS (code, nom, description, responsable_id) 
            VALUES (?, ?, ?, ?)
        ");
        
        if ($insert->execute([$code, $nom, $description, $responsable_id])) {
            $_SESSION['msg'] = "Département créé avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la création du département";
        }
    }
    elseif ($action === 'edit') {
        // Modifier un département existant
        $departement_id = intval($_POST['departement_id']);
        $code = trim($_POST['code']);
        $nom = trim($_POST['nom']);
        $description = trim($_POST['description'] ?? '');
        $responsable_id = !empty($_POST['responsable_id']) ? intval($_POST['responsable_id']) : null;
        
        // Vérifier que le département existe
        $verify = $conn->prepare("SELECT departement_id FROM DEPARTEMENTS WHERE departement_id = ? AND statut = 0");
        $verify->execute([$departement_id]);
        
        if ($verify->rowCount() === 0) {
            $_SESSION['error'] = "Ce département n'existe pas";
            header("Location: ../../Views/departements.php");
            exit();
        }
        
        // Vérifier si le code existe déjà pour un autre département
        $checkCode = $conn->prepare("
            SELECT departement_id 
            FROM DEPARTEMENTS 
            WHERE code = ? AND departement_id != ? AND statut = 0
        ");
        $checkCode->execute([$code, $departement_id]);
        
        if ($checkCode->rowCount() > 0) {
            $_SESSION['error'] = "Ce code de département est déjà utilisé par un autre département";
            header("Location: ../../Views/departements.php");
            exit();
        }
        
        // Vérifier si le nom existe déjà pour un autre département
        $checkNom = $conn->prepare("
            SELECT departement_id 
            FROM DEPARTEMENTS 
            WHERE nom = ? AND departement_id != ? AND statut = 0
        ");
        $checkNom->execute([$nom, $departement_id]);
        
        if ($checkNom->rowCount() > 0) {
            $_SESSION['error'] = "Ce nom de département est déjà utilisé par un autre département";
            header("Location: ../../Views/departements.php");
            exit();
        }
        
        // Vérifier le responsable si spécifié
        if ($responsable_id) {
            $checkResponsable = $conn->prepare("SELECT utilisateur_id FROM UTILISATEURS WHERE utilisateur_id = ? AND statut = 0 AND actif = 1");
            $checkResponsable->execute([$responsable_id]);
            
            if ($checkResponsable->rowCount() === 0) {
                $_SESSION['error'] = "Le responsable sélectionné n'existe pas ou n'est pas actif";
                header("Location: ../../Views/departements.php");
                exit();
            }
        }
        
        // Mettre à jour le département
        $update = $conn->prepare("
            UPDATE DEPARTEMENTS 
            SET code = ?, nom = ?, description = ?, responsable_id = ? 
            WHERE departement_id = ?
        ");
        
        if ($update->execute([$code, $nom, $description, $responsable_id, $departement_id])) {
            $_SESSION['msg'] = "Département mis à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du département";
        }
    }
    
    header("Location: ../../Views/departements.php");
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur département: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue";
    header("Location: ../../Views/departements.php");
    exit();
}
?>