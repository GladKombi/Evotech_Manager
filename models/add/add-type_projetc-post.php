<?php
include("../../connexion/connexion.php");
if (isset($_POST["Valider"])) {
    $Descriprion = htmlspecialchars($_POST["description"]);
    $statut = 0;
    if (!empty($Descriprion) ) {
        #verifier si l'utilisateur existe ou pas dans la bd
        $getTypeDeplicant = $connexion->prepare("SELECT * FROM `type_projetc` WHERE descriprion=? AND statut=?");
        $getTypeDeplicant->execute([$Descriprion, $statut]);
        $tab = $getTypeDeplicant->fetch();
        if ($tab > 0) {
            $_SESSION['msg'] = "Ce département existe deja dans la Base des donées !";
            header("location:../../views/type_projet.php");
        } else {
            $req = $connexion->prepare("INSERT INTO `type_projetc`(`descriprion`, `statut`) VALUES (?,?)");
            $test = $req->execute(array($Descriprion, $statut));
            if ($test == true) {
                $_SESSION['msg'] = "Enregistrement reussi !";
                header("location:../../views/type_projet.php");
            } else {
                $_SESSION['msg'] = "Echec d'enregistrement !";
                header("location:../../views/type_projet.php");
            }
        }
    }
} else {
    header("location:../../views/type_projet.php");
}
