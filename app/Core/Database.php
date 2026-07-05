<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;
    public static function conn(): PDO
    {
        if (!self::$pdo) {
            $c = require dirname(__DIR__, 2) . '/config/database.php';
            try {
                self::$pdo = new PDO("mysql:host={$c['host']};dbname={$c['dbname']};charset={$c['charset']}", $c['username'], $c['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                die('Erreur DB : ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
