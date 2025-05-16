<?php
# Se connecter à la BD
include '../connexion/connexion.php';
# Selection Querries
require_once("../models/select/select-participation.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participation</title>
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
                        <h4>Affectation des Agents</h4>
                    </div>
                    <!-- pour afficher les massage  -->
                    <?php
                    if (isset($_SESSION['msg']) && !empty($_SESSION['msg'])) {
                    ?>
                        <div class="alert-info alert text-center"><?= $_SESSION['msg'] ?></div>
                        <?php  }
                    #Cette ligne permet de vider la valeur qui se trouve dans la session message  
                    unset($_SESSION['msg']);
                    if (isset($_GET['idparticipation'])) {
                        # Confirmation de la suppression
                        if (isset($_GET['SupAffectation'])) {
                            $idpartic = $_GET["SupAffectation"];
                        ?>
                            <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                                <h3 class="bi bi-shield-exclamation text-danger text-center"> Zone Dangereuse</h3> <br>
                                <p class="text-center">
                                    Voule-vous vraiment supprimer cette affectaion ?? c'est dangereux ! <br>
                                    Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                    réaliser ! Elle permet de supprimer cette affectaion de la base de données et toutes les données liées à cette affectaion.
                                </p>
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                        <a href="participation.php?idparticipation=<?= $id ?>" class="btn btn-success  w-100"> Annuler</a>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                        <a href="../models/delete/delete-participation.php?SupAffectation=<?= $idpartic ?>&idparticipation=<?= $id ?>" class="btn btn-danger bi bi-trash w-100"> Supprimer cette affectaion</a>
                                    </div>
                                </div>
                            </div>
                        <?php
                        } else {
                        ?>
                            <!-- Le form qui enregistrer les données  -->
                            <div class="col-xl-4 col-lg-4 col-md-6">
                                <form action="<?= $url ?>" class="shadow p-3" method="POST">
                                    <h5 class="text-center"><?= $title ?></h5>
                                    <div class="row">
                                        <div class="col-12 p-3">
                                            <label for="">Agent <span class="text-danger">*</span></label>
                                            <select required id="" name="user" class="form-control select2">
                                                <?php
                                                while ($Users = $getUser->fetch()) {
                                                    if (isset($_GET['idAgent'])) {
                                                ?>
                                                        <option <?php if ($UsersModif == $Users['id']) { ?>Selected <?php } ?> value="<?= $Users['id'] ?>"><?= $Users['nom'] . " " . $Users['prenom'] . " " . $Users['mail'] ?></option>
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

                                        <div class="col-12 p-3">
                                            <input type="submit" class="btn btn-success w-100" name="save" value="<?= $btn ?>">
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <!-- La table qui affiche les données lorqs de l'enregistrement -->
                            <div class="col-xl-8 col-lg-8 col-md-6 table-responsive px-3 pt-3">
                            <div class="col-xl-12 px-3 mt-4 px-4 pt-3 card">
                                    <h5 class="text-center">Detail du projet</h5>
                                    <h6>Projet : <?= $type . " : " . $descriprion ?></h6>
                                    <h6 class="pb-2">Partenaire : <?= $denomination ?></h6>
                                </div>
                                <a href="project.php">
                                    <h6 class="bi bi-eye btn btn-dark btn-sm"> Voir les projets</h6>
                                </a>
                                <h5 class="text-center">Liste des participants</h5>
                                <table class='table table-hover' id="table1">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>noms</th>
                                            <th>Mail</th>
                                            <th>Photo</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $n = 0;
                                        while ($Affectation = $getData->fetch()) {
                                            $n++;
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $n; ?></th>
                                                <td> <?= $Affectation["nom"] . " " . $Affectation["prenom"] ?></td>
                                                <td> <?= $Affectation["mail"] ?></td>
                                                <td><img src="../images/profil/<?= $Affectation["profil"] ?>" alt="" class="rounded-circle mt-2" width="65px" height="60px"></td>
                                                <td>
                                                    <a href="participation.php?idparticipation=<?=$id?>&SupAffectation=<?= $Affectation['id'] ?>" class="btn btn-danger btn-sm mt-1">
                                                        <i class="bi bi-trash"></i>
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
                        }
                    } else {
                        ?>
                        <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                            <h3 class="bi bi-shield-exclamation text-danger text-center"> Attetion !</h3> <br>
                            <p class="text-center">
                                Voule-vous vraiment supprimer cette affectaion ?? c'est dangereux ! <br>
                                Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                réaliser ! Elle permet de supprimer cette affectaion de la base de données et toutes les données liées à cette affectaion.
                            </p>
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="<?= $btn ?>" class="btn btn-success w-100 bi bi-eye"> Voir les projets</a>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="../models/delete/delete-participation.php?SupAffectation=<?= $idpartic ?>" class="btn btn-danger bi bi-trash w-100"> Supprimer cette affectaion</a>
                                </div>
                            </div>
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