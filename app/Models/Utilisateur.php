<?php
namespace App\Models;
use App\Core\Model;
class Utilisateur extends Model {
    protected string $table='UTILISATEURS'; protected string $primaryKey='utilisateur_id';
    public function withRoles(): array {
        $sql="SELECT u.*, GROUP_CONCAT(r.fonction SEPARATOR ', ') roles FROM UTILISATEURS u LEFT JOIN ATTRIBUTIONS a ON u.utilisateur_id=a.utilisateur_id AND a.statut=0 LEFT JOIN ROLES r ON a.role_id=r.role_id AND r.statut=0 WHERE u.statut=0 GROUP BY u.utilisateur_id ORDER BY u.date_creation DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
