<?php

namespace App\Models;

use App\Core\Model;

class ActiviteContrat extends Model
{
    protected string $table = 'ACTIVITES_CONTRAT';
    protected string $primaryKey = 'activite_id';

    public function allWithDetails(): array
    {
        $sql = "SELECT ac.*, c.reference, c.type_contrat, p.nom AS partenaire_nom
                FROM ACTIVITES_CONTRAT ac
                INNER JOIN CONTRATS_COM c ON c.contrat_id = ac.contrat_id
                INNER JOIN PARTENAIRES p ON p.partenaire_id = c.partenaire_id
                WHERE ac.statut = 0
                ORDER BY ac.date_activite DESC, ac.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function byContract(int $contractId): array
    {
        $sql = "SELECT *
                FROM ACTIVITES_CONTRAT
                WHERE contrat_id = ? AND statut = 0
                ORDER BY date_activite DESC, date_creation DESC";
        $statement = $this->db->prepare($sql);
        $statement->execute([$contractId]);

        return $statement->fetchAll();
    }
}
