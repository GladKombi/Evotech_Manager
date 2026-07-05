<?php

namespace App\Models;

use App\Core\Model;

class Categorie extends Model
{
    protected string $table = 'CATEGORIES';
    protected string $primaryKey = 'categorie_id';

    public function all(): array
    {
        return $this->db->query("SELECT * FROM CATEGORIES ORDER BY date_creation DESC")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare("SELECT * FROM CATEGORIES WHERE categorie_id = ?");
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    public function softDelete(int $id): bool
    {
        $statement = $this->db->prepare("DELETE FROM CATEGORIES WHERE categorie_id = ?");
        return $statement->execute([$id]);
    }
}
