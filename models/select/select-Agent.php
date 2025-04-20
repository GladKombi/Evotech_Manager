<?php
if (isset($_GET["idAgent"])) {
    $id = $_GET["idAgent"];
    $getDataMod = $connexion->prepare("SELECT * FROM users WHERE id=?");
    $getDataMod->execute([$id]);
    $tab = $getDataMod->fetch();
    $departementModif = $tab['departement'];
    $title = "Modifier identité de l'agent " . $tab['nom'] . " " . $tab['prenom'];
    $btn = "Modifier";
    $url = "../models/updat/update-Agent-post.php?idAgent=" . $id;
} else {
    $title = "Enregister un nouvel agent";
    $btn = "Enregistrer";
    $url = "../models/add/add-Agent-post.php";
}
# Selection des departement de la DB
$statut = 0;
$getDepartement = $connexion->prepare("SELECT * FROM `departement` WHERE departement.statut=?;");
$getDepartement->execute([$statut]);
# Selection Des données des agents
$getData = $connexion->prepare("SELECT `users`.*, departement.nom AS deparetement FROM `users`, `departement` WHERE users.departement=departement.id AND users.statut=? ORDER BY `users`.`id` DESC;");
$getData->execute([$statut]);

