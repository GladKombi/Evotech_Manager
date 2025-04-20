<?php
if(isset($_GET["idPartenaire"])){
    # Script de modification
    $id = $_GET["idPartenaire"];    
    $getPartn = $connexion->prepare("SELECT * FROM `partenaire` WHERE id=?");
    $getPartn->execute([$id]);
    $ShowPartn = $getPartn->fetch();
    $Denomination=$ShowPartn['Denomination']; 
    $title="Modifier les informations de ".$Denomination;
    $btn="Modifier";
    $action="../models/updat/update-Partenaire.php?idPartenaire=" . $id;

}else{
    # Script d'enregistrement
    $title="Enregister un nouveau Partenaire";
    $btn="Enregistrer";
    $action = "../models/add/add-Partenaire-post.php";
}
# Selection des données des partenaires
$statut=0;
$req = $connexion->prepare("SELECT * FROM `partenaire` WHERE statut=? ORDER BY `partenaire`.`id` DESC ");
$req->execute([$statut]);