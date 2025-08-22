<?php
namespace App\Services;

use App\Utils\Database;

class MercadoPagoService
{
    private static string $accessToken;
    private static string $baseUrl = 'https://api.mercadopago.com';
    
    public static function init()
    {
        self::$accessToken = $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '';
    }
    
    public static function createPayment(array $paymentData): array
    {
        self::init();
        
        $preference = [
            'items' => [
                [
                    'title' => $paymentData['title'],
                    'description' => $paymentData['description'],
                    'quantity' => 1,
                    'unit_price' => (float) $paymentData['amount'],
                    'currency_id' => 'ARS'
                ]
            ],
            'payer' => [
                'email' => $paymentData['payer_email'],
                'name' => $paymentData['payer_name']
            ],
            'external_reference' => $paymentData['external_reference'],
            'notification_url' => $paymentData['notification_url'],
            'back_urls' => [
                'success' => $paymentData['success_url'],
                'failure' => $paymentData['failure_url'],
                'pending' => $paymentData['pending_url']
            ],
            'auto_return' => 'approved',
            'statement_descriptor' => 'Servicios Tecnicos'
        ];
        
        $response = self::makeRequest('POST', '/checkout/preferences', $preference);
        
        return $response;
    }
    
    public static function getPaymentInfo(string $paymentId): ?array
    {
        self::init();
        return self::makeRequest('GET', "/v1/payments/{$paymentId}");
    }
    
    public static function processWebhook(array $webhookData): bool
    {
        try {
            if ($webhookData['type'] === 'payment') {
                $paymentInfo = self::getPaymentInfo($webhookData['data']['id']);
                
                if ($paymentInfo && isset($paymentInfo['external_reference'])) {
                    return self::updatePaymentStatus($paymentInfo);
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log("Error procesando webhook MP: " . $e->getMessage());
            return false;
        }
    }
    
    private static function updatePaymentStatus(array $paymentInfo): bool
    {
        $externalRef = $paymentInfo['external_reference'];
        $status = $paymentInfo['status'];
        
        // Actualizar estado del pago en la base de datos
        $updateData = [
            'mp_payment_id' => $paymentInfo['id'],
            'mp_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === 'approved') {
            $updateData['estado_consulta'] = 'capturado';
            $updateData['consulta_pagada_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'rejected') {
            $updateData['estado_consulta'] = 'rechazado';
        }
        
        Database::execute(
            "UPDATE pagos SET mp_payment_id = ?, mp_status = ?, estado_consulta = ?, 
             consulta_pagada_at = ?, updated_at = ? 
             WHERE external_reference = ?",
            [
                $updateData['mp_payment_id'],
                $updateData['mp_status'],
                $updateData['estado_consulta'] ?? null,
                $updateData['consulta_pagada_at'] ?? null,
                $updateData['updated_at'],
                $externalRef
            ]
        );
        
        return true;
    }
    
    private static function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = self::$baseUrl . $endpoint;
        
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
        
        throw new \Exception("Error MercadoPago: HTTP {$httpCode} - {$response}");
    }
}