<?php

namespace App\Models;

use App\Core\Model;

class Realisation extends Model
{
    protected string $table = 'REALISATIONS';
    protected string $primaryKey = 'realisation_id';

    public function allWithDetails(): array
    {
        $sql = "SELECT r.*,
                       c.nom AS categorie_nom,
                       COUNT(ri.image_id) AS images_count,
                       MIN(ri.chemin_image) AS first_image,
                       GROUP_CONCAT(ri.chemin_image ORDER BY ri.image_id SEPARATOR '||') AS medias
                FROM REALISATIONS r
                LEFT JOIN CATEGORIES c ON c.categorie_id = r.categorie
                LEFT JOIN REALISATIONS_IMAGES ri ON ri.realisation_id = r.realisation_id
                WHERE r.suprime = 0
                GROUP BY r.realisation_id
                ORDER BY r.date_creation DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function publishedForHome(int $limit = 6): array
    {
        $statement = $this->db->prepare(
            "SELECT r.realisation_id,
                    r.titre,
                    r.description,
                    c.nom AS categorie,
                    GROUP_CONCAT(ri.chemin_image ORDER BY ri.image_id SEPARATOR '||') AS medias
             FROM REALISATIONS r
             LEFT JOIN CATEGORIES c ON c.categorie_id = r.categorie
             LEFT JOIN REALISATIONS_IMAGES ri ON ri.realisation_id = r.realisation_id
             WHERE r.suprime = 0 AND r.statut = 1
             GROUP BY r.realisation_id
             ORDER BY r.date_creation DESC
             LIMIT ?"
        );
        $statement->bindValue(1, $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function createAndReturnId(array $data): int
    {
        $data = $this->cleanData($data);
        $cols = array_keys($data);
        $sql = "INSERT INTO REALISATIONS (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";
        $this->db->prepare($sql)->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function mediaCount(int $realisationId): int
    {
        $statement = $this->db->prepare("SELECT COUNT(*) FROM REALISATIONS_IMAGES WHERE realisation_id = ?");
        $statement->execute([$realisationId]);

        return (int) $statement->fetchColumn();
    }

    public function softDelete(int $id): bool
    {
        $statement = $this->db->prepare("UPDATE REALISATIONS SET suprime = 1 WHERE realisation_id = ?");
        return $statement->execute([$id]);
    }

    public function addImage(int $realisationId, string $path): bool
    {
        $statement = $this->db->prepare("INSERT INTO REALISATIONS_IMAGES (realisation_id, chemin_image) VALUES (?, ?)");
        return $statement->execute([$realisationId, $path]);
    }
}
