<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonResponseMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);
        
        $uri = $request->getUri()->getPath();
        if (strpos($uri, '/api/') === 0) {
            $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        }
        
        return $response;
    }
}