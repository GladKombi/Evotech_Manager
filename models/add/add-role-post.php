<?php
include("../../connexion/connexion.php");
if (isset($_POST["Valider"])) {
    $role = htmlspecialchars($_POST["fonction"]);
    $statut = 0;
    if (!empty($role) ) {
        #verifier si l'utilisateur existe ou pas dans la bd
        $getRoleDeplicant = $connexion->prepare("SELECT * FROM `role` WHERE fonction=? AND statut=?");
        $getRoleDeplicant->execute([$role, $statut]);
        $tab = $getRoleDeplicant->fetch();
        if ($tab > 0) {
            $_SESSION['msg'] = "Ce département existe deja dans la Base des donées !";
            header("location:../../views/roles.php");
        } else {
            $req = $connexion->prepare("INSERT INTO `role`(`fonction`, `statut`) VALUES (?,?)");
            $test = $req->execute(array($role, $statut));
            if ($test == true) {
                $_SESSION['msg'] = "Enregistrement reussi !";
                header("location:../../views/roles.php");
            } else {
                $_SESSION['msg'] = "Echec d'enregistrement !";
                header("location:../../views/roles.php");
            }
        }
    }
} else {
    header("location:../../views/roles.php");
}
