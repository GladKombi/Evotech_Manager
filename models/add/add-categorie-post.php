<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
# creation de l'evenement sur le bouton valider
if (isset($_POST['valider'])) {
    $nom = htmlspecialchars($_POST['nom_Categorie']);
    $statut = 0;

    #verifier si l'utilisateur existe ou pas dans la bd
    $getCatDeplicant = $connexion->prepare("SELECT * FROM `categorie` WHERE nom=? AND statut=?");
    $getCatDeplicant->execute([$nom, $statut]);
    $tab = $getCatDeplicant->fetch();
    if ($tab > 0) {
        $_SESSION['msg'] = "Cette Catégorie existe deja dans la BD!";
        header("location:../../views/materiels-Details.php?NewCat");
    } else {
        # Insertion data into DB
        $req = $connexion->prepare("INSERT INTO `categorie`(`id`, `nom`, `statut`) VALUES (Null,?,?)");
        $resultat = $req->execute([$nom, $statut]);
        if ($resultat == true) {
            $_SESSION['msg'] = "Enregistrement de la catégorie reussi !";
            header("location:../../views/materiels-Details.php");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/materiels-Details.php");
        }
    }
} else {
    header("location:../../views/materiels-Details.php");
}
