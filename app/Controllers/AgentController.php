<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Agent;
use App\Models\Departement;
use RuntimeException;

class AgentController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $model = new Agent();
        $this->view('agents/index', [
            'title' => 'Agents',
            'items' => $model->all(),
            'departements' => (new Departement())->all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $model = new Agent();
        $data = $_POST;
        unset($data['_csrf']);

        try {
            $profilePath = $this->uploadProfile('photo_profil');
            if ($profilePath !== null) {
                $data['photo_profil'] = $profilePath;
            }
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            Helpers::redirect('agents');
        }

        $password = trim((string) ($data['password'] ?? ''));
        $data['mot_de_passe_hash'] = password_hash($password !== '' ? $password : '123456', PASSWORD_DEFAULT);
        unset($data['password']);

        $model->create($data);
        Session::flash('success', 'Enregistrement effectué avec succès');
        Helpers::redirect('agents');
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new Agent())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('agents');
    }

    private function uploadProfile(string $field): ?string
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload de la photo de profil impossible.');
        }

        $extension = strtolower(pathinfo((string) $_FILES[$field]['name'], PATHINFO_EXTENSION));
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

        if (!move_uploaded_file((string) $_FILES[$field]['tmp_name'], $target)) {
            throw new RuntimeException('Enregistrement de la photo impossible.');
        }

        return 'uploads/profiles/' . $filename;
    }
}
