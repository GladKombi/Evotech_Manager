<?php
include('../../connexion/connexion.php');
require_once('../../fonctions/fonctions.php');

if (isset($_POST['valider'])) {
  $nom = htmlspecialchars($_POST['nom']);
  $postnom = htmlspecialchars($_POST['postnom']);
  $prenom = htmlspecialchars($_POST['prenom']);
  $telephone = htmlspecialchars($_POST['telephone']);
  $fonction = htmlspecialchars($_POST['fonction']);
  $pwd = htmlspecialchars($_POST['motdepasse']);
  $statut = 0;
  $mail = "";
  if (is_numeric($telephone)) {
    #verifier si l'utilisateur existe ou pas dans la bd
    $getUserDeplicant = $connexion->prepare("SELECT * FROM `users` WHERE mail=?  AND statut=?");
    $getUserDeplicant->execute([$mail, $statut]);
    $tab = $getUserDeplicant->fetch();
    if ($tab > 0) {
      $_SESSION['msg'] = "Cet Agent existe deja dans le système!";
      header("location:../../views/user.php");
    } else {
      # verify pwd vadity
      if ($pwd != "") {
        $fichier_tmp = $_FILES['picture']['tmp_name'];
        $nom_original = $_FILES['picture']['name'];
        $destination = "../../assets/img/profiles/";
        // fonction permettant de recuperer la photo
        $newimage = RecuperPhoto($fichier_tmp, $nom_original, $destination);
        if ($fonction == "ceo") {
          $mail = "CEO$prenom@Eka.com";
        } else {
          $mail = "Admin$prenom@Eka.com";
        }

        # Insertion data from database
        $req = $connexion->prepare("INSERT INTO `users`(`nom`, `postnom`, `prenom`, `telephone`, `foction`, `profil`, `pwd`, `mail`, `statut`) VALUES  (?,?,?,?,?,?,?,?,?)");
        $resultat = $req->execute([$nom, $postnom, $prenom, $telephone, $fonction, $newimage, $pwd, $mail, $statut]);
        if ($resultat == true) {
          $_SESSION['msg'] = "Enregistrement reussi !";
          header("location:../../views/user.php");
        } else {
          $_SESSION['msg'] = "Echec d'enregistrement !";
          header("location:../../views/user.php");
        }
      } else {
        $_SESSION['msg'] = "Ajouter les modifications";
        header("location:../../views/user.php");
      }
    }
  } else {
    $_SESSION['msg'] = "Le numero de téléphone ne doit containir des caractères alphanumeriques !";
    header("location:../../views/user.php");
  }
} else {
  header("location:../../views/user.php");
}
