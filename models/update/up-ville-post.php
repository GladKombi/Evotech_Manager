<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
# Modification de données d'un Agent
if (isset($_POST["valider"]) && !empty($_GET["idVille"])) {
    $id = $_GET['idVille'];
    $nom = htmlspecialchars($_POST['nom']);
    $statut = 0;
    #verifier si la ville existe ou pas dans la bd
    $getVilleDeplicant = $connexion->prepare("SELECT * FROM `ville` WHERE nom=? AND statut=?");
    $getVilleDeplicant->execute([$nom, $statut]);
    $tab = $getVilleDeplicant->fetch();
    if ($tab > 0) {
        $_SESSION['msg'] = "Modification réussi !";
        header("location:../../views/ville.php");
    } else {
        $InsertData = $connexion->prepare("UPDATE ville SET nom=? WHERE id=?");
        $resultat=$InsertData->execute(array($nom, $id));
        if ($resultat == true) {
            $_SESSION['msg'] = "Modification de la Ville effectueé !";
            header("location:../../views/ville.php");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/ville.php");
        }
    }
}else{
    header("location:../../views/ville.php");
}
