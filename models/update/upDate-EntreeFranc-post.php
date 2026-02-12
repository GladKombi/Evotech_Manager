<?php
include("../../connexion/connexion.php");
if (isset($_POST["valider"])) {
    if (isset($_GET["idEntreeFc"])) {
        $id = $_GET["idEntreeFc"];
        $libellee = htmlspecialchars($_POST["libelle"]);
        $montant = htmlspecialchars($_POST["montant"]);
        $req = $connexion->prepare("UPDATE `mouvementcaisse` SET `libelle`=?,`montant`=? WHERE id=?");
        $test = $req->execute(array($libellee, $montant, $id));
        if ($test == true) {
            $_SESSION['msg'] = "Modification reussi !";
            header("location:../../views/entree.php");
        } else {
            $_SESSION['msg'] = "Echec de modification !";
            header("location:../../views/entree.php");
        }
    }
} else {
    header("location:../../views/entree.php");
}