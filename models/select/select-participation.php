<?php
if (isset($_GET["idparticipation"])) {
    $id = $_GET["idparticipation"];
    $statut = 0;
    # Selection des details du Projet
    $getProjetDetails = $connexion->prepare("SELECT `projet`.*,partenaire.Denomination, partenaire.adresse, type_projetc.descriprion as `type` FROM `projet`,partenaire, type_projetc WHERE projet.partenaire=partenaire.id AND projet.type_projet=type_projetc.id AND `projet`.statut=? AND Projet.id=?;");
    $getProjetDetails->execute([$statut, $id]);
    $ProjetDetails = $getProjetDetails->fetch();
    $denomination = $ProjetDetails["Denomination"];
    $type = $ProjetDetails["type"];
    $descriprion = $ProjetDetails["description"];
    $title = "Enregister une nouvelle affectation";
    $btn = "Enregistrer";
    $url = "../models/add/add-projet-post.php?idparticipation=" . $id;
} else {
    $title = "Veillez sélèctionner un projet au préalable ! ";
    $btn = "";
}
$statut = 0;
# Selection des User de la DB
$getUser = $connexion->prepare("SELECT * FROM `users` WHERE users.statut=?;");
$getUser->execute([$statut]);

# Selection des infos du projet
// $getProjet = $connexion->prepare("SELECT `projet`.*,partenaire.Denomination, partenaire.adresse, type_projetc.descriprion as `type` FROM `projet`,partenaire, type_projetc WHERE projet.partenaire=partenaire.id AND projet.type_projet=type_projetc.id AND `projet`.statut=?;  ");
// $getProjet->execute([$statut]);


# Selection Des données de la participation
$getData = $connexion->prepare("SELECT participation.*, users.nom, users.prenom, users.mail, users.profil FROM `participation`,users WHERE participation.agent=users.id  AND participation.projet=? and participation.statut=? ORDER BY `participation`.`id` DESC;");
$getData->execute([$id, $statut]);
