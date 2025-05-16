<?php
include("../../connexion/connexion.php");
if (isset($_GET["idSupType"]) && !empty($_GET["idSupType"])) {
    $id=$_GET["idSupType"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `type_projetc` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/type_projet.php");
    } else {
        $_SESSION['msg'] = "Echec de la suppression !";
        header("location:../../views/type_projet.php");
    }
} else {
    header("location:../../views/type_projet.php");
}