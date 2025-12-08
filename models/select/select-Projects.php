<?php
if (isset($_GET["idProjet"])) {
    # Modification Projet
    $id = $_GET["idProjet"];
    # Selection des données du Projet à modifier
    $getModifPro = $connexion->prepare("SELECT `projet`.*,partenaire.Denomination, partenaire.adresse, type_projetc.descriprion as `type` FROM `projet`,partenaire, type_projetc WHERE projet.partenaire=partenaire.id AND projet.type_projet=type_projetc.id AND `projet`.id=?");
    $getModifPro->execute([$id]);
    $ProMod = $getModifPro->fetch();
    $idPart=$ProMod["partenaire"];
    $idType=$ProMod["type_projet"];
    $denomination=$ProMod["Denomination"];
    $title = "Modifier le Projet de $denomination";
    $btn = "Modifier";
    $action = "../models/updat/update-projet.php?idProjet=" . $id;
} else {
    $title = "Enregister un nouveau projet";
    $btn = "Enregistrer";
    $action = "../models/add/add-projet-post.php";
}
# Selection des parteniares
$statut = 0;
$getPartenaire = $connexion->prepare("SELECT * FROM `partenaire` WHERE `partenaire`.statut=?");
$getPartenaire->execute([$statut]);

# Selection des Type des projets;
$getTypePro = $connexion->prepare("SELECT * FROM `type_projetc` WHERE `type_projetc`.statut=?");
$getTypePro->execute([$statut]);

# Selection des infos du projet
$getData = $connexion->prepare("SELECT `projet`.*,partenaire.Denomination, partenaire.adresse, type_projetc.descriprion as `type` FROM `projet`,partenaire, type_projetc WHERE projet.partenaire=partenaire.id AND projet.type_projet=type_projetc.id AND `projet`.statut=?;  ");
$getData->execute([$statut]);
