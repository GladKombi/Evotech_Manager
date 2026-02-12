<?php
include('../connexion/connexion.php');
// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? 'utilisateur';
    $remember = isset($_POST['remember']);

    // Validation des champs
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            if ($userType === 'agent') {
                // Connexion comme AGENT
                $query = "SELECT 
                            a.*,
                            d.nom as departement_nom,
                            d.code as departement_code
                          FROM AGENTS a
                          LEFT JOIN DEPARTEMENTS d ON a.departement_id = d.departement_id
                          WHERE a.email_professionnel = :email 
                          AND a.statut = 0";

                $stmt = $conn->prepare($query);
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Pour le développement : vérifier si le mot de passe est hashé ou en clair
                    $passwordValid = false;

                    // Vérifier si c'est un hash bcrypt/Argon2
                    if (password_verify($password, $user['mot_de_passe_hash'])) {
                        $passwordValid = true;
                    }
                    // Sinon, vérifier en texte clair (pour le développement seulement)
                    elseif ($password === $user['mot_de_passe_hash']) {
                        $passwordValid = true;
                        // Recommander de hacher le mot de passe
                        error_log("ATTENTION: Mot de passe en clair pour l'agent ID: " . $user['agent_id']);
                    }

                    if ($passwordValid) {
                        // Vérifier si le compte est actif
                        if ($user['etat'] === 'Actif') {
                            // Créer la session
                            $_SESSION['agent_id'] = $user['agent_id'];
                            $_SESSION['user_id'] = $user['agent_id'];
                            $_SESSION['user_type'] = 'agent';
                            $_SESSION['nom'] = $user['nom'];
                            $_SESSION['prenom'] = $user['prenom'];
                            $_SESSION['email'] = $user['email_professionnel'];
                            $_SESSION['departement_id'] = $user['departement_id'];
                            $_SESSION['departement_nom'] = $user['departement_nom'];
                            $_SESSION['role'] = 'Agent';
                            $_SESSION['photo_profil'] = $user['photo_profil'] ?? '';
                            $_SESSION['last_login'] = date('Y-m-d H:i:s');

                            // Si "se souvenir de moi" est coché
                            if ($remember) {
                                setcookie('evotech_remember', $user['agent_id'], time() + (30 * 24 * 60 * 60), '/');
                            }

                            // Redirection
                            header('Location: Views/dashboard.php');
                            exit();
                        } else {
                            $error = "Votre compte est " . $user['etat'] . ". Contactez l'administrateur.";
                        }
                    } else {
                        $error = "Email ou mot de passe incorrect.";
                    }
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            } else {
                // Connexion comme UTILISATEUR (admin, chef, etc.)
                // D'abord, vérifier si l'utilisateur existe
                $query = "SELECT * FROM UTILISATEURS WHERE email = :email AND statut = 0";
                $stmt = $conn->prepare($query);
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Pour le développement : vérifier si le mot de passe est hashé ou en clair
                    $passwordValid = false;

                    // Vérifier si c'est un hash bcrypt/Argon2
                    if (password_verify($password, $user['mot_de_passe_hash'])) {
                        $passwordValid = true;
                    }
                    // Sinon, vérifier en texte clair (pour le développement seulement)
                    elseif ($password === $user['mot_de_passe_hash']) {
                        $passwordValid = true;
                        // Recommander de hacher le mot de passe
                        error_log("ATTENTION: Mot de passe en clair pour l'utilisateur ID: " . $user['utilisateur_id']);
                    }

                    if ($passwordValid) {
                        // Vérifier si le compte est actif
                        if ($user['actif']) {
                            // Récupérer les rôles de l'utilisateur
                            $rolesQuery = "SELECT r.fonction 
                                         FROM ROLES r
                                         JOIN ATTRIBUTIONS a ON r.role_id = a.role_id
                                         WHERE a.utilisateur_id = :user_id 
                                         AND a.statut = 0 
                                         AND r.statut = 0";
                            $rolesStmt = $conn->prepare($rolesQuery);
                            $rolesStmt->execute([':user_id' => $user['utilisateur_id']]);
                            $userRoles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN, 0);

                            // Déterminer le rôle principal
                            $mainRole = 'Utilisateur';
                            if (in_array('DG', $userRoles)) {
                                $mainRole = 'DG';
                            } elseif (in_array('ChefDepartement', $userRoles)) {
                                $mainRole = 'ChefDepartement';
                            } elseif (in_array('Chef_projet', $userRoles)) {
                                $mainRole = 'Chef_projet';
                            } elseif (in_array('comptable', $userRoles)) {
                                $mainRole = 'comptable';
                            } elseif (in_array('actionnaire', $userRoles)) {
                                $mainRole = 'actionnaire';
                            }

                            // Créer la session
                            $_SESSION['user_id'] = $user['utilisateur_id'];
                            $_SESSION['user_type'] = 'utilisateur';
                            $_SESSION['nom'] = $user['nom'];
                            $_SESSION['prenom'] = $user['prenom'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['profil'] = $user['profil'] ?? '';
                            $_SESSION['roles'] = $userRoles;
                            $_SESSION['role'] = $mainRole;
                            $_SESSION['last_login'] = date('Y-m-d H:i:s');

                            // Si "se souvenir de moi" est coché
                            if ($remember) {
                                setcookie('evotech_remember', $user['utilisateur_id'], time() + (30 * 24 * 60 * 60), '/');
                            }

                            // Redirection
                            header('Location: Views/dashboard.php');
                            exit();
                        } else {
                            $error = "Votre compte est désactivé. Contactez l'administrateur.";
                        }
                    } else {
                        $error = "Email ou mot de passe incorrect.";
                    }
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            }
        } catch (Exception $e) {
            // Afficher l'erreur pour le débogage
            $error = "Erreur technique: " . $e->getMessage();
            error_log("Erreur de connexion: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
        }
    }
}