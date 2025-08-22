<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class UsuarioController
{
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $stmt = Database::execute(
                "SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.ciudad, tu.nombre as tipo_usuario
                 FROM usuarios u 
                 JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id 
                 WHERE u.activo = 1 
                 ORDER BY u.created_at DESC LIMIT 50"
            );
            
            $usuarios = $stmt->fetchAll();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $usuarios,
                'total' => count($usuarios)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function getById(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            
            $stmt = Database::execute(
                "SELECT u.*, tu.nombre as tipo_usuario
                 FROM usuarios u 
                 JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id 
                 WHERE u.id = ?",
                [$id]
            );
            
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                return $this->jsonResponse($response, ['error' => 'Usuario no encontrado'], 404);
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $usuario
            ]);
            
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
