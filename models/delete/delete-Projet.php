<?php
include("../../connexion/connexion.php");
if (isset($_GET["idSupPro"]) && !empty($_GET["idSupPro"])) {
    $id=$_GET["idSupPro"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `projet` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/project.php");
    } else {
        $_SESSION['msg'] = "Echec de la suppression !";
        header("location:../../views/project.php");
    }
} else {
    header("location:../../views/project.php");
}