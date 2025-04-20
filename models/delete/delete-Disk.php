<?php
include("../../connexion/connexion.php");
if (isset($_GET["idDisk"]) && !empty($_GET["idDisk"])) {
    $id=$_GET["idDisk"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `disk` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/disk.php");
    } else {
        $_SESSION['msg'] = "Echec de modification !";
        header("location:../../views/disk.php");
    }
} else {
    header("location:../../views/disk.php");
}