<?php
?php
namespace App\Services;

class CacheService
{
    private static array $cache = [];
    private static int $defaultTtl = 3600;
    
    public static function get(string $key): mixed
    {
        if (!isset(self::$cache[$key])) {
            return null;
        }
        
        $item = self::$cache[$key];
        
        if ($item['expires'] < time()) {
            unset(self::$cache[$key]);
            return null;
        }
        
        return $item['value'];
    }
    
    public static function set(string $key, mixed $value, int $ttl = null): void
    {
        $ttl = $ttl ?? self::$defaultTtl;
        
        self::$cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
    }
    
    public static function delete(string $key): void
    {
        unset(self::$cache[$key]);
    }
    
    public static function clear(): void
    {
        self::$cache = [];
    }
    
    public static function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        $value = self::get($key);
        
        if ($value === null) {
            $value = $callback();
            self::set($key, $value, $ttl);
        }
        
        return $value;
    }
}
