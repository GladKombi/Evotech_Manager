<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
require_once('../../fonctions/fonctions.php');
// creation de l'evenement sur le bouton valider
if (isset($_POST['valider']) && !empty($_GET['idProjet'])) {
    $idProjet = $_GET["idProjet"];
    $type = htmlspecialchars($_POST['type']);
    $agent = htmlspecialchars($_POST['user']);
    $disk = htmlspecialchars($_POST['disk']);
    $emplacement = htmlspecialchars($_POST['emplacement']);
    $statut=0;
    $livraison=0;
    $etat=0;
    # Verification des doublos
    $getProDiplicant = $connexion->prepare("SELECT * FROM `post_production` WHERE Typeproduction=? AND participation=? AND statut=?");
    $getProDiplicant->execute([$type,$agent, $statut]);
    $tab = $getProDiplicant->fetch();
    if ($tab > 0) {
        $_SESSION['msg'] = "Une production similaire est deja enregistrer veillez virifié svp !";
        header("location:../../views/production.php?NewProduction=$idProjet");
    } else {
        # Insertion data from database
        $req = $connexion->prepare("INSERT INTO `post_production`(`Typeproduction`, `participation`, `disk`, `emplacement`, `etat`, `livraison`, `statut`) VALUES (?,?,?,?,?,?,?)");
        $resultat = $req->execute([$type, $agent, $disk, $emplacement, $etat, $livraison, $statut]);
        if ($resultat == true) {
            $_SESSION['msg'] = "Enregistrement reussi !";
            header("location:../../views/production.php?NewProduction=$idProjet");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/production.php?NewProduction=$idProjet");
        }
    }
} else {
    header("location:../../views/production.php");
}
