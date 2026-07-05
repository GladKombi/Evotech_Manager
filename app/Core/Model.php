<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey;

    public function __construct()
    {
        $this->db = Database::conn();
    }

    public function all(): array
    {
        return $this->db->query("SELECT * FROM {$this->table} WHERE statut=0 ORDER BY date_creation DESC")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $s = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey}=? AND statut=0");
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function create(array $data): bool
    {
        $data = $this->cleanData($data);
        $cols = array_keys($data);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";
        return $this->db->prepare($sql)->execute($data);
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->cleanData($data);
        $sets = array_map(fn ($c) => "$c=:$c", array_keys($data));
        $data['id'] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(',', $sets) . " WHERE {$this->primaryKey}=:id";
        return $this->db->prepare($sql)->execute($data);
    }

    public function softDelete(int $id): bool
    {
        $s = $this->db->prepare("UPDATE {$this->table} SET statut=1 WHERE {$this->primaryKey}=?");
        return $s->execute([$id]);
    }

    public function countActive(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE statut=0")->fetchColumn();
    }

    protected function cleanData(array $data): array
    {
        return array_map(static fn ($value) => $value === '' ? null : $value, $data);
    }
}
