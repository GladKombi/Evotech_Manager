<?php
include('../connexion/connexion.php');
if (isset($_POST['connect'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    # Ferification des champs
    if (isset($_SESSION['User']) && !empty($_SESSION['User'])) {
        if ($_SESSION['User'] === "Admin" || $_SESSION['User'] === "ceo") {
            $statut = 0;
            $fonction=$_SESSION['User'];
            $getUserAdmin = $connexion->prepare("SELECT * FROM `users` WHERE mail=? AND users.statut=? AND users.foction=?");
            $getUserAdmin->execute(array($username, $statut, $fonction));
            if ($_identifiant = $getUserAdmin->fetch()) {
                $pwd = "";
                $pwd = $_identifiant['pwd'];
                if ($pwd == $password) {
                    $_SESSION['msg'] = "";
                    $_SESSION['fonction'] = $_identifiant['foction'];
                    $_SESSION['iduser'] = $_identifiant['id'];
                    $_SESSION['image'] = $_identifiant['profil'];
                    $_SESSION['prenom'] = $_identifiant['prenom'];
                    $_SESSION['telephone'] = $_identifiant['telephone'];
                    $_SESSION['noms'] = $_identifiant['nom'] . ' ' . $_identifiant['postnom'];
                    $_SESSION['nom'] = $_identifiant['nom'];
                    $_SESSION['postnom'] = $_identifiant['postnom'];
                    $_SESSION['pwd'] = $_identifiant['pwd'];
                    header("location:../views/index.php");
                } else {
                    $_SESSION['msg'] = "username or password incorrect ";
                    header("location:../login.php");
                }
            } else {
                $_SESSION['msg'] = "username or password incorrect !";
                header("location:../login.php?". $fonction);
            }
        } else {
            // Fetch the user based on the username
            $statut = 0;
            $req = $connexion->prepare("SELECT `agents`.*, departement.denomination AS role FROM `agents`, departement WHERE mail=? AND agents.statut=? AND agents.fonction=departement.id;");
            $req->execute(array($username, $statut));
            if ($_identifiant = $req->fetch()) {
                $pwd = "";
                $pwd = $_identifiant['pwd'];
                if ($pwd == $password) {
                    $_SESSION['msg'] = "";
                    $_SESSION['fonction'] = $_identifiant['role'];
                    $_SESSION['iduser'] = $_identifiant['id'];
                    $_SESSION['image'] = $_identifiant['profil'];
                    $_SESSION['prenom'] = $_identifiant['prenom'];
                    $_SESSION['telephone'] = $_identifiant['telephone'];
                    $_SESSION['genre'] = $_identifiant['genre'];
                    $_SESSION['adresse'] = $_identifiant['adresse'];
                    $_SESSION['noms'] = $_identifiant['nom'] . ' ' . $_identifiant['postnom'];
                    $_SESSION['nom'] = $_identifiant['nom'];
                    $_SESSION['postnom'] = $_identifiant['postnom'];
                    $_SESSION['pwd'] = $_identifiant['pwd'];
                    header("location:../views/horaire.php");
                } else {
                    $_SESSION['msg'] = "username or password incorrect";
                    header("location:../login.php");
                }
            } else {
                $_SESSION['msg'] = "username or password incorrect";
                header("location:../login.php");
            }
        }
    } else {
        header("location:../ops.php");
    }
} else {
    header("location:../ops.php");
}
