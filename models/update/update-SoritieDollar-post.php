<?php
include("../../connexion/connexion.php");
if (isset($_POST["valider"])) {
    if (isset($_GET["idSortie"])&& !empty($_GET['idSortie'])) {
        $id = $_GET["idSortie"];
        $libellee = htmlspecialchars($_POST["libelle"]);
        $montant = htmlspecialchars($_POST["montant"]);
        $req = $connexion->prepare("UPDATE `mouvementcaisse` SET `libelle`=?,`montant`=? WHERE id=?");
        $test = $req->execute(array($libellee, $montant, $id));
        if ($test == true) {
            $_SESSION['msg'] = "Modification reussi !";
            header("location:../../views/sortie.php");
        } else {
            $_SESSION['msg'] = "Echec de modification !";
            header("location:../../views/sortie.php");
        }
    }
} else {
    header("location:../../views/sortie.php");
}