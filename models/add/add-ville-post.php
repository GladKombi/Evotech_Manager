<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
# creation de l'evenement sur le bouton valider
if (isset($_POST['valider'])) {
    $nom = htmlspecialchars($_POST['nom']);
    $statut = 0;
    #verifier si la ville existe ou pas dans la bd
    $getVilleDeplicant = $connexion->prepare("SELECT * FROM `ville` WHERE nom=? AND statut=?");
    $getVilleDeplicant->execute([$nom, $statut]);
    $tab = $getVilleDeplicant->fetch();
    if ($tab > 0) {
        $_SESSION['msg'] = "Cette Ville existe deja dans la BD!";
        header("location:../../views/ville.php?NewCat");
    } else {
        # Insertion data into DB
        $req = $connexion->prepare("INSERT INTO `ville`(`id`, `nom`, `statut`) VALUES (Null,?,?)");
        $resultat = $req->execute([$nom, $statut]);
        if ($resultat == true) {
            $_SESSION['msg'] = "Enregistrement de la Ville reussi !";
            header("location:../../views/ville.php");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/ville.php");
        }
    }
} else {
    header("location:../../views/ville.php");
}
