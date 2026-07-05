<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Helpers; use App\Core\Session; use App\Models\Role;
class RoleController extends Controller {
    public function index(): void { Auth::requireAuth(); $model=new Role(); $this->view('roles/index',['title'=>'Roles','items'=>$model->all()]); }
    public function store(): void { Auth::requireAuth(); $model=new Role(); $data=$_POST; unset($data['_csrf']); $model->create($data); Session::flash('success','Enregistrement effectué avec succès'); Helpers::redirect('roles'); }
    public function delete(): void { Auth::requireAuth(); $id=(int)($_POST['id'] ?? $_GET['id'] ?? 0); (new Role())->softDelete($id); Session::flash('success','Suppression effectuée'); Helpers::redirect('roles'); }
}
