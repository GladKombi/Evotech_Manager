<?php
include("../../connexion/connexion.php");
if (isset($_GET["idMember"]) && !empty($_GET["idMember"])) {
    $id=$_GET["idMember"];
    $Approbation=1;
    $req = $connexion->prepare("UPDATE `membre` SET `aprobation`=? WHERE id=?");
    $test = $req->execute(array($Approbation, $id));
    if ($test == true) {
        $_SESSION['msg'] = "L'inscription viens d'être approuvée !";
        header("location:../../views/validation.php");
    } else {
        $_SESSION['msg'] = "Echec ! L'inscription n'a pas été approuvée !";
        header("location:../../views/validation.php");
    }
} else {
    header("location:../../views/validation.php");
}