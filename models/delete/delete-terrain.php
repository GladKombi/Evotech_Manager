<?php
include("../../connexion/connexion.php");
if (isset($_GET["SupTer"]) && !empty($_GET["SupTer"])) {
    $id=$_GET["SupTer"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `terrain` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/terrain.php");
    } else {
        $_SESSION['msg'] = "Echec de modification !";
        header("location:../../views/terrain.php");
    }
} else {
    header("location:../../views/terrain.php");
}
