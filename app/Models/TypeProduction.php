<?php

namespace App\Models;

use App\Core\Model;

class TypeProduction extends Model
{
    protected string $table = 'TYPES_PRODUCTION';
    protected string $primaryKey = 'type_prod_id';

    public function allWithDepartment(): array
    {
        $sql = "SELECT tp.*, d.nom AS departement_nom
                FROM TYPES_PRODUCTION tp
                LEFT JOIN DEPARTEMENTS d ON d.departement_id = tp.departement_id
                WHERE tp.statut = 0
                ORDER BY tp.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }
}
