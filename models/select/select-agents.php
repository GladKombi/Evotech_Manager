<?php
/**
 * models/select/select-agents.php
 * Requêtes pour la gestion des agents et leurs profils
 */

/**
 * Récupère tous les agents avec leurs profils
 */
function getAgents($pdo) {
    $sql = "SELECT 
                a.*,
                d.nom as departement_nom,
                d.code as departement_code,
                p.photo_profil,
                p.email_professionnel,
                p.telephone_professionnel,
                p.situation_matrimoniale,
                p.nombre_enfants,
                p.cv_path
            FROM AGENTS a
            LEFT JOIN DEPARTEMENTS d ON d.departement_id = a.departement_id
            LEFT JOIN PROFILS_AGENTS p ON p.agent_id = a.agent_id
            WHERE a.statut = 0
            ORDER BY a.date_creation DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère tous les agents (y compris supprimés)
 */
function getAgentsWithDeleted($pdo) {
    $sql = "SELECT 
                a.*,
                d.nom as departement_nom,
                d.code as departement_code,
                p.photo_profil,
                p.email_professionnel,
                p.telephone_professionnel,
                p.situation_matrimoniale,
                p.nombre_enfants,
                p.cv_path
            FROM AGENTS a
            LEFT JOIN DEPARTEMENTS d ON d.departement_id = a.departement_id
            LEFT JOIN PROFILS_AGENTS p ON p.agent_id = a.agent_id
            ORDER BY a.statut ASC, a.date_creation DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère un agent par son ID avec son profil complet
 */
function getAgentById($pdo, $id) {
    $sql = "SELECT 
                a.*,
                d.nom as departement_nom,
                d.code as departement_code,
                d.responsable_id as departement_responsable,
                p.*
            FROM AGENTS a
            LEFT JOIN DEPARTEMENTS d ON d.departement_id = a.departement_id
            LEFT JOIN PROFILS_AGENTS p ON p.agent_id = a.agent_id
            WHERE a.agent_id = :id
            AND a.statut = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

/**
 * Récupère les agents par département
 */
function getAgentsByDepartement($pdo, $departementId) {
    $sql = "SELECT 
                a.*,
                p.photo_profil,
                p.email_professionnel,
                p.telephone_professionnel
            FROM AGENTS a
            LEFT JOIN PROFILS_AGENTS p ON p.agent_id = a.agent_id
            WHERE a.departement_id = :departement_id
            AND a.etat = 'Actif'
            AND a.statut = 0
            ORDER BY a.nom, a.prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':departement_id' => $departementId]);
    return $stmt->fetchAll();
}

/**
 * Récupère les agents actifs
 */
function getAgentsActifs($pdo) {
    $sql = "SELECT 
                a.*,
                d.nom as departement_nom,
                p.photo_profil,
                p.email_professionnel,
                p.telephone_professionnel
            FROM AGENTS a
            LEFT JOIN DEPARTEMENTS d ON d.departement_id = a.departement_id
            LEFT JOIN PROFILS_AGENTS p ON p.agent_id = a.agent_id
            WHERE a.etat = 'Actif'
            AND a.statut = 0
            ORDER BY a.nom, a.prenom";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère les agents inactifs
 */
function getAgentsInactifs($pdo) {
    $sql = "SELECT 
                a.*,
                d.nom as departement_nom,
                p.photo_profil
            FROM AGENTS a
            LEFT JOIN DEPARTEMENTS d ON d.departement_id = a.departement_id
            LEFT JOIN PROFILS_AGENTS p ON p.agent_id = a.agent_id
            WHERE a.etat != 'Actif'
            AND a.statut = 0
            ORDER BY a.nom, a.prenom";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère les agents disponibles pour assignation (non participatifs à un projet)
 */
function getAgentsDisponibles($pdo, $projetId = null) {
    $sql = "SELECT 
                a.*,
                d.nom as departement_nom,
                p.photo_profil
            FROM AGENTS a
            LEFT JOIN DEPARTEMENTS d ON d.departement_id = a.departement_id
            LEFT JOIN PROFILS_AGENTS p ON p.agent_id = a.agent_id
            WHERE a.etat = 'Actif'
            AND a.statut = 0";
    
    if ($projetId) {
        $sql .= " AND a.agent_id NOT IN (
                    SELECT agent_id 
                    FROM PARTICIPATION 
                    WHERE projet_id = :projet_id 
                    AND statut = 0
                )";
    }
    
    $sql .= " ORDER BY a.nom, a.prenom";
    
    $stmt = $pdo->prepare($sql);
    if ($projetId) {
        $stmt->execute([':projet_id' => $projetId]);
    } else {
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/**
 * Récupère les participations d'un agent aux projets
 */
function getParticipationsAgent($pdo, $agentId) {
    $sql = "SELECT 
                p.*,
                pr.reference,
                pr.titre as projet_titre,
                pr.etat as projet_etat,
                pr.date_debut as projet_date_debut,
                pr.date_fin_prevue as projet_date_fin,
                CONCAT(u.nom, ' ', u.prenom) as chef_projet
            FROM PARTICIPATION p
            JOIN PROJETS pr ON pr.projet_id = p.projet_id
            LEFT JOIN UTILISATEURS u ON u.utilisateur_id = pr.chef_projet_id
            WHERE p.agent_id = :agent_id
            AND p.statut = 0
            AND pr.statut = 0
            ORDER BY pr.date_debut DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':agent_id' => $agentId]);
    return $stmt->fetchAll();
}

/**
 * Récupère les participations actives d'un agent
 */
function getParticipationsActivesAgent($pdo, $agentId) {
    $sql = "SELECT 
                p.*,
                pr.reference,
                pr.titre as projet_titre,
                pr.etat as projet_etat,
                CONCAT(u.nom, ' ', u.prenom) as chef_projet
            FROM PARTICIPATION p
            JOIN PROJETS pr ON pr.projet_id = p.projet_id
            LEFT JOIN UTILISATEURS u ON u.utilisateur_id = pr.chef_projet_id
            WHERE p.agent_id = :agent_id
            AND p.statut = 0
            AND pr.statut = 0
            AND pr.etat = 'En cours'
            ORDER BY pr.date_debut DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':agent_id' => $agentId]);
    return $stmt->fetchAll();
}

/**
 * Récupère le profil complet d'un agent
 */
function getProfilAgent($pdo, $agentId) {
    $sql = "SELECT * FROM PROFILS_AGENTS WHERE agent_id = :agent_id AND statut = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':agent_id' => $agentId]);
    return $stmt->fetch();
}

/**
 * Recherche des agents par nom, prénom ou email
 */
function searchAgents($pdo, $search) {
    $sql = "SELECT 
                a.*,
                d.nom as departement_nom,
                p.photo_profil,
                p.email_professionnel,
                p.telephone_professionnel
            FROM AGENTS a
            LEFT JOIN DEPARTEMENTS d ON d.departement_id = a.departement_id
            LEFT JOIN PROFILS_AGENTS p ON p.agent_id = a.agent_id
            WHERE a.statut = 0
            AND (a.nom LIKE :search OR a.prenom LIKE :search OR p.email_professionnel LIKE :search)
            ORDER BY a.nom, a.prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':search' => '%' . $search . '%']);
    return $stmt->fetchAll();
}

/**
 * Récupère les agents d'un projet
 */
function getAgentsByProjet($pdo, $projetId) {
    $sql = "SELECT 
                a.*,
                p.photo_profil,
                p.email_professionnel,
                p.telephone_professionnel
            FROM PARTICIPATION part
            JOIN AGENTS a ON a.agent_id = part.agent_id
            LEFT JOIN PROFILS_AGENTS p ON p.agent_id = a.agent_id
            WHERE part.projet_id = :projet_id
            AND part.statut = 0
            AND a.statut = 0
            ORDER BY a.nom, a.prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':projet_id' => $projetId]);
    return $stmt->fetchAll();
}

/**
 * Récupère le nombre d'agents par statut
 */
function getAgentsParStatut($pdo) {
    $sql = "SELECT 
                etat,
                COUNT(*) as total
            FROM AGENTS
            WHERE statut = 0
            GROUP BY etat";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Vérifie si un agent existe déjà (email professionnel)
 */
function agentEmailExists($pdo, $email, $excludeId = null) {
    $sql = "SELECT COUNT(*) as total 
            FROM PROFILS_AGENTS 
            WHERE email_professionnel = :email 
            AND statut = 0";
    $params = [':email' => $email];
    
    if ($excludeId) {
        $sql .= " AND agent_id != :exclude_id";
        $params[':exclude_id'] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

/**
 * Récupère les agents avec leurs statistiques de timesheet
 */
function getAgentsWithTimesheetStats($pdo, $dateDebut, $dateFin) {
    $sql = "SELECT 
                a.*,
                d.nom as departement_nom,
                COALESCE(SUM(t.worked_heures), 0) as total_heures,
                COUNT(t.timesheet_id) as nb_entrees
            FROM AGENTS a
            LEFT JOIN DEPARTEMENTS d ON d.departement_id = a.departement_id
            LEFT JOIN TIMESHEET t ON t.agent_id = a.agent_id 
                AND t.date_travail BETWEEN :date_debut AND :date_fin
                AND t.etat = 'Validée'
                AND t.statut = 0
            WHERE a.etat = 'Actif'
            AND a.statut = 0
            GROUP BY a.agent_id
            ORDER BY total_heures DESC, a.nom, a.prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':date_debut' => $dateDebut,
        ':date_fin' => $dateFin
    ]);
    return $stmt->fetchAll();
}