<?php

namespace App\Models;

use App\Core\Model;

class ContratCom extends Model
{
    protected string $table = 'CONTRATS_COM';
    protected string $primaryKey = 'contrat_id';

    public function allWithDetails(): array
    {
        $sql = "SELECT c.*, p.nom AS partenaire_nom, a.nom AS responsable_nom, a.prenom AS responsable_prenom
                FROM CONTRATS_COM c
                INNER JOIN PARTENAIRES p ON p.partenaire_id = c.partenaire_id
                LEFT JOIN AGENTS a ON a.agent_id = c.responsable_id
                WHERE c.statut = 0
                ORDER BY c.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT c.*, p.nom AS partenaire_nom, p.telephone AS partenaire_telephone, p.email AS partenaire_email,
                       a.nom AS responsable_nom, a.prenom AS responsable_prenom
                FROM CONTRATS_COM c
                INNER JOIN PARTENAIRES p ON p.partenaire_id = c.partenaire_id
                LEFT JOIN AGENTS a ON a.agent_id = c.responsable_id
                WHERE c.contrat_id = ? AND c.statut = 0";
        $statement = $this->db->prepare($sql);
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }
}
