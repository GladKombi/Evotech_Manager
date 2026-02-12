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
    $contrat_id = filter_input(INPUT_POST, 'contrat_id', FILTER_VALIDATE_INT);
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
    
    // ============ VALIDATIONS ============
    
    // 1. Validation de l'ID du contrat
    if (!$contrat_id) {
        $_SESSION['error'] = "ID contrat invalide";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // Vérifier que le contrat existe et n'est pas supprimé
    $queryCheckContrat = "SELECT * FROM CONTRATS_COM WHERE contrat_id = ? AND statut = 0";
    $stmtCheckContrat = $conn->prepare($queryCheckContrat);
    $stmtCheckContrat->execute([$contrat_id]);
    $contratExistant = $stmtCheckContrat->fetch(PDO::FETCH_ASSOC);
    
    if (!$contratExistant) {
        $_SESSION['error'] = "Contrat non trouvé ou supprimé";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 2. Validation des champs obligatoires
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
    
    // 3. Validation du montant
    if ($montant <= 0) {
        $_SESSION['error'] = "Le montant doit être supérieur à 0";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    if ($montant > 999999999999.99) {
        $_SESSION['error'] = "Le montant est trop élevé";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 4. Validation des dates
    $date_debut_obj = DateTime::createFromFormat('Y-m-d', $date_debut);
    if (!$date_debut_obj || $date_debut_obj->format('Y-m-d') !== $date_debut) {
        $_SESSION['error'] = "Format de date début invalide";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // Vérifier la cohérence avec les activités existantes
    if ($date_debut != $contratExistant['date_debut']) {
        // Vérifier s'il y a des activités avant la nouvelle date de début
        $queryActivites = "SELECT COUNT(*) as count FROM ACTIVITES_CONTRAT 
                          WHERE contrat_id = ? 
                          AND date_activite < ? 
                          AND statut = 0";
        $stmtActivites = $conn->prepare($queryActivites);
        $stmtActivites->execute([$contrat_id, $date_debut]);
        $activitesAvant = $stmtActivites->fetch(PDO::FETCH_ASSOC);
        
        if ($activitesAvant['count'] > 0) {
            $_SESSION['error'] = "Impossible de modifier la date de début : il existe des activités planifiées avant cette date";
            header('Location: ../../views/contrats-com.php');
            exit();
        }
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
        
        // Vérifier que le contrat ne dépasse pas 10 ans
        $interval = $date_debut_obj->diff($date_fin_obj);
        if ($interval->y > 10) {
            $_SESSION['error'] = "La durée du contrat ne peut pas dépasser 10 ans";
            header('Location: ../../views/contrats-com.php');
            exit();
        }
        
        // Vérifier la cohérence avec les activités existantes
        if ($date_fin != $contratExistant['date_fin']) {
            // Vérifier s'il y a des activités après la nouvelle date de fin
            $queryActivitesApres = "SELECT COUNT(*) as count FROM ACTIVITES_CONTRAT 
                                   WHERE contrat_id = ? 
                                   AND date_activite > ? 
                                   AND statut = 0";
            $stmtActivitesApres = $conn->prepare($queryActivitesApres);
            $stmtActivitesApres->execute([$contrat_id, $date_fin]);
            $activitesApres = $stmtActivitesApres->fetch(PDO::FETCH_ASSOC);
            
            if ($activitesApres['count'] > 0) {
                $_SESSION['error'] = "Impossible de modifier la date de fin : il existe des activités planifiées après cette date";
                header('Location: ../../views/contrats-com.php');
                exit();
            }
        }
    }
    
    // 5. Validation du partenaire
    $queryCheckPartenaire = "SELECT COUNT(*) as count FROM PARTENAIRES WHERE partenaire_id = ? AND statut = 0";
    $stmtCheckPartenaire = $conn->prepare($queryCheckPartenaire);
    $stmtCheckPartenaire->execute([$partenaire_id]);
    $partenaireExists = $stmtCheckPartenaire->fetch(PDO::FETCH_ASSOC);
    
    if ($partenaireExists['count'] == 0) {
        $_SESSION['error'] = "Le partenaire sélectionné n'existe pas ou a été supprimé";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 6. Validation du responsable (si fourni)
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
    
    // 7. Validation de l'état
    $etatsValides = ['Actif', 'En attente', 'Terminé', 'Résilié'];
    if (!in_array($etat, $etatsValides)) {
        $_SESSION['error'] = "État du contrat invalide";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // Validation spéciale pour les changements d'état
    if ($contratExistant['etat'] != $etat) {
        // Si on passe de Terminé/Résilié à Actif
        if (($contratExistant['etat'] == 'Terminé' || $contratExistant['etat'] == 'Résilié') && $etat == 'Actif') {
            $_SESSION['error'] = "Impossible de réactiver un contrat terminé ou résilié";
            header('Location: ../../views/contrats-com.php');
            exit();
        }
        
        // Si on termine un contrat
        if ($etat == 'Terminé') {
            // Vérifier s'il y a des activités non terminées
            $queryActivitesNonTerminees = "SELECT COUNT(*) as count FROM ACTIVITES_CONTRAT 
                                         WHERE contrat_id = ? 
                                         AND statut_activite != 'Terminé' 
                                         AND statut = 0";
            $stmtActivitesNonTerminees = $conn->prepare($queryActivitesNonTerminees);
            $stmtActivitesNonTerminees->execute([$contrat_id]);
            $activitesNonTerminees = $stmtActivitesNonTerminees->fetch(PDO::FETCH_ASSOC);
            
            if ($activitesNonTerminees['count'] > 0) {
                $_SESSION['error'] = "Impossible de terminer le contrat : il y a des activités non terminées";
                header('Location: ../../views/contrats-com.php');
                exit();
            }
            
            // Vérifier si la date de fin est passée
            if ($contratExistant['date_fin'] && new DateTime($contratExistant['date_fin']) > new DateTime()) {
                $_SESSION['error'] = "Impossible de terminer le contrat avant sa date de fin prévue";
                header('Location: ../../views/contrats-com.php');
                exit();
            }
        }
        
        // Si on résilie un contrat
        if ($etat == 'Résilié') {
            // Vérifier si le contrat n'est pas déjà terminé
            if ($contratExistant['etat'] == 'Terminé') {
                $_SESSION['error'] = "Impossible de résilier un contrat déjà terminé";
                header('Location: ../../views/contrats-com.php');
                exit();
            }
            
            // Demander confirmation pour la résiliation (côté client, pas ici)
        }
    }
    
    // 8. Validation de la périodicité
    $periodicitesValides = ['Mensuel', 'Trimestriel', 'Annuel', 'Forfait', 'Horaire'];
    if (!in_array($periodicite, $periodicitesValides)) {
        $_SESSION['error'] = "Périodicité invalide";
        header('Location: ../../views/contrats-com.php');
        exit();
    }
    
    // 9. Validation des longueurs de texte
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
    
    // 10. Vérifier les conflits de contrat (si partenaire ou type changé)
    if ($contratExistant['partenaire_id'] != $partenaire_id || $contratExistant['type_contrat'] != $type_contrat) {
        $queryCheckContratActif = "SELECT COUNT(*) as count FROM CONTRATS_COM 
                                   WHERE partenaire_id = ? 
                                   AND type_contrat LIKE ? 
                                   AND etat = 'Actif' 
                                   AND contrat_id != ?
                                   AND statut = 0";
        $stmtCheckContratActif = $conn->prepare($queryCheckContratActif);
        $stmtCheckContratActif->execute([$partenaire_id, '%' . $type_contrat . '%', $contrat_id]);
        $contratActifExists = $stmtCheckContratActif->fetch(PDO::FETCH_ASSOC);
        
        if ($contratActifExists['count'] > 0 && $etat == 'Actif') {
            $_SESSION['error'] = "Ce partenaire a déjà un contrat actif de ce type. Veuillez terminer ou résilier le contrat existant avant de modifier celui-ci.";
            header('Location: ../../views/contrats-com.php');
            exit();
        }
    }
    
    // ============ FIN DES VALIDATIONS ============
    
    // Mise à jour avec timestamp
    $query = "UPDATE CONTRATS_COM SET 
        partenaire_id = ?, 
        type_contrat = ?, 
        description = ?, 
        date_debut = ?, 
        date_fin = ?, 
        montant = ?, 
        periodicite = ?, 
        responsable_id = ?, 
        etat = ?, 
        conditions_particulieres = ?
        WHERE contrat_id = ? AND statut = 0";
    
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([
        $partenaire_id, $type_contrat, $description,
        $date_debut, $date_fin, $montant, $periodicite,
        $responsable_id, $etat, $conditions_particulieres,
        $contrat_id
    ]);
    
    if ($success) {
        $_SESSION['msg'] = "Contrat mis à jour avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour du contrat";
    }
    // if ($success && $stmt->rowCount() > 0) {
    //     // Journaliser l'action
    //     $changes = [];
        
    //     if ($contratExistant['partenaire_id'] != $partenaire_id) {
    //         $changes[] = "Partenaire changé";
    //     }
    //     if ($contratExistant['type_contrat'] != $type_contrat) {
    //         $changes[] = "Type changé";
    //     }
    //     if ($contratExistant['date_debut'] != $date_debut) {
    //         $changes[] = "Date début changée";
    //     }
    //     if ($contratExistant['date_fin'] != $date_fin) {
    //         $changes[] = "Date fin changée";
    //     }
    //     if ($contratExistant['montant'] != $montant) {
    //         $changes[] = "Montant changé";
    //     }
    //     if ($contratExistant['periodicite'] != $periodicite) {
    //         $changes[] = "Périodicité changée";
    //     }
    //     if ($contratExistant['responsable_id'] != $responsable_id) {
    //         $changes[] = "Responsable changé";
    //     }
    //     if ($contratExistant['etat'] != $etat) {
    //         $changes[] = "État changé de '{$contratExistant['etat']}' à '$etat'";
    //     }
        
    //     $detailsLog = "Modification contrat #$contrat_id ({$contratExistant['reference']})";
    //     if (!empty($changes)) {
    //         $detailsLog .= " - " . implode(", ", $changes);
    //     }
        
    //     $queryLog = "INSERT INTO logs_systeme (user_id, action, details, ip_address, user_agent) 
    //                 VALUES (?, ?, ?, ?, ?)";
    //     $stmtLog = $conn->prepare($queryLog);
    //     $stmtLog->execute([
    //         $_SESSION['user_id'] ?? 0,
    //         'UPDATE_CONTRAT',
    //         $detailsLog,
    //         $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    //         $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
    //     ]);
        
    //     // Si l'état a changé, journaliser spécifiquement ce changement
    //     if ($contratExistant['etat'] != $etat) {
    //         $queryLogEtat = "INSERT INTO logs_systeme (user_id, action, details, ip_address, user_agent) 
    //                        VALUES (?, ?, ?, ?, ?)";
    //         $stmtLogEtat = $conn->prepare($queryLogEtat);
    //         $stmtLogEtat->execute([
    //             $_SESSION['user_id'] ?? 0,
    //             'CHANGE_ETAT_CONTRAT',
    //             "Changement état contrat #$contrat_id ({$contratExistant['reference']}) : {$contratExistant['etat']} -> $etat",
    //             $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    //             $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
    //         ]);
    //     }
        
    //     $_SESSION['msg'] = "Contrat mis à jour avec succès";
    // } else {
    //     $_SESSION['error'] = "Aucune modification effectuée ou contrat non trouvé";
    // }
    
} catch (PDOException $e) {
    error_log("Erreur mise à jour contrat: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la mise à jour du contrat : " . $e->getMessage();
} catch (Exception $e) {
    error_log("Erreur validation contrat: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
} finally {
    header('Location: ../../views/contrats-com.php');
    exit();
}