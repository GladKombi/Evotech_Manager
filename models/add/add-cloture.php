 <?php
# Engistrement de la cloture

include("../../connexion/connexion.php");
if (isset($_GET["valider"]) && isset($_GET["sldD"]) && isset($_GET["sldF"])) {
    $soldeDol = $_GET["sldD"];
    $soldeFra = $_GET["sldF"];
    $libelle = "Réport à nouveau";
    $type = "Entree";
    $DeviseD = "Dollard";
    $DeviseF = "Franc";
    $statut = 0;
    $cloture = 1;
    $Etatcloture = 0;
    // echo $soldeDol;
    // echo $soldeFra;
    $setCloture = $connexion->prepare("UPDATE `mouvementcaisse` SET `cloture`=? WHERE mouvementcaisse.statut =? and mouvementcaisse.cloture=?");
    $test = $setCloture->execute(array($cloture, $statut, $Etatcloture));
    if ($test == true) {
        # insertion report à nouveau dollars
        $InsertDol = $connexion->prepare("INSERT INTO `mouvementcaisse`(`date`, `type`, `libelle`, `montant`, `devise`, `statut`) VALUES (NOW(),?,?,?,?,?)");
        $Chec = $InsertDol->execute([$type, $libelle, $soldeDol, $DeviseD, $statut]);
        # Inserssion report à nouveau Franc
        $req = $connexion->prepare("INSERT INTO `mouvementcaisse`(`date`, `type`, `libelle`, `montant`, `devise`, `statut`) VALUES (NOW(),?,?,?,?,?)");
        $resultat = $req->execute([$type, $libelle, $soldeFra, $DeviseF, $statut]);
        if ($resultat == true) {
            $_SESSION['msg'] = "Bravo ! vous avez effectuée une cloture";
            header("location:../../views/cloture.php");
        } else {
            $_SESSION['msg'] = "Echec de cloture !";
            header("location:../../views/cloture.php");
        }
    } else {
        $_SESSION['msg'] = "Echec de cloture !";
        header("location:../../views/cloture.php");
    }
} else {
    header("location:../../views/cloture.php");
}
