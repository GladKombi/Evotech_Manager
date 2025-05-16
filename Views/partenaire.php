<?php
# Se connecter à la BD
include '../connexion/connexion.php';
# Appel du script de selection
require_once('../models/select/select-Partenaire.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partenaire</title>
    <?php require_once('style.php') ?>
</head>

<body>
    <div id="app">
        <?php
        require_once('Active.php');
        $ActivePartenaire = 1;
        require_once('aside.php');
        ?>
        <div id="main">
            <?php require_once('navbar.php') ?>
            <div class="main-content container-fluid">
                <div class="row">
                    <div class="col-12">
                        <h4>Les Partenaires</h4>
                    </div>
                    <!-- pour afficher les massage  -->
                    <?php
                    if (isset($_SESSION['msg']) && !empty($_SESSION['msg'])) {
                    ?>
                        <div class="alert-info alert text-center"><?= $_SESSION['msg'] ?></div>
                    <?php  }
                    #Cette ligne permet de vider la valeur qui se trouve dans la session message  
                    unset($_SESSION['msg']);
                    # Confirmation de la suppression
                    if (isset($_GET['idSupPart'])) {
                        $id = $_GET["idSupPart"];
                    ?>
                        <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                            <h3 class="bi bi-shield-exclamation text-danger text-center"> Zone Dangereuse</h3> <br>
                            <p class="text-center">
                                Voule-vous vraiment supprimer ce partenaire ?? c'est dangereux ! <br>
                                Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                réaliser ! Elle permet de supprimer un partenaire de la base de données et toutes les données liées à cet partenaire .
                            </p>
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="partenaire.php" class="btn btn-danger  w-100"> Annuler</a>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="../models/delete/delete-partenaire.php?idSupPart=<?= $id ?>" class="btn btn-success bi bi-trash w-100"> Supprimer partenaire</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        if (isset($_GET['NewPartenaire'])) {
                        ?>
                            <!-- Le form qui enregistrer les données  -->
                            <div class="col-xl-12 col-lg-12 col-md-6">
                                <form action="<?= $action ?>" method="POST" class="shadow p-3">
                                    <div class="row">
                                        <h4 class="text-center"><?= $title ?></h4>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Denomination <span class="text-danger">*</span></label>
                                            <input required type="text" name="nom" class="form-control" placeholder="Entrez le nom" <?php if (isset($_GET['idPartenaire'])) { ?> value="<?= $Denomination; ?>" <?php } ?>>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Adresse <span class="text-danger">*</span></label>
                                            <input required type="text" name="adresse" class="form-control" placeholder="Entrez l'adresse" <?php if (isset($_GET['idPartenaire'])) { ?>value="<?= $ShowPartn['adresse'] ?>" <?php } ?>>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Telephone <span class="text-danger">*</span></label>
                                            <input required type="text" name="telephone" class="form-control" placeholder="Entrez le numero de téléphone" <?php if (isset($_GET['idPartenaire'])) { ?>value="<?= $ShowPartn['telephone'] ?>" <?php } ?>>
                                        </div>

                                        <?php if (isset($_GET['idPartenaire'])) {
                                        ?>
                                            <div class="row">
                                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 p-3">
                                                    <a href="partenaire.php" class="btn btn-danger  w-100"> Annuler</a>
                                                </div>
                                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 p-3 ">
                                                    <input type="submit" name="valider" class="btn btn-success w-100" value="Modifier">
                                                </div>

                                            </div>

                                        <?php
                                        } else {
                                        ?>
                                            <div class="col-xl-12 col-lg-12 col-md-12 mt-10 col-sm-12 p-3 aling-center">
                                                <input type="submit" name="valider" class="btn btn-dark w-100" value="<?= $btn ?>">
                                            </div>
                                        <?php
                                        }
                                        ?>

                                    </div>
                                </form>
                            </div>
                        <?php
                        } else {
                        ?>
                            <a href="partenaire.php?NewPartenaire" class="btn btn-dark w-100">Nouveau Partenaire</a>
                            <!-- La table qui affiche les données  -->
                            <div class="col-xl-12 col-lg-12 col-md-6 table-responsive px-3 pt-3">
                                <h4 class="text-center">Liste des Partenaires</h4>
                                <table class='table table-hover' id="table1">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Date</th>
                                            <th>Denomination</th>
                                            <th>Adresse</th>
                                            <th>Telephone</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $num = 0;
                                        while ($res = $req->fetch()) {
                                            $num = $num + 1 ?>
                                            <tr>
                                                <th scope="row"><?= $num ?></th>
                                                <td><?= $res['dateSignature'] ?></td>
                                                <td><?= $res['Denomination'] ?></td>
                                                <td><?= $res['adresse'] ?></td>
                                                <td><?= $res['telephone'] ?></td>
                                                <td>
                                                    <a href="partenaire.php?NewPartenaire&idPartenaire=<?= $res['id'] ?>" class="btn btn-success btn-sm mt-2">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="partenaire.php?idSupPart=<?= $res['id'] ?>" class="btn btn-danger btn-sm mt-2">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                    </tbody>
                                <?php } ?>
                                </table>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>

            <?php require_once('footer.php') ?>
        </div>
    </div>
    <?php require_once('script.php') ?>
</body>

</html>