<?php
if (isset($_GET["idModif"])) {
    $id = $_GET["idModif"];
    $gettype_projetc = $connexion->prepare("SELECT * FROM `type_projetc` WHERE id=?");
    $gettype_projetc->execute([$id]);
    $Afichtype_projetc = $gettype_projetc->fetch();
    $title = "Modifier le type_projetc " . $Afichtype_projetc['descriprion'];
    $btn = "Modifier";
    $url = "../models/updat/update-type_projetc-post.php?idModif=" . $id;
} else {
    $title = "Ajouter un nouveau type_projetc";
    $btn = "Enregistrer";
    $url = "../models/add/add-type_projetc-post.php";
}
$statut=0;
$getData = $connexion->prepare("SELECT * FROM `type_projetc` WHERE statut=? ORDER BY `type_projetc`.`id` DESC");
$getData->execute([$statut]);
