<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

abstract class BaseController
{
    protected function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
    
    protected function errorResponse(Response $response, string $message, int $status = 400): Response
    {
        return $this->jsonResponse($response, [
            'error' => $message,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ], $status);
    }
    
    protected function successResponse(Response $response, $data = null, string $message = 'Operación exitosa'): Response
    {
        $responseData = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $responseData['data'] = $data;
        }
        
        return $this->jsonResponse($response, $responseData);
    }
    
    protected function validateRequired(array $data, array $required): ?string
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return "Campo {$field} es requerido";
            }
        }
        return null;
    }
    
    protected function getUserId($request): ?int
    {
        return $request->getAttribute('user_id');
    }
}