<?php
include("../../connexion/connexion.php");
if (isset($_GET["idSupPart"]) && !empty($_GET["idSupPart"])) {
    $id=$_GET["idSupPart"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `partenaire` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/partenaire.php");
    } else {
        $_SESSION['msg'] = "Echec de modification !";
        header("location:../../views/partenaire.php");
    }
} else {
    header("location:../../views/partenaire.php");
}
