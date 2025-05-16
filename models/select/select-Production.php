<?php
if (isset($_GET["idProd"]) && !empty($_GET["NewProduction"])) {
    $idProduction = $_GET["idProd"];
    $Projet = $_GET["NewProduction"];
    # Selection de la post-production lors de la modification 
    $getDataMod = $connexion->prepare("SELECT post_production.*, disk.matricule, users.nom, users.prenom FROM `post_production`,`disk`,participation,users WHERE post_production.disk=disk.id AND post_production.participation=participation.id AND participation.agent=users.id AND post_production.id=?");
    $getDataMod->execute([$idProduction]);
    $ProdMod = $getDataMod->fetch();
    $diskModif = $ProdMod['matricule'];
    $AgentModif = $ProdMod['participation'];
    $typProd = $ProdMod['Typeproduction'];
    $title = "Modifier la production '$typProd'";
    $btn = "Modifier";
    $action = "../models/updat/update-production.php?idProd=" . $idProduction;
    # Selection des post-productions d'un terrain ciblé
    $statut = 0;
    $getPost_Prod = $connexion->prepare("SELECT `post_production`.*, projet.date, partenaire.Denomination,projet.description,disk.matricule,users.nom,users.prenom FROM post_production,projet,partenaire,`disk`,participation,users WHERE post_production.disk=disk.id AND post_production.participation=participation.id AND participation.agent=users.id AND participation.projet=projet.id AND projet.partenaire=partenaire.id AND participation.projet=?;");
    $getPost_Prod->execute([$Projet]);
    # Selection Des données des agents qui ont participer au projet
    $getUser = $connexion->prepare("SELECT `participation`.*, users.nom,users.prenom,users.mail FROM `users`, `departement`, `participation`, `projet` WHERE users.departement=departement.id AND participation.agent=users.id AND participation.projet=projet.id AND projet.id=? AND participation.statut=? ;");
    $getUser->execute([$Projet, $statut]);
} else if (!empty($_GET['NewProduction'])) {
    # Lors de l'Enregistrement des post-production
    $idProjet = $_GET["NewProduction"];
    $title = "Enregister un nouvelle production";
    $btn = "Enregistrer";
    $action = "../models/add/add-production-post.php?idProjet=" . $idProjet;
    # Selection des post-productions d'un terrain lors de la mis à jour
    $statut = 0;
    $getPost_Prod = $connexion->prepare("SELECT `post_production`.*, projet.date, partenaire.Denomination,projet.description,disk.matricule,users.nom,users.prenom FROM post_production,projet,partenaire,`disk`,participation,users WHERE post_production.disk=disk.id AND post_production.participation=participation.id AND participation.agent=users.id AND participation.projet=projet.id AND projet.partenaire=partenaire.id AND participation.projet=? AND post_production.statut=?;");
    $getPost_Prod->execute([$idProjet, $statut]);
    # Selection Des données des agents qui ont participer au terrain
    $getUser = $connexion->prepare("SELECT `participation`.*, users.nom,users.prenom,users.mail FROM `users`, `departement`, `participation`, `projet` WHERE users.departement=departement.id AND participation.agent=users.id AND participation.projet=projet.id AND projet.id=? AND participation.statut=? ;");
    $getUser->execute([$idProjet, $statut]);
} else if (!empty($_GET['VoirProd'])) {
    # Lors de la visualisation des post-production
    $ProjetProd = $_GET["VoirProd"];
    # Selection des post-productions d'un terrain ciblé lors de la visualisation
    $statut = 0;
    $getPost_Prod = $connexion->prepare("SELECT `post_production`.*, projet.date, partenaire.Denomination,projet.description,disk.matricule,users.nom,users.prenom FROM post_production,projet,partenaire,`disk`,participation,users WHERE post_production.disk=disk.id AND post_production.participation=participation.id AND participation.agent=users.id AND participation.projet=projet.id AND projet.partenaire=partenaire.id AND participation.projet=?;");
    $getPost_Prod->execute([$ProjetProd]);
    # Selection des details du Projet
    $getProjetDetails = $connexion->prepare("SELECT `projet`.*,partenaire.Denomination, partenaire.adresse, type_projetc.descriprion as `type` FROM `projet`,partenaire, type_projetc WHERE projet.partenaire=partenaire.id AND projet.type_projet=type_projetc.id AND `projet`.statut=? AND Projet.id=?;");
    $getProjetDetails->execute([$statut, $ProjetProd]);
    $ProjetDetails = $getProjetDetails->fetch();
    $denomination = $ProjetDetails["Denomination"];
    $type = $ProjetDetails["type"];
    $descriprion = $ProjetDetails["description"];
}
# Selection des departement de la DB
$statut = 0;
$Etat = 1;
$getDisk = $connexion->prepare("SELECT * FROM `disk` WHERE disk.statut=?;");
$getDisk->execute([$statut]);



# Selection des infos du projet
$getData = $connexion->prepare("SELECT `projet`.*,partenaire.Denomination, partenaire.adresse, type_projetc.descriprion as `type` FROM `projet`,partenaire, type_projetc WHERE projet.partenaire=partenaire.id AND projet.type_projet=type_projetc.id AND `projet`.statut=?;  ");
$getData->execute([$statut]);
