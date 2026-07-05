<?php

namespace App\Models;

use App\Core\Model;

class Participation extends Model
{
    protected string $table = 'PARTICIPATION';
    protected string $primaryKey = 'participation_id';

    public function byProject(int $projectId): array
    {
        $sql = "SELECT p.*, a.nom, a.prenom, a.email_professionnel, a.telephone_professionnel, a.photo_profil
                FROM PARTICIPATION p
                INNER JOIN AGENTS a ON a.agent_id = p.agent_id
                WHERE p.projet_id = ? AND p.statut = 0 AND a.statut = 0
                ORDER BY a.nom, a.prenom";
        $statement = $this->db->prepare($sql);
        $statement->execute([$projectId]);

        return $statement->fetchAll();
    }

    public function agentAlreadyAssigned(int $projectId, int $agentId): bool
    {
        $sql = "SELECT COUNT(*) FROM PARTICIPATION WHERE projet_id = ? AND agent_id = ? AND statut = 0";
        $statement = $this->db->prepare($sql);
        $statement->execute([$projectId, $agentId]);

        return (int) $statement->fetchColumn() > 0;
    }
}
