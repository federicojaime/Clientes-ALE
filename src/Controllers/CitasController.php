<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class CitasController
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
                        u1.telefono as cliente_telefono, u1.whatsapp as cliente_whatsapp,
                        u2.nombre as contratista_nombre, u2.apellido as contratista_apellido,
                        u2.telefono as contratista_telefono, u2.whatsapp as contratista_whatsapp
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
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $citas,
                'total' => count($citas)
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
                "SELECT c.*, 
                        s.titulo as solicitud_titulo, s.descripcion as solicitud_descripcion,
                        s.direccion_servicio,
                        u1.nombre as cliente_nombre, u1.apellido as cliente_apellido,
                        u1.telefono as cliente_telefono, u1.whatsapp as cliente_whatsapp,
                        u1.email as cliente_email, u1.direccion as cliente_direccion,
                        u2.nombre as contratista_nombre, u2.apellido as contratista_apellido,
                        u2.telefono as contratista_telefono, u2.whatsapp as contratista_whatsapp,
                        u2.email as contratista_email
                 FROM citas c
                 JOIN solicitudes s ON c.solicitud_id = s.id
                 JOIN usuarios u1 ON c.cliente_id = u1.id
                 JOIN usuarios u2 ON c.contratista_id = u2.id
                 WHERE c.id = ?",
                [$id]
            );
            
            $cita = $stmt->fetch();
            
            if (!$cita) {
                return $this->jsonResponse($response, ['error' => 'Cita no encontrada'], 404);
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $cita
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $required = ['solicitud_id', 'contratista_id', 'cliente_id', 'fecha_servicio', 'hora_inicio', 'precio_acordado'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, ['error' => "Campo {$field} requerido"], 400);
                }
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
                return $this->jsonResponse($response, [
                    'error' => 'El contratista ya tiene una cita en ese horario'
                ], 409);
            }
            
            $citaData = [
                'solicitud_id' => $data['solicitud_id'],
                'contratista_id' => $data['contratista_id'],
                'cliente_id' => $data['cliente_id'],
                'fecha_servicio' => $data['fecha_servicio'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'] ?? null,
                'precio_acordado' => $data['precio_acordado'],
                'estado' => 'programada',
                'notas_cliente' => $data['notas_cliente'] ?? null,
                'notas_contratista' => $data['notas_contratista'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $citaId = Database::insert('citas', $citaData);
            
            // Actualizar estado de solicitud
            Database::execute(
                "UPDATE solicitudes SET estado = 'confirmada', confirmada_at = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), $data['solicitud_id']]
            );
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Cita creada exitosamente',
                'data' => ['cita_id' => $citaId]
            ], 201);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function confirmar(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            
            Database::execute(
                "UPDATE citas SET estado = 'confirmada', confirmada_at = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), $id]
            );
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Cita confirmada'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function iniciar(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            
            Database::execute(
                "UPDATE citas SET estado = 'en_curso', iniciada_at = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), $id]
            );
            
            // Actualizar solicitud
            $cita = Database::findById('citas', $id);
            if ($cita) {
                Database::execute(
                    "UPDATE solicitudes SET estado = 'en_progreso' WHERE id = ?",
                    [$cita['solicitud_id']]
                );
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Servicio iniciado'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function completar(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            Database::execute(
                "UPDATE citas SET 
                 estado = 'completada', 
                 completada_at = ?,
                 notas_contratista = ?
                 WHERE id = ?",
                [
                    date('Y-m-d H:i:s'), 
                    $data['notas_final'] ?? null,
                    $id
                ]
            );
            
            // Actualizar solicitud
            $cita = Database::findById('citas', $id);
            if ($cita) {
                Database::execute(
                    "UPDATE solicitudes SET estado = 'completada', completada_at = ? WHERE id = ?",
                    [date('Y-m-d H:i:s'), $cita['solicitud_id']]
                );
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Servicio completado exitosamente'
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
