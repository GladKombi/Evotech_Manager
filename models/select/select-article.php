<?php
if (isset($_GET['idArticle'])) {
    # Lors de la modification
    $id = $_GET['idArticle'];
    $getArticleMod = $connexion->prepare("SELECT * FROM article where id='$id'");
    $getArticleMod->execute();
    $element = $getArticleMod->fetch();
    $contenus=$element['contenus'] ;
    $title = "Modification d'un article";
    $Action = "../models/updat/up-article-post.php?idArticle=" . $id;
    $btn = "Modifier";
} else {
    # Lors de l'enregistrement
    $title = "Enregistrer un nouvel article";
    $Action = "../models/add/add-article-post.php";
    $btn = "Enregistrer";
}
# Selection des toutes les villes en activité
$statut = 0;
$getArticle = $connexion->prepare("SELECT * FROM `article` WHERE article.statut=?;");
$getArticle->execute([$statut]);
