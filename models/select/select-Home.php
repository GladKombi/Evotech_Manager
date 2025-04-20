<?php

# Selection des materiel
$statut = 0;
$getMatos = $connexion->prepare("SELECT `materiel`.*, categorie.nom as nomcat FROM materiel,categorie WHERE materiel.categorie=categorie.id AND materiel.statut=? ORDER BY `materiel`.`id` DESC");
$getMatos->execute([$statut]);

# Selection du total des agent (Tous les agents)
$getTotalAgent = $connexion->prepare("SELECT COUNT(*) AS TotalAgent FROM agents WHERE statut=?");
$getTotalAgent->execute([$statut]);
$AgentTotal = $getTotalAgent->fetch();
$TotalAgent=$AgentTotal["TotalAgent"];
if($TotalAgent<1){
    $TotalAgent=0;
}
# Calcul du pourcentage selon le genre

# Selection des agent Homme
$GenreHom="Masculin";
$getAgentHomme = $connexion->prepare("SELECT COUNT(*) AS TotalHomme FROM agents WHERE agents.genre = ? AND statut=?");
$getAgentHomme->execute([$GenreHom, $statut]);
$Homme = $getAgentHomme->fetch();
$TotalHomme=$Homme["TotalHomme"];
if($TotalHomme<1){
    $TotalHomme=0;
}
$PourcenHome=0;
$PourcenHome=$TotalHomme/$TotalAgent*100;

# Selection des agent Femme
$GenreFem="Feminin";
$getAgentFemme = $connexion->prepare("SELECT COUNT(*) AS TotalFemme FROM agents WHERE agents.genre = ? AND statut=?");
$getAgentFemme->execute([$GenreFem, $statut]);
$Femme = $getAgentFemme->fetch();
$TotalFemme=$Femme["TotalFemme"];
if($TotalFemme<1){
    $TotalFemme=0;
}
$PourcenFeme=0;
$PourcenFeme=$TotalFemme/$TotalAgent*100;

# Selection des Disk Dispo
$getDisk = $connexion->prepare("SELECT COUNT(*) AS TotalDisk FROM disk WHERE statut=?");
$getDisk->execute([$statut]);
$Disk = $getDisk->fetch();
$TotalDisk=$Disk["TotalDisk"];
if($TotalDisk<1){
    $TotalDisk=0;
}

 # Selection des partenaires
$getPartenaire = $connexion->prepare("SELECT COUNT(*) AS TotalPartenaire FROM partenaire WHERE statut=?");
$getPartenaire->execute([$statut]);
$partenaire = $getPartenaire->fetch();
$TotalPartenaire=$partenaire["TotalPartenaire"];
if($TotalPartenaire<1){
    $TotalPartenaire=0;
}

# Selection des 10 derniers terrain
$getTerrain = $connexion->prepare("SELECT terrain.*, partenaire.Denomination FROM terrain, partenaire WHERE terrain.partenaire=partenaire.id AND terrain.statut=? ORDER BY date DESC LIMIT 10;");
$getTerrain->execute([$statut]);

