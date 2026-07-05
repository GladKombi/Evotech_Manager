<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Helpers; use App\Core\Session; use App\Models\Departement;
class DepartementController extends Controller {
    public function index(): void { Auth::requireAuth(); $model=new Departement(); $this->view('departements/index',['title'=>'Departements','items'=>$model->all()]); }
    public function store(): void { Auth::requireAuth(); $model=new Departement(); $data=$_POST; unset($data['_csrf']); $model->create($data); Session::flash('success','Enregistrement effectué avec succès'); Helpers::redirect('departements'); }
    public function delete(): void { Auth::requireAuth(); $id=(int)($_POST['id'] ?? $_GET['id'] ?? 0); (new Departement())->softDelete($id); Session::flash('success','Suppression effectuée'); Helpers::redirect('departements'); }
}
