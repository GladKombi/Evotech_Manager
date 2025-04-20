<?php
include_once '../../connexion/connexion-Temp.php';
if (isset($_POST["valider"])){
    $statut=1;
    $denomination=htmlspecialchars($_POST['nom']);
    $adresse=htmlspecialchars($_POST['adresse']);
    $telephone=htmlspecialchars($_POST['telephone']);
    $rq = $connexion->prepare("INSERT INTO `partenaire` (`Denomination`, `dateSignature`, `adresse`, `telephone`, `statut`) VALUES (?,NOW(),?,?,?)");
    $resultat = $rq->execute([$denomination, $adresse, $telephone, $statut]);
    if ($resultat == true) {
        $_SESSION['msg'] = "Enregistrement Effectué Avec succes !";
        header("location:../../views/partenaire.php");
    } else {
        $_SESSION['msg'] = "Echec d'enregistrement !";
        header("location:../../views/partenaire.php");
    }
}else{
    header("location:../../views/partenaire.php");
}
