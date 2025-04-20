<?php
include("../../connexion/connexion.php");
if (isset($_GET["idSupPro"]) && !empty($_GET["idSupPro"])) {
    $id = $_GET["idSupPro"];
    $statut = 1;
    # Selection du terrain concerner par la production
    $getTerrain = $connexion->prepare("SELECT terrain.id FROM `post_production`,participation,terrain WHERE post_production.participation=participation.id AND participation.terrain=terrain.id AND post_production.id=?;");
    $getTerrain->execute([$id]);
    $Terrain = $getTerrain->fetch();
    $SelectedTerrain = $Terrain['id'];
    $req = $connexion->prepare("UPDATE `post_production` SET `statut`=? WHERE id=?");
    $test = $req->execute(array($statut, $id));
    if ($test == true) {
        $_SESSION['msg'] = "Suppression réussi !";
        header("location:../../views/post-Production.php?idTerrain=$SelectedTerrain");
    } else {
        $_SESSION['msg'] = "Echec de modification !";
        header("location:../../views/post-Production.php?idTerrain=$SelectedTerrain");
    }
} else {
    header("location:../../views/post-Production.php");
}
