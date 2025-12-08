<?php
include("../../connexion/connexion.php");
if (isset($_POST["Valider"])) {
    if (isset($_GET["idProjet"]) && !empty($_GET["idProjet"])) {
        $id = $_GET["idProjet"];
        $description = htmlspecialchars($_POST["description"]);
        $partenaire = htmlspecialchars($_POST["partenaire"]);
        $type = htmlspecialchars($_POST["type"]);
        $statut = 0;
        #verifier si l'existances des donées ou pas dans la bd
        $getProjetDeplicant = $connexion->prepare("SELECT * FROM `projet` WHERE `description`=? AND `description`=? AND statut=? AND id!=?");
        $getProjetDeplicant->execute([$description, $partenaire, $statut,$id]);
        $tab = $getProjetDeplicant->fetch();
        if ($tab > 0) {
            $_SESSION['msg'] = "Des information similaire existe dans la base des données !";
            header("location:../../views/project.php");
        } else {
            $req = $connexion->prepare("UPDATE `projet` SET `description`=?, `partenaire`=?, `type_projet`=? WHERE id=?");
            $test = $req->execute(array($description, $partenaire, $type, $id));
            if ($test == true) {
                $_SESSION['msg'] = "Modification réussi !";
                header("location:../../views/project.php");
            } else {
                $_SESSION['msg'] = "Echec de modification !";
                header("location:../../views/project.php");
            }
        }
    } else {
        header("location:../../views/project.php");
    }
} else {
    header("location:../../views/project.php");
}
