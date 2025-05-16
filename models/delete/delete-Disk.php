<?php
include("../../connexion/connexion.php");
if (isset($_GET["idDisk"]) && !empty($_GET["idDisk"])) {
    $id=$_GET["idDisk"];
    $statut=1;
    # Arret d'affichage du disk
    $req = $connexion->prepare("UPDATE `disk` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Vous venez de déclasser un disque de stockage!";
        header("location:../../views/disk.php");
    } else {
        $_SESSION['msg'] = "Echec de déclassement !";
        header("location:../../views/disk.php");
    }
} else {
    header("location:../../views/disk.php");
}