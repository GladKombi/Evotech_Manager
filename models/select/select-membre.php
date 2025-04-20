<?php
if (isset($_GET['idMembre']) && !empty($_GET['idMembre'])) {
    $id = $_GET['idMembre'];
    $getMemberMod = $connexion->prepare("SELECT * FROM membre where id=?");
    $getMemberMod->execute([$id]);
    $element = $getMemberMod->fetch();
    $title = "Modification d'un Membre";
    $Action = "../models/updat/up-member-post.php?idMembre=" . $id;
    $btn = "Modifier";
} else {
    $title = "Enregistrer un nouveau membre";
    $Action = "../models/add/add-membre-post.php";
    $btn = "Modifier";
}
# Selection des toutes les villes en activité
$statut = 0;
$Approbation = 1;
$getVille = $connexion->prepare("SELECT * FROM `ville` WHERE ville.statut=?;");
$getVille->execute([$statut]);
# Selection donnée des membres
$getMember = $connexion->prepare("SELECT `membre`.*, ville.nom AS NomVille FROM `membre`,ville WHERE membre.ville=ville.id AND membre.statut=? AND membre.aprobation=?;");
$getMember->execute([$statut, $Approbation]);
