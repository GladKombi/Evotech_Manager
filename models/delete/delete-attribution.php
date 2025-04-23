<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
# Suppression d'une attribution
if (isset($_GET['SupAttrib']) && !empty($_GET['SupAttrib'])) {
    $id = $_GET['SupAttrib'];
    $statut = 1;
    $req = $connexion->prepare("UPDATE `attribition` SET statut=? WHERE id=?");
    $resultat = $req->execute([$statut, $id]);
    if ($resultat == true) {
        $_SESSION['msg'] = "Suppression de l'attribution réussie";
        header('location:../../views/attribution.php');
    }
} else {
    header('location:../../views/attribution.php');
}