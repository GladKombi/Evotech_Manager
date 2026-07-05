<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\ActiviteContrat;
use App\Models\Agent;
use App\Models\Categorie;
use App\Models\Compte;
use App\Models\ContratCom;
use App\Models\Departement;
use App\Models\DiskStockage;
use App\Models\MouvementCompte;
use App\Models\Partenaire;
use App\Models\Production;
use App\Models\Projet;
use App\Models\Realisation;
use App\Models\Role;
use App\Models\Tache;
use App\Models\Timesheet;
use App\Models\TransfertCompte;
use App\Models\TypeProduction;
use App\Models\Utilisateur;

class RecordController
{
    private array $modules = [
        'activites-contrat' => ActiviteContrat::class,
        'agents' => Agent::class,
        'categories' => Categorie::class,
        'comptes' => Compte::class,
        'contrats-com' => ContratCom::class,
        'departements' => Departement::class,
        'disks' => DiskStockage::class,
        'mouvements' => MouvementCompte::class,
        'partenaires' => Partenaire::class,
        'productions' => Production::class,
        'projets' => Projet::class,
        'realisations' => Realisation::class,
        'roles' => Role::class,
        'taches' => Tache::class,
        'timesheets' => Timesheet::class,
        'transferts' => TransfertCompte::class,
        'types-production' => TypeProduction::class,
        'utilisateurs' => Utilisateur::class,
    ];

    public function update(): void
    {
        Auth::requireAuth();

        $module = trim((string) ($_POST['_module'] ?? ''), '/');
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || !isset($this->modules[$module])) {
            Session::flash('error', 'Modification impossible');
            Helpers::redirect($module ?: 'dashboard');
        }

        Auth::requireAccess($module);

        $data = $_POST;
        unset($data['_module'], $data['_csrf'], $data['id']);

        if ($module === 'utilisateurs') {
            if (!empty($data['password'])) {
                $data['mot_de_passe_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
            }

            unset($data['password']);

            try {
                $profilePath = $this->uploadProfile('profil');
                if ($profilePath !== null) {
                    $data['profil'] = $profilePath;
                }
            } catch (\RuntimeException $exception) {
                Session::flash('error', $exception->getMessage());
                Helpers::redirect($module);
            }
        }

        if ($module === 'agents') {
            if (!empty($data['password'])) {
                $data['mot_de_passe_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
            }

            unset($data['password']);

            try {
                $profilePath = $this->uploadProfile('photo_profil');
                if ($profilePath !== null) {
                    $data['photo_profil'] = $profilePath;
                }
            } catch (\RuntimeException $exception) {
                Session::flash('error', $exception->getMessage());
                Helpers::redirect($module);
            }
        }

        if ($module === 'productions') {
            try {
                $document = $this->uploadDocument();
                if ($document !== null) {
                    $data['nom_fichier'] = $document['name'];
                    $data['chemin_stockage'] = $document['path'];
                    $data['date_modification'] = date('Y-m-d H:i:s');
                }
            } catch (\RuntimeException $exception) {
                Session::flash('error', $exception->getMessage());
                Helpers::redirect($module);
            }
        }

        if ($module === 'realisations') {
            try {
                $media = $this->uploadRealisationMedia($id);
                if ($media !== []) {
                    $realisation = new Realisation();
                    foreach ($media as $path) {
                        $realisation->addImage($id, $path);
                    }
                }
            } catch (\RuntimeException $exception) {
                Session::flash('error', $exception->getMessage());
                Helpers::redirect($module);
            }
        }

        $data = array_filter($data, static fn ($value) => $value !== null);

        if ($data !== []) {
            $modelClass = $this->modules[$module];
            (new $modelClass())->update($id, $data);
        }

        Session::flash('success', 'Modification effectuée');
        Helpers::redirect($module);
    }

    private function uploadProfile(string $field): ?string
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload de la photo de profil impossible.');
        }

        $extension = strtolower(pathinfo((string) $_FILES[$field]['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Format de photo non autorisé.');
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/profiles';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'profile_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $target = $uploadDir . '/' . $filename;

        if (!move_uploaded_file((string) $_FILES[$field]['tmp_name'], $target)) {
            throw new \RuntimeException('Enregistrement de la photo impossible.');
        }

        return 'uploads/profiles/' . $filename;
    }

    private function uploadDocument(): ?array
    {
        if (empty($_FILES['document']) || ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload du fichier impossible.');
        }

        $extension = strtolower(pathinfo((string) $_FILES['document']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'webp', 'zip', 'rar'];

        if (!in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Format de document non autorisé.');
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/documents';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'production_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $target = $uploadDir . '/' . $filename;

        if (!move_uploaded_file((string) $_FILES['document']['tmp_name'], $target)) {
            throw new \RuntimeException('Enregistrement du fichier impossible.');
        }

        return [
            'name' => (string) $_FILES['document']['name'],
            'path' => 'uploads/documents/' . $filename,
        ];
    }

    private function uploadRealisationMedia(int $realisationId): array
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

        if ((new Realisation())->mediaCount($realisationId) + $selectedCount > 3) {
            throw new \RuntimeException('Une réalisation ne peut contenir que 3 médias maximum.');
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
                throw new \RuntimeException('Upload du média impossible.');
            }

            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $videoExtensions = ['mp4', 'webm', 'ogg', 'mov'];

            if (!in_array($extension, array_merge($imageExtensions, $videoExtensions), true)) {
                throw new \RuntimeException('Format de média non autorisé.');
            }

            if (in_array($extension, $imageExtensions, true) && (int) ($sizes[$index] ?? 0) > 3 * 1024 * 1024) {
                throw new \RuntimeException('Chaque photo doit peser 3 MB maximum.');
            }

            $filename = 'realisation_media_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $target = $uploadDir . '/' . $filename;

            if (!move_uploaded_file((string) ($tmpNames[$index] ?? ''), $target)) {
                throw new \RuntimeException('Enregistrement du média impossible.');
            }

            $paths[] = 'uploads/realisations/' . $filename;
        }

        return $paths;
    }
}
