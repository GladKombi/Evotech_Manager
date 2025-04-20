<?php
if(isset($_GET["idUser"])){
    $title="Modifier identité Administrateur";
    $btn="Modifier";
    $action="";
}else{
    $title="Enregister un nouvel Administrateur";
    $btn="Enregistrer";
    $action="../models/add/add-user-post.php";
}
$statut = 0;
$getData = $connexion->prepare("SELECT * FROM `users` WHERE users.statut=?;");
$getData->execute([$statut]);