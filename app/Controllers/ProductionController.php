<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\DiskStockage;
use App\Models\Production;
use App\Models\Projet;
use App\Models\TypeProduction;
use RuntimeException;

class ProductionController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('productions/index', [
            'title' => 'Productions',
            'items' => (new Production())->allWithDetails(),
            'projets' => (new Projet())->all(),
            'typesProduction' => (new TypeProduction())->allWithDepartment(),
            'disks' => (new DiskStockage())->all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $data = $_POST;
        unset($data['_csrf']);

        try {
            $type = (new TypeProduction())->find((int) ($data['type_prod_id'] ?? 0));
            $document = $this->uploadDocument($type);
            $data['nom_fichier'] = $document['name'];
            $data['chemin_stockage'] = $document['path'];
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            Helpers::redirect('productions');
        }

        $data['date_upload'] = date('Y-m-d H:i:s');
        unset($data['date_modification']);

        (new Production())->create($data);
        Session::flash('success', 'Production enregistrée avec succès');
        Helpers::redirect('productions');
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new Production())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('productions');
    }

    private function uploadDocument(?array $type): array
    {
        if (empty($_FILES['document']) || ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Veuillez joindre le fichier de production.');
        }

        if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload du fichier impossible.');
        }

        $originalName = (string) $_FILES['document']['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = $this->allowedExtensions($type);

        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException('Extension non autorisée pour ce type de production.');
        }

        $maxMb = max(1, (int) ($type['taille_max_mo'] ?? 100));
        if ((int) $_FILES['document']['size'] > $maxMb * 1024 * 1024) {
            throw new RuntimeException('Le fichier dépasse la taille maximale autorisée.');
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/documents';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'production_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $target = $uploadDir . '/' . $filename;

        if (!move_uploaded_file((string) $_FILES['document']['tmp_name'], $target)) {
            throw new RuntimeException('Enregistrement du fichier impossible.');
        }

        return [
            'name' => $originalName,
            'path' => 'uploads/documents/' . $filename,
        ];
    }

    private function allowedExtensions(?array $type): array
    {
        $raw = trim((string) ($type['extension_autorisee'] ?? ''));
        if ($raw === '') {
            return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'webp', 'zip', 'rar'];
        }

        $extensions = preg_split('/[\s,;|]+/', strtolower($raw)) ?: [];
        return array_values(array_filter(array_map(static fn ($item) => ltrim(trim($item), '.'), $extensions)));
    }
}
