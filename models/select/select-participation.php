<?php
if (isset($_GET["idTerr"])) {
    $id = $_GET["idTerr"];

    # Selection des details du terrain
    $getTerrainDetails = $connexion->prepare("SELECT partenaire.Denomination, terrain.lieu, terrain.date FROM terrain,partenaire WHERE terrain.partenaire=partenaire.id AND terrain.id=?;");
    $getTerrainDetails->execute([$id]);
    $TerrainDetails = $getTerrainDetails->fetch();
    $denomination = $TerrainDetails["Denomination"];
    $lieu = $TerrainDetails["lieu"];
    $date = $TerrainDetails["date"];
    $title = "Enregister une nouvelle participation";
    $btn = "Enregistrer";
    $action = "../models/add/add-Terrain-post.php?idTerr=" . $id;
} else {
    $title = 0;
}
$statut = 0;
# Selection Des données des agents
$getAgent = $connexion->prepare("SELECT agents.id,agents.nom, agents.postnom, agents.prenom, departement.denomination FROM `agents`,`departement` WHERE agents.fonction=departement.id AND agents.statut=?;");
$getAgent->execute([$statut]);

# Selection Des données de la participation
$getData = $connexion->prepare("SELECT `participation`.*,agents.nom,agents.postnom,agents.prenom,partenaire.Denomination,terrain.description,terrain.lieu,terrain.date FROM `participation`,terrain,agents,partenaire WHERE participation.agent=agents.id AND participation.terrain=terrain.id AND terrain.partenaire=partenaire.id AND participation.statut=? AND terrain.id=?;");
$getData->execute([$statut,$id]);
