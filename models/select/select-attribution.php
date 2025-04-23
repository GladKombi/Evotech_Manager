<?php
if (isset($_GET["idModif"])) {
    $id = $_GET["idModif"];
    $getAttri = $connexion->prepare("SELECT * FROM attribition WHERE id=?");
    $getAttri->execute([$id]);
    $AfichAttrib = $getAttri->fetch();
    $title = "Modifier l'attribution ";
    $btn = "Modifier";
    $url = "../models/updat/update-attribution-post.php?idModif=" . $id;
} else {
    $title = "Ajouter une attribution";
    $btn = "Enregistrer";
    $url = "../models/add/add-attribution-post.php";
}
# Selection des User de la DB
$statut = 0;
$getUser = $connexion->prepare("SELECT * FROM `users` WHERE users.statut=?;");
$getUser->execute([$statut]);
# Selection des role de la DB
$getRole = $connexion->prepare("SELECT * FROM `role` WHERE role.statut=?;");
$getRole->execute([$statut]);
# Selection of data from attribution
$getData = $connexion->prepare("SELECT attribition.id, users.profil, users.nom, users.prenom, users.mail, role.fonction FROM `attribition`, `users`, `role` WHERE attribition.user=users.id AND attribition.role=role.id AND attribition.statut=? ORDER BY `attribition`.`id` DESC");
$getData->execute([$statut]);
