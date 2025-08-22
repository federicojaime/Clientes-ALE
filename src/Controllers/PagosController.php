<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;
use App\Services\MercadoPagoService;

class PagosController extends BaseController
{
    public function createConsultaPago(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $error = $this->validateRequired($data, ['cita_id', 'monto_consulta']);
            if ($error) {
                return $this->errorResponse($response, $error);
            }
            
            // Verificar que la cita existe
            $cita = Database::findById('citas', $data['cita_id']);
            if (!$cita) {
                return $this->errorResponse($response, 'Cita no encontrada', 404);
            }
            
            // Verificar que no existe un pago para esta cita
            $pagoExistente = Database::execute(
                "SELECT id FROM pagos WHERE cita_id = ?",
                [$data['cita_id']]
            )->fetch();
            
            if ($pagoExistente) {
                return $this->errorResponse($response, 'Ya existe un pago para esta cita', 409);
            }
            
            // Crear registro de pago
            $externalRef = 'CITA_' . $data['cita_id'] . '_' . time();
            
            $pagoData = [
                'cita_id' => (int) $data['cita_id'],
                'cliente_id' => (int) $cita['cliente_id'],
                'contratista_id' => (int) $cita['contratista_id'],
                'monto_consulta' => (float) $data['monto_consulta'],
                'monto_servicio' => (float) $cita['precio_acordado'],
                'monto_total' => (float) $data['monto_consulta'] + (float) $cita['precio_acordado'],
                'estado_consulta' => 'pendiente',
                'estado_servicio' => 'pendiente',
                'external_reference' => $externalRef,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $pagoId = Database::insert('pagos', $pagoData);
            
            // Crear preferencia en MercadoPago
            $cliente = Database::findById('usuarios', $cita['cliente_id']);
            
            $mpPaymentData = [
                'title' => 'Consulta - Servicio Técnico',
                'description' => 'Pago de consulta inicial para servicio técnico',
                'amount' => $data['monto_consulta'],
                'payer_email' => $cliente['email'],
                'payer_name' => $cliente['nombre'] . ' ' . $cliente['apellido'],
                'external_reference' => $externalRef,
                'notification_url' => $data['notification_url'] ?? $_ENV['APP_URL'] . '/api/v1/pagos/webhook/mercadopago',
                'success_url' => $data['success_url'] ?? $_ENV['APP_URL'] . '/pago-exitoso',
                'failure_url' => $data['failure_url'] ?? $_ENV['APP_URL'] . '/pago-fallido',
                'pending_url' => $data['pending_url'] ?? $_ENV['APP_URL'] . '/pago-pendiente'
            ];
            
            $mpResponse = MercadoPagoService::createPayment($mpPaymentData);
            
            // Actualizar con preference_id
            Database::update('pagos', $pagoId, [
                'mp_preference_id' => $mpResponse['id']
            ]);
            
            return $this->successResponse($response, [
                'pago_id' => $pagoId,
                'preference_id' => $mpResponse['id'],
                'init_point' => $mpResponse['init_point'],
                'sandbox_init_point' => $mpResponse['sandbox_init_point']
            ], 'Pago creado exitosamente');
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error creando pago: ' . $e->getMessage(), 500);
        }
    }
    
    public function webhook(Request $request, Response $response): Response
    {
        try {
            $webhookData = json_decode($request->getBody()->getContents(), true);
            
            $processed = MercadoPagoService::processWebhook($webhookData);
            
            if ($processed) {
                return $this->successResponse($response, null, 'Webhook procesado');
            } else {
                return $this->errorResponse($response, 'Error procesando webhook', 400);
            }
            
        } catch (\Exception $e) {
            error_log("Error webhook: " . $e->getMessage());
            return $this->errorResponse($response, 'Error interno', 500);
        }
    }
    
    public function getPagosByCita(Request $request, Response $response, array $args): Response
    {
        try {
            $citaId = (int) $args['citaId'];
            
            $stmt = Database::execute(
                "SELECT p.*, c.fecha_servicio, c.hora_inicio,
                        u1.nombre as cliente_nombre, u2.nombre as contratista_nombre
                 FROM pagos p
                 JOIN citas c ON p.cita_id = c.id
                 JOIN usuarios u1 ON p.cliente_id = u1.id
                 JOIN usuarios u2 ON p.contratista_id = u2.id
                 WHERE p.cita_id = ?",
                [$citaId]
            );
            
            $pagos = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'pagos' => $pagos,
                'total' => count($pagos)
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo pagos: ' . $e->getMessage(), 500);
        }
    }
}