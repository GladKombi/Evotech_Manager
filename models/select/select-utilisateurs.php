<?php
/**
 * models/select/select-utilisateurs.php
 * Requêtes pour la gestion des utilisateurs, rôles et attributions
 */

/**
 * Récupère tous les utilisateurs avec leurs rôles
 */
function getUtilisateurs($pdo) {
    $sql = "SELECT 
                u.*,
                GROUP_CONCAT(r.fonction SEPARATOR ', ') as roles,
                COUNT(a.attribution_id) as nb_roles
            FROM UTILISATEURS u
            LEFT JOIN ATTRIBUTIONS a ON a.utilisateur_id = u.utilisateur_id AND a.statut = 0
            LEFT JOIN ROLES r ON r.role_id = a.role_id AND r.statut = 0
            WHERE u.statut = 0
            GROUP BY u.utilisateur_id
            ORDER BY u.date_creation DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère tous les utilisateurs (y compris supprimés)
 */
function getUtilisateursWithDeleted($pdo) {
    $sql = "SELECT 
                u.*,
                GROUP_CONCAT(r.fonction SEPARATOR ', ') as roles,
                COUNT(a.attribution_id) as nb_roles
            FROM UTILISATEURS u
            LEFT JOIN ATTRIBUTIONS a ON a.utilisateur_id = u.utilisateur_id AND a.statut = 0
            LEFT JOIN ROLES r ON r.role_id = a.role_id AND r.statut = 0
            GROUP BY u.utilisateur_id
            ORDER BY u.statut ASC, u.date_creation DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère un utilisateur par son ID
 */
function getUtilisateurById($pdo, $id) {
    $sql = "SELECT * FROM UTILISATEURS WHERE utilisateur_id = :id AND statut = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

/**
 * Récupère un utilisateur par son email
 */
function getUtilisateurByEmail($pdo, $email) {
    $sql = "SELECT * FROM UTILISATEURS WHERE email = :email AND statut = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    return $stmt->fetch();
}

/**
 * Récupère un utilisateur par son email (y compris supprimé)
 */
function getUtilisateurByEmailAll($pdo, $email) {
    $sql = "SELECT * FROM UTILISATEURS WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    return $stmt->fetch();
}

/**
 * Récupère tous les rôles
 */
function getRoles($pdo) {
    $sql = "SELECT * FROM ROLES WHERE statut = 0 ORDER BY fonction";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère tous les rôles (y compris supprimés)
 */
function getRolesWithDeleted($pdo) {
    $sql = "SELECT * FROM ROLES ORDER BY fonction";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère un rôle par son ID
 */
function getRoleById($pdo, $id) {
    $sql = "SELECT * FROM ROLES WHERE role_id = :id AND statut = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

/**
 * Récupère les attributions d'un utilisateur
 */
function getAttributionsByUser($pdo, $userId) {
    $sql = "SELECT 
                a.*,
                r.fonction,
                r.date_creation as role_date_creation
            FROM ATTRIBUTIONS a
            LEFT JOIN ROLES r ON r.role_id = a.role_id
            WHERE a.utilisateur_id = :user_id
            AND a.statut = 0
            ORDER BY a.date_attribution DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Récupère les attributions actives d'un utilisateur
 */
function getActiveAttributionsByUser($pdo, $userId) {
    $sql = "SELECT 
                a.*,
                r.fonction
            FROM ATTRIBUTIONS a
            LEFT JOIN ROLES r ON r.role_id = a.role_id
            WHERE a.utilisateur_id = :user_id
            AND a.statut = 0
            AND r.statut = 0
            ORDER BY a.date_attribution DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Vérifie si un utilisateur a un rôle spécifique
 */
function userHasRole($pdo, $userId, $roleFonction) {
    $sql = "SELECT COUNT(*) as total 
            FROM ATTRIBUTIONS a
            JOIN ROLES r ON r.role_id = a.role_id
            WHERE a.utilisateur_id = :user_id
            AND r.fonction = :fonction
            AND a.statut = 0
            AND r.statut = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId,
        ':fonction' => $roleFonction
    ]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Récupère les utilisateurs disponibles (non attribués à un rôle spécifique)
 */
function getUtilisateursDisponibles($pdo, $roleId = null) {
    $sql = "SELECT u.* 
            FROM UTILISATEURS u
            WHERE u.statut = 0";
    
    if ($roleId) {
        $sql .= " AND u.utilisateur_id NOT IN (
                    SELECT utilisateur_id 
                    FROM ATTRIBUTIONS 
                    WHERE role_id = :role_id 
                    AND statut = 0
                )";
    }
    
    $sql .= " ORDER BY u.nom, u.prenom";
    
    $stmt = $pdo->prepare($sql);
    if ($roleId) {
        $stmt->execute([':role_id' => $roleId]);
    } else {
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/**
 * Récupère les utilisateurs par rôle
 */
function getUtilisateursByRole($pdo, $roleId) {
    $sql = "SELECT 
                u.*
            FROM UTILISATEURS u
            JOIN ATTRIBUTIONS a ON a.utilisateur_id = u.utilisateur_id
            WHERE a.role_id = :role_id
            AND a.statut = 0
            AND u.statut = 0
            ORDER BY u.nom, u.prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':role_id' => $roleId]);
    return $stmt->fetchAll();
}

/**
 * Récupère les rôles d'un utilisateur (noms)
 */
function getUserRolesNames($pdo, $userId) {
    $sql = "SELECT r.fonction
            FROM ATTRIBUTIONS a
            JOIN ROLES r ON r.role_id = a.role_id
            WHERE a.utilisateur_id = :user_id
            AND a.statut = 0
            AND r.statut = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Vérifie si un email existe déjà
 */
function emailExists($pdo, $email, $excludeId = null) {
    $sql = "SELECT COUNT(*) as total FROM UTILISATEURS WHERE email = :email AND statut = 0";
    $params = [':email' => $email];
    
    if ($excludeId) {
        $sql .= " AND utilisateur_id != :exclude_id";
        $params[':exclude_id'] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

/**
 * Récupère les utilisateurs actifs
 */
function getUtilisateursActifs($pdo) {
    $sql = "SELECT * FROM UTILISATEURS WHERE actif = 1 AND statut = 0 ORDER BY nom, prenom";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Recherche des utilisateurs
 */
function searchUtilisateurs($pdo, $search) {
    $sql = "SELECT 
                u.*,
                GROUP_CONCAT(r.fonction SEPARATOR ', ') as roles
            FROM UTILISATEURS u
            LEFT JOIN ATTRIBUTIONS a ON a.utilisateur_id = u.utilisateur_id AND a.statut = 0
            LEFT JOIN ROLES r ON r.role_id = a.role_id AND r.statut = 0
            WHERE u.statut = 0
            AND (u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search)
            GROUP BY u.utilisateur_id
            ORDER BY u.nom, u.prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':search' => '%' . $search . '%']);
    return $stmt->fetchAll();
}