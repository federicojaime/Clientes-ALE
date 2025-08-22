<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class EvaluacionesController extends BaseController
{
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, [
                'cita_id', 'evaluado_id', 'tipo_evaluador', 'calificacion'
            ]);
            
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            // Validar calificación
            if ($data['calificacion'] < 1 || $data['calificacion'] > 5) {
                return $this->errorResponse($response, 'La calificación debe estar entre 1 y 5');
            }
            
            // Verificar que la cita existe y está completada
            $cita = Database::findById('citas', $data['cita_id']);
            if (!$cita) {
                return $this->errorResponse($response, 'Cita no encontrada', 404);
            }
            
            if ($cita['estado'] !== 'completada') {
                return $this->errorResponse($response, 'Solo se puede evaluar citas completadas');
            }
            
            // Verificar que no existe evaluación previa
            $evaluacionExistente = Database::execute(
                "SELECT id FROM evaluaciones WHERE cita_id = ? AND evaluador_id = ?",
                [$data['cita_id'], $this->getUserId($request)]
            )->fetch();
            
            if ($evaluacionExistente) {
                return $this->errorResponse($response, 'Ya evaluaste esta cita', 409);
            }
            
            // Crear evaluación
            $evaluacionData = [
                'cita_id' => (int) $data['cita_id'],
                'evaluador_id' => $this->getUserId($request),
                'evaluado_id' => (int) $data['evaluado_id'],
                'tipo_evaluador' => $data['tipo_evaluador'],
                'calificacion' => (int) $data['calificacion'],
                'comentario' => $data['comentario'] ?? null,
                'puntualidad' => $data['puntualidad'] ?? null,
                'calidad_trabajo' => $data['calidad_trabajo'] ?? null,
                'comunicacion' => $data['comunicacion'] ?? null,
                'limpieza' => $data['limpieza'] ?? null,
                'visible' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $evaluacionId = Database::insert('evaluaciones', $evaluacionData);
            
            // Notificar al evaluado
            $evaluado = Database::findById('usuarios', $data['evaluado_id']);
            if ($evaluado) {
                $calificacionTexto = $this->getCalificacionTexto($data['calificacion']);
                \App\Services\WhatsAppService::sendNotificationToUser(
                    $data['evaluado_id'],
                    'evaluacion',
                    'Nueva Evaluación Recibida',
                    "Has recibido una evaluación: {$calificacionTexto} ({$data['calificacion']}/5 estrellas)"
                );
            }
            
            return $this->successResponse($response, [
                'evaluacion_id' => $evaluacionId
            ], 'Evaluación creada exitosamente');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error creando evaluación: ' . $e->getMessage(), 500);
        }
    }
    
    public function getByCita(Request $request, Response $response, array $args): Response
    {
        try {
            $citaId = (int) $args['citaId'];
            
            $stmt = Database::execute(
                "SELECT e.*, 
                        u_evaluador.nombre as evaluador_nombre,
                        u_evaluado.nombre as evaluado_nombre
                 FROM evaluaciones e
                 JOIN usuarios u_evaluador ON e.evaluador_id = u_evaluador.id
                 JOIN usuarios u_evaluado ON e.evaluado_id = u_evaluado.id
                 WHERE e.cita_id = ? AND e.visible = 1
                 ORDER BY e.created_at DESC",
                [$citaId]
            );
            
            $evaluaciones = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'evaluaciones' => $evaluaciones,
                'total' => count($evaluaciones)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo evaluaciones: ' . $e->getMessage(), 500);
        }
    }
    
    public function getByContratista(Request $request, Response $response, array $args): Response
    {
        try {
            $contratistaId = (int) $args['contratistaId'];
            $params = $request->getQueryParams();
            $limit = min((int) ($params['limit'] ?? 10), 50);
            
            $stmt = Database::execute(
                "SELECT e.*, u.nombre as cliente_nombre, c.fecha_servicio
                 FROM evaluaciones e
                 JOIN citas ci ON e.cita_id = ci.id
                 JOIN usuarios u ON e.evaluador_id = u.id
                 JOIN citas c ON e.cita_id = c.id
                 WHERE e.evaluado_id = ? 
                 AND e.tipo_evaluador = 'cliente' 
                 AND e.visible = 1
                 ORDER BY e.created_at DESC
                 LIMIT {$limit}",
                [$contratistaId]
            );
            
            $evaluaciones = $stmt->fetchAll();
            
            // Calcular estadísticas
            $statsStmt = Database::execute(
                "SELECT 
                    AVG(calificacion) as promedio_general,
                    AVG(puntualidad) as promedio_puntualidad,
                    AVG(calidad_trabajo) as promedio_calidad,
                    AVG(comunicacion) as promedio_comunicacion,
                    AVG(limpieza) as promedio_limpieza,
                    COUNT(*) as total_evaluaciones
                 FROM evaluaciones 
                 WHERE evaluado_id = ? AND tipo_evaluador = 'cliente' AND visible = 1",
                [$contratistaId]
            );
            
            $stats = $statsStmt->fetch();
            
            return $this->successResponse($response, [
                'evaluaciones' => $evaluaciones,
                'estadisticas' => [
                    'promedio_general' => round((float)$stats['promedio_general'], 1),
                    'promedio_puntualidad' => round((float)$stats['promedio_puntualidad'], 1),
                    'promedio_calidad' => round((float)$stats['promedio_calidad'], 1),
                    'promedio_comunicacion' => round((float)$stats['promedio_comunicacion'], 1),
                    'promedio_limpieza' => round((float)$stats['promedio_limpieza'], 1),
                    'total_evaluaciones' => (int)$stats['total_evaluaciones']
                ],
                'total' => count($evaluaciones)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo evaluaciones: ' . $e->getMessage(), 500);
        }
    }
    
    private function getCalificacionTexto(int $calificacion): string
    {
        $textos = [
            1 => 'Muy malo',
            2 => 'Malo', 
            3 => 'Regular',
            4 => 'Bueno',
            5 => 'Excelente'
        ];
        
        return $textos[$calificacion] ?? 'Sin calificar';
    }
}