<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
# Modification de données d'un Agent
if (isset($_POST['valider']) && !empty($_GET['idMembre'])) {
    $id = $_GET["idMembre"];
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
    $statut = 0;
    if ($age < 15) {
        $msg = "La date de naissance que vous avez selectionnez ne pas valide !";
        $_SESSION['msg'] = $msg;
        header('location:../../views/Membre.php');
    } else {
        if (is_numeric($telephone)) {
            # Deplicants verifications
            $statut = 0;
            ## Get similarity every where in inputs
            $getMemberSimilar = $connexion->prepare("SELECT * FROM membre where nom=? AND postnom=? AND prenom=? AND genre=? AND dateNaissance=? AND age=? AND adress=? AND ville=? AND etatCivil=? AND profession=? AND telephone=? AND statut=? ");
            $getMemberSimilar->execute(array($nom, $postnom, $prenom, $genre, $dateNai, $age, $adress, $ville, $EtatCivil,  $profession, $telephone, $statut));
            $Similarty = $getMemberSimilar->fetch();
            if ($Similarty > 0) {
                $msg = 'Modifiation reussi !';
                $_SESSION['msg'] = $msg;
                header("location:../../views/Membre.php");
            } else {
                ## Verification of the Number deplicant
                $getDeplicantMember = $connexion->prepare("SELECT * FROM membre where telephone=? and statut=? and id!=? ");
                $getDeplicantMember->execute(array($telephone, $statut, $id));
                $existant = $getDeplicantMember->fetch();
                if ($existant['id'] >= 1) {
                    # If there's similar phone number as the number in te input
                    if ($genre = 'Masculin') {
                        # To check the gender
                        $msg = "Le jeune monsieur $nom  $postnom $prenom existe déjà";
                        $_SESSION['msg'] = $msg;
                        header('location:../../views/Membre.php');
                    } else {
                        $msg = "La jeune demoiselle $nom.' '.$postnom.' '.$prenom.' '.existe deja";
                        $_SESSION['msg'] = $msg;
                        header('location:../../views/Membre.php');
                    }
                } else {
                    # Insertion of Data in the table
                    $req = $connexion->prepare("UPDATE `membre` SET `nom`=?,`postnom`=?,`prenom`=?,`genre`=?,`dateNaissance`=?,`age`=?,`adress`=?,`ville`=?,`etatCivil`=?,`profession`=?,`telephone`=?,`statut`=? WHERE id=?");
                    $resultat = $req->execute([$nom, $postnom, $prenom, $genre, $dateNai, $age, $adress, $ville, $EtatCivil,  $profession, $telephone, $statut, $id]);
                    if ($resultat == true) {
                        $_SESSION['msg'] = "Modification reussi !";
                        header("location:../../views/Membre.php");
                    } else {
                        $_SESSION['msg'] = "Echec d'enregistrement !";
                        header("location:../../views/Membre.php");
                    }
                }
            }
        } else {
            $_SESSION['msg'] = "Le numero de telephone ne doit pas contenir des caracteres Alphanumerique";
            header("location:../../views/Membre.php");
        }
    }
} else {
    header("location:../../views/Membre.php");
}
    
