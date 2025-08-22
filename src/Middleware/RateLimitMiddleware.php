<?php
?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Utils\Database;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $windowMinutes;
    
    public function __construct(int $maxRequests = 100, int $windowMinutes = 15)
    {
        $this->maxRequests = $maxRequests;
        $this->windowMinutes = $windowMinutes;
    }
    
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $clientIp = $this->getClientIp($request);
        $windowStart = date('Y-m-d H:i:s', strtotime("-{$this->windowMinutes} minutes"));
        
        try {
            // Contar requests en la ventana de tiempo
            $stmt = Database::execute(
                "SELECT COUNT(*) as total FROM rate_limits 
                 WHERE ip_address = ? AND created_at >= ?",
                [$clientIp, $windowStart]
            );
            
            $currentRequests = (int) $stmt->fetch()['total'];
            
            if ($currentRequests >= $this->maxRequests) {
                return $this->rateLimitResponse();
            }
            
            // Registrar request actual
            Database::insert('rate_limits', [
                'ip_address' => $clientIp,
                'endpoint' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Si hay error con rate limit, continuar sin bloquear
        }
        
        $response = $handler->handle($request);
        
        // Agregar headers de rate limit
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $this->maxRequests - $currentRequests - 1));
    }
    
    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $serverParams['HTTP_X_FORWARDED_FOR'])[0]);
        }
        
        return $serverParams['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function rateLimitResponse(): Response
    {
        $response = new \Slim\Psr7\Response();
        $data = [
            'error' => 'Rate limit exceeded. Too many requests.',
            'status' => 429,
            'retry_after' => $this->windowMinutes * 60
        ];
        
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus(429)
            ->withHeader('Content-Type', 'application/json');
    }
}
