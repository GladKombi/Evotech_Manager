<?php
/**
 * models/select/select-dash.php
 * Requêtes pour le tableau de bord Evotech Africa
 */

/**
 * Récupère les projets actifs
 */
function getProjetsActifs($pdo) {
    $sql = "SELECT 
                COUNT(*) as nb,
                COALESCE(SUM(budget_estime), 0) as total
            FROM PROJETS 
            WHERE etat = 'En cours' 
            AND statut = 0";
    $stmt = $pdo->query($sql);
    return $stmt->fetch();
}

/**
 * Récupère le nombre total d'employés actifs
 */
function getTotalEmployes($pdo) {
    $sql = "SELECT COUNT(*) as total 
            FROM AGENTS 
            WHERE etat = 'Actif' 
            AND statut = 0";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Récupère le nombre de contrats actifs
 */
function getContratsActifs($pdo) {
    $sql = "SELECT COUNT(*) as total 
            FROM CONTRATS_COM 
            WHERE etat = 'Actif' 
            AND statut = 0";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Récupère le chiffre d'affaires total
 */
function getChiffreAffaires($pdo) {
    $sql = "SELECT COALESCE(SUM(montant), 0) as total 
            FROM CONTRATS_COM 
            WHERE etat IN ('Actif', 'Terminé') 
            AND statut = 0";
    $stmt = $pdo->query($sql);
    return $stmt->fetch();
}

/**
 * Récupère l'évolution du chiffre d'affaires (mois précédent)
 */
function getTauxEvolution($pdo) {
    $sql = "SELECT COALESCE(SUM(montant), 0) as precedent 
            FROM CONTRATS_COM 
            WHERE date_creation >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)
            AND date_creation < CURRENT_DATE
            AND statut = 0";
    $stmt = $pdo->query($sql);
    return $stmt->fetch();
}

/**
 * Récupère le nombre de projets par département
 */
function getProjetsParDepartement($pdo, $limit = 5) {
    $sql = "SELECT 
                d.nom as departement,
                COUNT(p.projet_id) as total_projets
            FROM DEPARTEMENTS d
            LEFT JOIN AGENTS a ON a.departement_id = d.departement_id
            LEFT JOIN PARTICIPATION part ON part.agent_id = a.agent_id
            LEFT JOIN PROJETS p ON p.projet_id = part.projet_id AND p.statut = 0
            WHERE d.statut = 0
            GROUP BY d.departement_id
            ORDER BY total_projets DESC
            LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère l'évolution des projets sur les 7 derniers jours
 */
function getEvolutionProjets($pdo, $days = 7) {
    $result = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $sql = "SELECT COUNT(*) as total 
                FROM PROJETS 
                WHERE DATE(date_creation) = :date 
                AND statut = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':date' => $date]);
        $row = $stmt->fetch();
        $result[] = $row['total'] ?? 0;
    }
    return $result;
}

/**
 * Récupère les projets en alerte (date de fin proche)
 */
function getAlertesProjets($pdo, $limit = 10) {
    $sql = "SELECT 
                p.projet_id,
                p.reference,
                p.titre,
                p.date_fin_prevue,
                DATEDIFF(p.date_fin_prevue, CURRENT_DATE) as delai,
                CONCAT(u.nom, ' ', u.prenom) as chef_projet,
                COALESCE(
                    (SELECT COUNT(*) * 100 / NULLIF((SELECT COUNT(*) FROM TACHES WHERE projet_id = p.projet_id AND statut = 0), 0)
                     FROM TACHES WHERE projet_id = p.projet_id AND etat = 'Terminé' AND statut = 0), 0
                ) as avancement
            FROM PROJETS p
            LEFT JOIN UTILISATEURS u ON u.utilisateur_id = p.chef_projet_id
            WHERE p.etat = 'En cours'
            AND p.statut = 0
            AND p.date_fin_prevue BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
            ORDER BY p.date_fin_prevue ASC
            LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère les dernières activités
 */
function getDernieresActivites($pdo, $limit = 10) {
    $sql = "(
        SELECT 
            'timesheet' as source,
            t.description_travail as description,
            t.date_creation,
            CONCAT(a.nom, ' ', a.prenom) as agent_nom,
            p.titre as projet_titre,
            t.etat as statut
        FROM TIMESHEET t
        LEFT JOIN AGENTS a ON a.agent_id = t.agent_id
        LEFT JOIN TACHES ta ON ta.tache_id = t.tache_id
        LEFT JOIN PROJETS p ON p.projet_id = ta.projet_id
        WHERE t.statut = 0
        ORDER BY t.date_creation DESC
        LIMIT :limit
    ) UNION (
        SELECT 
            'activite_contrat' as source,
            ac.description_activite as description,
            ac.date_creation,
            NULL as agent_nom,
            c.reference as projet_titre,
            ac.statut_activite as statut
        FROM ACTIVITES_CONTRAT ac
        LEFT JOIN CONTRATS_COM c ON c.contrat_id = ac.contrat_id
        WHERE ac.statut = 0
        ORDER BY ac.date_creation DESC
        LIMIT :limit
    ) ORDER BY date_creation DESC
    LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère les statistiques globales
 */
function getGlobalStats($pdo) {
    $stats = [];
    
    $sql = "SELECT COUNT(*) as total FROM PROJETS WHERE statut = 0";
    $stmt = $pdo->query($sql);
    $stats['total_projets'] = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) as total FROM AGENTS WHERE statut = 0";
    $stmt = $pdo->query($sql);
    $stats['total_employes'] = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) as total FROM CONTRATS_COM WHERE statut = 0";
    $stmt = $pdo->query($sql);
    $stats['total_contrats'] = $stmt->fetchColumn();
    
    $sql = "SELECT COUNT(*) as total FROM UTILISATEURS WHERE statut = 0";
    $stmt = $pdo->query($sql);
    $stats['total_utilisateurs'] = $stmt->fetchColumn();
    
    return $stats;
}

/**
 * Récupère les projets récents
 */
function getProjetsRecents($pdo, $limit = 5) {
    $sql = "SELECT 
                p.*,
                CONCAT(u.nom, ' ', u.prenom) as chef_nom,
                part.nom as partenaire_nom
            FROM PROJETS p
            LEFT JOIN UTILISATEURS u ON u.utilisateur_id = p.chef_projet_id
            LEFT JOIN PARTENAIRES part ON part.partenaire_id = p.partenaire_id
            WHERE p.statut = 0
            ORDER BY p.date_creation DESC
            LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère le nombre de projets par statut
 */
function getProjetsParStatut($pdo) {
    $sql = "SELECT 
                etat,
                COUNT(*) as total
            FROM PROJETS
            WHERE statut = 0
            GROUP BY etat";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère le nombre d'agents par département
 */
function getAgentsParDepartement($pdo) {
    $sql = "SELECT 
                d.nom as departement,
                COUNT(a.agent_id) as total
            FROM DEPARTEMENTS d
            LEFT JOIN AGENTS a ON a.departement_id = d.departement_id AND a.etat = 'Actif' AND a.statut = 0
            WHERE d.statut = 0
            GROUP BY d.departement_id
            ORDER BY total DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}