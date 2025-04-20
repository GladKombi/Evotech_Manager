<?php
if (isset($_GET['iduser'])) {
    $id = $_GET['iduser'];
    $getDataMod = $connexion->prepare("SELECT * FROM utilisateur WHERE id=?");
    $getDataMod->execute([$id]);
    $tab = $getDataMod->fetch();

    $url = "../models/updat/up-utilisateur-post.php?iduser=" . $id;
    $btn = "Modifier";
    $title = "Modifier Utilisateur";
} else {
    $url = "../models/add/add-utilisateur-post.php";
    $btn = "valider";
    $title = "Ajouter Utilisateur";
}
$statut=0;
$getData = $connexion->prepare("SELECT * FROM `users` WHERE statut=?");
$getData->execute([$statut]);
