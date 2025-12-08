<?php
if (isset($_GET["idDisk"])) {
    $id = $_GET["idDisk"];
    $statut = 0;
    $getSelectDisk = $connexion->prepare("SELECT * FROM `disk` WHERE disk.statut=? AND disk.id=?;");
    $getSelectDisk->execute([$statut,$id]);
    $ShowDisk = $getSelectDisk->fetch();
    $DiskMatricule = $ShowDisk['matricule'];
}
# Affichage des disk
$statut = 0;
$getData = $connexion->prepare("SELECT * FROM `disk` WHERE disk.statut=?;");
$getData->execute([$statut]);
