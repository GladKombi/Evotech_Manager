<?php

namespace App\Models;

use App\Core\Model;

class Timesheet extends Model
{
    protected string $table = 'TIMESHEET';
    protected string $primaryKey = 'timesheet_id';

    public function allWithDetails(): array
    {
        $sql = "SELECT ts.*, t.description_tache, p.reference AS projet_reference, p.titre AS projet_titre,
                       a.nom AS agent_nom, a.prenom AS agent_prenom,
                       v.nom AS valideur_nom, v.prenom AS valideur_prenom
                FROM TIMESHEET ts
                INNER JOIN TACHES t ON t.tache_id = ts.tache_id
                INNER JOIN PROJETS p ON p.projet_id = t.projet_id
                INNER JOIN AGENTS a ON a.agent_id = ts.agent_id
                LEFT JOIN UTILISATEURS v ON v.utilisateur_id = ts.valide_par
                WHERE ts.statut = 0
                ORDER BY ts.date_travail DESC, ts.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }
}
