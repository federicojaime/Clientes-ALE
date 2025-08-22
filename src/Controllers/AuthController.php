<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;
use Firebase\JWT\JWT;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (empty($data['email'])) {
                return $this->jsonResponse($response, ['error' => 'Email requerido'], 400);
            }
            
            // Buscar usuario
            $stmt = Database::execute(
                "SELECT u.*, tu.nombre as tipo_usuario FROM usuarios u 
                 JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id 
                 WHERE u.email = ? AND u.activo = 1",
                [$data['email']]
            );
            
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                return $this->jsonResponse($response, ['error' => 'Usuario no encontrado'], 401);
            }
            
            // Generar token simple (sin JWT por ahora)
            $token = base64_encode($usuario['id'] . ':' . time());
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $usuario['id'],
                        'nombre' => $usuario['nombre'],
                        'email' => $usuario['email'],
                        'tipo_usuario' => $usuario['tipo_usuario']
                    ],
                    'token' => $token
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $required = ['nombre', 'apellido', 'email', 'tipo_usuario_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, ['error' => "Campo {$field} requerido"], 400);
                }
            }
            
            // Verificar email unico
            $stmt = Database::execute("SELECT id FROM usuarios WHERE email = ?", [$data['email']]);
            if ($stmt->fetch()) {
                return $this->jsonResponse($response, ['error' => 'Email ya registrado'], 409);
            }
            
            // Crear usuario
            $userData = [
                'tipo_usuario_id' => $data['tipo_usuario_id'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'activo' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = Database::insert('usuarios', $userData);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => ['user_id' => $userId]
            ], 201);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
