<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService
{
    private static string $secretKey;
    private static string $algorithm = 'HS256';
    private static int $accessTokenExpiry = 3600; // 1 hora
    private static int $refreshTokenExpiry = 604800; // 7 días
    
    public static function init()
    {
        self::$secretKey = $_ENV['JWT_SECRET'] ?? 'mi_clave_super_secreta_2024';
    }
    
    public static function generateTokens(array $userData): array
    {
        self::init();
        $now = time();
        
        // Access Token (1 hora)
        $accessPayload = [
            'iss' => 'servicios-tecnicos-api',
            'aud' => 'servicios-tecnicos-app',
            'iat' => $now,
            'exp' => $now + self::$accessTokenExpiry,
            'user_id' => $userData['id'],
            'email' => $userData['email'],
            'tipo_usuario' => $userData['tipo_usuario_id'],
            'type' => 'access'
        ];
        
        // Refresh Token (7 días)
        $refreshPayload = [
            'iss' => 'servicios-tecnicos-api',
            'aud' => 'servicios-tecnicos-app',
            'iat' => $now,
            'exp' => $now + self::$refreshTokenExpiry,
            'user_id' => $userData['id'],
            'type' => 'refresh'
        ];
        
        return [
            'access_token' => JWT::encode($accessPayload, self::$secretKey, self::$algorithm),
            'refresh_token' => JWT::encode($refreshPayload, self::$secretKey, self::$algorithm),
            'token_type' => 'Bearer',
            'expires_in' => self::$accessTokenExpiry
        ];
    }
    
    public static function validateToken(string $token): ?array
    {
        try {
            self::init();
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function refreshAccessToken(string $refreshToken): ?array
    {
        $payload = self::validateToken($refreshToken);
        
        if (!$payload || $payload['type'] !== 'refresh') {
            return null;
        }
        
        // Buscar usuario para generar nuevo access token
        $stmt = \App\Utils\Database::execute(
            "SELECT * FROM usuarios WHERE id = ? AND activo = 1",
            [$payload['user_id']]
        );
        
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }
        
        return self::generateTokens($user);
    }
}