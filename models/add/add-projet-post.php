<?php
include '../../connexion/connexion.php';

// Vérifier l'autorisation
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide.";
    header('Location: ../../views/projets.php');
    exit();
}

// Récupérer les rôles de l'utilisateur
$userRoles = $_SESSION['roles'] ?? [];

// Vérifier les permissions
$canAccessRoles = in_array('DG', $userRoles) || in_array('Chef_projet', $userRoles);
if (!$canAccessRoles) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour créer un projet.";
    header('Location: ../../views/projets.php');
    exit();
}

// Fonction pour générer la référence
function generateProjetReference($conn) {
    $year = date('Y');
    $query = "
        SELECT COUNT(*) as count 
        FROM PROJETS 
        WHERE reference LIKE 'EVPRO-{$year}-%'
        AND statut = 0
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextNumber = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
    return "EVPRO-{$year}-{$nextNumber}";
}

try {
    // Récupérer et valider les données
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description_projet'] ?? '');
    $partenaire_id = !empty($_POST['partenaire_id']) ? intval($_POST['partenaire_id']) : null;
    $chef_projet_id = intval($_POST['chef_projet_id'] ?? 0);
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin_prevue = !empty($_POST['date_fin_prevue']) ? $_POST['date_fin_prevue'] : null;
    $budget_estime = !empty($_POST['budget_estime']) ? floatval($_POST['budget_estime']) : 0.00;
    $priorite = $_POST['priorite'] ?? 'Normale';
    $etat = $_POST['etat'] ?? 'En cours';
    
    // Validation
    if (empty($titre)) {
        throw new Exception("Le titre du projet est requis.");
    }
    
    if ($chef_projet_id <= 0) {
        throw new Exception("Le chef de projet est requis.");
    }
    
    if (empty($date_debut)) {
        throw new Exception("La date de début est requise.");
    }
    
    // Vérifier que la date de début n'est pas dans le passé
    if (strtotime($date_debut) < strtotime(date('Y-m-d'))) {
        throw new Exception("La date de début ne peut pas être dans le passé.");
    }
    
    // Vérifier que la date de fin est après la date de début
    if ($date_fin_prevue && strtotime($date_fin_prevue) < strtotime($date_debut)) {
        throw new Exception("La date de fin doit être après la date de début.");
    }
    
    // Vérifier l'existence du partenaire si spécifié
    if ($partenaire_id) {
        $stmt = $conn->prepare("SELECT partenaire_id FROM PARTENAIRES WHERE partenaire_id = ? AND statut = 0");
        $stmt->execute([$partenaire_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Le partenaire sélectionné n'existe pas.");
        }
    }
    
    // Vérifier l'existence du chef de projet
    $stmt = $conn->prepare("SELECT utilisateur_id FROM UTILISATEURS WHERE utilisateur_id = ? AND statut = 0");
    $stmt->execute([$chef_projet_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Le chef de projet sélectionné n'existe pas.");
    }
    
    // Générer la référence
    $reference = generateProjetReference($conn);
    
    // Insérer le projet
    $query = "
        INSERT INTO PROJETS (
            reference, 
            partenaire_id, 
            titre, 
            description_projet, 
            chef_projet_id, 
            date_debut, 
            date_fin_prevue, 
            budget_estime, 
            priorite, 
            etat
        ) VALUES (
            :reference, 
            :partenaire_id, 
            :titre, 
            :description, 
            :chef_projet_id, 
            :date_debut, 
            :date_fin_prevue, 
            :budget_estime, 
            :priorite, 
            :etat
        )
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':reference' => $reference,
        ':partenaire_id' => $partenaire_id,
        ':titre' => $titre,
        ':description' => $description,
        ':chef_projet_id' => $chef_projet_id,
        ':date_debut' => $date_debut,
        ':date_fin_prevue' => $date_fin_prevue,
        ':budget_estime' => $budget_estime,
        ':priorite' => $priorite,
        ':etat' => $etat
    ]);
    
    $projet_id = $conn->lastInsertId();
    
    // Journaliser l'action
    $user_id = $_SESSION['user_id'];
    $action = "Création du projet: $titre (ID: $projet_id, Référence: $reference)";
    $query_log = "INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)";
    $stmt_log = $conn->prepare($query_log);
    $stmt_log->execute([$user_id, $action, $_SERVER['REMOTE_ADDR']]);
    
    $_SESSION['msg'] = "Projet créé avec succès ! Référence: $reference";
    
} catch (Exception $e) {
    error_log("Erreur création projet: " . $e->getMessage());
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}

// Rediriger vers la page des projets
header('Location: ../../views/projets.php');
exit();