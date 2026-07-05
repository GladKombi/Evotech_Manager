<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Helpers; use App\Core\Session; use App\Models\Partenaire;
class PartenaireController extends Controller {
    public function index(): void { Auth::requireAuth(); $model=new Partenaire(); $this->view('partenaires/index',['title'=>'Partenaires','items'=>$model->all()]); }
    public function store(): void { Auth::requireAuth(); $model=new Partenaire(); $data=$_POST; unset($data['_csrf']); $model->create($data); Session::flash('success','Enregistrement effectué avec succès'); Helpers::redirect('partenaires'); }
    public function delete(): void { Auth::requireAuth(); $id=(int)($_POST['id'] ?? $_GET['id'] ?? 0); (new Partenaire())->softDelete($id); Session::flash('success','Suppression effectuée'); Helpers::redirect('partenaires'); }
}
