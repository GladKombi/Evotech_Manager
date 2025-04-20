<?php
include_once '../../connexion/connexion.php';
if (isset($_GET["AddDisk"])){
    # Creation du matricule disk
    $getLastMAt = $connexion->prepare("SELECT * FROM `disk` ORDER BY matricule DESC LIMIT 1 ");
    $getLastMAt->execute();
    if ($mat = $getLastMAt->fetch()) {
        $valeur = $mat['matricule'];
        // echo $valeur;
        if (strlen($valeur) == 10) {
            $numero = substr($valeur, 5, 1) + 1;
             echo $numero;
        } else {
            $nb = strlen($valeur) - 10 + 1;
            $numero = substr($valeur, 5, $nb);
            echo $numero;
        }
    } else {
        $numero = 1;
    }
    $matricule = "EKA/D" .$numero. "/HDD";
echo $matricule;
    $statut = 0;
    $req = $connexion->prepare("INSERT INTO `disk` (matricule,statut) VALUES (?,?)");
    $resultat = $req->execute([$matricule, $statut]);
    if ($resultat == true) {
        $_SESSION['msg'] = "Enregistrement Effectué Avec succes !";
        header("location:../../views/disk.php");
    } else {
        $_SESSION['msg'] = "Echec d'enregistrement !";
        header("location:../../views/disk.php");
    }
}else{
    header("location:../../views/disk.php");
}
