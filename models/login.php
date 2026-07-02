<?php
// models/login.php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['flash_error'] = 'Veuillez remplir tous les champs';
        header('Location: ../Login.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ? AND deleted_at IS NULL");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['motDePasse'])) {
            // Stockage session utilisateur
            $_SESSION['user'] = [
                'id' => $user['idUtilisateur'],
                'nom' => $user['nom'],
                'prenoms' => $user['prenoms'],
                'email' => $user['email'],
                'role' => $user['role'],
                'telephone' => $user['telephone']
            ];

            // Redirection selon le rôle
            if ($user['role'] === 'client') {
                // Vérifier si une commande était en cours
                if (isset($_SESSION['pending_order']) && $_SESSION['pending_order'] === true) {
                    unset($_SESSION['pending_order']);
                    $_SESSION['flash_success'] = 'Connecté avec succès ! Vous pouvez maintenant passer commande.';
                    header('Location: ../index.php?order=direct');
                } else {
                    $_SESSION['flash_success'] = 'Connecté avec succès !';
                    header('Location: ../index.php');
                }
            } elseif ($user['role'] === 'admin' || $user['role'] === 'gestionnaire') {
                $_SESSION['flash_success'] = 'Connecté avec succès !';
                header('Location: ../views/dashboard.php');
            } else {
                $_SESSION['flash_success'] = 'Connecté avec succès !';
                header('Location: ../index.php');
            }
            exit;
        } else {
            $_SESSION['flash_error'] = 'Email ou mot de passe incorrect';
            header('Location: ../Login.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = 'Erreur technique, veuillez réessayer';
        header('Location: ../Login.php');
        exit;
    }
} else {
    header('Location: ../Login.php');
    exit;
}
