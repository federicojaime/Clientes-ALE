<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class SolicitudController extends BaseController
{
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = min((int) ($params['limit'] ?? 20), 100);
            $estado = $params['estado'] ?? null;
            $categoria_id = $params['categoria_id'] ?? null;
            $urgencia = $params['urgencia'] ?? null;
            
            $whereClause = 'WHERE 1=1';
            $queryParams = [];
            
            if ($estado) {
                $whereClause .= ' AND s.estado = ?';
                $queryParams[] = $estado;
            }
            
            if ($categoria_id) {
                $whereClause .= ' AND s.categoria_id = ?';
                $queryParams[] = $categoria_id;
            }
            
            if ($urgencia) {
                $whereClause .= ' AND s.urgencia = ?';
                $queryParams[] = $urgencia;
            }
            
            $stmt = Database::execute(
                "SELECT s.*, u.nombre as cliente_nombre, u.apellido as cliente_apellido,
                        u.whatsapp as cliente_whatsapp, cs.nombre as categoria_nombre,
                        srv.nombre as servicio_nombre
                 FROM solicitudes s
                 JOIN usuarios u ON s.cliente_id = u.id
                 JOIN categorias_servicios cs ON s.categoria_id = cs.id
                 LEFT JOIN servicios srv ON s.servicio_id = srv.id
                 {$whereClause}
                 ORDER BY s.created_at DESC 
                 LIMIT {$limit}",
                $queryParams
            );
            
            $solicitudes = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'solicitudes' => $solicitudes,
                'total' => count($solicitudes),
                'filtros' => compact('estado', 'categoria_id', 'urgencia')
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo solicitudes: ' . $e->getMessage(), 500);
        }
    }
    
    public function getById(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            
            $stmt = Database::execute(
                "SELECT s.*, u.nombre as cliente_nombre, u.apellido as cliente_apellido,
                        u.telefono as cliente_telefono, u.whatsapp as cliente_whatsapp,
                        cs.nombre as categoria_nombre, srv.nombre as servicio_nombre
                 FROM solicitudes s
                 JOIN usuarios u ON s.cliente_id = u.id
                 JOIN categorias_servicios cs ON s.categoria_id = cs.id
                 LEFT JOIN servicios srv ON s.servicio_id = srv.id
                 WHERE s.id = ?",
                [$id]
            );
            
            $solicitud = $stmt->fetch();
            
            if (!$solicitud) {
                return $this->errorResponse($response, 'Solicitud no encontrada', 404);
            }
            
            // Obtener asignaciones
            $asignacionesStmt = Database::execute(
                "SELECT a.*, u.nombre as contratista_nombre, u.apellido as contratista_apellido,
                        u.whatsapp as contratista_whatsapp
                 FROM asignaciones a
                 JOIN usuarios u ON a.contratista_id = u.id
                 WHERE a.solicitud_id = ?
                 ORDER BY a.enviada_at DESC",
                [$id]
            );
            
            $solicitud['asignaciones'] = $asignacionesStmt->fetchAll();
            
            return $this->successResponse($response, $solicitud);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo solicitud: ' . $e->getMessage(), 500);
        }
    }
    
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, [
                'cliente_id', 'categoria_id', 'titulo', 'descripcion', 'direccion_servicio'
            ]);
            
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            // Verificar que el cliente existe
            $cliente = Database::findById('usuarios', $data['cliente_id']);
            if (!$cliente || $cliente['tipo_usuario_id'] != 1) {
                return $this->errorResponse($response, 'Cliente no válido');
            }
            
            // Verificar categoría
            $categoria = Database::findById('categorias_servicios', $data['categoria_id']);
            if (!$categoria || !$categoria['activo']) {
                return $this->errorResponse($response, 'Categoría no válida');
            }
            
            try {
                $solicitudData = [
                    'cliente_id' => (int) $data['cliente_id'],
                    'categoria_id' => (int) $data['categoria_id'],
                    'servicio_id' => isset($data['servicio_id']) ? (int) $data['servicio_id'] : null,
                    'titulo' => trim($data['titulo']),
                    'descripcion' => trim($data['descripcion']),
                    'descripcion_personalizada' => $data['descripcion_personalizada'] ?? null,
                    'urgencia' => $data['urgencia'] ?? 'media',
                    'direccion_servicio' => trim($data['direccion_servicio']),
                    'latitud' => $data['latitud'] ?? null,
                    'longitud' => $data['longitud'] ?? null,
                    'fecha_preferida' => $data['fecha_preferida'] ?? null,
                    'hora_preferida' => $data['hora_preferida'] ?? null,
                    'flexible_horario' => $data['flexible_horario'] ?? 1,
                    'presupuesto_maximo' => $data['presupuesto_maximo'] ?? null,
                    'estado' => 'pendiente',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $solicitudId = Database::insert('solicitudes', $solicitudData);
                
                // Asignar contratistas disponibles
                $this->asignarContratistas($solicitudId, $data['categoria_id']);
                
                return $this->successResponse($response, [
                    'solicitud_id' => $solicitudId
                ], 'Solicitud creada exitosamente');
                
            } catch (\Exception $e) {
                throw $e;
            }
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error creando solicitud: ' . $e->getMessage(), 500);
        }
    }
    
    public function updateEstado(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, ['estado']);
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            $estadosValidos = ['pendiente', 'asignada', 'confirmada', 'en_progreso', 'completada', 'cancelada'];
            if (!in_array($data['estado'], $estadosValidos)) {
                return $this->errorResponse($response, 'Estado no válido');
            }
            
            $updateData = [
                'estado' => $data['estado'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($data['estado'] === 'completada') {
                $updateData['completada_at'] = date('Y-m-d H:i:s');
            }
            
            $updated = Database::update('solicitudes', $id, $updateData);
            
            if (!$updated) {
                return $this->errorResponse($response, 'Solicitud no encontrada', 404);
            }
            
            return $this->successResponse($response, null, 'Estado actualizado correctamente');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error actualizando estado: ' . $e->getMessage(), 500);
        }
    }
    
    private function asignarContratistas(int $solicitudId, int $categoriaId): void
    {
        try {
            $stmt = Database::execute(
                "SELECT DISTINCT u.id
                 FROM usuarios u
                 JOIN contratistas_servicios cs ON u.id = cs.contratista_id
                 WHERE u.tipo_usuario_id = 2 
                 AND u.activo = 1 
                 AND cs.categoria_id = ? 
                 AND cs.activo = 1
                 ORDER BY RAND()
                 LIMIT 5",
                [$categoriaId]
            );
            
            $contratistas = $stmt->fetchAll();
            
            foreach ($contratistas as $contratista) {
                Database::insert('asignaciones', [
                    'solicitud_id' => $solicitudId,
                    'contratista_id' => $contratista['id'],
                    'estado' => 'enviada',
                    'enviada_at' => date('Y-m-d H:i:s'),
                    'expira_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
                ]);
            }
            
        } catch (\Exception $e) {
            error_log("Error asignando contratistas: " . $e->getMessage());
        }
    }
}