<?php

namespace App\Models;

use App\Core\Model;

class TransfertCompte extends Model
{
    protected string $table = 'TRANSFERTS_COMPTE';
    protected string $primaryKey = 'transfert_id';

    public function allWithDetails(): array
    {
        $sql = "SELECT t.*, src.nom AS compte_source, dst.nom AS compte_destination,
                       u.nom AS valideur_nom, u.prenom AS valideur_prenom
                FROM TRANSFERTS_COMPTE t
                INNER JOIN COMPTES src ON src.compte_id = t.compte_source_id
                INNER JOIN COMPTES dst ON dst.compte_id = t.compte_destination_id
                LEFT JOIN UTILISATEURS u ON u.utilisateur_id = t.valide_par
                WHERE t.statut = 0
                ORDER BY t.date_transfert DESC, t.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }
}
