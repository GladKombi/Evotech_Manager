<?php
include_once '../../connexion/connexion-Temp.php';
if (isset($_POST["valider"])) {
    $statut = 0;
    $description = htmlspecialchars($_POST['description']);
    $lieu = htmlspecialchars($_POST['lieu']);
    $partenaire = htmlspecialchars($_POST['partenaire']);
    $rq = $connexion->prepare("INSERT INTO `terrain` (`date`, `description`, `lieu`,`partenaire`, `statut`) VALUES (NOW(),?,?,?,?)");
    $resultat = $rq->execute([$description, $lieu, $partenaire, $statut]);
    $id = $connexion->lastInsertId();
    if ($resultat == true) {
        $_SESSION['msg'] = "Enregistrement Effectué Avec succes !";
        header("location:../../views/participation.php?idTerr=$id");
    } else {
        $_SESSION['msg'] = "Echec d'enregistrement !";
        header("location:../../views/terrain.php");
    }
} elseif (isset($_POST['save']) && !empty($_GET["idTerr"])) {
    # Ajouter des participant à la commande 
    $statut = 0;
    $id = $_GET['idTerr'];
    $Agent = htmlspecialchars($_POST['agent']);
    #verifier si l'utilisateur existe ou pas dans la bd
    $getPartdeplicant = $connexion->prepare("SELECT * FROM `participation` WHERE agent=? AND terrain=? AND statut=?");
    $getPartdeplicant->execute([$Agent, $id, $statut]);
    $tab = $getPartdeplicant->fetch();
    if ($tab > 0) {
        $_SESSION['msg'] = "Cet Agent est deja enregistré comme participant à ce terrain !";
        header("location:../../views/participation.php?idTerr=$id");
    } else {
        $query = $connexion->prepare("INSERT INTO `participation`(`agent`, `terrain`, `statut`) VALUES (?,?,?)");
        $test = $query->execute(array($Agent, $id, $statut));
        if ($test == true) {
            $_SESSION['msg'] = "Enregistrement de la participation effectué !";
            header("location:../../views/participation.php?idTerr=$id");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/participation.php?idTerr=$id");
        }
    }
} else {
    header("location:../../views/terrain.php");
}
