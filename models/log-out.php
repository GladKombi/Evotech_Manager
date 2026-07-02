<?php
/**
 * models/logout.php
 * Déconnexion Evotech Africa
 */

session_start();

// Supprimer les cookies de session
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('user_email', '', time() - 3600, '/');
}

// Supprimer la session
$_SESSION = [];
session_destroy();

// Rediriger vers la page de connexion
header('Location: ../Login.php?logout=success');
exit();