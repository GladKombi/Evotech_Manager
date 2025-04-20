<?php
include("../../connexion/connexion.php");
if (isset($_POST["valider"])) {
    if (isset($_GET["idPartenaire"])) {
        $id = $_GET["idPartenaire"];
        $denomination = htmlspecialchars($_POST['nom']);
        $adresse = htmlspecialchars($_POST['adresse']);
        $telephone = htmlspecialchars($_POST['telephone']);
        if (is_numeric($telephone)) {
            $req = $connexion->prepare("UPDATE `partenaire` SET `Denomination`=?,`adresse`=?,`telephone`=? WHERE id=?");
            $test = $req->execute(array($denomination, $adresse, $telephone, $id));
            if ($test == true) {
                $_SESSION['msg'] = "Modification reussi !";
                header("location:../../views/partenaire.php");
            } else {
                $_SESSION['msg'] = "Echec de modification !";
                header("location:../../views/partenaire.php");
            }
        } else {
            $_SESSION['msg'] = "Le numero de telephone ne doit pas contenir des caractere Alphanumeric !";
            header("location:../../views/partenaire.php");
        }
    }
} else {
    header("location:../../views/partenaire.php");
}
