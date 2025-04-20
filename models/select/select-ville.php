<?php
if (isset($_GET['idVille'])) {
    # Lors de la modification
    $id = $_GET['idVille'];
    $getVileMod = $connexion->prepare("SELECT * FROM ville where id='$id'");
    $getVileMod->execute();
    $element = $getVileMod->fetch();
    $title = "Modification d'un ville";
    $Action = "../models/updat/up-ville-post.php?idVille=" . $id;
    $btn = "Modifier";
} else {
    # Lors de l'enregistrement
    $title = "Enregistrer une nouvelle ville";
    $Action = "../models/add/add-ville-post.php";
    $btn = "Enregistrer";
}
# Selection des toutes les villes en activité
$statut = 0;
$getVille = $connexion->prepare("SELECT * FROM `ville` WHERE ville.statut=?;");
$getVille->execute([$statut]);
