<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class SolicitudController
{
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = min((int) ($params['limit'] ?? 20), 100);
            $estado = $params['estado'] ?? null;
            
            $whereClause = '';
            $queryParams = [];
            
            if ($estado) {
                $whereClause = ' WHERE s.estado = ?';
                $queryParams[] = $estado;
            }
            
            $stmt = Database::execute(
                "SELECT s.*, u.nombre as cliente_nombre, u.apellido as cliente_apellido,
                        u.whatsapp as cliente_whatsapp, cs.nombre as categoria_nombre
                 FROM solicitudes s
                 JOIN usuarios u ON s.cliente_id = u.id
                 JOIN categorias_servicios cs ON s.categoria_id = cs.id
                 {$whereClause}
                 ORDER BY s.created_at DESC LIMIT {$limit}",
                $queryParams
            );
            
            $solicitudes = $stmt->fetchAll();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $solicitudes,
                'total' => count($solicitudes)
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
                "SELECT s.*, u.nombre as cliente_nombre, u.apellido as cliente_apellido,
                        u.telefono as cliente_telefono, u.whatsapp as cliente_whatsapp,
                        cs.nombre as categoria_nombre
                 FROM solicitudes s
                 JOIN usuarios u ON s.cliente_id = u.id
                 JOIN categorias_servicios cs ON s.categoria_id = cs.id
                 WHERE s.id = ?",
                [$id]
            );
            
            $solicitud = $stmt->fetch();
            
            if (!$solicitud) {
                return $this->jsonResponse($response, ['error' => 'Solicitud no encontrada'], 404);
            }
            
            // Obtener asignaciones
            $asignacionesStmt = Database::execute(
                "SELECT a.*, u.nombre as contratista_nombre, u.apellido as contratista_apellido
                 FROM asignaciones a
                 JOIN usuarios u ON a.contratista_id = u.id
                 WHERE a.solicitud_id = ?
                 ORDER BY a.enviada_at DESC",
                [$id]
            );
            
            $solicitud['asignaciones'] = $asignacionesStmt->fetchAll();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $solicitud
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $required = ['cliente_id', 'categoria_id', 'titulo', 'descripcion', 'direccion_servicio'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, ['error' => "Campo {$field} requerido"], 400);
                }
            }
            
            // Verificar que el cliente existe
            $cliente = Database::findById('usuarios', $data['cliente_id']);
            if (!$cliente || $cliente['tipo_usuario_id'] != 1) {
                return $this->jsonResponse($response, ['error' => 'Cliente no valido'], 400);
            }
            
            $solicitudData = [
                'cliente_id' => $data['cliente_id'],
                'categoria_id' => $data['categoria_id'],
                'servicio_id' => $data['servicio_id'] ?? null,
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'],
                'descripcion_personalizada' => $data['descripcion_personalizada'] ?? null,
                'urgencia' => $data['urgencia'] ?? 'media',
                'direccion_servicio' => $data['direccion_servicio'],
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
            
            // Buscar contratistas disponibles y crear asignaciones
            $this->asignarContratistas($solicitudId, $data['categoria_id'], $data['latitud'] ?? null, $data['longitud'] ?? null);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Solicitud creada exitosamente',
                'data' => ['solicitud_id' => $solicitudId]
            ], 201);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function updateEstado(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (empty($data['estado'])) {
                return $this->jsonResponse($response, ['error' => 'Estado requerido'], 400);
            }
            
            $estadosValidos = ['pendiente', 'asignada', 'confirmada', 'en_progreso', 'completada', 'cancelada'];
            if (!in_array($data['estado'], $estadosValidos)) {
                return $this->jsonResponse($response, ['error' => 'Estado no valido'], 400);
            }
            
            Database::execute(
                "UPDATE solicitudes SET estado = ?, updated_at = ? WHERE id = ?",
                [$data['estado'], date('Y-m-d H:i:s'), $id]
            );
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Estado actualizado'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    private function asignarContratistas(int $solicitudId, int $categoriaId, ?float $latitud, ?float $longitud): void
    {
        try {
            // Buscar contratistas de la categoria
            $stmt = Database::execute(
                "SELECT DISTINCT u.id
                 FROM usuarios u
                 JOIN contratistas_servicios cs ON u.id = cs.contratista_id
                 WHERE u.tipo_usuario_id = 2 
                 AND u.activo = 1 
                 AND cs.categoria_id = ? 
                 AND cs.activo = 1
                 LIMIT 5",
                [$categoriaId]
            );
            
            $contratistas = $stmt->fetchAll();
            
            // Crear asignaciones
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
    
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
