<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
// creation de l'evenement sur le bouton valider
if (isset($_POST['valider'])) {
    $devise = "Franc";
    $type = "Sortie";
    $libelle = htmlspecialchars($_POST['libelle']);
    $montant = htmlspecialchars($_POST['montant']);
    $statut = 0;
    # Insertion data from database
    $req = $connexion->prepare("INSERT INTO `mouvementcaisse`(`date`, `type`, `libelle`, `montant`, `devise`, `statut`) VALUES (NOW(),?,?,?,?,?)");
    $resultat = $req->execute([$type, $libelle, $montant, $devise, $statut]);
    if ($resultat == true) {
        $_SESSION['msg'] = "Enregistrement d'une nouvelle Sortie en 'Fc' effectué !";
        header("location:../../views/sortie.php");
    } else {
        $_SESSION['msg'] = "Echec d'enregistrement !";
        header("location:../../views/sortie.php");
    }
} else {
    header("location:../../views/sortie.php");
}
