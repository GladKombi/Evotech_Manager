<?php
include("../../connexion/connexion.php");
if (isset($_POST["Valider"])) {
    if (isset($_GET["idModif"]) && !empty($_GET["idModif"])) {
        $id = $_GET["idModif"];
        $role = htmlspecialchars($_POST["fonction"]);
        $statut = 0;
        if (!empty($role)) {
            #verifier si l'utilisateur existe ou pas dans la bd
            $getRoleDeplicant = $connexion->prepare("SELECT * FROM `role` WHERE fonction=? AND statut=?");
            $getRoleDeplicant->execute([$role, $statut]);
            $tab = $getRoleDeplicant->fetch();
            if ($tab > 0) {
                $_SESSION['msg'] = "Un Rôle similaire existe deja dans la base des données !";
                header("location:../../views/roles.php");
            } else {
                $req = $connexion->prepare("UPDATE `role` SET `fonction`=? WHERE id=?");
                $test = $req->execute(array($role, $id));
                if ($test == true) {
                    $_SESSION['msg'] = "Modification réussi !";
                    header("location:../../views/roles.php");
                } else {
                    $_SESSION['msg'] = "Echec de modification !";
                    header("location:../../views/roles.php");
                }
            }
        } else {
            $_SESSION['msg'] = "Veillez remplir tous les champs !";
            header("location:../../views/roles.php");
        }
    } else {
        header("location:../../views/roles.php");
    }
} else {
    header("location:../../views/roles.php");
}
