<?php
# Se connecter à la BD
include '../connexion/connexion.php';
# Appel du script de selection
require_once('../models/select/select-Agent.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agents</title>
    <?php require_once('style.php') ?>
</head>

<body>
    <div id="app">
        <?php
        require_once('Active.php');
        $ActiveAgent = 1;
        require_once('aside.php');
        ?>
        <div id="main">
            <?php require_once('navbar.php') ?>
            <div class="main-content container-fluid">
                <div class="row">
                    <div class="col-12">
                        <h4>Les Agents</h4>
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
                    if (isset($_GET['SupAgent'])) {
                        $id = $_GET["SupAgent"];
                    ?>
                        <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                            <h3 class="bi bi-shield-exclamation text-danger text-center"> Zone Dangereuse</h3> <br>
                            <p class="text-center">
                                Voule-vous vraiment supprimer cet Agent ?? c'est dangereux ! <br>
                                Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                                réaliser ! Elle permet de supprimer un Agent de la base de données et toutes les données liées à cet Agent .
                            </p>
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="Agent.php" class="btn btn-success  w-100"> Annuler</a>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                    <a href="../models/delete/delete-Agent.php?idSupAgent=<?= $id ?>" class="btn btn-danger bi bi-trash w-100"> Supprimer Agent</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        if (isset($_GET['NewAgent'])) {
                        ?>
                            <!-- Le form qui enregistrer les données  -->
                            <div class="col-xl-12 col-lg-12 col-md-6">
                                <form action="<?= $url ?>" class="shadow p-3" method="POST" enctype="multipart/form-data">
                                    <h5 class="text-center"><?= $title ?></h5>
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Nom <span class="text-danger">*</span></label>
                                            <input required autocomplete="off" type="text" name="nom" class="form-control" placeholder="Entrez le nom" <?php if (isset($_GET['idAgent'])) { ?>value="<?= $tab['nom'] ?>" <?php } ?>>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Prenom <span class="text-danger">*</span></label>
                                            <input required autocomplete="off" type="text" name="prenom" class="form-control" placeholder="Entrez le prenom" <?php if (isset($_GET['idAgent'])) { ?>value="<?= $tab['prenom'] ?>" <?php } ?>>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Genre <span class="text-danger">*</span></label>
                                            <select required id="" name="genre" class="form-control select2">
                                                <?php
                                                if (isset($_GET['idAgent'])) {
                                                ?>

                                                    <?php if ($tab['genre'] == 'Masculin') { ?>
                                                        <option value="Masculin" Selected>Masculin</option>
                                                        <option value="Feminin">Feminin</option>

                                                    <?php } else {
                                                    ?>
                                                        <option value="Masculin">Masculin</option>
                                                        <option value="Feminin" Selected>Feminin</option>

                                                    <?php }
                                                } else {
                                                    ?>
                                                    <option value="" desabled>Choisir un genre</option>
                                                    <option value="Masculin">Masculin</option>
                                                    <option value="Feminin">Feminin</option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Telephone <span class="text-danger">*</span></label>
                                            <input required autocomplete="off" type="text" name="telephone" class="form-control" placeholder="Entrez le N° Tel" <?php if (isset($_GET['idAgent'])) { ?>value="<?= $tab['telephone'] ?>" <?php } ?>>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Département <span class="text-danger">*</span></label>
                                            <select required id="" name="fonction" class="form-control select2">
                                                <?php
                                                while ($departement = $getDepartement->fetch()) {
                                                    if (isset($_GET['idAgent'])) {
                                                ?>
                                                        <option <?php if ($departementModif == $departement['id']) { ?>Selected <?php } ?> value="<?= $departement['id'] ?>"><?= $departement['nom'] ?></option>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <option value="<?= $departement['id'] ?>"><?= $departement['nom'] ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                            <label for="">Adresse mail <span class="text-danger">*</span></label>
                                            <input required autocomplete="off" type="email" name="mail" class="form-control" placeholder="EX: Exemple@gmail.com" <?php if (isset($_GET['idAgent'])) { ?>value="<?= $tab['mail'] ?>" <?php } ?>>
                                        </div>
                                        <?php if (isset($_GET['idAgent'])) {
                                        ?>
                                            <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                <input type="submit" name="Valider" class="btn btn-success w-100" value="Modifier">
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 mt-4 col-sm-6 p-3 ">
                                                <a href="agent.php" class="btn btn-danger w-100">Annuler</a>
                                            </div>
                                        <?php
                                        } else {
                                        ?>
                                            <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                                                <label for="">Mot de passe <span class="text-danger">*</span></label>
                                                <input required autocomplete="off" type="password" name="pwd" class="form-control" placeholder="Ex:..." <?php if (isset($_GET['idAgent'])) { ?>value="<?= $element['telephone'] ?>" <?php } ?>>
                                            </div>
                                            <div class="col-xl-4 col-lg-4 col-md-4  col-sm-6 p-3">
                                                <label for="">Photo de profil<span class="text-danger">*</span></label>
                                                <input autocomplete="off" value="" name="picture" class="img-fluid" type="file" class="form-control" placeholder="Aucun fichier">
                                            </div>
                                            <div class="col-12 p-3">
                                                <input type="submit" class="btn btn-success w-100" name="Valider" value="<?= $btn ?>">
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
                            <a href="Agent.php?NewAgent" class="btn btn-dark w-100">Nouveau Agent</a>
                            <!-- La table qui affiche les données  -->
                            <div class="col-xl-12 col-lg-12 col-md-6 table-responsive px-3 pt-3">
                                <h4 class="text-center">Liste des rôles</h4>
                                <table class='table table-hover' id="table1">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Noms</th>
                                            <th>Département</th>
                                            <th>mail</th>
                                            <th>Phone</th>
                                            <th>Profil</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $n = 0;
                                        while ($Agent = $getData->fetch()) {
                                            $n++;
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $n; ?></th>
                                                <td> <?= $Agent["nom"] . " " . $Agent["prenom"] ?></td>
                                                <td><?= $Agent["deparetement"] ?></td>
                                                <td><?= $Agent["mail"] ?></td>
                                                <td><?= $Agent["telephone"] ?></td>
                                                <td><img src="../images/profil/<?= $Agent["profil"] ?>" alt="" class="rounded-circle mt-2" width="65px" height="60px"></td>
                                                <td>
                                                    <a href='Agent.php?NewAgent&idAgent=<?= $Agent['id'] ?>' class="btn btn-sm btn-success mt-1">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="Agent.php?SupAgent=<?= $Agent['id'] ?>" class="btn btn-danger btn-sm mt-1">
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