<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class UsuarioController extends BaseController
{
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = min((int) ($params['limit'] ?? 50), 100);
            
            $stmt = Database::execute(
                "SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.ciudad, u.verificado,
                        tu.nombre as tipo_usuario, u.created_at
                 FROM usuarios u 
                 JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id 
                 WHERE u.activo = 1 
                 ORDER BY u.created_at DESC 
                 LIMIT {$limit}"
            );
            
            $usuarios = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'usuarios' => $usuarios,
                'total' => count($usuarios)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo usuarios: ' . $e->getMessage(), 500);
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
                return $this->errorResponse($response, 'Usuario no encontrado', 404);
            }
            
            // Remover datos sensibles
            unset($usuario['google_id'], $usuario['facebook_id'], $usuario['apple_id']);
            
            return $this->successResponse($response, $usuario);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo usuario: ' . $e->getMessage(), 500);
        }
    }
}
