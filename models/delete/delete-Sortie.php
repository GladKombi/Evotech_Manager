<?php
include("../../connexion/connexion.php");
if (isset($_GET["SupSortie"]) && !empty($_GET["SupSortie"])) {
    $id=$_GET["SupSortie"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `mouvementcaisse` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/sortie.php");
    } else {
        $_SESSION['msg'] = "Echec de modification !";
        header("location:../../views/sortie.php");
    }
} else {
    header("location:../../views/sortie.php");
}