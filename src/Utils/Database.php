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
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]);
                
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                throw new \Exception("Error de conexión a la base de datos");
            }
        }
        
        return self::$connection;
    }
    
    public static function execute(string $query, array $params = []): \PDOStatement
    {
        try {
            $stmt = self::getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage() . " Query: " . $query);
            throw new \Exception("Error en la consulta a la base de datos");
        }
    }
    
    public static function findById(string $table, int $id): ?array
    {
        $stmt = self::execute("SELECT * FROM {$table} WHERE id = ? LIMIT 1", [$id]);
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
    
    public static function update(string $table, int $id, array $data): bool
    {
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $query = "UPDATE {$table} SET {$setClause} WHERE id = ?";
        $params = array_merge(array_values($data), [$id]);
        
        $stmt = self::execute($query, $params);
        return $stmt->rowCount() > 0;
    }
    
    public static function delete(string $table, int $id): bool
    {
        $stmt = self::execute("DELETE FROM {$table} WHERE id = ?", [$id]);
        return $stmt->rowCount() > 0;
    }
    
    public static function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }
    
    public static function commit(): void
    {
        self::getConnection()->commit();
    }
    
    public static function rollback(): void
    {
        self::getConnection()->rollBack();
    }
}
