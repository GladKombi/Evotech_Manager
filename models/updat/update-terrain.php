<?php
include("../../connexion/connexion.php");
if (isset($_POST["valider"])) {
    if (isset($_GET["idTerrain"])) {
        $id = $_GET["idTerrain"];
        $description = htmlspecialchars($_POST['description']);
        $lieu = htmlspecialchars($_POST['lieu']);
        $partenaire = htmlspecialchars($_POST['partenaire']);
        $req = $connexion->prepare("UPDATE `terrain` SET `description`=?,`lieu`=?,`partenaire`=? WHERE id=?");
        $test = $req->execute(array($description, $lieu, $partenaire, $id));
        if ($test == true) {
            $_SESSION['msg'] = "Modification réussi !";
            header("location:../../views/terrain.php");
        } else {
            $_SESSION['msg'] = "Echec de modification !";
            header("location:../../views/terrain.php");
        }
    }
} else {
    header("location:../../views/terrain.php");
}
