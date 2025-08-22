<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// Controladores
use App\Controllers\AuthController;
use App\Controllers\UsuarioController;
use App\Controllers\SolicitudController;
use App\Controllers\ContratistasController;
use App\Controllers\AsignacionController;
use App\Controllers\CitasController;
use App\Controllers\ConfiguracionController;

// Middleware
use App\Middleware\AuthMiddleware;
use App\Middleware\JsonResponseMiddleware;

$app = AppFactory::create();

// Error handling
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Middleware global de JSON
$app->add(new JsonResponseMiddleware());

// CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
        ->withHeader('Access-Control-Max-Age', '3600');
});

// Handle preflight requests
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

// Ruta principal
$app->get('/', function (Request $request, Response $response) {
    $data = [
        'message' => '🚀 API Servicios Técnicos COMPLETA',
        'version' => '1.0.0',
        'status' => 'online',
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoints' => [
            'auth' => [
                'login' => 'POST /api/v1/auth/login',
                'register' => 'POST /api/v1/auth/register'
            ],
            'usuarios' => [
                'list' => 'GET /api/v1/usuarios',
                'get' => 'GET /api/v1/usuarios/{id}'
            ],
            'solicitudes' => [
                'list' => 'GET /api/v1/solicitudes',
                'create' => 'POST /api/v1/solicitudes',
                'get' => 'GET /api/v1/solicitudes/{id}',
                'update_estado' => 'PUT /api/v1/solicitudes/{id}/estado'
            ],
            'contratistas' => [
                'list' => 'GET /api/v1/contratistas',
                'get' => 'GET /api/v1/contratistas/{id}',
                'buscar' => 'POST /api/v1/contratistas/buscar'
            ],
            'config' => [
                'categorias' => 'GET /api/v1/config/categorias',
                'servicios' => 'GET /api/v1/config/servicios'
            ]
        ]
    ];
    
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});

// API Routes
$app->group('/api/v1', function (RouteCollectorProxy $group) {
    
    // AUTH - No requieren autenticación
    $group->post('/auth/login', [AuthController::class, 'login']);
    $group->post('/auth/register', [AuthController::class, 'register']);
    
    // USUARIOS - Públicos
    $group->get('/usuarios', [UsuarioController::class, 'getAll']);
    $group->get('/usuarios/{id:[0-9]+}', [UsuarioController::class, 'getById']);
    
    // SOLICITUDES
    $group->get('/solicitudes', [SolicitudController::class, 'getAll']);
    $group->get('/solicitudes/{id:[0-9]+}', [SolicitudController::class, 'getById']);
    
    // CONTRATISTAS - Públicos
    $group->get('/contratistas', [ContratistasController::class, 'getAll']);
    $group->get('/contratistas/{id:[0-9]+}', [ContratistasController::class, 'getById']);
    $group->post('/contratistas/buscar', [ContratistasController::class, 'buscarDisponibles']);
    
    // CONFIGURACION - Públicas
    $group->get('/config/categorias', [ConfiguracionController::class, 'getCategorias']);
    $group->get('/config/servicios', [ConfiguracionController::class, 'getServicios']);
    $group->get('/config/servicios/categoria/{categoriaId:[0-9]+}', [ConfiguracionController::class, 'getServiciosPorCategoria']);
    
    // RUTAS PROTEGIDAS
    $group->group('', function (RouteCollectorProxy $protected) {
        
        // SOLICITUDES - Requieren auth
        $protected->post('/solicitudes', [SolicitudController::class, 'create']);
        $protected->put('/solicitudes/{id:[0-9]+}/estado', [SolicitudController::class, 'updateEstado']);
        
    })->add(new AuthMiddleware());
});

$app->run();