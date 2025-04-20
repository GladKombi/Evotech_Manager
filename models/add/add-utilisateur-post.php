<?php
# Connexion à la BD
include('../../connexion/connexion.php');
# La fonction pour Upload les photos de profil
require_once('../../fonctions/fonctions.php');

if (isset($_POST['valider'])) {
  $nom = htmlspecialchars($_POST['nom']);
  $postnom = htmlspecialchars($_POST['postnom']);
  $prenom = htmlspecialchars($_POST['prenom']);
  $fonction = htmlspecialchars($_POST['fonction']);
  $telephone = htmlspecialchars($_POST['telephone']);
  $pwd = htmlspecialchars($_POST['pwd']);
  # recuperer le denier ID
  $lastId = 0;
  $statut = 0;
  $dif_User = 0;
  $getlastUserId = $connexion->prepare("SELECT * FROM `users` ORDER BY id DESC LIMIT 1;");
  $getlastUserId->execute();
  if ($tab = $getlastUserId->fetch()) {
    $lastId = $tab['id'];
  } else {
    $lastId = 1;
  }
  $dif_User = $lastId + 1;
  $username = "$nom$dif_User$postnom@nature.com";

  #verifier si l'utilisateur existe ou pas dans la bd
  $getBoutiqueUtilisateurs = $connexion->prepare("SELECT * FROM `users` WHERE telephone=? AND statut=?");
  $getBoutiqueUtilisateurs->execute([$telephone, $statut]);
  $tab = $getBoutiqueUtilisateurs->fetch();
  if ($tab > 0) {
    $_SESSION['msg'] = 'cet Utlisateur existe dejà dans la base de données'; //Cette variable recoit le message pour notifier l'utilisateur de l'opération qu'il deja fait
    header("location:../../views/utilsateur.php");
  } else {
    $fichier_tmp = $_FILES['picture']['tmp_name'];
    $nom_original = $_FILES['picture']['name'];
    $destination = "../../assets/img/profiles/";
    # fonction permettant de recuperer la photo
    $newimage = RecuperPhoto($fichier_tmp, $nom_original, $destination);
    # Insertion data from database
    $req = $connexion->prepare("INSERT INTO `users`(`nom`, `fonction`, `pwd`, `profil`, `statut`, `postnom`, `prenom`, `user_name`, `telephone`) VALUES (?,?,?,?,?,?,?,?,?)");
    $resultat = $req->execute([$nom, $fonction, $pwd, $newimage, $statut, $postnom, $prenom, $username,$telephone]);
    if ($resultat == true) {
      $_SESSION['msg'] = "Enregistrement réussi !";
      header("location:../../views/utilisateur.php");
    } else {
      $_SESSION['msg'] = "Echec d'enregistrement !";
      header("location:../../views/utilisateur.php");
    }
  }
} else {
  header("location:../../views/utilisateur.php");
}
