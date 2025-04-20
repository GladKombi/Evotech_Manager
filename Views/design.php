<?php
# Se connecter à la BD
require_once('../connexion/connexion-Temp.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <style>
        body.dark-mode {
            background-color: #121212;
            color: #ffffff;
        }

        .bg-dark.dark-mode {
            background-color: #1c1c1c !important;
        }

        .sidebar {
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light px-3">
        <div class="d-flex align-items-center">
            <img src="logo.png" alt="Logo" style="width: 40px; height: 40px;" class="me-3">
            <button id="toggleSidebar" class="btn btn-outline-secondary me-3">☰</button>
            <button id="toggleDarkMode" class="btn btn-outline-secondary">🌙</button>
        </div>
        <div class="ms-auto d-flex align-items-center">
            <img src="profile.jpg" alt="Profile" style="width: 40px; height: 40px;" class="rounded-circle me-2">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    John Doe (Admin)
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">Settings</a></li>
                    <li><a class="dropdown-item" href="#">Profile</a></li>
                    <li><a class="dropdown-item" href="#">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <?php require_once('sidebar.php'); ?>
        <!-- Main Content -->
        <div class="flex-grow-1 p-4">
            <div class="col-xl-12 px-3 card mt-4 px-4 pt-3">
                <h3 class="bi bi-shield-exclamation text-danger text-center"> Zone Dangereuse</h3> <br>
                <p class="text-center">
                    Voule-Vous vraiment supprimer ce terrain ?? c'est dangereux ! <br>
                    Cette action est irreverssible, Assurez-vous que c'est l'action que vous souhaiter
                    réaliser ! Elle permet de supprimer un Terain de la base de données et toutes les données liées à ce terrain .
                </p>
                <div class="row">
                    <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                        <a href="terrain.php" class="btn btn-dark  w-100"> Annler</a>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6  col-sm-6 p-3">
                        <a href="../models/delete/delete-terrain.php?SupTer=<?= $id ?>" class="btn btn-danger bi bi-trash w-100"> Supprimer un partenaire</a>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <!-- Form -->
                <div class="col-md-6">
                    <form>
                        <div class="mb-3">
                            <label for="input1" class="form-label">Input 1</label>
                            <input type="text" class="form-control" id="input1" placeholder="Enter something">
                        </div>
                        <div class="mb-3">
                            <label for="input2" class="form-label">Input 2</label>
                            <input type="email" class="form-control" id="input2" placeholder="Enter email">
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
            <div class="row">
                <!-- DataTable -->
                <div class="col-12">
                    <table id="dataTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John Doe</td>
                                <td>john@example.com</td>
                                <td><button class="btn btn-sm btn-danger">Delete</button></td>
                            </tr>
                            <!-- Add more rows as needed -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });

        // Sidebar toggle
        const sidebar = document.querySelector('.sidebar');
        const toggleSidebarButton = document.getElementById('toggleSidebar');
        toggleSidebarButton.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });

        // Dark mode toggle
        const toggleDarkModeButton = document.getElementById('toggleDarkMode');
        toggleDarkModeButton.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            sidebar.classList.toggle('dark-mode');
        });
    </script>
</body>

</html>