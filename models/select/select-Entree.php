<?php
if (isset($_GET['dollar'])) {
    $title = "Nouvelle Entrée en Dollars";
    $btn = "Enregistrer";
    $action = "../models/add/add-EntreeDollar-post.php";
    # Selection des entrées en dollards
    $type = "Entree";
    $devise = "Dollard";
    $statut = 0;
    $getEntreeDolar = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=?;");
    $getEntreeDolar->execute([$type, $devise, $statut]);
} elseif (isset($_GET["Franc"])) {
    $title = "Nouvelle Entrée en Franc";
    $btn = "Enregistrer";
    $action = "../models/add/add-EntreeFranc-post.php";
    # Selection des entrées en Franc
    $type = "Entree";
    $devise = "Franc";
    $statut = 0;
    $getEntreeFranc = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=?;");
    $getEntreeFranc->execute([$type, $devise, $statut]);
}
if(isset($_GET['Dollard']) && !empty($_GET['idEntree'])){
    $idModDolar=$_GET['idEntree'];
    $title = "Modification Entrée en Dollars";
    $btn = "Modifier";
    $action = "../models/add/add-EntreeDollar-post.php";
    # Selection des entrées en dollards
    $type = "Entree";
    $devise = "Dollard";
    $statut = 0;
    $getEntreeDolar = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.id=? ;");
    $getEntreeDolar->execute([$type, $devise, $statut,$idModDolar]);
}

# Selection des tous les mouvement de caisses 
$statut = 0;
$typeEnt="Entree";
$getEntree = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.statut=?;");
$getEntree->execute([$typeEnt, $statut]);
