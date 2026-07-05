<?php

namespace App\Models;

use App\Core\Model;

class Production extends Model
{
    protected string $table = 'PRODUCTION';
    protected string $primaryKey = 'production_id';

    public function allWithDetails(): array
    {
        $sql = "SELECT p.*,
                       pr.reference AS projet_reference,
                       pr.titre AS projet_titre,
                       tp.nom AS type_nom,
                       tp.extension_autorisee,
                       tp.taille_max_mo,
                       ds.matricule AS disk_matricule,
                       ds.etat AS disk_etat
                FROM PRODUCTION p
                LEFT JOIN PROJETS pr ON pr.projet_id = p.projet_id
                LEFT JOIN TYPES_PRODUCTION tp ON tp.type_prod_id = p.type_prod_id
                LEFT JOIN DISK_STOCKAGE ds ON ds.disk_id = p.disk_id
                WHERE p.statut = 0
                ORDER BY p.date_upload DESC, p.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }
}
