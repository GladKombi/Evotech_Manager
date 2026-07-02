<?php
/**
 * config/database.php
 * Configuration de la connexion à la base de données
 * Version simplifiée - Uniquement la connexion et les fonctions de base
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
 * Exécute une requête et retourne tous les résultats
 * @param string $sql Requête SQL
 * @param array $params Paramètres
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Exécute une requête et retourne une seule ligne
 * @param string $sql Requête SQL
 * @param array $params Paramètres
 * @return array|null
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Exécute une requête et retourne une seule valeur
 * @param string $sql Requête SQL
 * @param array $params Paramètres
 * @return mixed
 */
function fetchValue($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Récupère le dernier ID inséré
 * @return int
 */
function lastInsertId() {
    return getDBConnection()->lastInsertId();
}

/**
 * Compte le nombre de lignes retournées par une requête
 * @param string $sql Requête SQL
 * @param array $params Paramètres
 * @return int
 */
function countRows($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
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

// =============================================
// FONCTIONS DE SESSION ET AUTHENTIFICATION
// =============================================

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
        $_SESSION['login_error'] = "Veuillez vous connecter pour acceder à cette page.";
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

/**
 * Récupère l'utilisateur actuellement connecté
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'nom' => $_SESSION['user_nom'] ?? null,
        'prenom' => $_SESSION['user_prenom'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null
    ];
}

// =============================================
// FONCTIONS DE GÉNÉRATION
// =============================================

/**
 * Génère une référence unique pour un projet
 * @param PDO $pdo
 * @return string
 */
function generateProjetReference($pdo) {
    $year = date('Y');
    $sql = "SELECT COUNT(*) as total FROM PROJETS WHERE YEAR(date_creation) = :year";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':year' => $year]);
    $count = $stmt->fetchColumn() + 1;
    return 'EVPRO-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

/**
 * Génère une référence unique pour un contrat
 * @param PDO $pdo
 * @return string
 */
function generateContratReference($pdo) {
    $year = date('Y');
    $sql = "SELECT COUNT(*) as total FROM CONTRATS_COM WHERE YEAR(date_creation) = :year";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':year' => $year]);
    $count = $stmt->fetchColumn() + 1;
    return 'CONT-COM-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

/**
 * Génère un email professionnel pour un agent
 * @param string $prenom
 * @param int $agentId
 * @return string
 */
function generateAgentEmail($prenom, $agentId) {
    $prenomClean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $prenom)));
    return $prenomClean . '-agent' . $agentId . '@Evo.africa';
}

/**
 * Génère un email pour un utilisateur
 * @param string $prenom
 * @param int $utilisateurId
 * @return string
 */
function generateUserEmail($prenom, $utilisateurId) {
    $prenomClean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $prenom)));
    return $prenomClean . '-utilisateur' . $utilisateurId . '@Evo.africa';
}

/**
 * Génère un matricule pour un disque de stockage
 * @param PDO $pdo
 * @return string
 */
function generateDiskMatricule($pdo) {
    $sql = "SELECT COUNT(*) as total FROM DISK_STOCKAGE WHERE statut = 0";
    $stmt = $pdo->query($sql);
    $count = $stmt->fetchColumn() + 1;
    return 'EVO/D' . $count . '/HDD';
}

/**
 * Génère un code de département
 * @param string $nom
 * @return string
 */
function generateDepartementCode($nom) {
    $mapping = [
        'Informatique' => 'Dev.',
        'Architecture' => 'Arch.',
        'Communication' => 'Com',
        'Design' => 'Des.',
        'Marketing' => 'Mkt.',
        'Ressources Humaines' => 'RH',
        'Finance' => 'Fin.',
        'Commercial' => 'Com.'
    ];
    
    return $mapping[$nom] ?? substr($nom, 0, 4) . '.';
}
?>