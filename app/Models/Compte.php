<?php

namespace App\Models;

use App\Core\Model;

class Compte extends Model
{
    protected string $table = 'COMPTES';
    protected string $primaryKey = 'compte_id';

    public function allWithResponsable(): array
    {
        $sql = "SELECT c.*, a.nom AS responsable_nom, a.prenom AS responsable_prenom
                FROM COMPTES c
                LEFT JOIN AGENTS a ON a.agent_id = c.responsable_id
                WHERE c.statut = 0
                ORDER BY c.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function adjustBalance(int $id, float $amount): bool
    {
        $statement = $this->db->prepare("UPDATE COMPTES SET solde_actuel = COALESCE(solde_actuel, 0) + ? WHERE compte_id = ?");
        return $statement->execute([$amount, $id]);
    }
}
