<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Crear contenedor DI
$container = new Container();
AppFactory::setContainer($container);

// Crear aplicación Slim
$app = AppFactory::create();

// Agregar middleware de parsing del body
$app->addBodyParsingMiddleware();

// Middleware de enrutamiento
$app->addRoutingMiddleware();

// Middleware CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Middleware de respuesta JSON
$app->add(new \App\Middleware\JsonResponseMiddleware());

// Middleware de Rate Limiting (si está habilitado)
if ($_ENV['RATE_LIMIT_ENABLED'] === 'true') {
    $app->add(new \App\Middleware\RateLimitMiddleware(
        (int)($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 100),
        (int)($_ENV['RATE_LIMIT_WINDOW_MINUTES'] ?? 15)
    ));
}

// Middleware de manejo de errores
$errorMiddleware = $app->addErrorMiddleware(
    $_ENV['DEBUG_MODE'] === 'true',
    true,
    true
);

// Cargar rutas
require_once __DIR__ . '/../src/routes.php';

// Ejecutar aplicación
$app->run();