<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Departement;
use App\Models\TypeProduction;

class TypeProductionController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('types-production/index', [
            'title' => 'Types de production',
            'items' => (new TypeProduction())->allWithDepartment(),
            'departements' => (new Departement())->all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $data = $_POST;
        unset($data['_csrf']);

        (new TypeProduction())->create($data);
        Session::flash('success', 'Type de production enregistré');
        Helpers::redirect('types-production');
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new TypeProduction())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('types-production');
    }
}
