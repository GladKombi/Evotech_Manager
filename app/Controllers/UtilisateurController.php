<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Role;
use App\Models\Utilisateur;
use RuntimeException;

class UtilisateurController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('utilisateurs/index', [
            'title' => 'Gestion des Utilisateurs',
            'items' => (new Utilisateur())->withRoles(),
            'roles' => (new Role())->all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        try {
            $profilePath = $this->uploadProfile();
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            Helpers::redirect('utilisateurs');
        }

        $password = trim((string) ($_POST['password'] ?? ''));
        $data = [
            'nom' => $_POST['nom'] ?? '',
            'prenom' => $_POST['prenom'] ?? '',
            'email' => $_POST['email'] ?? null,
            'profil' => $profilePath,
            'mot_de_passe_hash' => password_hash($password !== '' ? $password : '123456', PASSWORD_DEFAULT),
            'actif' => 1,
        ];

        (new Utilisateur())->create($data);
        Session::flash('success', 'Utilisateur créé');
        Helpers::redirect('utilisateurs');
    }

    public function delete(): void
    {
        Auth::requireAuth();

        (new Utilisateur())->softDelete((int) ($_POST['id'] ?? 0));
        Session::flash('success', 'Utilisateur supprimé');
        Helpers::redirect('utilisateurs');
    }

    private function uploadProfile(): ?string
    {
        if (empty($_FILES['profil']) || ($_FILES['profil']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($_FILES['profil']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload de la photo de profil impossible.');
        }

        $extension = strtolower(pathinfo((string) $_FILES['profil']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException('Format de photo non autorisé.');
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/profiles';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'profile_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $target = $uploadDir . '/' . $filename;

        if (!move_uploaded_file((string) $_FILES['profil']['tmp_name'], $target)) {
            throw new RuntimeException('Enregistrement de la photo impossible.');
        }

        return 'uploads/profiles/' . $filename;
    }
}
