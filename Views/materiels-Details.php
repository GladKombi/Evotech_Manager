<?php
# Se connecter à la BD
include '../connexion/connexion.php';
# Appel du script de selection
require_once('../models/select/select-Matos.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finances</title>
    <?php require_once('style.php') ?>
</head>

<body>
    <div id="app">
        <?php
        require_once('Active.php');
        $ActiveCloture = 1;
        require_once('aside.php');
        ?>
        <div id="main">
            <?php require_once('navbar.php') ?>
            <div class="main-content container-fluid">
                <div class="row">
                    <div class="col-12">
                        <h4>Materiels</h4>
                    </div>
                    <!-- pour afficher les massage  -->

                    <?php
                    if (isset($_SESSION['msg']) && !empty($_SESSION['msg'])) { ?>
                        <div class="col-xl-12 mt-3">
                            <div class="alert-info alert text-center"><?= $_SESSION['msg'] ?></div>
                        </div>
                    <?php }
                    #Cette ligne permet de vider la valeur qui se trouve dans la session message
                    unset($_SESSION['msg']);

                    if (isset($_GET["NewMatos"]) && !isset($_GET['NewCat'])) {
                    ?>
                        <!-- Le form qui enregistre les materiels  -->
                        <div class="col-xl-12 ">
                            <form action="<?= $action ?>" method="POST" class="shadow p-3" enctype="multipart/form-data">
                                <div class="row">
                                    <h4 class="text-center"><?= $title ?></h4>
                                    <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                        <label for="">Nom <span class="text-danger">*</span></label>
                                        <input required type="text" name="nom_materiel" <?php if (isset($_GET['idModif'])) { ?>value="<?= $AfichDepartement['nom_Departement'] ?>" <?php } ?>class="form-control" placeholder="Entrez le nom du materiel ">
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                        <label for="">Marque <span class="text-danger">*</span></label>
                                        <input required type="text" name="marque" <?php if (isset($_GET['idModif'])) { ?>value="<?= $AfichDepartement['denomination'] ?>" <?php } ?> class="form-control" placeholder="Entrer la marque ">
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                        <label for="">Categorie de materiel <span class="text-danger">*</span></label>
                                        <select required id="" name="categorie" class="form-control select2">
                                            <?php
                                            while ($CatMatos = $getCatMatos->fetch()) {
                                                if (isset($_GET['idAgent'])) {
                                            ?>
                                                    <option <?php if ($CatMatosModif == $CatMatos['id']) { ?>Selected <?php } ?> value="<?= $CatMatos['id'] ?>"><?= $CatMatos['nom'] ?></option>
                                                <?php
                                                } else {
                                                ?>
                                                    <option value="<?= $CatMatos['id'] ?>"><?= $CatMatos['nom'] ?></option>
                                            <?php
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                        <label for="">Specialité <span class="text-danger">*</span></label>
                                        <input required type="text" name="specialite" <?php if (isset($_GET['idModif'])) { ?>value="<?= $AfichDepartement['denomination'] ?>" <?php } ?> class="form-control" placeholder="Entrer la marque ">
                                    </div>
                                    <div class="col-xl-4 col-lg-4 col-md-4  col-sm-6 p-3">
                                        <label for="">Photo de profil<span class="text-danger">*</span></label>
                                        <input autocomplete="off" value="" name="picture" class="img-fluid" type="file" class="form-control" placeholder="Aucun fichier">
                                    </div>

                                    <?php if (isset($_GET['idModif'])) {
                                    ?>
                                        <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                            <input type="submit" name="valider" class="btn btn-primary w-100" value="Modifier">
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                            <a href="departement.php" class="btn btn-danger w-100">Annuler</a>
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
                    <?php
                    } elseif (isset($_GET['NewCat'])) {
                    ?>
                        <!-- Le form qui enregistre les categorie de materiel  -->
                        <div class="col-xl-12 ">
                            <form action="<?= $action ?>" method="POST" class="shadow p-3">
                                <div class="row">
                                    <h4 class="text-center"><?= $title ?></h4>
                                    <div class="col-xl-12 col-lg-12 col-md-12  col-sm-12 p-3">
                                        <label for="">Nom <span class="text-danger">*</span></label>
                                        <input required type="text" name="nom_Categorie" <?php if (isset($_GET['idCatMateriel'])) { ?>value="<?= $AfichDepartement['nom_Departement'] ?>" <?php } ?>class="form-control" placeholder="Entrez le nom du departement ">
                                    </div>

                                    <?php if (isset($_GET['idCatMateriel'])) {
                                    ?>
                                        <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                            <input type="submit" name="valider" class="btn btn-primary w-100" value="Modifier">
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                            <a href="departement.php" class="btn btn-danger w-100">Annuler</a>
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
                        <!-- La table qui affiche les données  -->
                        <div class="col-xl-12 table-responsive px-3 card mt-4 px-4 pt-3">
                            <a href="materiels-Details.php">
                                <h6 class="bi bi-plus btn btn-primary btn-sm">Voir les materiels</h6>
                            </a>
                            <h6 class="text-center">Listes des categories</h6>
                            <table class="table table-borderless datatable ">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>Nom </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $num = 0;
                                    while ($materiel = $getCatMatos->fetch()) {
                                        $num++;
                                    ?>
                                        <tr>
                                            <!--<th scope="row">1</th> -->
                                            <td><?php echo $num ?></td>
                                            <td><?php echo $materiel["nom"] ?></td>
                                            <td>
                                                <a href="materiel.php?Newmateriel&idModif=<?php echo $materiel[0] ?>" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a onclick=" return confirm('Voulez-vous vraiment supprimer ?')" href="../models/delete/delete-materiel.php?idSupDepar=<?php echo $departement[0] ?>" class="btn btn-danger btn-sm">
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
                        <a href="materiels-Details.php?NewMatos" class="btn btn-primary w-100">Nouveau materiel</a>
                    <?php
                    }
                    ?>


                    <!-- La table qui affiche les données  -->
                    <div class="col-xl-12 table-responsive px-3 card mt-4 px-4 pt-3">
                        <a href="materiels-Details.php?NewCat">
                            <h6 class="bi bi-plus btn btn-primary btn-sm">Categorie de materiel</h6>
                        </a>
                        <h6 class="text-center">Listes des materiels</h6>
                        <table class="table table-borderless datatable ">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Categorie</th>
                                    <th>Nom & Marque</th>
                                    <th>Specialité</th>
                                    <th>Photo</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $num = 0;
                                while ($materiel = $getData->fetch()) {
                                    $num++;
                                ?>
                                    <tr>
                                        <!--<th scope="row">1</th> -->
                                        <td><?php echo $num ?></td>
                                        <td><?php echo $materiel["nomcat"] ?></td>
                                        <td><?php echo $materiel["marque"] . " " . $materiel["nom"] ?></td>
                                        <td><?php echo $materiel["specialite"] ?></td>
                                        <td><img src="../assets/img/Matos/<?= $materiel["photo"] ?>" alt="" class="rounded-circle mt-2" width="65px" height="60px"></td>
                                        <td>
                                            <a href="materiel.php?Newmateriel&idModif=<?php echo $materiel[0] ?>" class="btn btn-primary btn-sm">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a onclick=" return confirm('Voulez-vous vraiment supprimer ?')" href="../models/delete/delete-materiel.php?idSupDepar=<?php echo $departement[0] ?>" class="btn btn-danger btn-sm">
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
                </div>
            </div>

            <?php require_once('footer.php') ?>
        </div>
    </div>
    <?php require_once('script.php') ?>
</body>

</html>