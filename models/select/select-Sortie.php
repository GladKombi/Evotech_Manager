<?php
if (isset($_GET['dollar'])) {
    $title = "Nouvelle Sortie en Dollars";
    $btn = "Enregistrer";
    $action = "../models/add/add-SortieDollar-post.php";
    # Selection des entrées en dollards
    $type = "Sortie";
    $devise = "Dollard";
    $statut = 0;
    $getSortieDolar = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=?;");
    $getSortieDolar->execute([$type, $devise, $statut]);
} elseif (isset($_GET["Franc"])) {
    $title = "Nouvelle Sortie en Franc";
    $btn = "Enregistrer";
    $action = "../models/add/add-SortieFranc-post.php";
    # Selection des entrées en Franc
    $type = "Sortie";
    $devise = "Franc";
    $statut = 0;
    $getSortieFranc = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=?;");
    $getSortieFranc->execute([$type, $devise, $statut]);
}

# Selection des tous les mouvement de caisses 
$statut = 0;
$typeMouv="Sortie";
$getSortie = $connexion->prepare("SELECT * FROM `mouvementcaisse` WHERE mouvementcaisse.type=? AND mouvementcaisse.statut=?;");
$getSortie->execute([$typeMouv, $statut]);
