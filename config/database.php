<?php
/**
 * config/database.php
 * Configuration de la connexion à la base de données
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'evotech_africa');
define('DB_USER', 'root');  
define('DB_PASS', '');      
define('DB_CHARSET', 'utf8mb4');

/**
 * Obtient une connexion PDO à la base de données
 * @return PDO
 * @throws PDOException
 */
function getDBConnection() {
    // Vérifier si une connexion existe déjà
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log l'erreur sans exposer les détails
            error_log("Erreur connexion DB: " . $e->getMessage());
            throw new PDOException("Erreur de connexion à la base de données", 500);
        }
    }
    
    return $pdo;
}

/**
 * Exécute une requête avec des paramètres
 * @param string $sql Requête SQL
 * @param array $params Paramètres
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Démarre une transaction
 */
function beginTransaction() {
    getDBConnection()->beginTransaction();
}

/**
 * Valide une transaction
 */
function commitTransaction() {
    getDBConnection()->commit();
}

/**
 * Annule une transaction
 */
function rollbackTransaction() {
    getDBConnection()->rollBack();
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * @param string|array $roles Rôle(s) autorisé(s)
 * @return bool
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['user_role'], $roles);
}

/**
 * Redirige si non connecté
 * @param string $redirectUrl URL de redirection
 */
function requireLogin($redirectUrl = '../index.php') {
    if (!isLoggedIn()) {
        $_SESSION['login_error'] = "Veuillez vous connecter pour acceder a cette page.";
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Redirige si rôle non autorisé
 * @param string|array $roles Rôle(s) autorisé(s)
 * @param string $redirectUrl URL de redirection
 */
function requireRole($roles, $redirectUrl = '../views/dashboard.php') {
    requireLogin();
    
    if (!hasRole($roles)) {
        $_SESSION['error'] = "Accès non autorisé";
        header('Location: ' . $redirectUrl);
        exit();
    }
}
?>
