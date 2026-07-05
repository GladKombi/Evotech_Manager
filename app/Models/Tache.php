<?php

namespace App\Models;

use App\Core\Model;

class Tache extends Model
{
    protected string $table = 'TACHES';
    protected string $primaryKey = 'tache_id';

    public function allWithDetails(): array
    {
        $sql = "SELECT t.*, p.reference AS projet_reference, p.titre AS projet_titre,
                       a.nom AS agent_nom, a.prenom AS agent_prenom
                FROM TACHES t
                INNER JOIN PROJETS p ON p.projet_id = t.projet_id
                LEFT JOIN AGENTS a ON a.agent_id = t.assigne_a
                WHERE t.statut = 0
                ORDER BY t.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function byProject(int $projectId): array
    {
        $sql = "SELECT t.*, a.nom AS agent_nom, a.prenom AS agent_prenom, a.photo_profil
                FROM TACHES t
                LEFT JOIN AGENTS a ON a.agent_id = t.assigne_a
                WHERE t.projet_id = ? AND t.statut = 0
                ORDER BY t.date_creation DESC";
        $statement = $this->db->prepare($sql);
        $statement->execute([$projectId]);

        return $statement->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT t.*, p.reference AS projet_reference, p.titre AS projet_titre,
                       a.nom AS agent_nom, a.prenom AS agent_prenom, a.email_professionnel
                FROM TACHES t
                INNER JOIN PROJETS p ON p.projet_id = t.projet_id
                LEFT JOIN AGENTS a ON a.agent_id = t.assigne_a
                WHERE t.tache_id = ? AND t.statut = 0";
        $statement = $this->db->prepare($sql);
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }
}
