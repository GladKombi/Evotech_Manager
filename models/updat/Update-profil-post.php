<?php
# Inclusion de la page de connexion
include('../../connexion/connexion.php');
# Fonction pour Upuload l'image
require_once('../../fonctions/fonctions.php');
# Creation de l'evenement sur le bouton valider
if (isset($_POST['valider'])) {
    $fichier_tmp = $_FILES['picture']['tmp_name'];
    $nom_original = $_FILES['picture']['name'];
    $destination = "../../assets/img/profiles/";
    
    if ($_SESSION['User'] === "Admin" || $_SESSION['User'] === "ceo") {
        # Mise a jour de la photo
        echo 'Admin';
        # fonction permettant de recuperer la photo
        $newimage = RecuperPhoto($fichier_tmp, $nom_original, $destination);
        echo $newimage;
        $req = $connexion->prepare("UPDATE `users` SET `profil`=? WHERE `users`.`id`=?");
        $resultat = $req->execute([$newimage, $id]);
        if ($resultat == true) {
            $_SESSION['msg'] = "Modification reussi !";
            header("location:../../views/profil.php");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/profil.php");
        }
    } else {
        # Mise a jour de la photo
        echo 'Agent';
        $req = $connexion->prepare("UPDATE `agents` SET `profil`=? WHERE id=?");
        $resultat = $req->execute([$newimage, $id]);
        if ($resultat == true) {
            $_SESSION['msg'] = "Modification reussi !";
            header("location:../../views/profil.php");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/profil.php");
        }
    }
} else {
    header("location:../../views/profil.php");
}
