<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class ConfiguracionController
{
    public function getCategorias(Request $request, Response $response): Response
    {
        try {
            $stmt = Database::execute(
                "SELECT id, nombre, descripcion, icono 
                 FROM categorias_servicios 
                 WHERE activo = 1 
                 ORDER BY nombre"
            );
            
            $categorias = $stmt->fetchAll();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $categorias,
                'total' => count($categorias)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function getServicios(Request $request, Response $response): Response
    {
        try {
            $stmt = Database::execute(
                "SELECT s.*, cs.nombre as categoria_nombre
                 FROM servicios s
                 JOIN categorias_servicios cs ON s.categoria_id = cs.id
                 WHERE s.activo = 1 AND cs.activo = 1
                 ORDER BY cs.nombre, s.nombre"
            );
            
            $servicios = $stmt->fetchAll();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $servicios,
                'total' => count($servicios)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function getServiciosPorCategoria(Request $request, Response $response, array $args): Response
    {
        try {
            $categoriaId = (int) $args['categoriaId'];
            
            $stmt = Database::execute(
                "SELECT s.*
                 FROM servicios s
                 WHERE s.categoria_id = ? AND s.activo = 1
                 ORDER BY s.nombre",
                [$categoriaId]
            );
            
            $servicios = $stmt->fetchAll();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $servicios,
                'categoria_id' => $categoriaId,
                'total' => count($servicios)
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
