<?php
include("../../connexion/connexion.php");
if (isset($_POST["Valider"])) {
    $description = htmlspecialchars($_POST["description"]);
    $partenaire = htmlspecialchars($_POST["partenaire"]);
    $type= htmlspecialchars($_POST["type"]);
    $statut = 0;
    #verifier si l'utilisateur existe ou pas dans la bd
    $getDepartDeplicant = $connexion->prepare("SELECT * FROM `projet` WHERE `description`=? AND `partenaire`=? AND statut=?");
    $getDepartDeplicant->execute([$description, $partenaire, $statut]);
    $tab = $getDepartDeplicant->fetch();
    if ($tab > 0) {
        $_SESSION['msg'] = "Ce projet existe deja dans la Base des donées !";
        header("location:../../views/project.php");
    } else {
        $req = $connexion->prepare("INSERT INTO `projet`(`date`, `description`, `partenaire`, `type_projet`, `statut`) VALUES (now(),?,?,?,?)");
        $test = $req->execute(array($description, $partenaire, $type, $statut));
        $id = $connexion->lastInsertId();
        if ($test == true) {
            $_SESSION['msg'] = "Enregistrement reussi !";
            header("location:../../views/participation.php?idparticipation=$id");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/project.php");
        }
    }

} elseif (isset($_POST['save']) && !empty($_GET["idparticipation"])) {
    # Ajouter des participant à la commande 
    $statut = 0;
    $id = $_GET['idparticipation'];
    $Agent = htmlspecialchars($_POST['user']);
    #verifier si l'utilisateur existe ou pas dans la bd
    $getPartdeplicant = $connexion->prepare("SELECT * FROM `participation` WHERE agent=? AND projet=? AND statut=?");
    $getPartdeplicant->execute([$Agent, $id, $statut]);
    $tab = $getPartdeplicant->fetch();
    if ($tab > 0) {
        $_SESSION['msg'] = "Cet Agent partcipe deja au projet !";
        header("location:../../views/participation.php?idparticipation=$id");
    } else {
        $query = $connexion->prepare("INSERT INTO `participation`(`agent`, `projet`, `statut`) VALUES (?,?,?)");
        $test = $query->execute(array($Agent, $id, $statut));
        if ($test == true) {
            $_SESSION['msg'] = "Enregistrement de la participation effectué !";
            header("location:../../views/participation.php?idparticipation=$id");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/participation.php?idparticipation=$id");
        }
    }
} else {
    header("location:../../views/project.php");
}
