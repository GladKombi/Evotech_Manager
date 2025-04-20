<?php
if (isset($_GET['idApprob'])) {
    $id = $_GET['idApprob'];
    $Approbation = 0;
    $statut = 0;
    $getMemberId = $connexion->prepare("SELECT `membre`.*, ville.nom AS NomVille FROM `membre`,ville WHERE membre.ville=ville.id AND membre.statut=? AND membre.aprobation=? AND membre.id=?;");
    $getMemberId->execute([$statut, $Approbation,$id]);
    $AprodId = $getMemberId->fetch();

}
# Selection donnée des membres non encore valider
$Approbation = 0;
$statut = 0;
$getMember = $connexion->prepare("SELECT `membre`.*, ville.nom AS NomVille FROM `membre`,ville WHERE membre.ville=ville.id AND membre.statut=? AND membre.aprobation=?;");
$getMember->execute([$statut, $Approbation]);
