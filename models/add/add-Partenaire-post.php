<?php
include_once '../../connexion/connexion.php';
if (isset($_POST["valider"])) {
    $statut = 0;
    $denomination = htmlspecialchars($_POST['nom']);
    $adresse = htmlspecialchars($_POST['adresse']);
    $telephone = htmlspecialchars($_POST['telephone']);
    #verifier si l'utilisateur existe ou pas dans la bd
    $getPartenDeplicant = $connexion->prepare("SELECT * FROM `partenaire` WHERE telephone=? AND statut=?");
    $getPartenDeplicant->execute([$telephone, $statut]);
    $tab = $getPartenDeplicant->fetch();
    if ($tab > 0) {
        $_SESSION['msg'] = "un partenaire avec les informations similaires existe deja dans la Base des donées !";
        header("location:../../views/partenaire.php");
    } else {
        $rq = $connexion->prepare("INSERT INTO `partenaire` (`Denomination`, `dateSignature`, `adresse`, `telephone`, `statut`) VALUES (?,NOW(),?,?,?)");
        $resultat = $rq->execute([$denomination, $adresse, $telephone, $statut]);
        if ($resultat == true) {
            $_SESSION['msg'] = "Enregistrement effectué Avec succes !";
            header("location:../../views/partenaire.php");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/partenaire.php");
        }
    }
} else {
    header("location:../../views/partenaire.php");
}
