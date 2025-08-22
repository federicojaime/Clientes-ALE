<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class ConfiguracionController extends BaseController
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
            
            return $this->successResponse($response, [
                'categorias' => $categorias,
                'total' => count($categorias)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo categorías: ' . $e->getMessage(), 500);
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
            
            return $this->successResponse($response, [
                'servicios' => $servicios,
                'total' => count($servicios)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo servicios: ' . $e->getMessage(), 500);
        }
    }
    
    public function getServiciosPorCategoria(Request $request, Response $response, array $args): Response
    {
        try {
            $categoriaId = (int) $args['categoriaId'];
            
            // Verificar que la categoría existe
            $categoria = Database::findById('categorias_servicios', $categoriaId);
            if (!$categoria || !$categoria['activo']) {
                return $this->errorResponse($response, 'Categoría no encontrada', 404);
            }
            
            $stmt = Database::execute(
                "SELECT s.*
                 FROM servicios s
                 WHERE s.categoria_id = ? AND s.activo = 1
                 ORDER BY s.nombre",
                [$categoriaId]
            );
            
            $servicios = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'categoria' => $categoria,
                'servicios' => $servicios,
                'total' => count($servicios)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo servicios: ' . $e->getMessage(), 500);
        }
    }
}
