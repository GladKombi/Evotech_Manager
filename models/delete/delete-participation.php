<?php
include("../../connexion/connexion.php");
if (isset($_GET["SupAffectation"]) && !empty($_GET["SupAffectation"])) {
    $id=$_GET["SupAffectation"];
    $idPro = $_GET['idparticipation'];
    $statut=1;
    $req = $connexion->prepare("UPDATE `participation` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/participation.php?idparticipation=$idPro");
    } else {
        $_SESSION['msg'] = "Echec de la suppression !";
        header("location:../../views/participation.php?idparticipation=$idPro");
    }
} else {
    header("location:../../views/participation.php");
}