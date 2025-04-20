<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
# Suppression de données d'une ville 
if (isset($_GET['idSupVille']) && !empty($_GET['idSupVille'])) {
    $id = $_GET['idSupVille'];
    $supprimer = 1;
    $req = $connexion->prepare("UPDATE `ville` SET statut=? WHERE id=?");
    $resultat = $req->execute([$supprimer, $id]);
    if ($resultat == true) {
        $_SESSION['msg'] = 'Suppression réussie';
        header('location:../../views/ville.php');
    }
} else {
    header('location:../../views/ville.php');
}
