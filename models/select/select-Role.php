<?php
if (isset($_GET["idModif"])) {
    $id = $_GET["idModif"];
    $getRole = $connexion->prepare("SELECT * FROM `role` WHERE id=?");
    $getRole->execute([$id]);
    $AfichRole = $getRole->fetch();
    $title = "Modifier le Rôle " . $AfichRole['fonction'];
    $btn = "Modifier";
    $url = "../models/updat/update-Role-post.php?idModif=" . $id;
} else {
    $title = "Ajouter un nouveau rôle";
    $btn = "Enregistrer";
    $url = "../models/add/add-role-post.php";
}
$statut=0;
$getData = $connexion->prepare("SELECT * FROM `role` WHERE statut=? ORDER BY `role`.`id` DESC");
$getData->execute([$statut]);
