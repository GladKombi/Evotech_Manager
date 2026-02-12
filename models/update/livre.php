<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
require_once('../../fonctions/fonctions.php');
// creation de l'evenement sur le bouton valider
if (isset($_GET["idPro"]) && !empty($_GET["idPro"]) && isset($_GET["VoirProd"])) {
    $id=$_GET["idPro"];
    $idTer=$_GET["VoirProd"];
    $livraison=1;
    $req = $connexion->prepare("UPDATE `post_production` SET `livraison`=? WHERE id=?");
    $test = $req->execute(array($livraison, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Enregistrement de la livraison effectuée !";
        header("location:../../views/post-Production.php?VoirProd=$idTer");
    } else {
        $_SESSION['msg'] = "Echec de l'enregistrement de la livraison !";
        header("location:../../views/post-Production.php?VoirProd=$idTer");
    }
} else {
    header("location:../../views/post-Production.php?VoirProd=$idTer");
}
