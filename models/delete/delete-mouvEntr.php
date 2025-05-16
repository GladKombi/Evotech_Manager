<?php
include("../../connexion/connexion.php");
if (isset($_GET["SupMouv"]) && !empty($_GET["SupMouv"])) {
    $id=$_GET["SupMouv"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `mouvementcaisse` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/entree.php");
    } else {
        $_SESSION['msg'] = "Echec de modification !";
        header("location:../../views/entree.php");
    }
} else {
    header("location:../../views/entree.php");
}
