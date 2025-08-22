<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Utils\Database;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authHeader = $request->getHeader('Authorization');
        
        if (empty($authHeader)) {
            return $this->unauthorizedResponse();
        }
        
        $token = str_replace('Bearer ', '', $authHeader[0]);
        
        if (empty($token)) {
            return $this->unauthorizedResponse();
        }
        
        $userId = $this->validateToken($token);
        
        if (!$userId) {
            return $this->unauthorizedResponse();
        }
        
        $request = $request->withAttribute('user_id', $userId);
        
        return $handler->handle($request);
    }
    
    private function validateToken(string $token): ?int
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);
            
            if (count($parts) !== 2) {
                return null;
            }
            
            $userId = (int) $parts[0];
            $timestamp = (int) $parts[1];
            
            if ((time() - $timestamp) > (24 * 60 * 60)) {
                return null;
            }
            
            $user = Database::findById('usuarios', $userId);
            
            return $user && $user['activo'] ? $userId : null;
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function unauthorizedResponse(): Response
    {
        $response = new \Slim\Psr7\Response();
        $data = [
            'error' => 'Token de autorización requerido',
            'status' => 401
        ];
        
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}