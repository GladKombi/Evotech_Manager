<?php
# Se connecter à la BD
include '../connexion/connexion.php';
# Appel du script de selection
require_once('../models/select/select-Projects.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects</title>
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
                        <h4>Les Projects</h4>
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
                        if (isset($_GET['NewProject'])) {
                        ?>
                            <!-- Le form qui enregistrer les données  -->
                            <div class="col-xl-4 col-lg-4 col-md-6">
                                <form action="<?= $action ?>" class="shadow p-3" method="POST" enctype="multipart/form-data">
                                    <h5 class="text-center"><?= $title ?></h5>
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Description <span class="text-danger">*</span></label>
                                            <input required autocomplete="off" type="text" name="description" class="form-control" placeholder="Entrez la descriiption" <?php if (isset($_GET['idProjet'])) { ?>value="<?= $ProMod["description"] ?>" <?php } ?>>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Partenaires <span class="text-danger">*</span></label>
                                            <select required id="" name="partenaire" class="form-control select2">
                                                <?php
                                                while ($partenaire = $getPartenaire->fetch()) {
                                                    if (isset($_GET['idProjet'])) {
                                                ?>
                                                        <option <?php if ($idPart == $partenaire['id']) { ?>Selected <?php } ?> value="<?= $partenaire['id'] ?>"><?= $partenaire['Denomination'] . " " . $partenaire['adresse'] . " " . $partenaire['telephone'] ?></option>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <option value="<?= $partenaire['id'] ?>"><?= $partenaire['Denomination'] . " " . $partenaire['adresse'] . " " . $partenaire['telephone']  ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Types de projets <span class="text-danger">*</span></label>
                                            <select required id="" name="type" class="form-control select2">
                                                <?php
                                                while ($TypePro = $getTypePro->fetch()) {
                                                    if (isset($_GET['idProjet'])) {
                                                ?>
                                                        <option <?php if ($idType == $TypePro['id']) { ?>Selected <?php } ?> value="<?= $TypePro['id'] ?>"><?= $TypePro['descriprion'] ?></option>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <option value="<?= $TypePro['id'] ?>"><?= $TypePro['descriprion'] ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-12 p-3">
                                            <input type="submit" class="btn btn-success w-100" name="Valider" value="<?= $btn ?>">
                                        </div>


                                    </div>
                                </form>
                            </div>
                            <!-- La table qui affiche les données lors de l'enregistrement -->
                            <div class="col-xl-8 col-lg-8 col-md-6 table-responsive px-3 pt-3">
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
                                                    <a href='project.php?NewProject&idProjet=<?= $Projet['id'] ?>' class="btn btn-sm btn-success mt-1">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="project.php?SupProjet=<?= $Projet['id'] ?>" class="btn btn-danger btn-sm mt-1">
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
                            <!-- Le form qui enregistrer les données  -->
                            <div class="col-xl-12 col-lg-12 col-md-6">

                            </div>
                        <?php
                        } else {
                        ?>
                            <a href="project.php?NewProject" class="btn btn-dark w-100">Ajouter un nouveau projet</a>
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
                                                    <a href='participation.php?idparticipation=<?= $Projet['id'] ?>' class="btn btn-sm btn-dark mt-1">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href='project.php?NewProject&idProjet=<?= $Projet['id'] ?>' class="btn btn-sm btn-success mt-1">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="project.php?SupProjet=<?= $Projet['id'] ?>" class="btn btn-danger btn-sm mt-1">
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