<?php
  include('../../connexion/connexion.php');

  if(isset($_POST['modifier']) && !empty($_GET['iduser'])){
    $id=$_GET['iduser'];
    $nom=htmlspecialchars($_POST['nom']);
    $postnom=htmlspecialchars($_POST['postnom']);
    $prenom=htmlspecialchars($_POST['prenom']);    
    $telephone=htmlspecialchars($_POST['telephone']);    
    $pwd=htmlspecialchars($_POST['pwd']);    
    //select data from database
      
    $req=$connexion->prepare("UPDATE `utilisateur` SET  nom=?,postnom=?,prenom=?,telephone=?,pwd=? WHERE id='$id'");
    $resultat=$req->execute([$nom,$postnom,$prenom,$telephone,$pwd]);
    if($resultat==true){
      $msg="Modification réussie";
      $_SESSION['msg']=$msg;
      header("location:../../views/utilisateur.php");
    }
  }
  else{
    header("location:../../views/utilisateur.php");
  }
?>