<?php
	session_start();

// connexion/connexion.php
$host = 'localhost';
$dbname = 'evotech_africa';
$username = 'root'; // À changer en production
$password = ''; // À changer en production

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Définir le fuseau horaire
    $conn->exec("SET time_zone = '+00:00'");
    
} catch(PDOException $e) {
    // Ne pas afficher les erreurs en production
    error_log("Connection failed: " . $e->getMessage());
    
    // En développement, vous pouvez afficher l'erreur
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}
?>