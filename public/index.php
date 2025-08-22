<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Controladores
use App\Controllers\AuthController;
use App\Controllers\UsuarioController;
use App\Controllers\SolicitudController;
use App\Controllers\ContratistasController;
use App\Controllers\AsignacionController;
use App\Controllers\CitasController;
use App\Controllers\ConfiguracionController;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Ruta principal
$app->get('/', function (Request $request, Response $response) {
    $data = [
        'message' => '🚀 API Servicios Tecnicos COMPLETA',
        'version' => '1.0.0',
        'status' => 'online',
        'endpoints' => [
            'auth' => [
                'login' => 'POST /api/v1/auth/login',
                'register' => 'POST /api/v1/auth/register'
            ],
            'usuarios' => 'GET /api/v1/usuarios',
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
            'asignaciones' => [
                'list' => 'GET /api/v1/asignaciones',
                'by_contratista' => 'GET /api/v1/asignaciones/contratista/{id}',
                'aceptar' => 'PUT /api/v1/asignaciones/{id}/aceptar',
                'rechazar' => 'PUT /api/v1/asignaciones/{id}/rechazar'
            ],
            'config' => [
                'categorias' => 'GET /api/v1/config/categorias',
                'servicios' => 'GET /api/v1/config/servicios'
            ]
        ]
    ];
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// API Routes
$app->group('/api/v1', function ($group) {
    
    // AUTH
    $group->post('/auth/login', [AuthController::class, 'login']);
    $group->post('/auth/register', [AuthController::class, 'register']);
    
    // USUARIOS
    $group->get('/usuarios', [UsuarioController::class, 'getAll']);
    $group->get('/usuarios/{id}', [UsuarioController::class, 'getById']);
    
    // SOLICITUDES
    $group->get('/solicitudes', [SolicitudController::class, 'getAll']);
    $group->post('/solicitudes', [SolicitudController::class, 'create']);
    $group->get('/solicitudes/{id}', [SolicitudController::class, 'getById']);
    $group->put('/solicitudes/{id}/estado', [SolicitudController::class, 'updateEstado']);
    
    // CONTRATISTAS
    $group->get('/contratistas', [ContratistasController::class, 'getAll']);
    $group->get('/contratistas/{id}', [ContratistasController::class, 'getById']);
    $group->post('/contratistas/buscar', [ContratistasController::class, 'buscarDisponibles']);
    
    // ASIGNACIONES
    $group->get('/asignaciones', [AsignacionController::class, 'getAll']);
    $group->get('/asignaciones/contratista/{contratistaId}', [AsignacionController::class, 'getByContratista']);
    $group->put('/asignaciones/{id}/aceptar', [AsignacionController::class, 'aceptar']);
    $group->put('/asignaciones/{id}/rechazar', [AsignacionController::class, 'rechazar']);
    
    // CONFIGURACION
    $group->get('/config/categorias', [ConfiguracionController::class, 'getCategorias']);
    $group->get('/config/servicios', [ConfiguracionController::class, 'getServicios']);
    $group->get('/config/servicios/categoria/{categoriaId}', [ConfiguracionController::class, 'getServiciosPorCategoria']);
});

$app->run();
