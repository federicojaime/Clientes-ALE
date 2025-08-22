<?php
namespace App\Utils;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $dbname = $_ENV['DB_NAME'] ?? 'u565673608_clientes';
                $username = $_ENV['DB_USER'] ?? 'root';
                $password = $_ENV['DB_PASS'] ?? '';
                
                $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                
                self::$connection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
            } catch (PDOException $e) {
                throw new \Exception("Error conexion BD: " . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    public static function execute(string $query, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }
    
    public static function findById(string $table, int $id): ?array
    {
        $stmt = self::execute("SELECT * FROM {$table} WHERE id = ?", [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public static function insert(string $table, array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $query = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        self::execute($query, array_values($data));
        return (int) self::getConnection()->lastInsertId();
    }
}
