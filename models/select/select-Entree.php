<?php
if (isset($_GET['dollar']) && !isset($_GET['idEntree'])) {
    $title = "Nouvelle Entrée en Dollars";
    $btn = "Enregistrer";
    $action = "../models/add/add-EntreeDollar-post.php";
    # Selection des entrées en dollards
    $type = "Entree";
    $devise = "Dollard";
    $statut = 0;
    $cloture = 0;
    # Affichage dans le tableau
    $getEntreeDolTab = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=? ORDER BY mouvementcaisse.id DESC;");
    $getEntreeDolTab->execute([$type, $devise, $statut, $cloture]);
    // $getEntreeDolar = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=?;");
    // $getEntreeDolar->execute([$type, $devise, $statut]);
} elseif (isset($_GET["Franc"])) {
    $title = "Nouvelle Entrée en Franc";
    $btn = "Enregistrer";
    $action = "../models/add/add-EntreeFranc-post.php";
    # Selection des entrées en Franc
    $type = "Entree";
    $devise = "Franc";
    $statut = 0;
    $cloture = 0;
    $getEntreeFranc = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=? ORDER BY mouvementcaisse.id DESC;");
    $getEntreeFranc->execute([$type, $devise, $statut, $cloture]);
}
if (isset($_GET['dollar']) && isset($_GET['idEntree']) || isset($_GET['Dollard'])) {
    $idModDolar = $_GET['idEntree'];
    $title = "Modification Entrée en Dollars";
    $btn = "Modifier";
    $action = "../models/updat/upDate-EntreeDollar-post.php?idEntree=" . $idModDolar;
    $deviseModif = 'dollar';
    # Selection des entrées en dollards
    $type = "Entree";
    $devise = "Dollard";
    $statut = 0;
    $cloture = 0;
    $getEntreeDolar = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.id=? ;");
    $getEntreeDolar->execute([$type, $devise, $statut, $idModDolar]);
    $terMod = $getEntreeDolar->fetch();
    # Affichage dans le tableau
    $getEntreeDolTab = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=? ORDER BY mouvementcaisse.id DESC;");
    $getEntreeDolTab->execute([$type, $devise, $statut, $cloture]);
}
if (isset($_GET["Franc"]) && !empty($_GET['idEntreeFc']) || isset($_GET['Fc'])) {
    $idModFranc = $_GET['idEntreeFc'];
    $title = "Modification Entrée en Franc";
    $btn = "Modifier";
    $action = "../models/updat/upDate-EntreeFranc-post.php?idEntreeFc=" . $idModFranc;
    # Selection des entrées en dollards
    $type = "Entree";
    $devise = "Franc";
    $statut = 0;
    $cloture = 0;
    $getEntreeFra = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.id=? ;");
    $getEntreeFra->execute([$type, $devise, $statut, $idModFranc]);
    $terMod = $getEntreeFra->fetch();
    # Affichage dans le tableau
    $getEntreeDolTab = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=? ORDER BY mouvementcaisse.id DESC;");
    $getEntreeDolTab->execute([$type, $devise, $statut, $cloture]);
}

# Selection des tous les mouvement de caisses 
$statut = 0;
$cloture = 0;
$typeEnt = "Entree";
$getEntree = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=? ORDER BY mouvementcaisse.id DESC;");
$getEntree->execute([$typeEnt, $statut, $cloture]);
