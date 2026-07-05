<?php

namespace App\Models;

use App\Core\Model;

class MouvementCompte extends Model
{
    protected string $table = 'MOUVEMENT_COMPTE';
    protected string $primaryKey = 'mouvement_id';

    public function allWithDetails(): array
    {
        $sql = "SELECT m.*, c.nom AS compte_nom, c.devise, p.reference AS projet_reference, cc.reference AS contrat_reference,
                       u.nom AS createur_nom, u.prenom AS createur_prenom
                FROM MOUVEMENT_COMPTE m
                INNER JOIN COMPTES c ON c.compte_id = m.compte_id
                LEFT JOIN PROJETS p ON p.projet_id = m.projet_id
                LEFT JOIN CONTRATS_COM cc ON cc.contrat_id = m.contrat_id
                LEFT JOIN UTILISATEURS u ON u.utilisateur_id = m.cree_par
                WHERE m.statut = 0
                ORDER BY m.date_mouvement DESC, m.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function createAndReturnId(array $data): int
    {
        $this->create($data);
        return (int) $this->db->lastInsertId();
    }
}
