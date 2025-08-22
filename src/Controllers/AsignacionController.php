<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class AsignacionController extends BaseController
{
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $estado = $params['estado'] ?? null;
            $limit = min((int) ($params['limit'] ?? 20), 100);
            
            $whereClause = 'WHERE 1=1';
            $queryParams = [];
            
            if ($estado) {
                $whereClause .= ' AND a.estado = ?';
                $queryParams[] = $estado;
            }
            
            $stmt = Database::execute(
                "SELECT a.*, s.titulo as solicitud_titulo, s.urgencia,
                        u1.nombre as cliente_nombre, u1.apellido as cliente_apellido,
                        u2.nombre as contratista_nombre, u2.apellido as contratista_apellido,
                        cs.nombre as categoria_nombre
                 FROM asignaciones a
                 JOIN solicitudes s ON a.solicitud_id = s.id
                 JOIN usuarios u1 ON s.cliente_id = u1.id
                 JOIN usuarios u2 ON a.contratista_id = u2.id
                 JOIN categorias_servicios cs ON s.categoria_id = cs.id
                 {$whereClause}
                 ORDER BY a.enviada_at DESC
                 LIMIT {$limit}",
                $queryParams
            );
            
            $asignaciones = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'asignaciones' => $asignaciones,
                'total' => count($asignaciones)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo asignaciones: ' . $e->getMessage(), 500);
        }
    }
    
    public function getByContratista(Request $request, Response $response, array $args): Response
    {
        try {
            $contratistaId = (int) $args['contratistaId'];
            $params = $request->getQueryParams();
            $estado = $params['estado'] ?? null;
            
            $whereClause = 'WHERE a.contratista_id = ?';
            $queryParams = [$contratistaId];
            
            if ($estado) {
                $whereClause .= ' AND a.estado = ?';
                $queryParams[] = $estado;
            }
            
            $stmt = Database::execute(
                "SELECT a.*, s.titulo, s.descripcion, s.urgencia, s.direccion_servicio,
                        s.fecha_preferida, s.hora_preferida, s.presupuesto_maximo,
                        u.nombre as cliente_nombre, u.apellido as cliente_apellido,
                        u.telefono as cliente_telefono, u.whatsapp as cliente_whatsapp,
                        cs.nombre as categoria_nombre
                 FROM asignaciones a
                 JOIN solicitudes s ON a.solicitud_id = s.id
                 JOIN usuarios u ON s.cliente_id = u.id
                 JOIN categorias_servicios cs ON s.categoria_id = cs.id
                 {$whereClause}
                 ORDER BY a.enviada_at DESC",
                $queryParams
            );
            
            $asignaciones = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'asignaciones' => $asignaciones,
                'total' => count($asignaciones)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo asignaciones: ' . $e->getMessage(), 500);
        }
    }
    
    public function aceptar(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            $asignacion = Database::findById('asignaciones', $id);
            if (!$asignacion) {
                return $this->errorResponse($response, 'Asignación no encontrada', 404);
            }
            
            if ($asignacion['estado'] !== 'enviada') {
                return $this->errorResponse($response, 'Asignación ya procesada');
            }
            
            Database::update('asignaciones', $id, [
                'estado' => 'rechazada',
                'comentarios' => $data['motivo'] ?? 'Sin motivo especificado',
                'respondida_at' => date('Y-m-d H:i:s')
            ]);
            
            // Reasignar a otro contratista disponible
            $this->reasignarSolicitud($asignacion['solicitud_id']);
            
            return $this->successResponse($response, null, 'Asignación rechazada');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error rechazando asignación: ' . $e->getMessage(), 500);
        }
    }
    
    private function reasignarSolicitud(int $solicitudId): void
    {
        try {
            $solicitud = Database::findById('solicitudes', $solicitudId);
            if (!$solicitud) return;
            
            // Obtener contratistas ya contactados
            $contactadosStmt = Database::execute(
                "SELECT contratista_id FROM asignaciones WHERE solicitud_id = ?",
                [$solicitudId]
            );
            $contactados = array_column($contactadosStmt->fetchAll(), 'contratista_id');
            
            if (empty($contactados)) {
                $whereContactados = '';
                $queryParams = [$solicitud['categoria_id']];
            } else {
                $placeholders = str_repeat('?,', count($contactados) - 1) . '?';
                $whereContactados = "AND u.id NOT IN ({$placeholders})";
                $queryParams = array_merge([$solicitud['categoria_id']], $contactados);
            }
            
            $stmt = Database::execute(
                "SELECT u.id
                 FROM usuarios u
                 JOIN contratistas_servicios cs ON u.id = cs.contratista_id
                 WHERE u.tipo_usuario_id = 2 
                 AND u.activo = 1 
                 AND cs.categoria_id = ? 
                 AND cs.activo = 1
                 {$whereContactados}
                 LIMIT 1",
                $queryParams
            );
            
            $siguienteContratista = $stmt->fetch();
            
            if ($siguienteContratista) {
                Database::insert('asignaciones', [
                    'solicitud_id' => $solicitudId,
                    'contratista_id' => $siguienteContratista['id'],
                    'estado' => 'enviada',
                    'enviada_at' => date('Y-m-d H:i:s'),
                    'expira_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
                ]);
            }
            
        } catch (\Exception $e) {
            error_log("Error reasignando solicitud: " . $e->getMessage());
        }
    }
}
