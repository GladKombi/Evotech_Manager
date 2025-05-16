<?php
# Se connecter à la BD
include '../connexion/connexion.php';
# Appel du script de selection
require_once("../models/select/select-Entree.php");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caisse</title>
    <?php require_once('style.php') ?>
</head>

<body>
    <div id="app">
        <?php
        require_once('Active.php');
        $ActiveEntree = 1;
        require_once('aside.php');
        ?>
        <div id="main">
            <?php require_once('navbar.php') ?>
            <div class="main-content container-fluid">
                <div class="row">
                    <div class="row">
                        <div class="col-12">
                            <h4>Entrées en caisse</h4>
                        </div>
                    </div>
                    <?php
                    # Confirmation de la suppression
                    if (isset($_GET['SupTer'])) {
                        $id = $_GET["SupTer"];
                    ?>
                        <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                            <h3 class="bi bi-shield-exclamation text-danger text-center"> Zone Dangereuse</h3> <br>
                            <p class="text-center">
                                Voule-Vous vraiment supprimer cette operation ?? c'est dangereux ! <br>
                                Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                réaliser ! Elle permet de supprimer un operation de la base de données et toutes les données liées à ce operation .
                            </p>
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="entree.php" class="btn btn-primary  w-100"> Annler</a>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="../models/delete/delete-mouvEntr.php?SupMouv=<?= $id ?>" class="btn btn-danger bi bi-trash w-100"> Supprimer cette operation</a>
                                </div>
                            </div>
                        </div>
                    <?php
                    } else {
                    ?>
                        <div class="row">
                            <!-- pour afficher les massage  -->
                            <?php
                            if (isset($_SESSION['msg']) && !empty($_SESSION['msg'])) { ?>
                                <div class="col-xl-12 mt-3">
                                    <div class="alert-info alert text-center"><?= $_SESSION['msg'] ?></div>
                                </div>
                            <?php }
                            #Cette ligne permet de vider la valeur qui se trouve dans la session message
                            unset($_SESSION['msg']);

                            if (isset($_GET["dollar"]) || isset($_GET['Dollard']) || !empty($_GET['idEntree'])) {
                            ?>
                                <!-- Le form qui enregistrer les entree en dollard  -->
                                <div class="col-xl-12 ">
                                    <form action="<?= $action ?>" method="POST" class="shadow p-3">
                                        <div class="row">
                                            <h4 class="text-center"><?= $title ?></h4>
                                            <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                                <label for="">Libelle <span class="text-danger">*</span></label>
                                                <input required autocomplete="off" type="text" name="libelle" class="form-control" placeholder="Entrez la déscription" <?php if (isset($_GET['idEntree'])) { ?>value="<?= $terMod['libelle'] ?>" <?php } ?>>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                                <label for="">Montant <span class="text-danger">*</span></label>
                                                <input required autocomplete="off" type="text" name="montant" class="form-control" placeholder="EX: 5000" <?php if (isset($_GET['idEntree'])) { ?>value="<?= $terMod['montant'] ?>" <?php } ?>>
                                            </div>

                                            <?php if (isset($_GET['idEntree'])) {
                                            ?>
                                                <div class="row">
                                                    <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                        <input type="submit" name="valider" class="btn btn-primary w-100" value="Modifier">
                                                    </div>
                                                    <?php
                                                    if (isset($_GET['Dollard'])) {
                                                    ?>
                                                        <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                            <a href="entree.php" class="btn btn-danger w-100">Annuler</a>
                                                        </div>
                                                        <?php
                                                    } else {
                                                        if ($deviseModif == "dollar") {
                                                        ?>
                                                            <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                                <a href="entree.php?dollar" class="btn btn-danger w-100">Annuler</a>
                                                            </div>
                                                        <?php
                                                        } else {
                                                        ?>
                                                            <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                                <a href="entree.php?franc" class="btn btn-danger w-100">Annuler</a>
                                                            </div>
                                                    <?php
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            <?php
                                            } else {
                                            ?>
                                                <div class="col-xl-12 col-lg-12 col-md-12 mt-10 col-sm-12 p-3 aling-center">
                                                    <input type="submit" name="valider" class="btn btn-primary w-100" value="<?= $btn ?>">
                                                </div>
                                            <?php
                                            }
                                            ?>

                                        </div>
                                    </form>
                                </div>
                                <!-- La table qui affiche les entree en dollard  -->
                                <div class="col-xl-12 table-responsive px-3 card mt-4 px-4 pt-3">
                                    <h6 class="text-center">Listes des Entrées en Dollars</h6>
                                    <table class='table table-hover' id="table1">
                                        <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Montant</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $num = 0;
                                            while ($EntreeDol = $getEntreeDolTab->fetch()) {
                                                $num = $num + 1;
                                                $libelle = $EntreeDol["libelle"];
                                                $report = "Réport à nouveau";
                                            ?>
                                                <tr>
                                                    <th scope="row"><?= $num ?></th>
                                                    <td><?= $EntreeDol["date"] ?></td>
                                                    <td><?= $EntreeDol["libelle"] ?></td>
                                                    <td><?= $EntreeDol["montant"] . " " . "$" ?></td>
                                                    <?php
                                                    if ($libelle == $report) {
                                                    ?>
                                                        <td>
                                                            <i class="text-mute">Null</i>
                                                        </td>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <td>
                                                            <a href="entree.php?dollar&idEntree=<?= $EntreeDol["id"] ?>" class="btn btn-primary btn-sm">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                            <a href="entree.php?SupTer=<?= $EntreeDol["id"] ?>" class="btn btn-danger btn-sm">
                                                                <i class="bi bi-trash3-fill"></i>
                                                            </a>
                                                        </td>
                                                    <?php
                                                    }
                                                    ?>
                                                </tr>
                                        </tbody>
                                    <?php
                                            }
                                    ?>
                                    </table>
                                </div>
                            <?php
                            } elseif (isset($_GET["Franc"]) || !empty($_GET['idEntree'])) {
                            ?>
                                <!-- Le form qui enregistrer les entree en franc  -->
                                <div class="col-xl-12 ">
                                    <form action="<?= $action ?>" method="POST" class="shadow p-3">
                                        <div class="row">
                                            <h4 class="text-center"><?= $title ?></h4>
                                            <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                                <label for="">Libelle <span class="text-danger">*</span></label>
                                                <input required autocomplete="off" type="text" name="libelle" class="form-control" placeholder="Entrez la déscription du mouvement" <?php if (isset($_GET['idEntreeFc'])) { ?>value="<?= $terMod['libelle'] ?>" <?php } ?>>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                                <label for="">Montant <span class="text-danger">*</span></label>
                                                <input required autocomplete="off" type="text" name="montant" class="form-control" placeholder="EX: 1000" <?php if (isset($_GET['idEntreeFc'])) { ?>value="<?= $terMod['montant'] ?>" <?php } ?>>
                                            </div>

                                            <?php if (isset($_GET['idEntreeFc'])) {
                                            ?>
                                                <div class="row">
                                                    <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                        <input type="submit" name="valider" class="btn btn-primary w-100" value="Modifier">
                                                    </div>
                                                    <?php
                                                    if (isset($_GET['Fc'])) {
                                                    ?>
                                                        <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                            <a href="entree.php?Franc" class="btn btn-danger w-100">Annuler</a>
                                                        </div>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                            <a href="entree.php" class="btn btn-danger w-100">Annuler</a>
                                                        </div>
                                                    <?php
                                                    }
                                                    ?>

                                                </div>
                                            <?php
                                            } else {
                                            ?>
                                                <div class="col-xl-12 col-lg-12 col-md-12 mt-10 col-sm-12 p-3 aling-center">
                                                    <input type="submit" name="valider" class="btn btn-primary w-100" value="<?= $btn ?>">
                                                </div>
                                            <?php
                                            }
                                            ?>

                                        </div>
                                    </form>
                                </div>
                                <!-- La table qui affiche les entree en Fran -->
                                <div class="col-xl-12 table-responsive px-3 card mt-4 px-4 pt-3">
                                    <h6 class="text-center">Listes des Entrées en Franc</h6>
                                    <table class='table table-hover' id="table1">
                                        <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Montant</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $num = 0;
                                            while ($EntreeDol = $getEntreeFranc->fetch()) {
                                                $num = $num + 1;
                                                $libelle = $EntreeDol["libelle"];
                                                $report = "Réport à nouveau";
                                            ?>
                                                <tr>
                                                    <th scope="row"><?= $num ?></th>
                                                    <td><?= $EntreeDol["date"] ?></td>
                                                    <td><?= $EntreeDol["libelle"] ?></td>
                                                    <td><?= $EntreeDol["montant"] . " " . "Fc" ?></td>
                                                    <?php
                                                    if ($libelle == $report) {
                                                    ?>
                                                        <td>
                                                            <i class="text-mute">Null</i>
                                                        </td>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <td>
                                                            <a href="entree.php?Franc&Fc&idEntreeFc=<?= $EntreeDol["id"] ?>" class="btn btn-primary btn-sm">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                            <a href="entree.php?SupTer=<?= $EntreeDol["id"] ?>" class="btn btn-danger btn-sm">
                                                                <i class="bi bi-trash3-fill"></i>
                                                            </a>
                                                        </td>
                                                    <?php
                                                    }
                                                    ?>
                                                </tr>
                                        </tbody>
                                    <?php
                                            }
                                    ?>
                                    </table>
                                </div>
                            <?php
                            } else {
                            ?>
                                <div class="col-lg-12">
                                    <!-- Carte du choix de la devise -->
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title text-center">Vous devez choisir une dévise !</h5>
                                            <h6 class="card-subtitle mb-2 text-muted text-center">Entrée en '$' / 'Fc'</h6>
                                            <p class="card-text text-center">
                                                Pour Enregister un mouvement de caisse vous devez choisir avant la devise
                                                pour permettre au système de déterminer le solde exacte.
                                            </p>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <p class="card-text">
                                                        <a href="entree.php?dollar" class="btn btn-primary btn-sm w-100">Dollars / "$"</a>
                                                    </p>
                                                </div>
                                                <div class="col-lg-6">
                                                    <p class="card-text">
                                                        <a href="entree.php?Franc" class="btn btn-primary btn-sm w-100">Francs / "Fc"</a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- End Carte du choix de la devise -->
                                </div>
                                <!-- La table qui affiche les entree en Fran -->
                                <div class="col-xl-12 table-responsive px-3 card mt-4 px-4 pt-3">
                                    <h5 class="text-center">Listes des Entrées</h5>
                                    <table class='table table-hover' id="table1">
                                        <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Montant</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $num = 0;
                                            while ($EntreeDol = $getEntree->fetch()) {
                                                $num = $num + 1;
                                                $libelle = $EntreeDol["libelle"];
                                                $report = "Réport à nouveau";
                                                $getDevise = "";
                                                $getDevise = $EntreeDol["devise"];
                                                if ($getDevise == "Dollard") {
                                                    $EntreeDevise = "$";
                                                } else {
                                                    $EntreeDevise = "Fc";
                                                }
                                            ?>
                                                <tr>
                                                    <th scope="row"><?= $num ?></th>
                                                    <td><?= $EntreeDol["date"] ?></td>
                                                    <td><?= $EntreeDol["libelle"] ?></td>
                                                    <td><?= $EntreeDol["montant"] . " " . $EntreeDevise ?></td>
                                                    <?php
                                                    if ($libelle == $report) {
                                                    ?>
                                                        <td>
                                                            <i class="text-mute">Null</i>
                                                        </td>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <td>
                                                            <a href="entree.php?SupTer=<?= $EntreeDol["id"] ?>" class="btn btn-danger btn-sm">
                                                                <i class="bi bi-trash3-fill"></i>
                                                            </a>
                                                        </td>
                                                    <?php
                                                    }
                                                    ?>
                                                </tr>
                                        </tbody>
                                    <?php
                                            }
                                    ?>
                                    </table>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                    <?php
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