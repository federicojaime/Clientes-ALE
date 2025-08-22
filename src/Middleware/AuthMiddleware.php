<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Services\JWTService;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authHeader = $request->getHeader('Authorization');
        
        if (empty($authHeader)) {
            return $this->unauthorizedResponse('Token requerido');
        }
        
        $token = str_replace('Bearer ', '', $authHeader[0]);
        
        if (empty($token)) {
            return $this->unauthorizedResponse('Formato de token inválido');
        }
        
        $payload = JWTService::validateToken($token);
        
        if (!$payload) {
            return $this->unauthorizedResponse('Token inválido o expirado');
        }
        
        if ($payload['type'] !== 'access') {
            return $this->unauthorizedResponse('Tipo de token inválido');
        }
        
        // Agregar datos del usuario al request
        $request = $request->withAttribute('user_id', $payload['user_id']);
        $request = $request->withAttribute('user_email', $payload['email']);
        $request = $request->withAttribute('user_type', $payload['tipo_usuario']);
        
        return $handler->handle($request);
    }
    
    private function unauthorizedResponse(string $message = 'No autorizado'): Response
    {
        $response = new \Slim\Psr7\Response();
        $data = [
            'error' => $message,
            'status' => 401,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}