<?php
include("../../connexion/connexion.php");
if (isset($_POST["Valider"])) {
    if (isset($_GET["idModif"]) && !empty($_GET["idModif"])) {
        $id = $_GET["idModif"];
        $Descriprion = htmlspecialchars($_POST["description"]);
        $statut = 0;
        if (!empty($Descriprion)) {
            #verifier si l'utilisateur existe ou pas dans la bd
            $getTypeDeplicant = $connexion->prepare("SELECT * FROM `type_projetc` WHERE descriprion=? AND statut=?");
            $getTypeDeplicant->execute([$Descriprion, $statut]);
            $tab = $getTypeDeplicant->fetch();
            if ($tab > 0) {
                $_SESSION['msg'] = "Ce département existe deja dans la Base des donées !";
                header("location:../../views/type_projet.php");
            } else {
                $req = $connexion->prepare("UPDATE `type_projetc` SET `descriprion`=? WHERE id=?");
                $test = $req->execute(array($Descriprion, $id));
                if ($test == true) {
                    $_SESSION['msg'] = "Modification réussi !";
                    header("location:../../views/type_projet.php");
                } else {
                    $_SESSION['msg'] = "Echec de modification !";
                    header("location:../../views/type_projet.php");
                }
            }
        } else {
            $_SESSION['msg'] = "Veillez remplir les champs !";
            header("location:../../views/type_projet.php");
        }
    } else {
        header("location:../../views/type_projet.php");
    }
} else {
    header("location:../../views/type_projet.php");
}
