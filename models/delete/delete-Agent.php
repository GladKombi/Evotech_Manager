<?php
include("../../connexion/connexion.php");
if (isset($_GET["idSupAgent"]) && !empty($_GET["idSupAgent"])) {
    $id=$_GET["idSupAgent"];
    $statut=1;
    $req = $connexion->prepare("UPDATE `users` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression reussi !";
        header("location:../../views/agent.php");
    } else {
        $_SESSION['msg'] = "Echec de Suppression !";
        header("location:../../views/agent.php");
    }
} else {
    header("location:../../views/agent.php");
}
