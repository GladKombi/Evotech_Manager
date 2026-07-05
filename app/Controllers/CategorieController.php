<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Categorie;

class CategorieController extends Controller
{
    public function index(): void
    {
        Auth::requireAccess('categories');

        $this->view('categories/index', [
            'title' => 'Catégories',
            'items' => (new Categorie())->all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAccess('categories');

        $data = $_POST;
        unset($data['_csrf']);

        (new Categorie())->create($data);
        Session::flash('success', 'Catégorie enregistrée');
        Helpers::redirect('categories');
    }

    public function delete(): void
    {
        Auth::requireAccess('categories');

        (new Categorie())->softDelete((int) ($_POST['id'] ?? $_GET['id'] ?? 0));
        Session::flash('success', 'Catégorie supprimée');
        Helpers::redirect('categories');
    }
}
