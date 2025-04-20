<?php
// setlocale(LC_TIME,'fr_FR.UTF-8');
// $nomMoiActuel = strftime('%B');
// echo $nomMoiActuel;
$mois_actuel_nom = date('F');
$statut = 0;
$cloture = 0;
# Selection du total des entrees en dollars
$typeDol = "Entree";
$deviseDol = "Dollard";
$getEntreeDolar = $connexion->prepare("SELECT SUM(mouvementcaisse.montant) AS TotalEntreeDol FROM mouvementcaisse WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=?;");
$getEntreeDolar->execute([$typeDol, $deviseDol, $statut, $cloture]);
$EntreeDolar = $getEntreeDolar->fetch();
$TotalEntreeDol=$EntreeDolar["TotalEntreeDol"];
if($TotalEntreeDol<1){
    $TotalEntreeDol=0;
}
# Selection du total des entrees en franc 
$typeFr = "Entree";
$deviseFr = "Franc";
$getEntreeFranc = $connexion->prepare("SELECT SUM(mouvementcaisse.montant) AS TotalEntreeFranc FROM mouvementcaisse WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=?;");
$getEntreeFranc->execute([$typeFr, $deviseFr, $statut, $cloture]);
$EntreeFranc = $getEntreeFranc->fetch();
$TotalEntreeFranc=$EntreeFranc["TotalEntreeFranc"];
if($TotalEntreeFranc<1){
    $TotalEntreeFranc=0;
}
# Selection du total des sorties en dollars 
$typeSDol = "Sortie";
$deviseSDol = "Dollard";
$getSortieDolar = $connexion->prepare("SELECT SUM(mouvementcaisse.montant) AS TotalSortieDol FROM mouvementcaisse WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=?;");
$getSortieDolar->execute([$typeSDol, $deviseSDol, $statut, $cloture]);
$EntreeDolar = $getSortieDolar->fetch();
$TotalSortieDol=$EntreeDolar["TotalSortieDol"];
if($TotalSortieDol<1){
    $TotalSortieDol=0;
}
# Selection du total des Soties en franc
$typeSFr = "Sortie";
$deviseSFr = "Franc";
$getSortieFra = $connexion->prepare("SELECT SUM(mouvementcaisse.montant) AS TotalSortieFranc FROM mouvementcaisse WHERE mouvementcaisse.type=? AND mouvementcaisse.devise=? AND mouvementcaisse.statut=? AND mouvementcaisse.cloture=?;");
$getSortieFra->execute([$typeSFr, $deviseSFr, $statut, $cloture]);
$EntreeFranc = $getSortieFra->fetch();
$TotalSortieFranc=$EntreeFranc["TotalSortieFranc"];
if($TotalSortieFranc<1){
    $TotalSortieFranc=0;
}
# Calcul du Solde
$soldeDol=$TotalEntreeDol-$TotalSortieDol;
$soldeFranc= $TotalEntreeFranc-$TotalSortieFranc;