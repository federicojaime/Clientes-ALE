<?php

use Slim\Routing\RouteCollectorProxy;
use App\Middleware\AuthMiddleware;

// Ruta raíz
$app->get('/', function ($request, $response) {
    $data = [
        'success' => true,
        'message' => 'API Servicios Técnicos v2.0',
        'version' => '2.0.0',
        'endpoints' => [
            'auth' => '/api/v1/auth',
            'usuarios' => '/api/v1/usuarios',
            'contratistas' => '/api/v1/contratistas',
            'solicitudes' => '/api/v1/solicitudes',
            'citas' => '/api/v1/citas',
            'pagos' => '/api/v1/pagos',
            'evaluaciones' => '/api/v1/evaluaciones',
            'horarios' => '/api/v1/horarios',
            'notificaciones' => '/api/v1/notificaciones',
            'admin' => '/api/v1/admin'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// Health check
$app->get('/health', function ($request, $response) {
    $data = [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '2.0.0'
    ];

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Grupo API v1
$app->group('/api/v1', function (RouteCollectorProxy $group) {

    // ================== AUTENTICACIÓN (sin middleware) ==================
    $group->group('/auth', function (RouteCollectorProxy $auth) {
        $auth->post('/login', '\App\Controllers\AuthController:login');
        $auth->post('/register', '\App\Controllers\AuthController:register');
        $auth->post('/refresh', '\App\Controllers\AuthController:refresh');
        $auth->post('/logout', '\App\Controllers\AuthController:logout');

        // Rutas protegidas de auth
        $auth->get('/me', '\App\Controllers\AuthController:me')->add(new AuthMiddleware());
    });

    // ================== CONFIGURACIÓN (público) ==================
    $group->group('/config', function (RouteCollectorProxy $config) {
        $config->get('/categorias', '\App\Controllers\ConfiguracionController:getCategorias');
        $config->get('/servicios', '\App\Controllers\ConfiguracionController:getServicios');
        $config->get('/categorias/{categoriaId}/servicios', '\App\Controllers\ConfiguracionController:getServiciosPorCategoria');
    });

    // ================== CONTRATISTAS (público para búsqueda) ==================
    $group->group('/contratistas', function (RouteCollectorProxy $contratistas) {
        $contratistas->get('', '\App\Controllers\ContratistasController:getAll');
        $contratistas->get('/{id}', '\App\Controllers\ContratistasController:getById');
        $contratistas->post('/buscar-disponibles', '\App\Controllers\ContratistasController:buscarDisponibles');
    });

    // ================== USUARIOS (protegido) ==================
    $group->group('/usuarios', function (RouteCollectorProxy $usuarios) {
        $usuarios->get('', '\App\Controllers\UsuarioController:getAll');
        $usuarios->get('/{id}', '\App\Controllers\UsuarioController:getById');
    })->add(new AuthMiddleware());

    // ================== SOLICITUDES (protegido) ==================
    $group->group('/solicitudes', function (RouteCollectorProxy $solicitudes) {
        $solicitudes->get('', '\App\Controllers\SolicitudController:getAll');
        $solicitudes->get('/{id}', '\App\Controllers\SolicitudController:getById');
        $solicitudes->post('', '\App\Controllers\SolicitudController:create');
        $solicitudes->put('/{id}/estado', '\App\Controllers\SolicitudController:updateEstado');
    })->add(new AuthMiddleware());

    // ================== ASIGNACIONES (protegido) ==================
    $group->group('/asignaciones', function (RouteCollectorProxy $asignaciones) {
        $asignaciones->get('', '\App\Controllers\AsignacionController:getAll');
        $asignaciones->get('/contratista/{contratistaId}', '\App\Controllers\AsignacionController:getByContratista');
        $asignaciones->post('/{id}/aceptar', '\App\Controllers\AsignacionController:aceptar');
        $asignaciones->post('/{id}/rechazar', '\App\Controllers\AsignacionController:rechazar');
    })->add(new AuthMiddleware());

    // ================== CITAS (protegido) ==================
    $group->group('/citas', function (RouteCollectorProxy $citas) {
        $citas->get('', '\App\Controllers\CitasController:getAll');
        $citas->get('/{id}', '\App\Controllers\CitasController:getById');
        $citas->post('', '\App\Controllers\CitasController:create');
        $citas->post('/{id}/confirmar', '\App\Controllers\CitasController:confirmar');
        $citas->post('/{id}/iniciar', '\App\Controllers\CitasController:iniciar');
        $citas->post('/{id}/completar', '\App\Controllers\CitasController:completar');
    })->add(new AuthMiddleware());

    // ================== PAGOS ==================
    $group->group('/pagos', function (RouteCollectorProxy $pagos) {
        // Rutas protegidas
        $pagos->post('/consulta', '\App\Controllers\PagosController:createConsultaPago')->add(new AuthMiddleware());
        $pagos->get('/cita/{citaId}', '\App\Controllers\PagosController:getPagosByCita')->add(new AuthMiddleware());

        // Webhook público (sin auth)
        $pagos->post('/webhook/mercadopago', '\App\Controllers\PagosController:webhook');
    });

    // ================== EVALUACIONES (protegido) ==================
    $group->group('/evaluaciones', function (RouteCollectorProxy $evaluaciones) {
        $evaluaciones->post('', '\App\Controllers\EvaluacionesController:create');
        $evaluaciones->get('/cita/{citaId}', '\App\Controllers\EvaluacionesController:getByCita');
        $evaluaciones->get('/contratista/{contratistaId}', '\App\Controllers\EvaluacionesController:getByContratista');
    })->add(new AuthMiddleware());

    // ================== HORARIOS (protegido) ==================
    $group->group('/horarios', function (RouteCollectorProxy $horarios) {
        $horarios->get('/contratista/{contratistaId}', '\App\Controllers\HorariosController:getByContratista');
        $horarios->get('/contratista/{contratistaId}/disponibilidad', '\App\Controllers\HorariosController:getDisponibilidad');
        $horarios->post('', '\App\Controllers\HorariosController:create');
        $horarios->put('/{id}', '\App\Controllers\HorariosController:update');
    })->add(new AuthMiddleware());

    // ================== NOTIFICACIONES (protegido) ==================
    $group->group('/notificaciones', function (RouteCollectorProxy $notificaciones) {
        $notificaciones->get('/usuario/{userId}', '\App\Controllers\NotificacionesController:getByUser');
        $notificaciones->put('/{id}/leida', '\App\Controllers\NotificacionesController:marcarLeida');
        $notificaciones->post('/enviar', '\App\Controllers\NotificacionesController:enviarManual');
    })->add(new AuthMiddleware());

    // ================== ADMINISTRACIÓN (protegido) ==================
    $group->group('/admin', function (RouteCollectorProxy $admin) {
        $admin->get('/dashboard', '\App\Controllers\AdminController:dashboard');
        $admin->get('/estadisticas', '\App\Controllers\AdminController:estadisticas');
        $admin->get('/usuarios', '\App\Controllers\AdminController:gestionUsuarios');
    })->add(new AuthMiddleware());
});

// Manejar preflight OPTIONS para CORS
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Ruta catch-all para 404
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $data = [
        'error' => 'Endpoint no encontrado',
        'status' => 404,
        'path' => $request->getUri()->getPath(),
        'method' => $request->getMethod(),
        'available_endpoints' => [
            'GET /' => 'Información de la API',
            'GET /health' => 'Health check',
            'POST /api/v1/auth/login' => 'Login',
            'GET /api/v1/config/categorias' => 'Categorías'
        ]
    ];

    $response->getBody()->write(json_encode($data));
    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
});
