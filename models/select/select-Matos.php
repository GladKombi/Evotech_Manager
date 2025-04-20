<?php
if (isset($_GET["idModif"])) {
    $id = $_GET["idModif"];
    $getdepart = $connexion->prepare("SELECT * FROM departement WHERE id=?");
    $getdepart->execute([$id]);
    $AfichDepartement = $getdepart->fetch();
    $title = "Modifier departement " . $AfichDepartement['nom_Departement'];
    $btn = "Modifier";
    $action = "../models/updat/update-Departement-post.php?idModif=" . $id;
} elseif (isset($_GET["NewCat"])) {
    $title = "Ajouter une nouvelle categorie de materiel";
    $btn = "Enregistrer";
    $action = "../models/add/add-categorie-post.php";

    
} else {
    $title = "Ajouter un nouveau materiel";
    $btn = "Enregistrer";
    $action = "../models/add/add-materiel-post.php";
}
$statut = 0;
$getData = $connexion->prepare("SELECT `materiel`.*, categorie.nom as nomcat FROM materiel,categorie WHERE materiel.categorie=categorie.id AND materiel.statut=? ORDER BY `materiel`.`id` DESC");
$getData->execute([$statut]);

# Selection des categories des materiels 
$getCatMatos = $connexion->prepare("SELECT * FROM `categorie` WHERE statut=?");
$getCatMatos->execute([$statut]);
