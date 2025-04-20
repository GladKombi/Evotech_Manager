<?php
if (isset($_GET["idTerrain"])) {
    # Modification terrain
    $id = $_GET["idTerrain"];
    # Selection des données du terrain à modifier
    $getModifTer = $connexion->prepare("SELECT terrain.*, partenaire.Denomination FROM terrain, partenaire WHERE terrain.partenaire=partenaire.id AND terrain.id=?");
    $getModifTer->execute([$id]);
    $terMod = $getModifTer->fetch();
    $partenaire = $terMod["Denomination"];
    $idPart=$terMod["partenaire"];
    $title = "Modifier le terrain de";
    $btn = "Modifier";
    $action = "../models/updat/update-terrain.php?idTerrain=" . $id;
} else {
    $title = "Enregister un nouveau terrain";
    $btn = "Enregistrer";
    $action = "../models/add/add-Terrain-post.php";
}
# Selection des Terrains
$statut = 0;
$req = $connexion->prepare("SELECT terrain.*, partenaire.Denomination FROM terrain, partenaire WHERE terrain.partenaire=partenaire.id AND terrain.statut=?");
$req->execute([$statut]);

# Selection des partenaires dans le Cmb
$getPartenaire = $connexion->prepare("SELECT * FROM `partenaire` ");
$getPartenaire->execute();
