<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Categorie;
use App\Models\Realisation;
use RuntimeException;

class RealisationController extends Controller
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogg', 'mov'];
    private const PHOTO_MAX_BYTES = 3145728;
    private const MEDIA_LIMIT = 3;

    public function index(): void
    {
        Auth::requireAccess('realisations');

        $this->view('realisations/index', [
            'title' => 'Réalisations',
            'items' => (new Realisation())->allWithDetails(),
            'categories' => (new Categorie())->all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAccess('realisations');

        $model = new Realisation();
        $data = $_POST;
        unset($data['_csrf']);

        $data['statut'] = (int) ($data['statut'] ?? 0);

        try {
            $media = $this->uploadMedia(0);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            Helpers::redirect('realisations');
        }

        $id = $model->createAndReturnId($data);
        foreach ($media as $path) {
            $model->addImage($id, $path);
        }

        Session::flash('success', 'Réalisation enregistrée');
        Helpers::redirect('realisations');
    }

    public function delete(): void
    {
        Auth::requireAccess('realisations');

        (new Realisation())->softDelete((int) ($_POST['id'] ?? $_GET['id'] ?? 0));
        Session::flash('success', 'Réalisation supprimée');
        Helpers::redirect('realisations');
    }

    public function addMedia(): void
    {
        Auth::requireAccess('realisations');

        $id = (int) ($_POST['realisation_id'] ?? 0);
        $model = new Realisation();
        $existingCount = $model->mediaCount($id);

        if ($id <= 0) {
            Session::flash('error', 'Réalisation introuvable.');
            Helpers::redirect('realisations');
        }

        try {
            $media = $this->uploadMedia($existingCount);
            if ($media === []) {
                throw new RuntimeException('Veuillez sélectionner au moins un média.');
            }

            foreach ($media as $path) {
                $model->addImage($id, $path);
            }
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            Helpers::redirect('realisations');
        }

        Session::flash('success', 'Média ajouté à la réalisation');
        Helpers::redirect('realisations');
    }

    private function uploadMedia(int $existingCount): array
    {
        if (empty($_FILES['images']) || empty($_FILES['images']['name'])) {
            return [];
        }

        $paths = [];
        $names = (array) $_FILES['images']['name'];
        $tmpNames = (array) $_FILES['images']['tmp_name'];
        $errors = (array) $_FILES['images']['error'];
        $sizes = (array) $_FILES['images']['size'];
        $selectedCount = count(array_filter($errors, static fn ($error) => $error !== UPLOAD_ERR_NO_FILE));

        if ($existingCount + $selectedCount > self::MEDIA_LIMIT) {
            throw new RuntimeException('Une réalisation ne peut contenir que 3 médias maximum.');
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/realisations';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        foreach ($names as $index => $name) {
            if (($errors[$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (($errors[$index] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload du média impossible.');
            }

            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($extension, array_merge(self::IMAGE_EXTENSIONS, self::VIDEO_EXTENSIONS), true)) {
                throw new RuntimeException('Format de média non autorisé.');
            }

            if (in_array($extension, self::IMAGE_EXTENSIONS, true) && (int) ($sizes[$index] ?? 0) > self::PHOTO_MAX_BYTES) {
                throw new RuntimeException('Chaque photo doit peser 3 MB maximum.');
            }

            $filename = 'realisation_media_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $target = $uploadDir . '/' . $filename;

            if (!move_uploaded_file((string) ($tmpNames[$index] ?? ''), $target)) {
                throw new RuntimeException('Enregistrement du média impossible.');
            }

            $paths[] = 'uploads/realisations/' . $filename;
        }

        return $paths;
    }
}
