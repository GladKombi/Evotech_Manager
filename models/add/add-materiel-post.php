<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
# La fonction pour Upload les photos de materiel
require_once('../../fonctions/fonctions.php');
# creation de l'evenement sur le bouton valider
if (isset($_POST['valider'])) {
    $nom = htmlspecialchars($_POST['nom_materiel']);
    $marque = htmlspecialchars($_POST['marque']);
    $categorie = htmlspecialchars($_POST['categorie']);
    $specialite = htmlspecialchars($_POST['specialite']);
    $statut = 0;
    $fichier_tmp = $_FILES['picture']['tmp_name'];
    $nom_original = $_FILES['picture']['name'];
    $destination = "../../assets/img/Matos/";
    $newimage = RecuperPhoto($fichier_tmp, $nom_original, $destination);

    # Insertion data into DB
    $req = $connexion->prepare("INSERT INTO `materiel`(`id`, `nom`, `marque`, `specialite`, `categorie`, `photo`, `statut`) VALUES (Null,?,?,?,?,?,?)");
    $resultat = $req->execute([$nom, $marque, $specialite, $categorie, $newimage, $statut]);
    if ($resultat == true) {
        $_SESSION['msg'] = "Enregistrement reussi !";
        header("location:../../views/materiels-Details.php");
    } else {
        $_SESSION['msg'] = "Echec d'enregistrement !";
        header("location:../../views/materiels-Details.php");
    }
} else {
    header("location:../../views/materiels-Details.php");
}
