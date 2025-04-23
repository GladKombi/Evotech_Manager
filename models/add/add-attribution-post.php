<?php
#inclusion de la page de connexion
include('../../connexion/connexion.php');
if (isset($_POST['Valider'])) {
    $Agent = htmlspecialchars($_POST['user']);
    $fonction = htmlspecialchars($_POST['fonction']);
    $statut = 0;
    #verifier si la fonction n'est pas deja attribuer à cette utilisateur
    $getPartdeplicant = $connexion->prepare("SELECT * FROM `attribition` WHERE user=? AND `role`=? AND statut=?");
    $getPartdeplicant->execute([$Agent, $fonction, $statut]);
    $tab = $getPartdeplicant->fetch();
    if ($tab > 0) {
        $_SESSION['msg'] = "Une attribution similaire existe deja dans la BD !";
        header("location:../../views/attribution.php");
    } else {
        $query = $connexion->prepare("INSERT INTO `attribition`(`user`, `role`, `statut`) VALUES  (?,?,?)");
        $test = $query->execute(array($Agent, $fonction, $statut));
        if ($test == true) {
            # Selection des User de la DB
            $getUser = $connexion->prepare("SELECT nom, prenom, mail FROM `users` WHERE users.id=?;");
            $getUser->execute([$Agent]);
            $user = $getUser->fetch();
            $nom=$user['nom'];
            $prenom=$user['prenom'];
            $mail=$user['mail'];
            # Selection des role de la DB            
            $getRole = $connexion->prepare("SELECT fonction FROM `role` WHERE role.id=?;");
            $getRole->execute([$fonction]);
            $userRole = $getRole->fetch();
            $role=$userRole['fonction'];
            $_SESSION['msg'] = "L'agent $nom $prenom $mail va maintenant assumer la fonction de $role";
            header("location:../../views/attribution.php");
        } else {
            $_SESSION['msg'] = "Echec d'enregistrement !";
            header("location:../../views/attribution.php");
        }
    }
} else {
    header("location:../../views/attribution.php");
}
