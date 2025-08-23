<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;
use App\Services\JWTService;

class AuthController extends BaseController
{
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, ['email', 'password']);
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            $stmt = Database::execute(
                "SELECT u.*, tu.nombre as tipo_usuario 
                 FROM usuarios u 
                 JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id 
                 WHERE u.email = ? AND u.activo = 1",
                [$data['email']]
            );
            
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                return $this->errorResponse($response, 'Credenciales incorrectas', 401);
            }
            
            // TODO: Verificar password hasheado en producción
            // if (!password_verify($data['password'], $usuario['password'])) {
            //     return $this->errorResponse($response, 'Credenciales incorrectas', 401);
            // }
            
            $tokens = JWTService::generateTokens($usuario);
            
            return $this->successResponse($response, [
                'user' => [
                    'id' => (int) $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'apellido' => $usuario['apellido'],
                    'email' => $usuario['email'],
                    'tipo_usuario' => $usuario['tipo_usuario'],
                    'tipo_usuario_id' => (int) $usuario['tipo_usuario_id'],
                    'verificado' => (bool) $usuario['verificado']
                ],
                'tokens' => $tokens
            ], 'Login exitoso');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error interno: ' . $e->getMessage(), 500);
        }
    }
    
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, ['nombre', 'apellido', 'email', 'password', 'tipo_usuario_id']);
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->errorResponse($response, 'Email no válido');
            }
            
            if (strlen($data['password']) < 6) {
                return $this->errorResponse($response, 'Password debe tener al menos 6 caracteres');
            }
            
            $stmt = Database::execute("SELECT id FROM usuarios WHERE email = ?", [$data['email']]);
            if ($stmt->fetch()) {
                return $this->errorResponse($response, 'El email ya está registrado', 409);
            }
            
            $userData = [
                'tipo_usuario_id' => (int) $data['tipo_usuario_id'],
                'nombre' => trim($data['nombre']),
                'apellido' => trim($data['apellido']),
                'email' => strtolower(trim($data['email'])),
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'telefono' => $data['telefono'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'ciudad' => $data['ciudad'] ?? null,
                'provincia' => $data['provincia'] ?? null,
                'activo' => 1,
                'verificado' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = Database::insert('usuarios', $userData);
            
            return $this->successResponse($response, [
                'user_id' => $userId,
                'email' => $userData['email']
            ], 'Usuario registrado exitosamente');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error interno: ' . $e->getMessage(), 500);
        }
    }
    
    public function refresh(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, ['refresh_token']);
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            $tokens = JWTService::refreshAccessToken($data['refresh_token']);
            
            if (!$tokens) {
                return $this->errorResponse($response, 'Refresh token inválido o expirado', 401);
            }
            
            return $this->successResponse($response, [
                'tokens' => $tokens
            ], 'Token renovado exitosamente');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error renovando token: ' . $e->getMessage(), 500);
        }
    }
    
    public function me(Request $request, Response $response): Response
    {
        try {
            $userId = $this->getUserId($request);
            
            $stmt = Database::execute(
                "SELECT u.*, tu.nombre as tipo_usuario
                 FROM usuarios u 
                 JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id 
                 WHERE u.id = ?",
                [$userId]
            );
            
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                return $this->errorResponse($response, 'Usuario no encontrado', 404);
            }
            
            // Remover datos sensibles
            unset($usuario['password']);
            
            return $this->successResponse($response, $usuario);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo perfil: ' . $e->getMessage(), 500);
        }
    }
    
    public function logout(Request $request, Response $response): Response
    {
        // En un sistema real, aquí invalidarías el token en una blacklist
        return $this->successResponse($response, null, 'Logout exitoso');
    }
}