<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;
use App\Services\WhatsAppService;

class NotificacionesController extends BaseController
{
    public function getByUser(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int) $args['userId'];
            $params = $request->getQueryParams();
            $limit = min((int) ($params['limit'] ?? 20), 100);
            $leida = $params['leida'] ?? null;
            
            $whereClause = 'WHERE usuario_id = ?';
            $queryParams = [$userId];
            
            if ($leida !== null) {
                $whereClause .= ' AND leida = ?';
                $queryParams[] = (int) $leida;
            }
            
            $stmt = Database::execute(
                "SELECT * FROM notificaciones 
                 {$whereClause}
                 ORDER BY created_at DESC 
                 LIMIT {$limit}",
                $queryParams
            );
            
            $notificaciones = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'notificaciones' => $notificaciones,
                'total' => count($notificaciones)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo notificaciones: ' . $e->getMessage(), 500);
        }
    }
    
    public function marcarLeida(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            
            $updated = Database::update('notificaciones', $id, [
                'leida' => 1,
                'leida_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$updated) {
                return $this->errorResponse($response, 'Notificación no encontrada', 404);
            }
            
            return $this->successResponse($response, null, 'Notificación marcada como leída');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error marcando notificación: ' . $e->getMessage(), 500);
        }
    }
    
    public function enviarManual(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, ['usuario_id', 'titulo', 'mensaje']);
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            $enviado = WhatsAppService::sendNotificationToUser(
                $data['usuario_id'],
                $data['tipo'] ?? 'manual',
                $data['titulo'],
                $data['mensaje']
            );
            
            if ($enviado) {
                return $this->successResponse($response, null, 'Notificación enviada exitosamente');
            } else {
                return $this->errorResponse($response, 'Error enviando notificación', 500);
            }
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error enviando notificación: ' . $e->getMessage(), 500);
        }
    }
}