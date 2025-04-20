<?php
include("../../connexion/connexion.php");
if (isset($_POST["Valider"])) {
    if (isset($_GET["idModif"]) && !empty($_GET["idModif"])) {
        $id = $_GET["idModif"];
        $descript = htmlspecialchars($_POST["description"]);
        $pseudo = htmlspecialchars($_POST["pseudo"]);
        $statut = 0;
        if (!empty($descript) && !empty($pseudo)) {
            #verifier si l'utilisateur existe ou pas dans la bd
            $getDepartDeplicant = $connexion->prepare("SELECT * FROM `departement` WHERE nom=? AND pseudo=? AND statut=?");
            $getDepartDeplicant->execute([$descript, $pseudo, $statut]);
            $tab = $getDepartDeplicant->fetch();
            if ($tab > 0) {
                $_SESSION['msg'] = "Modification Réussi !";
                header("location:../../views/departement.php");
            }
            $getDepartDepli = $connexion->prepare("SELECT * FROM `departement` WHERE nom=? AND pseudo=? AND statut=? AND id!=?");
            $getDepartDepli->execute([$descript, $pseudo, $statut, $id]);
            ($Eleve = $getDepartDepli->fetch());
            if ($Eleve > 0) {
                $_SESSION['msg'] = "Un département similaire existe deja dans la base des données !";
                header("location:../../views/departement.php");
            } else {
                $req = $connexion->prepare("UPDATE `departement` SET `nom`=?,`pseudo`=? WHERE id=?");
                $test = $req->execute(array($descript, $pseudo, $id));
                if ($test == true) {
                    $_SESSION['msg'] = "Modification réussi !";
                    header("location:../../views/departement.php");
                } else {
                    $_SESSION['msg'] = "Echec de modification !";
                    header("location:../../views/departement.php");
                }
            }
        }
    } else {
        header("location:../../views/departement.php");
    }
} else {
    header("location:../../views/departement.php");
}
