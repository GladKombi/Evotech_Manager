<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
require_once('../../fonctions/fonctions.php');

# creation de l'evenement sur le bouton valider
if (isset($_POST['Valider'])) {
  $nom = htmlspecialchars($_POST['nom']);
  $prenom = htmlspecialchars($_POST['prenom']);
  $genre = htmlspecialchars($_POST['genre']);
  $telephone = htmlspecialchars($_POST['telephone']);
  $departement = htmlspecialchars($_POST['fonction']);
  $pwd = htmlspecialchars($_POST['pwd']);
  $mail = htmlspecialchars($_POST['mail']);
  $statut = 0;
  /**
   *  “Here, we have hashed the password. So, for a new user, you first need to create a file that will allow you to hash the password in order to log in. Please create this file outside of this ‘Eka_task_manager’ project.”
   * for example
   * $pwd=1234;
   * $hash = password_hash($pwd, PASSWORD_DEFAULT);
   * print $hash;
   */
  # password hashing
  $passwordh = $pwd;
  $passwordhacher = password_hash($passwordh, PASSWORD_DEFAULT);

  if (is_numeric($telephone)) {
    #verifier si l'utilisateur existe ou pas dans la bd
    $getUserDeplicant = $connexion->prepare("SELECT * FROM `users` WHERE mail=? AND telephone=? AND statut=?");
    $getUserDeplicant->execute([$telephone, $mail, $statut]);
    $tab = $getUserDeplicant->fetch();
    if ($tab > 0) {
      $_SESSION['msg'] = "Cet Agent existe deja dans a BD!";
      header("location:../../views/agent.php");
    } else {
      $fichier_tmp = $_FILES['picture']['tmp_name'];
      $nom_original = $_FILES['picture']['name'];
      $destination = "../../images/profil/";
      // fonction permettant de recuperer la photo
      $newimage = RecuperPhoto($fichier_tmp, $nom_original, $destination);
      # verify pwd vadity
      if ($pwd != "") {
        # Insertion data from database
        $req = $connexion->prepare("INSERT INTO `users`( `nom`, `prenom`, `genre`, `departement`, `mail`, `telephone`, `pwd`, `profil`, `statut`) VALUES (?,?,?,?,?,?,?,?,?)");
        $resultat = $req->execute([$nom, $prenom, $genre, $departement, $mail, $telephone, $passwordhacher, $newimage, $statut]);
        if ($resultat == true) {
          $_SESSION['msg'] = "Enregistrement réussi !";
          header("location:../../views/Agent.php");
        } else {
          $_SESSION['msg'] = "Echec d'enregistrement !";
          header("location:../../views/Agent.php");
        }
      } else {
        $_SESSION['msg'] = "Ajouter les modifications";
        header("location:../../views/Agent.php");
      }
    }
  } else {
    $_SESSION['msg'] = "Le numero de téléphone ne doit pas containir des lettres !";
    header("location:../../views/Agent.php");
  }
} else {
  header("location:../../views/Agent.php");
}
