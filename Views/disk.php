<?php
# Se connecter à la BD
include '../connexion/connexion.php';
# Appel du script de selection
require_once('../models/select/select-Disk.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disk</title>
    <?php require_once('style.php') ?>
</head>

<body>
    <div id="app">
        <?php
        require_once('Active.php');
        $ActiveMateriel = 1;
        require_once('aside.php');
        ?>
        <div id="main">
            <?php require_once('navbar.php') ?>
            <div class="main-content container-fluid">
                <div class="row">
                    <div class="col-12">
                        <h4>Les Disk de stockage</h4>
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
                    } else {
                        if (isset($_GET["NewDisk"])) {
                            ?>
                                <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                                    <h3 class="bi bi-shield-exclamation text-center"> Enregistrer un nouveau Disk !</h3> <br>
                                    <p class="text-center">
                                        Voule-Vous Ajouter un nouveau disk dans la base des données ?? <br>
                                        Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                        réaliser ! Elle permet d'ajouter un nouveau disquer dur et lui génerer un matricule.
                                    </p>
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <a href="disk.php" class="btn btn-danger bi bi-x-circle w-100"> Annler</a>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <a href="../models/add/add-Disk-post.php?AddDisk" class="btn btn-primary bi bi-plus w-100"> Nouveau Disk</a>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            } elseif (isset($_GET["idDisk"])){
                                ?>
                                    <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                                        <h3 class="bi bi-shield-exclamation text-danger text-center"> Un disk dure déclasser deviens unitilisable.</h3> <br>
                                        <p class="text-center">
                                            Voule-Vous vraiment déclasser le disk <?= $DiskMatricule?> ?? <br>
                                            Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                            réaliser ! Elle permet de déclasser un disque dur. 
                                        </p>
                                        <div class="row">
                                            <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                                <a href="disk.php" class="btn btn-primary bi bi-plus w-100"> Annler</a>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                                <a href="../models/delete/delete-Disk.php?idDisk=<?=$id?>" class="btn btn-danger bi bi-x-circle w-100"> Déclasser Disk</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                } else {
                            ?>
                                <!-- La table qui affiche les données  -->
                                <div class="col-xl-12 table-responsive px-3 card mt-4 px-4 pt-3">
                                    <a href="disk.php?NewDisk">
                                        <h6 class="bi bi-plus-circle btn btn-primary btn-sm"> Disk</h6>
                                    </a>
                                    <h4 class="text-center">Listes des Disques de stockages</h4>
                                    <table class='table table-hover' id="table1">
                                        <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>Matricule</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $n = 0;
                                            while ($Disk = $getData->fetch()) {
                                                $n++;
                                            ?>
                                                <tr>
                                                    <th scope="row"><?= $n?></th>
                                                    <td><?= $Disk["matricule"]?></td>
                                                    <td>
                                                        <a href="disk.php?idDisk=<?= $Disk["id"]?>" class="btn btn-primary btn-sm">
                                                            Declasser
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