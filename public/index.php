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
use App\Controllers\PagosController;
use App\Controllers\NotificacionesController;
use App\Controllers\EvaluacionesController;

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
        'message' => '🚀 API Servicios Técnicos FASE 1 COMPLETA',
        'version' => '1.1.0',
        'status' => 'online',
        'timestamp' => date('Y-m-d H:i:s'),
        'features' => [
            '✅ JWT real con refresh tokens',
            '✅ Sistema de pagos (MercadoPago)',
            '✅ Notificaciones WhatsApp',
            '✅ Sistema de evaluaciones',
            '✅ CRUD completo de todas las entidades',
            '✅ Middleware de seguridad'
        ],
        'endpoints' => [
            'auth' => [
                'login' => 'POST /api/v1/auth/login',
                'register' => 'POST /api/v1/auth/register',
                'refresh' => 'POST /api/v1/auth/refresh',
                'me' => 'GET /api/v1/auth/me 🔒',
                'logout' => 'POST /api/v1/auth/logout 🔒'
            ],
            'usuarios' => [
                'list' => 'GET /api/v1/usuarios',
                'get' => 'GET /api/v1/usuarios/{id}'
            ],
            'solicitudes' => [
                'list' => 'GET /api/v1/solicitudes',
                'create' => 'POST /api/v1/solicitudes 🔒',
                'get' => 'GET /api/v1/solicitudes/{id}',
                'update_estado' => 'PUT /api/v1/solicitudes/{id}/estado 🔒'
            ],
            'contratistas' => [
                'list' => 'GET /api/v1/contratistas',
                'get' => 'GET /api/v1/contratistas/{id}',
                'buscar' => 'POST /api/v1/contratistas/buscar'
            ],
            'asignaciones' => [
                'list' => 'GET /api/v1/asignaciones',
                'by_contratista' => 'GET /api/v1/asignaciones/contratista/{id} ��',
                'aceptar' => 'PUT /api/v1/asignaciones/{id}/aceptar 🔒',
                'rechazar' => 'PUT /api/v1/asignaciones/{id}/rechazar 🔒'
            ],
            'citas' => [
                'list' => 'GET /api/v1/citas',
                'create' => 'POST /api/v1/citas 🔒',
                'get' => 'GET /api/v1/citas/{id}',
                'confirmar' => 'PUT /api/v1/citas/{id}/confirmar 🔒',
                'iniciar' => 'PUT /api/v1/citas/{id}/iniciar 🔒',
                'completar' => 'PUT /api/v1/citas/{id}/completar 🔒'
            ],
            'pagos' => [
                'create_consulta' => 'POST /api/v1/pagos/consulta 🔒',
                'webhook' => 'POST /api/v1/pagos/webhook/mercadopago',
                'by_cita' => 'GET /api/v1/pagos/cita/{id} 🔒'
            ],
            'notificaciones' => [
                'by_user' => 'GET /api/v1/notificaciones/usuario/{id} 🔒',
                'marcar_leida' => 'PUT /api/v1/notificaciones/{id}/leer 🔒',
                'enviar_manual' => 'POST /api/v1/notificaciones/enviar 🔒'
            ],
            'evaluaciones' => [
                'create' => 'POST /api/v1/evaluaciones 🔒',
                'by_cita' => 'GET /api/v1/evaluaciones/cita/{id}',
                'by_contratista' => 'GET /api/v1/evaluaciones/contratista/{id}'
            ],
            'config' => [
                'categorias' => 'GET /api/v1/config/categorias',
                'servicios' => 'GET /api/v1/config/servicios',
                'servicios_por_categoria' => 'GET /api/v1/config/servicios/categoria/{id}'
            ]
        ],
        'nota' => '🔒 = Requiere autenticación (Header: Authorization: Bearer {access_token})'
    ];
    
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});

// API Routes
$app->group('/api/v1', function (RouteCollectorProxy $group) {
    
    // AUTH - No requieren autenticación
    $group->post('/auth/login', [AuthController::class, 'login']);
    $group->post('/auth/register', [AuthController::class, 'register']);
    $group->post('/auth/refresh', [AuthController::class, 'refresh']);
    
    // USUARIOS - Públicos
    $group->get('/usuarios', [UsuarioController::class, 'getAll']);
    $group->get('/usuarios/{id:[0-9]+}', [UsuarioController::class, 'getById']);
    
    // SOLICITUDES - Públicas para listado
    $group->get('/solicitudes', [SolicitudController::class, 'getAll']);
    $group->get('/solicitudes/{id:[0-9]+}', [SolicitudController::class, 'getById']);
    
    // CONTRATISTAS - Públicos
    $group->get('/contratistas', [ContratistasController::class, 'getAll']);
    $group->get('/contratistas/{id:[0-9]+}', [ContratistasController::class, 'getById']);
    $group->post('/contratistas/buscar', [ContratistasController::class, 'buscarDisponibles']);
    
    // ASIGNACIONES - Públicas para listado
    $group->get('/asignaciones', [AsignacionController::class, 'getAll']);
    
    // CITAS - Públicas para listado
    $group->get('/citas', [CitasController::class, 'getAll']);
    $group->get('/citas/{id:[0-9]+}', [CitasController::class, 'getById']);
    
    // EVALUACIONES - Públicas para lectura
    $group->get('/evaluaciones/cita/{citaId:[0-9]+}', [EvaluacionesController::class, 'getByCita']);
    $group->get('/evaluaciones/contratista/{contratistaId:[0-9]+}', [EvaluacionesController::class, 'getByContratista']);
    
    // CONFIGURACION - Públicas
    $group->get('/config/categorias', [ConfiguracionController::class, 'getCategorias']);
    $group->get('/config/servicios', [ConfiguracionController::class, 'getServicios']);
    $group->get('/config/servicios/categoria/{categoriaId:[0-9]+}', [ConfiguracionController::class, 'getServiciosPorCategoria']);
    
    // WEBHOOKS - Públicos
    $group->post('/pagos/webhook/mercadopago', [PagosController::class, 'webhook']);
    
    // RUTAS PROTEGIDAS
    $group->group('', function (RouteCollectorProxy $protected) {
        
        // AUTH PROTEGIDAS
        $protected->get('/auth/me', [AuthController::class, 'me']);
        $protected->post('/auth/logout', [AuthController::class, 'logout']);
        
        // SOLICITUDES - Requieren auth
        $protected->post('/solicitudes', [SolicitudController::class, 'create']);
        $protected->put('/solicitudes/{id:[0-9]+}/estado', [SolicitudController::class, 'updateEstado']);
        
        // ASIGNACIONES - Requieren auth
        $protected->get('/asignaciones/contratista/{contratistaId:[0-9]+}', [AsignacionController::class, 'getByContratista']);
        $protected->put('/asignaciones/{id:[0-9]+}/aceptar', [AsignacionController::class, 'aceptar']);
        $protected->put('/asignaciones/{id:[0-9]+}/rechazar', [AsignacionController::class, 'rechazar']);
        
        // CITAS - Requieren auth
        $protected->post('/citas', [CitasController::class, 'create']);
        $protected->put('/citas/{id:[0-9]+}/confirmar', [CitasController::class, 'confirmar']);
        $protected->put('/citas/{id:[0-9]+}/iniciar', [CitasController::class, 'iniciar']);
        $protected->put('/citas/{id:[0-9]+}/completar', [CitasController::class, 'completar']);
        
        // PAGOS - Requieren auth
        $protected->post('/pagos/consulta', [PagosController::class, 'createConsultaPago']);
        $protected->get('/pagos/cita/{citaId:[0-9]+}', [PagosController::class, 'getPagosByCita']);
        
        // NOTIFICACIONES - Requieren auth
        $protected->get('/notificaciones/usuario/{userId:[0-9]+}', [NotificacionesController::class, 'getByUser']);
        $protected->put('/notificaciones/{id:[0-9]+}/leer', [NotificacionesController::class, 'marcarLeida']);
        $protected->post('/notificaciones/enviar', [NotificacionesController::class, 'enviarManual']);
        
        // EVALUACIONES - Requieren auth
        $protected->post('/evaluaciones', [EvaluacionesController::class, 'create']);
        
    })->add(new AuthMiddleware());
});

$app->run();