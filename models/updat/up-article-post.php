<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
# Fonction pour upload les images
require_once('../../fonctions/fonctions.php');
if (isset($_POST['valider'])) {
    $id = $_GET["idArticle"];
    $titre = htmlspecialchars($_POST['titre']);
    $text = htmlspecialchars($_POST['text']);
    $fichier_tmp = $_FILES['picture']['tmp_name'];
    $nom_original = $_FILES['picture']['name'];
    $destination = "../../assets/img/mis_enavant/";
    # fonction permettant de recuperer la photo
    $newimage = RecuperPhoto($fichier_tmp, $nom_original, $destination);
    # Insertion data into DB
    $InsertData = $connexion->prepare("UPDATE `article` SET `titre`=?,`contenus`=?,`image`=? WHERE id=?");
    $resultat = $InsertData->execute(array($titre, $text, $newimage, $id));
    if ($resultat == true) {
        $_SESSION['msg'] = "Modification de l'article effectueé !";
        header("location:../../views/article.php");
    } else {
        $_SESSION['msg'] = "Echec de modicitation!";
        header("location:../../views/article.php");
    }
} else {
    header("location:../../views/article.php");
}
