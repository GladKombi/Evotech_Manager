<?php
// models/select/select-utilisateur.php

/**
 * Récupère uniquement les statistiques (plus léger que tous les utilisateurs)
 */
function getUsersStatistics() {
    global $conn; 
    
    if (!$conn) {
        throw new Exception("La connexion à la base de données n'est pas disponible.");
    }
    
    try {
        // Récupérer uniquement les statistiques, pas tous les utilisateurs
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(u.actif = 1) as actifs,
                    SUM(u.actif = 0) as inactifs,
                    SUM(u.profil IS NOT NULL AND u.profil != 'default-avatar.png') as avec_photo
                FROM UTILISATEURS u
                WHERE u.statut = 0";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur getUsersStatistics : " . $e->getMessage());
        throw new Exception("Impossible de charger les statistiques des utilisateurs.");
    }
}

/**
 * Récupère un utilisateur spécifique par son ID avec ses détails
 */
function getUtilisateurById($id) {
    global $conn;
    
    if (!$conn) {
        throw new Exception("Connexion à la base de données non établie.");
    }
    
    try {
        $sql = "SELECT 
                    u.*, 
                    r.fonction as role_nom,
                    r.role_id
                FROM UTILISATEURS u
                LEFT JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id AND a.statut = 0
                LEFT JOIN ROLES r ON a.role_id = r.role_id
                WHERE u.utilisateur_id = :id AND u.statut = 0";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception("Utilisateur introuvable.");
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Erreur getUtilisateurById : " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération de l'utilisateur.");
    }
}

/**
 * Récupère les utilisateurs avec pagination (pour AJAX DataTables)
 */
function getUsersPaginated($start = 0, $length = 25, $search = '', $orderColumn = 1, $orderDir = 'asc') {
    global $conn;
    
    if (!$conn) {
        throw new Exception("La connexion à la base de données n'est pas disponible.");
    }
    
    try {
        // Construction de la requête de base
        $sql = "SELECT 
                    u.utilisateur_id,
                    u.nom,
                    u.prenom,
                    u.email,
                    u.profil,
                    u.actif,
                    u.date_creation,
                    r.fonction as role_nom
                FROM UTILISATEURS u
                LEFT JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id AND a.statut = 0
                LEFT JOIN ROLES r ON a.role_id = r.role_id
                WHERE u.statut = 0";
        
        $params = [];
        
        // Ajouter la recherche si nécessaire
        if (!empty($search)) {
            $sql .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        // Définir les colonnes pour le tri
        $columns = ['u.profil', 'u.nom', 'u.email', 'u.actif', 'u.date_creation'];
        if (isset($columns[$orderColumn])) {
            $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
        } else {
            $sql .= " ORDER BY u.nom " . $orderDir;
        }
        
        // Ajouter la pagination
        $sql .= " LIMIT :start, :length";
        $params[':start'] = (int)$start;
        $params[':length'] = (int)$length;
        
        $stmt = $conn->prepare($sql);
        
        // Bind les paramètres
        foreach ($params as $key => $value) {
            $type = PDO::PARAM_INT;
            if ($key === ':search') {
                $type = PDO::PARAM_STR;
            }
            $stmt->bindValue($key, $value, $type);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur getUsersPaginated : " . $e->getMessage());
        throw new Exception("Impossible de charger les utilisateurs.");
    }
}

/**
 * Compte le nombre total d'utilisateurs filtrés
 */
function countFilteredUsers($search = '') {
    global $conn;
    
    if (!$conn) {
        throw new Exception("La connexion à la base de données n'est pas disponible.");
    }
    
    try {
        $sql = "SELECT COUNT(*) as total
                FROM UTILISATEURS u
                WHERE u.statut = 0";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] ?? 0;
        
    } catch (PDOException $e) {
        error_log("Erreur countFilteredUsers : " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère uniquement la liste des rôles disponibles (utile pour les formulaires)
 */
function getAllRoles() {
    global $conn;
    try {
        $stmt = $conn->query("SELECT * FROM ROLES WHERE statut = 0 ORDER BY fonction ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
?>