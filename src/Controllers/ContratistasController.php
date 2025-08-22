<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class ContratistasController extends BaseController
{
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $categoriaId = $params['categoria_id'] ?? null;
            $limit = min((int) ($params['limit'] ?? 20), 100);
            
            $whereClause = "WHERE u.tipo_usuario_id = 2 AND u.activo = 1";
            $queryParams = [];
            
            if ($categoriaId) {
                $whereClause .= " AND cs.categoria_id = ?";
                $queryParams[] = $categoriaId;
            }
            
            $stmt = Database::execute(
                "SELECT DISTINCT u.id, u.nombre, u.apellido, u.email, u.telefono, 
                        u.whatsapp, u.ciudad, u.provincia, u.verificado
                 FROM usuarios u
                 LEFT JOIN contratistas_servicios cs ON u.id = cs.contratista_id
                 {$whereClause}
                 ORDER BY u.created_at DESC
                 LIMIT {$limit}",
                $queryParams
            );
            
            $contratistas = $stmt->fetchAll();
            
            foreach ($contratistas as &$contratista) {
                // Servicios del contratista
                $serviciosStmt = Database::execute(
                    "SELECT cs.*, cat.nombre as categoria_nombre, cat.icono
                     FROM contratistas_servicios cs
                     JOIN categorias_servicios cat ON cs.categoria_id = cat.id
                     WHERE cs.contratista_id = ? AND cs.activo = 1",
                    [$contratista['id']]
                );
                $contratista['servicios'] = $serviciosStmt->fetchAll();
                
                // Rating promedio
                $ratingStmt = Database::execute(
                    "SELECT AVG(calificacion) as promedio, COUNT(*) as total
                     FROM evaluaciones 
                     WHERE evaluado_id = ? AND tipo_evaluador = 'cliente'",
                    [$contratista['id']]
                );
                $rating = $ratingStmt->fetch();
                $contratista['rating'] = [
                    'promedio' => $rating['promedio'] ? round((float)$rating['promedio'], 1) : 0,
                    'total_evaluaciones' => (int)$rating['total']
                ];
            }
            
            return $this->successResponse($response, [
                'contratistas' => $contratistas,
                'total' => count($contratistas)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo contratistas: ' . $e->getMessage(), 500);
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
                 WHERE u.id = ? AND u.tipo_usuario_id = 2",
                [$id]
            );
            
            $contratista = $stmt->fetch();
            
            if (!$contratista) {
                return $this->errorResponse($response, 'Contratista no encontrado', 404);
            }
            
            // Servicios
            $serviciosStmt = Database::execute(
                "SELECT cs.*, cat.nombre as categoria_nombre, cat.icono
                 FROM contratistas_servicios cs
                 JOIN categorias_servicios cat ON cs.categoria_id = cat.id
                 WHERE cs.contratista_id = ? AND cs.activo = 1",
                [$id]
            );
            $contratista['servicios'] = $serviciosStmt->fetchAll();
            
            // Evaluaciones recientes
            $evaluacionesStmt = Database::execute(
                "SELECT e.*, u.nombre as cliente_nombre, DATE(e.created_at) as fecha
                 FROM evaluaciones e
                 JOIN citas c ON e.cita_id = c.id
                 JOIN usuarios u ON c.cliente_id = u.id
                 WHERE e.evaluado_id = ? AND e.tipo_evaluador = 'cliente' AND e.visible = 1
                 ORDER BY e.created_at DESC
                 LIMIT 10",
                [$id]
            );
            $contratista['evaluaciones'] = $evaluacionesStmt->fetchAll();
            
            // Estadísticas
            $statsStmt = Database::execute(
                "SELECT 
                    COUNT(c.id) as total_trabajos,
                    AVG(e.calificacion) as rating_promedio,
                    SUM(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) as trabajos_completados
                 FROM citas c
                 LEFT JOIN evaluaciones e ON c.id = e.cita_id AND e.tipo_evaluador = 'cliente'
                 WHERE c.contratista_id = ?",
                [$id]
            );
            $stats = $statsStmt->fetch();
            $contratista['estadisticas'] = [
                'total_trabajos' => (int)$stats['total_trabajos'],
                'trabajos_completados' => (int)$stats['trabajos_completados'],
                'rating_promedio' => $stats['rating_promedio'] ? round((float)$stats['rating_promedio'], 1) : 0
            ];
            
            return $this->successResponse($response, $contratista);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo contratista: ' . $e->getMessage(), 500);
        }
    }
    
    public function buscarDisponibles(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, ['categoria_id']);
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            $categoriaId = (int) $data['categoria_id'];
            $latitud = $data['latitud'] ?? null;
            $longitud = $data['longitud'] ?? null;
            $fechaServicio = $data['fecha_servicio'] ?? date('Y-m-d');
            $radioKm = (int) ($data['radio_km'] ?? 15);
            
            $distanciaClause = '';
            $queryParams = [$categoriaId];
            
            if ($latitud && $longitud) {
                $distanciaClause = "AND (6371 * acos(cos(radians(?)) * cos(radians(u.latitud)) * 
                                     cos(radians(u.longitud) - radians(?)) + 
                                     sin(radians(?)) * sin(radians(u.latitud)))) <= ?";
                $queryParams = array_merge($queryParams, [$latitud, $longitud, $latitud, $radioKm]);
            }
            
            $stmt = Database::execute(
                "SELECT DISTINCT u.id, u.nombre, u.apellido, u.whatsapp, u.telefono,
                        cs.tarifa_base, cs.experiencia_anos, cs.certificado,
                        AVG(e.calificacion) as rating_promedio,
                        COUNT(e.id) as total_evaluaciones
                 FROM usuarios u
                 JOIN contratistas_servicios cs ON u.id = cs.contratista_id
                 LEFT JOIN evaluaciones e ON u.id = e.evaluado_id AND e.tipo_evaluador = 'cliente'
                 WHERE u.tipo_usuario_id = 2 
                 AND u.activo = 1
                 AND cs.categoria_id = ?
                 AND cs.activo = 1
                 {$distanciaClause}
                 AND u.id NOT IN (
                     SELECT c.contratista_id 
                     FROM citas c 
                     WHERE c.fecha_servicio = ? 
                     AND c.estado IN ('programada', 'confirmada', 'en_curso')
                 )
                 GROUP BY u.id
                 ORDER BY rating_promedio DESC, cs.experiencia_anos DESC
                 LIMIT 10",
                array_merge($queryParams, [$fechaServicio])
            );
            
            $contratistas = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'contratistas' => $contratistas,
                'total' => count($contratistas),
                'criterios' => [
                    'categoria_id' => $categoriaId,
                    'fecha_servicio' => $fechaServicio,
                    'radio_km' => $radioKm
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error buscando contratistas: ' . $e->getMessage(), 500);
        }
    }
}
