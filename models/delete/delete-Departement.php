<?php
include("../../connexion/connexion.php");
if (isset($_GET["idSupDepar"]) && !empty($_GET["idSupDepar"])) {
    $id=$_GET["idSupDepar"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `departement` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/departement.php");
    } else {
        $_SESSION['msg'] = "Echec de la suppression !";
        header("location:../../views/departement.php");
    }
} else {
    header("location:../../views/departement.php");
}
