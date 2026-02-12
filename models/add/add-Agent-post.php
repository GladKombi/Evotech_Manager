<?php
session_start();
require_once('../../connexion/connexion.php');

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de sécurité invalide";
    header("Location: ../../Views/agents.php");
    exit();
}

$action = $_POST['action'] ?? '';

// Configuration du dossier d'upload
$uploadDirProfil = '../../images/profil/agents/';
$uploadDirCv = '../../uploads/cv/';

// Créer les dossiers s'ils n'existent pas
if (!file_exists($uploadDirProfil)) {
    mkdir($uploadDirProfil, 0777, true);
}
if (!file_exists($uploadDirCv)) {
    mkdir($uploadDirCv, 0777, true);
}

try {
    if ($action === 'add') {
        // Ajouter un nouvel agent
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email_professionnel = trim($_POST['email_professionnel']);
        $etat = $_POST['etat'];

        // Recuperation mails / telephones pour validations d'unicite
        $email_personnel = !empty($_POST['email_personnel']) ? trim($_POST['email_personnel']) : null;
        $telephone_personnel = !empty($_POST['telephone_personnel']) ? trim($_POST['telephone_personnel']) : null;
        $telephone_professionnel = !empty($_POST['telephone_professionnel']) ? trim($_POST['telephone_professionnel']) : null;

        // Validation
        if (empty($nom) || empty($prenom) || empty($email_professionnel) || empty($etat)) {
            $_SESSION['error'] = "Les champs obligatoires sont manquants";
            header("Location: ../../Views/agents.php");
            exit();
        }

        // Validation âge minimum (>= 7 ans) si date de naissance fournie
        if (!empty($_POST['date_naissance'])) {
            $dob = $_POST['date_naissance'];
            $birth = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$birth) {
                $_SESSION['error'] = "Date de naissance invalide";
                header("Location: ../../Views/agents.php");
                exit();
            }
            $today = new DateTime();
            $age = $today->diff($birth)->y;
            if ($age < 7) {
                $_SESSION['error'] = "L'agent doit avoir au moins 7 ans";
                header("Location: ../../Views/agents.php");
                exit();
            }
        }
        
        // Vérifier unicité des emails (professionnel + personnel)
        $checkEmailStmt = $conn->prepare("SELECT agent_id FROM AGENTS WHERE (email_personnel = ? OR email_professionnel = ?) AND statut = 0");
        // professional email
        $checkEmailStmt->execute([$email_professionnel, $email_professionnel]);
        if ($checkEmailStmt->rowCount() > 0) {
            $_SESSION['error'] = "Cet email professionnel est déjà utilisé";
            header("Location: ../../Views/agents.php");
            exit();
        }
        // personnel email (si fourni)
        if (!empty($email_personnel)) {
            $checkEmailStmt->execute([$email_personnel, $email_personnel]);
            if ($checkEmailStmt->rowCount() > 0) {
                $_SESSION['error'] = "Cet email personnel est déjà utilisé";
                header("Location: ../../Views/agents.php");
                exit();
            }
        }

        // Vérifier unicité des numéros de téléphone (aucun agent ne doit partager un numéro)
        $checkTelStmt = $conn->prepare("SELECT agent_id FROM AGENTS WHERE (telephone_personnel = ? OR telephone_professionnel = ?) AND statut = 0");
        if (!empty($telephone_personnel)) {
            $checkTelStmt->execute([$telephone_personnel, $telephone_personnel]);
            if ($checkTelStmt->rowCount() > 0) {
                $_SESSION['error'] = "Ce numéro de téléphone personnel est déjà utilisé par un autre agent";
                header("Location: ../../Views/agents.php");
                exit();
            }
        }
        if (!empty($telephone_professionnel)) {
            $checkTelStmt->execute([$telephone_professionnel, $telephone_professionnel]);
            if ($checkTelStmt->rowCount() > 0) {
                $_SESSION['error'] = "Ce numéro de téléphone professionnel est déjà utilisé par un autre agent";
                header("Location: ../../Views/agents.php");
                exit();
            }
        }
        
        // Traitement des fichiers
        $photo_profil = null;
        if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] == 0) {
            $photoInfo = pathinfo($_FILES['photo_profil']['name']);
            $photoExt = strtolower($photoInfo['extension']);
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($photoExt, $allowedExt) && $_FILES['photo_profil']['size'] <= 2 * 1024 * 1024) {
                $photoName = uniqid() . '.' . $photoExt;
                move_uploaded_file($_FILES['photo_profil']['tmp_name'], $uploadDirProfil . $photoName);
                $photo_profil = $photoName;
            }
        }
        
        $cv_path = null;
        if (isset($_FILES['cv_path']) && $_FILES['cv_path']['error'] == 0) {
            $cvInfo = pathinfo($_FILES['cv_path']['name']);
            $cvExt = strtolower($cvInfo['extension']);
            
            if ($cvExt == 'pdf' && $_FILES['cv_path']['size'] <= 5 * 1024 * 1024) {
                $cvName = uniqid() . '.pdf';
                move_uploaded_file($_FILES['cv_path']['tmp_name'], $uploadDirCv . $cvName);
                $cv_path = $cvName;
            }
        }
        
        // Préparer les données
        $data = [
            'nom' => $nom,
            'prenom' => $prenom,
            'date_naissance' => !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null,
            'adresse' => !empty($_POST['adresse']) ? trim($_POST['adresse']) : null,
            'telephone_personnel' => !empty($_POST['telephone_personnel']) ? trim($_POST['telephone_personnel']) : null,
            'telephone_professionnel' => !empty($_POST['telephone_professionnel']) ? trim($_POST['telephone_professionnel']) : null,
            'email_personnel' => !empty($_POST['email_personnel']) ? trim($_POST['email_personnel']) : null,
            'email_professionnel' => $email_professionnel,
            'departement_id' => !empty($_POST['departement_id']) ? intval($_POST['departement_id']) : null,
            'situation_matrimoniale' => !empty($_POST['situation_matrimoniale']) ? trim($_POST['situation_matrimoniale']) : null,
            'nombre_enfants' => !empty($_POST['nombre_enfants']) ? intval($_POST['nombre_enfants']) : 0,
            'urgence_nom' => !empty($_POST['urgence_nom']) ? trim($_POST['urgence_nom']) : null,
            'urgence_telephone' => !empty($_POST['urgence_telephone']) ? trim($_POST['urgence_telephone']) : null,
            'photo_profil' => $photo_profil,
            'cv_path' => $cv_path,
            'etat' => $etat,
            'date_embauche' => date('Y-m-d'),
            'date_depart' => !empty($_POST['date_depart']) ? $_POST['date_depart'] : null,
            'mot_de_passe_hash' => password_hash('Evotech@123', PASSWORD_DEFAULT) // Mot de passe par défaut
        ];
        
        // Insérer le nouvel agent
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $insert = $conn->prepare("INSERT INTO AGENTS ($fields) VALUES ($placeholders)");
        
        if ($insert->execute($data)) {
            $_SESSION['msg'] = "Agent créé avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la création de l'agent";
        }
    }
    elseif ($action === 'edit') {
        // Modifier un agent existant
        $agent_id = intval($_POST['agent_id']);
        
        // Vérifier que l'agent existe
        $verify = $conn->prepare("SELECT agent_id FROM AGENTS WHERE agent_id = ? AND statut = 0");
        $verify->execute([$agent_id]);
        
        if ($verify->rowCount() === 0) {
            $_SESSION['error'] = "Cet agent n'existe pas";
            header("Location: ../../Views/agents.php");
            exit();
        }
        
        $email_professionnel = trim($_POST['email_professionnel']);
        $email_personnel = !empty($_POST['email_personnel']) ? trim($_POST['email_personnel']) : null;
        $telephone_personnel = !empty($_POST['telephone_personnel']) ? trim($_POST['telephone_personnel']) : null;
        $telephone_professionnel = !empty($_POST['telephone_professionnel']) ? trim($_POST['telephone_professionnel']) : null;

        // Validation âge minimum (>= 7 ans) si date de naissance fournie (édition)
        if (!empty($_POST['date_naissance'])) {
            $dob = $_POST['date_naissance'];
            $birth = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$birth) {
                $_SESSION['error'] = "Date de naissance invalide";
                header("Location: ../../Views/agents.php");
                exit();
            }
            $today = new DateTime();
            $age = $today->diff($birth)->y;
            if ($age < 7) {
                $_SESSION['error'] = "L'agent doit avoir au moins 7 ans";
                header("Location: ../../Views/agents.php");
                exit();
            }
        }

        // Vérifier unicité des emails (professionnel + personnel) pour d'autres agents
        $checkEmailStmt = $conn->prepare("SELECT agent_id FROM AGENTS WHERE (email_personnel = ? OR email_professionnel = ?) AND agent_id != ? AND statut = 0");
        $checkEmailStmt->execute([$email_professionnel, $email_professionnel, $agent_id]);
        if ($checkEmailStmt->rowCount() > 0) {
            $_SESSION['error'] = "Cet email professionnel est déjà utilisé par un autre agent";
            header("Location: ../../Views/agents.php");
            exit();
        }
        if (!empty($email_personnel)) {
            $checkEmailStmt->execute([$email_personnel, $email_personnel, $agent_id]);
                if ($checkEmailStmt->rowCount() > 0) {
                $_SESSION['error'] = "Cet email personnel est déjà utilisé par un autre agent";
                header("Location: ../../Views/agents.php");
                exit();
            }
        }

        // Vérifier unicité des numéros de téléphone pour d'autres agents
        $checkTelStmt = $conn->prepare("SELECT agent_id FROM AGENTS WHERE (telephone_personnel = ? OR telephone_professionnel = ?) AND agent_id != ? AND statut = 0");
        if (!empty($telephone_personnel)) {
            $checkTelStmt->execute([$telephone_personnel, $telephone_personnel, $agent_id]);
            if ($checkTelStmt->rowCount() > 0) {
                $_SESSION['error'] = "Ce numéro de téléphone personnel est déjà utilisé par un autre agent";
                header("Location: ../../Views/agents.php");
                exit();
            }
        }
        if (!empty($telephone_professionnel)) {
            $checkTelStmt->execute([$telephone_professionnel, $telephone_professionnel, $agent_id]);
            if ($checkTelStmt->rowCount() > 0) {
                $_SESSION['error'] = "Ce numéro de téléphone professionnel est déjà utilisé par un autre agent";
                header("Location: ../../Views/agents.php");
                exit();
            }
        }
        
        // Récupérer l'agent actuel
        $currentAgent = $conn->prepare("SELECT photo_profil, cv_path FROM AGENTS WHERE agent_id = ? AND statut = 0");
        $currentAgent->execute([$agent_id]);
        $current = $currentAgent->fetch(PDO::FETCH_ASSOC);
        
        // Traitement de la photo
        $photo_profil = $current['photo_profil'];
        if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] == 0) {
            $photoInfo = pathinfo($_FILES['photo_profil']['name']);
            $photoExt = strtolower($photoInfo['extension']);
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($photoExt, $allowedExt) && $_FILES['photo_profil']['size'] <= 2 * 1024 * 1024) {
                // Supprimer l'ancienne photo si elle existe
                if ($photo_profil && file_exists($uploadDirProfil . $photo_profil)) {
                    unlink($uploadDirProfil . $photo_profil);
                }
                
                $photoName = uniqid() . '.' . $photoExt;
                move_uploaded_file($_FILES['photo_profil']['tmp_name'], $uploadDirProfil . $photoName);
                $photo_profil = $photoName;
            }
        }
        
        // Traitement du CV
        $cv_path = $current['cv_path'];
        if (isset($_FILES['cv_path']) && $_FILES['cv_path']['error'] == 0) {
            $cvInfo = pathinfo($_FILES['cv_path']['name']);
            $cvExt = strtolower($cvInfo['extension']);
            
            if ($cvExt == 'pdf' && $_FILES['cv_path']['size'] <= 5 * 1024 * 1024) {
                // Supprimer l'ancien CV s'il existe
                if ($cv_path && file_exists($uploadDirCv . $cv_path)) {
                    unlink($uploadDirCv . $cv_path);
                }
                
                $cvName = uniqid() . '.pdf';
                move_uploaded_file($_FILES['cv_path']['tmp_name'], $uploadDirCv . $cvName);
                $cv_path = $cvName;
            }
        }
        
        // Préparer les données de mise à jour
        $updateData = [
            'nom' => trim($_POST['nom']),
            'prenom' => trim($_POST['prenom']),
            'date_naissance' => !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null,
            'adresse' => !empty($_POST['adresse']) ? trim($_POST['adresse']) : null,
            'telephone_personnel' => !empty($_POST['telephone_personnel']) ? trim($_POST['telephone_personnel']) : null,
            'telephone_professionnel' => !empty($_POST['telephone_professionnel']) ? trim($_POST['telephone_professionnel']) : null,
            'email_personnel' => !empty($_POST['email_personnel']) ? trim($_POST['email_personnel']) : null,
            'email_professionnel' => $email_professionnel,
            'departement_id' => !empty($_POST['departement_id']) ? intval($_POST['departement_id']) : null,
            'situation_matrimoniale' => !empty($_POST['situation_matrimoniale']) ? trim($_POST['situation_matrimoniale']) : null,
            'nombre_enfants' => !empty($_POST['nombre_enfants']) ? intval($_POST['nombre_enfants']) : 0,
            'urgence_nom' => !empty($_POST['urgence_nom']) ? trim($_POST['urgence_nom']) : null,
            'urgence_telephone' => !empty($_POST['urgence_telephone']) ? trim($_POST['urgence_telephone']) : null,
            'photo_profil' => $photo_profil,
            'cv_path' => $cv_path,
            'etat' => $_POST['etat'],
            'date_depart' => !empty($_POST['date_depart']) ? $_POST['date_depart'] : null,
            'agent_id' => $agent_id
        ];
        
        // Construire la requête de mise à jour
        $setClause = [];
        foreach ($updateData as $key => $value) {
            if ($key !== 'agent_id') {
                $setClause[] = "$key = :$key";
            }
        }
        
        $sql = "UPDATE AGENTS SET " . implode(', ', $setClause) . " WHERE agent_id = :agent_id";
        $update = $conn->prepare($sql);
        
        if ($update->execute($updateData)) {
            $_SESSION['msg'] = "Agent mis à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour de l'agent";
        }
    }
    
    header("Location: ../../Views/agents.php");
    exit();
    
} catch (PDOException $e) {
    error_log("Erreur agent: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue";
    header("Location: ../../Views/agents.php");
    exit();
}
?>