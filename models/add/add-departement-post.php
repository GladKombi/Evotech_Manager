<?php
include("../../connexion/connexion.php");
if (isset($_POST["Valider"])) {
    $descript = htmlspecialchars($_POST["description"]);
    $pseudo = htmlspecialchars($_POST["pseudo"]);
    $statut = 0;
    if (!empty($descript) && !empty($pseudo) ) {
        #verifier si l'utilisateur existe ou pas dans la bd
        $getDepartDeplicant = $connexion->prepare("SELECT * FROM `departement` WHERE nom=? AND statut=?");
        $getDepartDeplicant->execute([$descript, $statut]);
        $tab = $getDepartDeplicant->fetch();
        if ($tab > 0) {
            $_SESSION['msg'] = "Ce département existe deja dans la Base des donées !";
            header("location:../../views/departement.php");
        } else {
            $req = $connexion->prepare("INSERT INTO `departement`(`nom`, `pseudo`, `statut`) VALUES (?,?,?)");
            $test = $req->execute(array($descript, $pseudo, $statut));
            if ($test == true) {
                $_SESSION['msg'] = "Enregistrement reussi !";
                header("location:../../views/departement.php");
            } else {
                $_SESSION['msg'] = "Echec d'enregistrement !";
                header("location:../../views/departement.php");
            }
        }
    }
} else {
    header("location:../../views/departement.php");
}
