<?php
require_once '../../connexion/connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/contrats-com.php');
    exit();
}

// Vérifier CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF invalide";
    header('Location: ../../views/contrats-com.php');
    exit();
}

// Vérifier permissions
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('DG', $userRoles) && !in_array('ChefDepartement', $userRoles)) {
    $_SESSION['error'] = "Permission refusée";
    header('Location: ../../views/contrats-com.php');
    exit();
}

try {
    // Récupérer et valider les données
    $partenaire_id = filter_input(INPUT_POST, 'partenaire_id', FILTER_VALIDATE_INT);
    $type_contrat = htmlspecialchars(trim($_POST['type_contrat']));
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $date_debut = $_POST['date_debut'];
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
    $montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);
    $periodicite = $_POST['periodicite'];
    $responsable_id = !empty($_POST['responsable_id']) ? filter_input(INPUT_POST, 'responsable_id', FILTER_VALIDATE_INT) : null;
    $etat = $_POST['etat'];
    $conditions_particulieres = htmlspecialchars(trim($_POST['conditions_particulieres'] ?? ''));
    
    // ============ VALIDATIONS RENFORCÉES ============
    
    // 1. Validation des champs obligatoires
    if (!$partenaire_id) {
        $_SESSION['error'] = "Veuillez sélectionner un partenaire";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    if (empty($type_contrat)) {
        $_SESSION['error'] = "Le type de contrat est requis";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    if (strlen($type_contrat) > 100) {
        $_SESSION['error'] = "Le type de contrat ne doit pas dépasser 100 caractères";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    if (empty($date_debut)) {
        $_SESSION['error'] = "La date de début est requise";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    if (!$montant) {
        $_SESSION['error'] = "Le montant est requis";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 2. Validation du montant
    if ($montant <= 0) {
        $_SESSION['error'] = "Le montant doit être supérieur à 0";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    if ($montant > 999999999999.99) { // 15 chiffres, 2 décimales
        $_SESSION['error'] = "Le montant est trop élevé";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 3. Validation des dates
    $date_debut_obj = DateTime::createFromFormat('Y-m-d', $date_debut);
    if (!$date_debut_obj || $date_debut_obj->format('Y-m-d') !== $date_debut) {
        $_SESSION['error'] = "Format de date début invalide";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // Vérifier que la date de début n'est pas dans le passé (optionnel mais recommandé)
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    if ($date_debut_obj < $today) {
        $_SESSION['error'] = "La date de début ne peut pas être dans le passé";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    if ($date_fin) {
        $date_fin_obj = DateTime::createFromFormat('Y-m-d', $date_fin);
        if (!$date_fin_obj || $date_fin_obj->format('Y-m-d') !== $date_fin) {
            $_SESSION['error'] = "Format de date fin invalide";
            header('Location: ../../views/contrats-com.php');
            exit();
        }
        
        // Vérifier que la date de fin est après la date de début
        if ($date_fin_obj <= $date_debut_obj) {
            $_SESSION['error'] = "La date de fin doit être postérieure à la date de début";
            header('Location: ../../views/contrats-com.php');
            exit();
        }
        
        // Vérifier que le contrat ne dépasse pas 10 ans (optionnel)
        $interval = $date_debut_obj->diff($date_fin_obj);
        if ($interval->y > 10) {
            $_SESSION['error'] = "La durée du contrat ne peut pas dépasser 10 ans";
            header('Location: ../../views/contrats-com.php');
            exit();
        }
    }
    
    // 4. Validation du partenaire
    $queryCheckPartenaire = "SELECT COUNT(*) as count FROM PARTENAIRES WHERE partenaire_id = ? AND statut = 0";
    $stmtCheckPartenaire = $conn->prepare($queryCheckPartenaire);
    $stmtCheckPartenaire->execute([$partenaire_id]);
    $partenaireExists = $stmtCheckPartenaire->fetch(PDO::FETCH_ASSOC);
    
    if ($partenaireExists['count'] == 0) {
        $_SESSION['error'] = "Le partenaire sélectionné n'existe pas ou a été supprimé";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 5. Validation du responsable (si fourni)
    if ($responsable_id) {
        $queryCheckResponsable = "SELECT COUNT(*) as count FROM AGENTS WHERE agent_id = ? AND etat = 'Actif' AND statut = 0";
        $stmtCheckResponsable = $conn->prepare($queryCheckResponsable);
        $stmtCheckResponsable->execute([$responsable_id]);
        $responsableExists = $stmtCheckResponsable->fetch(PDO::FETCH_ASSOC);
        
        if ($responsableExists['count'] == 0) {
            $_SESSION['error'] = "Le responsable sélectionné n'existe pas ou n'est pas actif";
            header('Location: ../../views/contrats-com.php');
            exit();
        }
    }
    
    // 6. Validation de l'état
    $etatsValides = ['Actif'];
    if (!in_array($etat, $etatsValides)) {
        $_SESSION['error'] = "État du contrat doit etre Actif pardefaut";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 7. Validation de la périodicité
    $periodicitesValides = ['Mensuel', 'Trimestriel', 'Annuel', 'Forfait', 'Horaire', 'Hebdomadaire'];
    if (!in_array($periodicite, $periodicitesValides)) {
        $_SESSION['error'] = "Périodicité invalide";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 8. Validation des longueurs de texte
    if (strlen($description) > 65535) {
        $_SESSION['error'] = "La description est trop longue";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    if (strlen($conditions_particulieres) > 65535) {
        $_SESSION['error'] = "Les conditions particulières sont trop longues";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 9. Vérifier si le partenaire a déjà un contrat actif du même type (optionnel)
    $queryCheckContratActif = "SELECT COUNT(*) as count FROM CONTRATS_COM 
                               WHERE partenaire_id = ? 
                               AND type_contrat LIKE ? 
                               AND etat = 'Actif' 
                               AND statut = 0";
    $stmtCheckContratActif = $conn->prepare($queryCheckContratActif);
    $stmtCheckContratActif->execute([$partenaire_id, '%' . $type_contrat . '%']);
    $contratActifExists = $stmtCheckContratActif->fetch(PDO::FETCH_ASSOC);
    
    if ($contratActifExists['count'] > 0 && $etat == 'Actif') {
        $_SESSION['error'] = "Ce partenaire a déjà un contrat actif de ce type. Veuillez terminer ou résilier le contrat existant avant d'en créer un nouveau.";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // ============ FIN DES VALIDATIONS ============
    
    // Générer la référence unique
    $year = date('Y');
    $query = "SELECT COUNT(*) as count FROM CONTRATS_COM WHERE YEAR(date_creation) = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$year]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $num = $result['count'] + 1;
    
    // Générer la référence avec vérification d'unicité
    $reference = "CONT-COM-" . $year . "-" . str_pad($num, 3, '0', STR_PAD_LEFT);
    
    // Vérifier que la référence n'existe pas déjà (cas rare mais possible)
    $tentatives = 0;
    $maxTentatives = 10;
    
    while ($tentatives < $maxTentatives) {
        $queryCheckRef = "SELECT COUNT(*) as count FROM CONTRATS_COM WHERE reference = ?";
        $stmtCheckRef = $conn->prepare($queryCheckRef);
        $stmtCheckRef->execute([$reference]);
        $refExists = $stmtCheckRef->fetch(PDO::FETCH_ASSOC);
        
        if ($refExists['count'] == 0) {
            break; // Référence unique trouvée
        }
        
        // Incrémenter et générer une nouvelle référence
        $num++;
        $reference = "CONT-COM-" . $year . "-" . str_pad($num, 3, '0', STR_PAD_LEFT);
        $tentatives++;
    }
    
    if ($tentatives >= $maxTentatives) {
        throw new Exception("Impossible de générer une référence unique après $maxTentatives tentatives");
    }
    
    // Insertion sécurisée
    $query = "INSERT INTO CONTRATS_COM (
        reference, partenaire_id, type_contrat, description, 
        date_debut, date_fin, montant, periodicite, 
        responsable_id, etat, conditions_particulieres,
        date_creation
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([
        $reference, $partenaire_id, $type_contrat, $description,
        $date_debut, $date_fin, $montant, $periodicite,
        $responsable_id, $etat, $conditions_particulieres
    ]);

    if($success){
        $_SESSION['msg'] = "Contrat ajouté avec succès (Référence: $reference)";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout du contrat";
    }
    
    // if ($success) {
    //     $contrat_id = $conn->lastInsertId();
        
    //     // Journaliser l'action (optionnel mais recommandé)
    //     $queryLog = "INSERT INTO logs_systeme (user_id, action, details, ip_address, user_agent) 
    //                 VALUES (?, ?, ?, ?, ?)";
    //     $stmtLog = $conn->prepare($queryLog);
    //     $stmtLog->execute([
    //         $_SESSION['user_id'] ?? 0,
    //         'CREATE_CONTRAT',
    //         "Création contrat #$contrat_id ($reference) - $type_contrat - Montant: $$montant",
    //         $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    //         $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
    //     ]);
        
    //     $_SESSION['msg'] = "Contrat ajouté avec succès (Référence: $reference)";
    // } else {
    //     throw new Exception("Échec de l'insertion dans la base de données");
    // }
    
} catch (PDOException $e) {
    error_log("Erreur ajout contrat: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de l'ajout du contrat : " . $e->getMessage();
} catch (Exception $e) {
    error_log("Erreur validation contrat: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
} finally {
    header('Location: ../../views/contrats-com.php');
    exit();
}