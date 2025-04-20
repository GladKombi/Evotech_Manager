<?php
include("../../connexion/connexion.php");
if (isset($_POST["valider"])) {
    if (isset($_GET["idProd"])) {
        $id = $_GET["idProd"];
        $type = htmlspecialchars($_POST['type']);
        $agent = htmlspecialchars($_POST['agent']);
        $disk = htmlspecialchars($_POST['disk']);
        $emplacement = htmlspecialchars($_POST['emplacement']);
        # Selection du terrain concerner par la production
        $getTerrain = $connexion->prepare("SELECT terrain.id FROM `post_production`,participation,terrain WHERE post_production.participation=participation.id AND participation.terrain=terrain.id AND post_production.id=?;");
        $getTerrain->execute([$id]);
        $Terrain = $getTerrain->fetch();
        $SelectedTerrain = $Terrain['id'];
        $req = $connexion->prepare("UPDATE `post_production` SET `Typeproduction`=?,`participation`=?,`disk`=?,`emplacement`=? WHERE id=?");
        $test = $req->execute(array($type, $agent, $disk, $emplacement, $id));
        if ($test == true) {
            $_SESSION['msg'] = "Modification réussi !";
            header("location:../../views/post-Production.php?idTerrain=$SelectedTerrain");
        } else {
            $_SESSION['msg'] = "Echec de modification !";
            header("location:../../views/post-Production.php?idTerrain=$SelectedTerrain");
        }
    }
} else {
    header("location:../../views/post-Production.php");
}
