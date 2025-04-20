<?php
include '../../connexion/connexion.php'; //Se connecter à la BD
#suppression
if (isset($_GET['idSupcat']) && !empty($_GET['idSupcat'])) {
  $id = $_GET['idSupcat'];
  $supprimer = 1;
  $req = $connexion->prepare("UPDATE `utilisateur` SET supprimer=? WHERE id=?");
  $resultat = $req->execute([$supprimer, $id]);
  if ($resultat == true) {
    $_SESSION['msg'] = 'Suppression réussie';
    header('location:../../views/utilisateur.php');
  }
} else {
  header('location:../../views/utilisateur.php');
}
