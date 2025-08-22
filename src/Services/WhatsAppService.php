<?php
namespace App\Services;

use App\Utils\Database;

class WhatsAppService
{
    private static string $apiUrl = 'https://graph.facebook.com/v18.0';
    private static string $accessToken;
    private static string $phoneNumberId;
    
    public static function init()
    {
        self::$accessToken = $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? '';
        self::$phoneNumberId = $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? '';
    }
    
    public static function sendMessage(string $to, string $message): bool
    {
        try {
            self::init();
            
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => self::formatPhoneNumber($to),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];
            
            $response = self::makeRequest('POST', '/' . self::$phoneNumberId . '/messages', $data);
            
            return isset($response['messages']) && count($response['messages']) > 0;
            
        } catch (\Exception $e) {
            error_log("Error enviando WhatsApp: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendTemplate(string $to, string $templateName, array $parameters = []): bool
    {
        try {
            self::init();
            
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => self::formatPhoneNumber($to),
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => 'es'
                    ]
                ]
            ];
            
            if (!empty($parameters)) {
                $data['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => array_map(function($param) {
                            return ['type' => 'text', 'text' => $param];
                        }, $parameters)
                    ]
                ];
            }
            
            $response = self::makeRequest('POST', '/' . self::$phoneNumberId . '/messages', $data);
            
            return isset($response['messages']) && count($response['messages']) > 0;
            
        } catch (\Exception $e) {
            error_log("Error enviando template WhatsApp: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendNotificationToUser(int $userId, string $tipo, string $titulo, string $mensaje): bool
    {
        try {
            // Obtener datos del usuario
            $stmt = Database::execute(
                "SELECT whatsapp, nombre FROM usuarios WHERE id = ? AND activo = 1",
                [$userId]
            );
            
            $usuario = $stmt->fetch();
            if (!$usuario || empty($usuario['whatsapp'])) {
                return false;
            }
            
            // Crear notificación en BD
            Database::insert('notificaciones', [
                'usuario_id' => $userId,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'leida' => 0,
                'enviada_whatsapp' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Enviar por WhatsApp
            $mensajeCompleto = "📲 *{$titulo}*\n\n{$mensaje}\n\n_Servicios Técnicos_";
            $enviado = self::sendMessage($usuario['whatsapp'], $mensajeCompleto);
            
            if ($enviado) {
                Database::execute(
                    "UPDATE notificaciones SET enviada_whatsapp = 1 WHERE usuario_id = ? AND titulo = ? AND created_at >= ?",
                    [$userId, $titulo, date('Y-m-d H:i:s', strtotime('-1 minute'))]
                );
            }
            
            return $enviado;
            
        } catch (\Exception $e) {
            error_log("Error notificación WhatsApp: " . $e->getMessage());
            return false;
        }
    }
    
    private static function formatPhoneNumber(string $phone): string
    {
        // Remover caracteres no numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si empieza con 0, removerlo
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        
        // Si no empieza con código de país, agregar Argentina (54)
        if (substr($phone, 0, 2) !== '54') {
            $phone = '54' . $phone;
        }
        
        return $phone;
    }
    
    private static function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = self::$apiUrl . $endpoint;
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::$accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        throw new \Exception("Error WhatsApp API: HTTP {$httpCode} - {$response}");
    }
}