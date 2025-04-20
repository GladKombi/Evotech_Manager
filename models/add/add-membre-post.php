<?php
# Appel de la connexion
include_once '../../connexion/connexion.php';
# Fonction pour upload les images
require_once('../../fonctions/fonctions.php');
# Enregistrement d'un jeune 
if (isset($_POST['valider'])) {
    $nom = htmlspecialchars($_POST['nom']);
    $postnom = htmlspecialchars($_POST['postnom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $genre = htmlspecialchars($_POST['genre']);
    $dateNai = htmlspecialchars($_POST['dateNaiss']);
    $EtatCivil = htmlspecialchars($_POST['EtatCivil']);
    $adress = htmlspecialchars($_POST['adress']);
    $ville = htmlspecialchars($_POST['ville']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $profession = htmlspecialchars($_POST['profession']);
    # Calucule de l'age
    $dateNaiss = $dateNai;
    $dateactue = date('Y-m-d');
    $anniv = date_diff(date_create($dateNaiss), date_create($dateactue));
    $age = $anniv->format('%y');
    # Verification age
    if ($age < 15) {
        if ($_SESSION['User'] > 1 ) {
            $msg = "La date de naissance que vous avez selectionnez ne pas valide !";
            $_SESSION['msg'] = $msg;
            header('location:../../views/Membre.php');
        } else {
            $msg = "La date de naissance que vous avez séléctionnez ne pas valide !";
            $_SESSION['msg'] = $msg;
            header('location:../../Inscription.php');
        }
    } else {
        if (is_numeric($telephone)) {
            $statut = 0;
            $getDeplicantMember = $connexion->prepare("SELECT * FROM membre where telephone=? and statut=? ");
            $getDeplicantMember->execute(array($telephone, $statut));
            $existant = $getDeplicantMember->fetch();
            if ($existant['id'] >= 1) {
                if ($genre = 'M') {
                    if ($_SESSION['User'] > 1 ) {
                        $msg = "Le jeune monsieur $nom  $postnom $prenom existe déjà";
                        $_SESSION['msg'] = $msg;
                        header('location:../../views/Membre.php');
                    } else {
                        $msg = "Le jeune monsieur $nom  $postnom $prenom existe déjà";
                        $_SESSION['msg'] = $msg;
                        header('location:../../Inscription.php');
                    }
                } else {
                    if ($_SESSION['User'] > 1 ) {
                        $msg = "La jeune demoiselle $nom.' '.$postnom.' '.$prenom.' '.existe deja";
                        $_SESSION['msg'] = $msg;
                        header('location:../../views/Membre.php');
                    } else {
                        $msg = "La jeune demoiselle $nom.' '.$postnom.' '.$prenom.' '.existe deja";
                        $_SESSION['msg'] = $msg;
                        header('location:../../Inscription.php');
                    }
                }
            } else {
                $fichier_tmp = $_FILES['picture']['tmp_name'];
                $nom_original = $_FILES['picture']['name'];
                $destination = "../../assets/img/profiles/";
                # fonction permettant de recuperer la photo
                $newimage = RecuperPhoto($fichier_tmp, $nom_original, $destination);
                $statut = 0;
                $aprobation = 0;
                $req = $connexion->prepare("INSERT INTO `membre`(`nom`, `postnom`, `prenom`, `genre`, `dateNaissance`, `age`, `adress`, `ville`, `etatCivil`, `profession`, `telephone`, `dateIntegration`, `photo`, `statut`, `aprobation`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $req->execute(array($nom, $postnom, $prenom, $genre, $dateNai, $age, $adress, $ville, $EtatCivil,  $profession, $telephone, $dateactue, $newimage, $statut, $aprobation));
                if ($req) {
                    if ($_SESSION['User'] > 1 ) {
                        $msg = "Enregistrement reussi";
                        $_SESSION['msg'] = $msg;
                        header('location:../../views/Membre.php');
                    } else {
                        $msg = "Félicitation, Votre demande d'inscription viens d'etre enregistrer !";
                        $_SESSION['msg'] = $msg;
                        header('location:../../Inscription.php');
                    }
                } else {
                    if ($_SESSION['User'] > 1 ) {
                        header('location:../../views/Membre.php');
                        $_SESSION['msg'] = "Echec d'enregistrement !";
                    } else {
                        header('location:../../Inscription.php');
                        $_SESSION['msg'] = "Echec d'enregistrement !";
                    }
                }
            }
        } else {
            if ($_SESSION['User'] > 1 ) {
                $_SESSION['msg'] = "Le numero de téléphone ne doit pas contenir des caracteres Alphanumerique";
                header("location:../../views/Membre.php");
            } else {
                $_SESSION['msg'] = "Le numero de téléphone ne doit pas contenir des caracteres Alphanumerique";
                header("location:../../views/Inscription.php");
            }
        }
    }
} else {
    header('location:../../index.php');
}
