<?php
# Se connecter à la BD
include '../connexion/connexion.php';
# Appel du script de selection
require_once('../models/select/select-Production.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productions</title>
    <?php require_once('style.php') ?>
</head>

<body>
    <div id="app">
        <?php
        require_once('Active.php');
        $ActiveProjet = 1;
        require_once('aside.php');
        ?>
        <div id="main">
            <?php require_once('navbar.php') ?>
            <div class="main-content container-fluid">
                <div class="row">
                    <div class="col-12">
                        <h4>Les Productions</h4>
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
                    if (isset($_GET['SupProjet'])) {
                        $id = $_GET["SupProjet"];
                    ?>
                        <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                            <h3 class="bi bi-shield-exclamation text-danger text-center"> Zone Dangereuse</h3> <br>
                            <p class="text-center">
                                Voule-vous vraiment supprimer ce projet ?? c'est dangereux ! <br>
                                Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                réaliser ! Elle permet de supprimer un Projet de la base de données et toutes les données liées à cet Projet .
                            </p>
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="projet.php" class="btn btn-success  w-100"> Annuler</a>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="../models/delete/delete-Projet.php?idSupPro=<?= $id ?>" class="btn btn-danger bi bi-trash w-100"> Supprimer un Projet</a>
                                </div>
                            </div>
                        </div>
                    <?php
                    } else if (isset($_GET['livree']) && !empty($_GET['livree'])) {
                        $prod = $_GET["livree"];
                        $ProjetProd = $_GET["Prod"];
                    ?>
                        <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                            <h3 class="bi bi-shield-exclamation text-danger text-center"> Zone Dangereuse</h3> <br>
                            <p class="text-center">
                                Voule-vous vraiment supprimer ce projet ?? c'est dangereux ! <br>
                                Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                réaliser ! Elle permet de supprimer un Projet de la base de données et toutes les données liées à cet Projet .
                            </p>
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="production.php?VoirProd=<?= $ProjetProd ?>" class="btn btn-success  w-100"> Annuler</a>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="../models/updat/livre.php?VoirProd=<?= $ProjetProd ?>&idPro=<?= $prod["id"] ?>" class="btn btn-danger bi bi-trash w-100"> Livrer le Projet</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        if (isset($_GET['NewProduction'])) {
                        ?>
                            <!-- Le form qui enregistrer les données  -->
                            <div class="col-xl-4 col-lg-4 col-md-6">
                                <form action="<?= $action ?>" method="POST" class="shadow p-3">
                                    <div class="row">
                                        <h4 class="text-center"><?= $title ?></h4>
                                        <div class="col-xl-12 col-lg-12 col-md-12  col-sm-12 p-3">
                                            <label for="">Type de Production<span class="text-danger">*</span></label>
                                            <input required autocomplete="off" type="text" name="type" class="form-control" placeholder="EX: 'Projet Web' ou 'APK'" <?php if (isset($_GET['idProd'])) { ?>value="<?= $ProdMod['Typeproduction'] ?>" <?php } ?>>
                                        </div>
                                        <div class="col-xl-12 col-lg-12 col-md-12  col-sm-12 p-3">
                                            <label for="">Agent <span class="text-danger">*</span></label>
                                            <select required id="" name="user" class="form-control select2">
                                                <?php
                                                while ($Users = $getUser->fetch()) {
                                                    if (isset($_GET['idAgent'])) {
                                                ?>
                                                        <option <?php if ($AgentModif == $Users['id']) { ?>Selected <?php } ?> value="<?= $Users['id'] ?>"><?= $Users['nom'] . " " . $Users['prenom'] . " " . $Users['mail'] ?></option>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <option value="<?= $Users['id'] ?>"><?= $Users['nom'] . " " . $Users['prenom'] . " " . $Users['mail'] ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-xl-12 col-lg-12 col-md-12  col-sm-12 p-3">
                                            <label for="">Choisir Un disque de stockage <span class="text-danger">*</span></label>
                                            <select required id="" name="disk" class="form-control select2">
                                                <?php
                                                while ($disk = $getDisk->fetch()) {
                                                    if (isset($_GET['idProd'])) {
                                                ?>
                                                        <option <?php if ($diskModif == $disk['id']) { ?>Selected <?php } ?> value="<?= $disk['id'] ?>"><?= $disk['matricule'] ?></option>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <option value="<?= $disk['id'] ?>"><?= $disk['matricule'] ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-xl-12 col-lg-12 col-md-12  col-sm-12 p-3">
                                            <label for="">Emplacement/nom du dossier <span class="text-danger">*</span></label>
                                            <input required autocomplete="off" type="text" name="emplacement" class="form-control" placeholder="Entrez l'adresse" <?php if (isset($_GET['idProd'])) { ?>value="<?= $ProdMod['emplacement'] ?>" <?php } ?>>
                                        </div>

                                        <?php if (isset($_GET['idProd'])) {
                                        ?>
                                            <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                <a href="production.php?VoirProd=<?= $Projet ?>" class="btn btn-danger w-100">Annuler</a>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                <input type="submit" name="valider" class="btn btn-dark w-100" value="Modifier">
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
                            <!-- La table qui affiche les données lors de l'enregistrement -->
                            <div class="col-xl-8 col-lg-8 col-md-6 table-responsive px-3 pt-3">
                                <h4 class="text-center">Liste des Productions</h4>
                                <table class='table table-hover' id="table1">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Projet</th>
                                            <th>Agent</th>
                                            <th>Type de production</th>
                                            <th>Disk</th>
                                            <th>Emplacement</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $n = 0;
                                        while ($prod = $getPost_Prod->fetch()) {
                                            $n++;
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $n ?></th>
                                                <td><?= $prod["description"] ?></td>
                                                <td><?= $prod["nom"] . " " . $prod["prenom"] ?></td>
                                                <td><?= $prod["Typeproduction"] ?></td>
                                                <td><?= $prod["matricule"] ?></td>
                                                <td><?= $prod["emplacement"] ?></td>
                                                <td>
                                                    <a href="production.php?NewProduction=<?= $idProjet ?>&idProd=<?= $prod["id"] ?>" class="btn btn-success btn-sm mb-2">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a onclick=" return confirm('Voulez-vous vraiment supprimer ?')" href="../models/delete/delete-production.php?idSupPro=<?= $prod["id"] ?>" class="btn btn-danger btn-sm mb-2">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Le form qui enregistrer les données  -->
                            <div class="col-xl-12 col-lg-12 col-md-6">

                            </div>
                        <?php
                        } elseif (isset($_GET["VoirProd"])) {
                            $idTer = $_GET["VoirProd"];
                        ?>
                            <div class="col-xl-12 table-responsive px-3 mt-4 px-4 pt-3">
                                <div class="col-xl-12 px-3 mt-4 px-4 pt-3 card">
                                    <h5 class="text-center">Detail du projet</h5>
                                    <h6>Projet : <?= $type . " : " . $descriprion ?></h6>
                                    <h6 class="pb-2">Partenaire : <?= $denomination ?></h6>
                                </div>

                                <h4 class="text-center">Listes des Productions</h4>
                                <table class='table table-hover' id="table1">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Terrain</th>
                                            <th>Agent</th>
                                            <th>Type de production</th>
                                            <th>Disk</th>
                                            <th>Emplacement</th>
                                            <th>Livraison</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $n = 0;
                                        while ($prod = $getPost_Prod->fetch()) {
                                            $n++;
                                            $Livraison = $prod["livraison"];
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $n ?></th>
                                                <td><?= $prod["Denomination"] ?></td>
                                                <td><?= $prod["nom"] . " " . $prod["prenom"] ?></td>
                                                <td><?= $prod["Typeproduction"] ?></td>
                                                <td><?= $prod["matricule"] ?></td>
                                                <td><?= $prod["emplacement"] ?></td>
                                                <?php
                                                if ($Livraison == 0) {
                                                ?>
                                                    <td>
                                                        <a href="production.php?livree<?= $prod["id"] ?>&Prod=<?= $ProjetProd ?>" class="btn btn-dark btn-sm">Livrée</a>
                                                    </td>
                                                <?php
                                                } else {
                                                ?>
                                                    <td>
                                                        Deja livrée
                                                    </td>
                                                <?php
                                                }
                                                ?>

                                                <td>
                                                    <a href="production.php?NewProduction=<?= $ProjetProd ?>&idProd=<?= $prod["id"] ?>" class="btn btn-success btn-sm mb-2">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a onclick=" return confirm('Voulez-vous vraiment supprimer ?')" href="../models/delete/delete-production.php?idSupPro=<?= $prod["id"] ?>" class="btn btn-danger btn-sm mb-2">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php
                        } else {
                        ?>
                            <!-- La table qui affiche les données  -->
                            <div class="col-xl-12 col-lg-12 col-md-6 table-responsive px-3 pt-3">
                                <h4 class="text-center">Liste des Projets</h4>
                                <table class='table table-hover' id="table1">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>partenaires</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $n = 0;
                                        while ($Projet = $getData->fetch()) {
                                            $n++;
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $n; ?></th>
                                                <td> <?= $Projet["date"] ?></td>
                                                <td><?= $Projet["type"] . " : " . $Projet["description"] ?></td>
                                                <td><?= $Projet['Denomination'] . " " . $Projet['adresse'] ?></td>
                                                <td>
                                                    <a href="production.php?VoirProd=<?= $Projet["id"] ?>" class="btn btn-dark btn-sm bi bi-eye mt-2"> </a>
                                                    <a href="production.php?NewProduction=<?= $Projet["id"] ?>" class="btn btn-primary btn-sm bi bi-plus mt-2"> </a>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
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