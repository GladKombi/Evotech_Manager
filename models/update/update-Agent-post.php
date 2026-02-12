<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
# Modification de données d'un Agent
if (isset($_POST['Valider']) && !empty($_GET['idAgent'])) {
    $id = $_GET["idAgent"];
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $genre = htmlspecialchars($_POST['genre']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $departement = htmlspecialchars($_POST['fonction']);
    $mail = htmlspecialchars($_POST['mail']);
    $statut = 0;
    if (is_numeric($telephone)) {
        #verifier si le client existe ou pas dans la bd
        $statut = 0;
        $getUserDeplicant = $connexion->prepare("SELECT * FROM `users` WHERE mail=? AND telephone=? AND statut=? AND id!=$id");
        $getUserDeplicant->execute([$mail, $telephone, $statut]);
        $tab = $getUserDeplicant->fetch();
        if ($tab > 0) {
            $_SESSION['msg'] = "Cet Agent existe deja dans la Base de Données!";
            header("location:../../views/agent.php");
        } else {
            //Insertion data from database
            $req = $connexion->prepare("UPDATE `users` SET `nom`=?,`prenom`=?,`genre`=?,`departement`=?,`mail`=?,`telephone`=? WHERE id=?");
            $resultat = $req->execute([$nom, $prenom, $genre, $departement, $mail, $telephone, $id]);
            if ($resultat == true) {
                $_SESSION['msg'] = "Modification reussi !";
                header("location:../../views/agent.php");
            } else {
                $_SESSION['msg'] = "Echec d'enregistrement !";
                header("location:../../views/agent.php");
            }
        }
    } else {
        $_SESSION['msg'] = "Le numero de telephone ne doit pas contenir des caracteres Alphanumerique";
        header("location:../../views/agent.php");
    }
} else {
    header("location:../../views/agent.php");
}
