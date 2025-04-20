<?php
# Se connecter à la BD
include '../connexion/connexion.php'; 
# Appel du script de selection
require_once('../models/select/select-Role.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rôle</title>
    <?php require_once('style.php') ?>
</head>

<body>
    <div id="app">
        <?php
        require_once('Active.php');
        $ActiveRole = 1;
        require_once('aside.php');
        ?>
        <div id="main">
            <?php require_once('navbar.php') ?>
            <div class="main-content container-fluid">
                <div class="row">
                    <div class="col-12">
                        <h4>Les Rôles</h4>
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
                    if (isset($_GET['SupRole'])) {
                        $id = $_GET["SupRole"];
                    ?>
                        <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                            <h3 class="bi bi-shield-exclamation text-danger text-center"> Zone Dangereuse</h3> <br>
                            <p class="text-center">
                                Voule-vous vraiment supprimer ce rôle ?? c'est dangereux ! <br>
                                Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                réaliser ! Elle permet de supprimer un rôle de la base de données et toutes les données liées à ce rôle .
                            </p>
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="roles.php" class="btn btn-success  w-100"> Annuler</a>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="../models/delete/delete-role.php?idSupRole=<?= $id ?>" class="btn btn-danger bi bi-trash w-100"> Supprimer le rôle</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {

                        if (isset($_GET['NewRole'])) {
                        ?>
                            <!-- Le form qui enregistrer les données  -->
                            <div class="col-xl-4 col-lg-4 col-md-6">
                                <form action="<?= $url ?>" class="shadow p-3" method="POST">
                                    <h5 class="text-center"><?= $title ?></h5>
                                    <div class="row">
                                        <div class="col-12 p-3">
                                            <label for="">Decription du rôle<span class="text-danger">*</span></label>
                                            <input required type="text" class="form-control" name="fonction" placeholder="Entrez la description" <?php if (isset($_GET['idModif'])) { ?> value="<?php echo $AfichRole['fonction']; ?> <?php } ?>">
                                        </div>
                                        <div class="col-12 p-3">
                                            <input type="submit" class="btn btn-success w-100" name="Valider" value="<?= $btn ?>">
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <!-- La table qui affiche les données lorqs de l'enregistrement -->
                            <div class="col-xl-8 col-lg-8 col-md-6 table-responsive px-3 pt-3">
                                <h4 class="text-center">Liste des rôles</h4>
                                <table class='table table-hover' id="table1">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $n = 0;
                                        while ($Role = $getData->fetch()) {
                                            $n++;
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $n; ?></th>
                                                <td> <?= $Role["fonction"] ?></td>
                                                <td>
                                                    <a href='roles.php?NewRole&idModif=<?= $Role['id'] ?>' class="btn btn-sm btn-success mt-1">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="roles.php?SupRole=<?= $Role['id'] ?>" class="btn btn-danger btn-sm mt-1">
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
                        } else {
                        ?>
                            <a href="roles.php?NewRole" class="btn btn-dark w-100">Nouveau rôle</a>

                            <!-- La table qui affiche les données  -->
                            <div class="col-xl-12 col-lg-12 col-md-6 table-responsive px-3 pt-3">
                                <h4 class="text-center">Liste des rôles</h4>
                                <table class='table table-hover' id="table1">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $n = 0;
                                        while ($Role = $getData->fetch()) {
                                            $n++;
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $n; ?></th>
                                                <td> <?= $Role["fonction"] ?></td>
                                                <td>
                                                    <a href='roles.php?NewRole&idModif=<?= $Role['id'] ?>' class="btn btn-sm btn-success mt-1">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="roles.php?SupRole=<?= $Role['id'] ?>" class="btn btn-danger btn-sm mt-1">
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