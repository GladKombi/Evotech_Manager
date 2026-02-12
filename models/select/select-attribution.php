<?php

# Variables pour le formulaire
$url = "../controllers/attribution-controller.php";
$title = "Nouvelle attribution";
$btn = "Enregistrer";

# Connexion à la base de données
require_once('../connexion/connexion.php');

# Initialisation des variables de modification
$UsersModif = "";
$FonctionModif = "";

# Requête pour récupérer toutes les attributions
$getData = $conn->query("
    SELECT a.attribution_id as id, 
           u.nom, 
           u.prenom, 
           u.email as mail, 
           u.profil, 
           r.fonction
    FROM ATTRIBUTIONS a
    INNER JOIN UTILISATEURS u ON a.utilisateur_id = u.utilisateur_id
    INNER JOIN ROLES r ON a.role_id = r.role_id
    WHERE a.statut = 0
    ORDER BY a.date_creation DESC
");

# Requête pour récupérer les utilisateurs
$getUser = $conn->query("
    SELECT utilisateur_id as id, 
           nom, 
           prenom, 
           email as mail
    FROM UTILISATEURS 
    WHERE statut = 0 
    AND actif = true
    ORDER BY nom ASC
");

# Requête pour récupérer les rôles
$getRole = $conn->query("
    SELECT role_id as id, 
           fonction 
    FROM ROLES 
    WHERE statut = 0 
    ORDER BY fonction ASC
");

# Logique de modification si un ID est passé
if (isset($_GET['idModif'])) {
    $idModif = intval($_GET['idModif']);
    
    # Récupération des données à modifier
    $stmt = $conn->prepare("
        SELECT utilisateur_id, role_id 
        FROM ATTRIBUTIONS 
        WHERE attribution_id = ? AND statut = 0
    ");
    $stmt->execute([$idModif]);
    $attribution = $stmt->fetch();
    
    if ($attribution) {
        $UsersModif = $attribution['utilisateur_id'];
        $FonctionModif = $attribution['role_id'];
        $title = "Modifier l'attribution";
        $btn = "Modifier";
        $url = "../controllers/attribution-controller.php?idModif=" . $idModif;
    }
}
?>