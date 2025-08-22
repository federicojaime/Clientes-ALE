<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class CitasController extends BaseController
{
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $estado = $params['estado'] ?? null;
            $fecha = $params['fecha'] ?? null;
            $limit = min((int) ($params['limit'] ?? 20), 100);
            
            $whereClause = 'WHERE 1=1';
            $queryParams = [];
            
            if ($estado) {
                $whereClause .= ' AND c.estado = ?';
                $queryParams[] = $estado;
            }
            
            if ($fecha) {
                $whereClause .= ' AND c.fecha_servicio = ?';
                $queryParams[] = $fecha;
            }
            
            $stmt = Database::execute(
                "SELECT c.*, 
                        s.titulo as solicitud_titulo,
                        u1.nombre as cliente_nombre, u1.apellido as cliente_apellido,
                        u2.nombre as contratista_nombre, u2.apellido as contratista_apellido
                 FROM citas c
                 JOIN solicitudes s ON c.solicitud_id = s.id
                 JOIN usuarios u1 ON c.cliente_id = u1.id
                 JOIN usuarios u2 ON c.contratista_id = u2.id
                 {$whereClause}
                 ORDER BY c.fecha_servicio DESC, c.hora_inicio DESC
                 LIMIT {$limit}",
                $queryParams
            );
            
            $citas = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'citas' => $citas,
                'total' => count($citas)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo citas: ' . $e->getMessage(), 500);
        }
    }
    
    public function getById(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            
            $stmt = Database::execute(
                "SELECT c.*, 
                        s.titulo as solicitud_titulo, s.descripcion as solicitud_descripcion,
                        s.direccion_servicio,
                        u1.nombre as cliente_nombre, u1.apellido as cliente_apellido,
                        u1.telefono as cliente_telefono, u1.whatsapp as cliente_whatsapp,
                        u2.nombre as contratista_nombre, u2.apellido as contratista_apellido,
                        u2.telefono as contratista_telefono, u2.whatsapp as contratista_whatsapp
                 FROM citas c
                 JOIN solicitudes s ON c.solicitud_id = s.id
                 JOIN usuarios u1 ON c.cliente_id = u1.id
                 JOIN usuarios u2 ON c.contratista_id = u2.id
                 WHERE c.id = ?",
                [$id]
            );
            
            $cita = $stmt->fetch();
            
            if (!$cita) {
                return $this->errorResponse($response, 'Cita no encontrada', 404);
            }
            
            return $this->successResponse($response, $cita);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo cita: ' . $e->getMessage(), 500);
        }
    }
    
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, [
                'solicitud_id', 'contratista_id', 'cliente_id', 
                'fecha_servicio', 'hora_inicio', 'precio_acordado'
            ]);
            
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            // Verificar que no haya conflictos de horario
            $conflictoStmt = Database::execute(
                "SELECT id FROM citas 
                 WHERE contratista_id = ? 
                 AND fecha_servicio = ? 
                 AND hora_inicio = ?
                 AND estado IN ('programada', 'confirmada', 'en_curso')",
                [$data['contratista_id'], $data['fecha_servicio'], $data['hora_inicio']]
            );
            
            if ($conflictoStmt->fetch()) {
                return $this->errorResponse($response, 
                    'El contratista ya tiene una cita en ese horario', 409);
            }
            
            try {
                $citaData = [
                    'solicitud_id' => (int) $data['solicitud_id'],
                    'contratista_id' => (int) $data['contratista_id'],
                    'cliente_id' => (int) $data['cliente_id'],
                    'fecha_servicio' => $data['fecha_servicio'],
                    'hora_inicio' => $data['hora_inicio'],
                    'hora_fin' => $data['hora_fin'] ?? null,
                    'precio_acordado' => (float) $data['precio_acordado'],
                    'estado' => 'programada',
                    'notas_cliente' => $data['notas_cliente'] ?? null,
                    'notas_contratista' => $data['notas_contratista'] ?? null,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $citaId = Database::insert('citas', $citaData);
                
                // Actualizar estado de solicitud
                Database::update('solicitudes', $data['solicitud_id'], [
                    'estado' => 'confirmada',
                    'confirmada_at' => date('Y-m-d H:i:s')
                ]);
                
                return $this->successResponse($response, [
                    'cita_id' => $citaId
                ], 'Cita creada exitosamente');
                
            } catch (\Exception $e) {
                throw $e;
            }
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error creando cita: ' . $e->getMessage(), 500);
        }
    }
    
    public function confirmar(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            
            Database::update('citas', $id, [
                'estado' => 'confirmada',
                'confirmada_at' => date('Y-m-d H:i:s')
            ]);
            
            return $this->successResponse($response, null, 'Cita confirmada');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error confirmando cita: ' . $e->getMessage(), 500);
        }
    }
    
    public function iniciar(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            
            Database::update('citas', $id, [
                'estado' => 'en_curso',
                'iniciada_at' => date('Y-m-d H:i:s')
            ]);
            
            // Actualizar solicitud
            $cita = Database::findById('citas', $id);
            if ($cita) {
                Database::update('solicitudes', $cita['solicitud_id'], [
                    'estado' => 'en_progreso'
                ]);
            }
            
            return $this->successResponse($response, null, 'Servicio iniciado');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error iniciando servicio: ' . $e->getMessage(), 500);
        }
    }
    
    public function completar(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            Database::update('citas', $id, [
                'estado' => 'completada',
                'completada_at' => date('Y-m-d H:i:s'),
                'notas_contratista' => $data['notas_final'] ?? null
            ]);
            
            // Actualizar solicitud
            $cita = Database::findById('citas', $id);
            if ($cita) {
                Database::update('solicitudes', $cita['solicitud_id'], [
                    'estado' => 'completada',
                    'completada_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            return $this->successResponse($response, null, 'Servicio completado exitosamente');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error completando servicio: ' . $e->getMessage(), 500);
        }
    }
}