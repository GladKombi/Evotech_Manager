<?php
include("../../connexion/connexion.php");
if (isset($_GET["idSupRole"]) && !empty($_GET["idSupRole"])) {
    $id=$_GET["idSupRole"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `role` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/roles.php");
    } else {
        $_SESSION['msg'] = "Echec de la suppression !";
        header("location:../../views/roles.php");
    }
} else {
    header("location:../../views/roles.php");
}