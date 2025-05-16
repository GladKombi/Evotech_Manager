<?php
include("../../connexion/connexion.php");
if (isset($_POST["valider"])) {
    if (isset($_GET["idProd"])) {
        $id = $_GET["idProd"];
        $type = htmlspecialchars($_POST['type']);
        $agent = htmlspecialchars($_POST['user']);
        $disk = htmlspecialchars($_POST['disk']);
        $emplacement = htmlspecialchars($_POST['emplacement']);
        # Selection du terrain concerner par la production
        $getProjet = $connexion->prepare("SELECT projet.id FROM `post_production`,participation,projet WHERE post_production.participation=participation.id AND participation.projet=projet.id AND post_production.id=?;");
        $getProjet->execute([$id]);
        $Projet = $getProjet->fetch();
        $SelectedProjet = $Projet['id'];
        # Verification des doublos
        $getProDiplicant = $connexion->prepare("SELECT * FROM `post_production` WHERE Typeproduction=? AND participation=? AND statut=?");
        $getProDiplicant->execute([$type, $agent, $statut]);
        $tab = $getProDiplicant->fetch();
        if ($tab > 0) {
            $_SESSION['msg'] = "Une production similaire est deja enregistrer veillez virifié svp !";
            header("location:../../views/production.php?VoirProd=$SelectedProjet");
        } else {
            $req = $connexion->prepare("UPDATE `post_production` SET `Typeproduction`=?,`participation`=?,`disk`=?,`emplacement`=? WHERE id=?");
            $test = $req->execute(array($type, $agent, $disk, $emplacement, $id));
            if ($test == true) {
                $_SESSION['msg'] = "Modification réussi !";
                header("location:../../views/production.php?VoirProd=$SelectedProjet");
            } else {
                $_SESSION['msg'] = "Echec de modification !";
                header("location:../../views/production.php?VoirProd=$SelectedProjet");
            }
        }
    }
} else {
    header("location:../../views/post-Production.php");
}
