<?php
if (isset($_GET['dollar']) && !isset($_GET['idSortie'])) {
    $title = "Nouvelle Sortie en Dollars";
    $btn = "Enregistrer";
    $action = "../models/add/add-SortieDollar-post.php";
    # Selection des Sorties en dollards
    $type = "Sortie";
    $devise = "Dollard";
    $statut = 0;
    $cloture = 0;
    $getSortieDolar = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=? ORDER BY mouvementcaisse.id DESC;");
    $getSortieDolar->execute([$type, $devise, $statut, $cloture]);
} elseif (isset($_GET["Franc"])) {
    $title = "Nouvelle Sortie en Franc";
    $btn = "Enregistrer";
    $action = "../models/add/add-SortieFranc-post.php";
    # Selection des Sorties en Franc
    $type = "Sortie";
    $devise = "Franc";
    $statut = 0;
    $cloture = 0;
    $getSortieFranc = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=? ORDER BY mouvementcaisse.id DESC;");
    $getSortieFranc->execute([$type, $devise, $statut, $cloture]);
}
if (isset($_GET['dollar']) && !empty($_GET['idSortie']) || isset($_GET['Dollard'])) {
    $idModDolar = $_GET['idSortie'];
    $title = "Modification Sortie en Dollars";
    $btn = "Modifier";
    $action = "../models/updat/update-SoritieDollar-post.php?idSortie=" . $idModDolar;
    $deviseModif = 'dollar';
    # Selection de la Sortie en dollards qui subit la modification
    $type = "Sortie";
    $devise = "Dollard";
    $statut = 0;
    $cloture = 0;
    $getSortieDolar = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.id=? ;");
    $getSortieDolar->execute([$type, $devise, $idModDolar]);
    $terMod = $getSortieDolar->fetch();
    # Affichage dans le tableau
    $getSortieDolar = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=? ORDER BY mouvementcaisse.id DESC;");
    $getSortieDolar->execute([$type, $devise, $statut, $cloture]);
}
if (isset($_GET["Franc"]) && !empty($_GET['idSortie'])) {
    $idModFranc = $_GET['idSortie'];
    $title = "Modification Sortie en Franc";
    $btn = "Modifier";
    $action = "../models/updat/update-SortieFranc-post.php?idSortie=" . $idModFranc;
    # Selection des Sorties en dollards
    $type = "Sortie";
    $devise = "Franc";
    $statut = 0;
    $cloture = 0;
    $getEntreeFra = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.id=? ;");
    $getEntreeFra->execute([$type, $devise, $statut, $idModFranc]);
    $terMod = $getEntreeFra->fetch();
    # Affichage dans le tableau
    // $getSortieFraTab = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? ;");
    // $getSortieFraTab->execute([$type, $devise, $statut]);
}

# Selection des tous les mouvement de caisses 
$statut = 0;
$cloture = 0;
$typeMouv = "Sortie";
$getSortie = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=? ORDER BY mouvementcaisse.id DESC;");
$getSortie->execute([$typeMouv, $statut, $cloture]);
