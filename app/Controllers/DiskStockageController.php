<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Helpers; use App\Core\Session; use App\Models\DiskStockage;
class DiskStockageController extends Controller {
    public function index(): void { Auth::requireAuth(); $model=new DiskStockage(); $this->view('disks/index',['title'=>'Disks','items'=>$model->all()]); }
    public function store(): void { Auth::requireAuth(); $model=new DiskStockage(); $data=$_POST; unset($data['_csrf']); $model->create($data); Session::flash('success','Enregistrement effectué avec succès'); Helpers::redirect('disks'); }
    public function delete(): void { Auth::requireAuth(); $id=(int)($_POST['id'] ?? $_GET['id'] ?? 0); (new DiskStockage())->softDelete($id); Session::flash('success','Suppression effectuée'); Helpers::redirect('disks'); }
}
