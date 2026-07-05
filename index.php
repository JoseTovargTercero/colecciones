<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/middlewares/SessionRedirectMiddleware.php';
require_once __DIR__ . '/middlewares/LoginRequiredMiddleware.php';
require_once __DIR__ . '/helpers/helpers.php'; // Permission helpers

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/SystemUserController.php';
require_once __DIR__ . '/controllers/MenuController.php';
require_once __DIR__ . '/controllers/MenuCategoriaController.php';
require_once __DIR__ . '/controllers/UsersPermisosController.php';
require_once __DIR__ . '/controllers/AlertaController.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/RecoveryPasswordController.php';
require_once __DIR__ . '/controllers/EmpresaController.php';
require_once __DIR__ . '/controllers/TemporadaController.php';
require_once __DIR__ . '/controllers/ColeccionController.php';
require_once __DIR__ . '/controllers/PremioController.php';
require_once __DIR__ . '/controllers/VendedorController.php';
require_once __DIR__ . '/controllers/AsignacionController.php';
require_once __DIR__ . '/controllers/ControlPagosController.php';
require_once __DIR__ . '/controllers/CargaPagosController.php';
require_once __DIR__ . '/controllers/PreferenciasPremiosController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/CronController.php';
require_once __DIR__ . '/controllers/TutorialController.php';


use App\Core\ViewRenderer;

use App\Router;

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Iniciar sesión si no está iniciada
}


$host = $_SERVER['HTTP_HOST'];

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';


// Uso para archivos en php
define('APP_ROOT', __DIR__ . '/'); // Define la ruta raíz de la aplicación
// Uso para rutas en el js/html
define('BASE_URL', "$protocol://$host$path");


$viewRenderer = new ViewRenderer('views/');

$router = new Router($viewRenderer);







// Rutas públicas (sin autenticación)
$router->group(['middleware' => SessionRedirectMiddleware::class], function ($router) {
    $router->get('/login', ['vista' => 'auth/login', 'vistaData' => ['titulo' => 'Iniciar Sesión', 'layout' => false]]);
    $router->get('/', ['vista' => 'auth/login', 'vistaData' => ['titulo' => 'Iniciar Sesión', 'layout' => false]]);
    $router->get('', ['vista' => 'auth/login', 'vistaData' => ['titulo' => 'Iniciar Sesión', 'layout' => false]]);
});
/*
// Rutas protegidas (requieren autenticación)
$router->group(['middleware' => AuthMiddleware::class], function ($router) {
    // Para permisos y niveles (No disponible)
});
*/

// El perfil es la única vista que todos los usuarios logueados deben ver
$router->group(['middleware' => LoginRequiredMiddleware::class], function ($router) {
    $router->get('/perfil', ['vista' => 'modules/perfil_view', 'vistaData' => ['titulo' => 'Perfil de Usuario']]);
    $router->get('/empresas', ['vista' => 'modules/empresas_view', 'vistaData' => ['titulo' => 'Empresas']]);
    $router->get('/temporadas', ['vista' => 'modules/temporadas_view', 'vistaData' => ['titulo' => 'Temporadas']]);
    $router->get('/colecciones', ['vista' => 'modules/colecciones_view', 'vistaData' => ['titulo' => 'Colecciones']]);
    $router->get('/premios', ['vista' => 'modules/premios_view', 'vistaData' => ['titulo' => 'Premios']]);
    $router->get('/vendedores', ['vista' => 'modules/vendedores_view', 'vistaData' => ['titulo' => 'Vendedores']]);
    $router->get('/asignaciones', ['vista' => 'modules/asignaciones_view', 'vistaData' => ['titulo' => 'Asignaciones']]);
    $router->get('/control_pagos', ['vista' => 'modules/control_pagos_view', 'vistaData' => ['titulo' => 'Control de pagos']]);
    $router->get('/preferencias-premios', ['vista' => 'modules/preferencias_premios_view', 'vistaData' => ['titulo' => 'Preferencias de Premios']]);
    $router->get('/dashboard', ['vista' => 'modules/dashboard_view', 'vistaData' => ['titulo' => 'Dashboard']]);
});






$router->group(['prefix' => '/api'], function ($router) {


    // perfil
    $router->get('/perfil', ['controlador' => SystemUserController::class, 'accion' => 'perfil']);
    $router->post('/perfil', ['controlador' => SystemUserController::class, 'accion' => 'actualizarPerfil']);











    // FUNCIONAMIENTO DEL SISMTEA

    // endpoints de usuarios
    $router->get('/system_users', ['controlador' => SystemUserController::class, 'accion' => 'listar']);
    $router->get('/system_users/{user_id}', ['controlador' => SystemUserController::class, 'accion' => 'mostrar']);
    $router->post('/system_users', ['controlador' => SystemUserController::class, 'accion' => 'crear']);
    $router->put('/system_users/{user_id}', ['controlador' => SystemUserController::class, 'accion' => 'actualizar']);
    $router->delete('/system_users/{user_id}', ['controlador' => SystemUserController::class, 'accion' => 'eliminar']);
    // check email user
    $router->post('/system_users/check_email', ['controlador' => SystemUserController::class, 'accion' => 'checkEmail']);
    // Login
    $router->post('/system_users/login', ['controlador' => SystemUserController::class, 'accion' => 'login']);
    // Logout
    $router->get('/logout', ['controlador' => AuthController::class, 'accion' => 'logout']);

    // endpoints de empresas
    $router->get('/empresas',         ['controlador' => EmpresaController::class, 'accion' => 'listar']);
    $router->get('/empresas/{id}',    ['controlador' => EmpresaController::class, 'accion' => 'mostrar']);
    $router->post('/empresas',        ['controlador' => EmpresaController::class, 'accion' => 'crear']);
    $router->post('/empresas/{id}',   ['controlador' => EmpresaController::class, 'accion' => 'actualizar']);
    $router->delete('/empresas/{id}', ['controlador' => EmpresaController::class, 'accion' => 'eliminar']);

    // endpoints de temporadas
    $router->get('/temporadas', ['controlador' => TemporadaController::class, 'accion' => 'listar']);
    $router->post('/temporadas', ['controlador' => TemporadaController::class, 'accion' => 'crear']);
    $router->put('/temporadas/{id}', ['controlador' => TemporadaController::class, 'accion' => 'actualizar']);
    $router->delete('/temporadas/{id}', ['controlador' => TemporadaController::class, 'accion' => 'eliminar']);

    // endpoints de colecciones
    $router->get('/colecciones', ['controlador' => ColeccionController::class, 'accion' => 'listar']);
    $router->post('/colecciones', ['controlador' => ColeccionController::class, 'accion' => 'crear']);
    $router->post('/colecciones/{id}', ['controlador' => ColeccionController::class, 'accion' => 'actualizar']);
    $router->delete('/colecciones/{id}', ['controlador' => ColeccionController::class, 'accion' => 'eliminar']);

    // endpoints de premios
    $router->get('/premios', ['controlador' => PremioController::class, 'accion' => 'listar']);
    $router->post('/premios', ['controlador' => PremioController::class, 'accion' => 'crear']);
    $router->post('/premios/{id}', ['controlador' => PremioController::class, 'accion' => 'actualizar']);
    $router->delete('/premios/{id}', ['controlador' => PremioController::class, 'accion' => 'eliminar']);

    // endpoints de vendedores
    $router->get('/vendedores', ['controlador' => VendedorController::class, 'accion' => 'listar']);
    $router->get('/vendedores/buscar', ['controlador' => VendedorController::class, 'accion' => 'buscarPorCedula']);
    $router->get('/vendedores/{id}/detalles', ['controlador' => VendedorController::class, 'accion' => 'detalles']);
    $router->post('/vendedores', ['controlador' => VendedorController::class, 'accion' => 'crear']);
    $router->post('/vendedores/{id}', ['controlador' => VendedorController::class, 'accion' => 'actualizar']);
    $router->delete('/vendedores/{id}', ['controlador' => VendedorController::class, 'accion' => 'eliminar']);

    // endpoints de asignaciones
    $router->get('/asignaciones', ['controlador' => AsignacionController::class, 'accion' => 'listar']);
    $router->post('/asignaciones', ['controlador' => AsignacionController::class, 'accion' => 'crear']);
    $router->delete('/asignaciones/{id}', ['controlador' => AsignacionController::class, 'accion' => 'eliminar']);
    $router->get('/asignaciones/{id}/cuotas', ['controlador' => AsignacionController::class, 'accion' => 'cuotas']);

    // control-pagos
    $router->get('/control-pagos', ['controlador' => ControlPagosController::class, 'accion' => 'listar']);
    $router->get('/control-pagos/historial', ['controlador' => ControlPagosController::class, 'accion' => 'historial']);
    $router->get('/control-pagos/premio-info', ['controlador' => ControlPagosController::class, 'accion' => 'premioInfo']);
    $router->post('/control-pagos/solicitar-premio', ['controlador' => ControlPagosController::class, 'accion' => 'solicitarPremio']);
    $router->post('/cargar-pago', ['controlador' => CargaPagosController::class, 'accion' => 'procesar']);
    $router->get('/cargar-pago/cuotas', ['controlador' => CargaPagosController::class, 'accion' => 'cuotas']);
    $router->get('/cargar-pago/deuda', ['controlador' => CargaPagosController::class, 'accion' => 'deuda']);

    // preferencias-premios
    $router->get('/preferencias-premios', ['controlador' => PreferenciasPremiosController::class, 'accion' => 'listar']);
    $router->get('/preferencias-premios/pagos-tiempo', ['controlador' => PreferenciasPremiosController::class, 'accion' => 'pagosTiempo']);
    $router->get('/preferencias-premios/premios-disponibles', ['controlador' => PreferenciasPremiosController::class, 'accion' => 'premiosDisponibles']);
    $router->post('/preferencias-premios/asignar-premios', ['controlador' => PreferenciasPremiosController::class, 'accion' => 'asignarPremios']);
    $router->post('/preferencias-premios/{id}/entregar', ['controlador' => PreferenciasPremiosController::class, 'accion' => 'entregar']);

    // dashboard
    $router->get('/dashboard/kpis', ['controlador' => DashboardController::class, 'accion' => 'kpis']);

    // endpoints de alertas
    $router->get('/alertas', ['controlador' => AlertaController::class, 'accion' => 'listar']);
    $router->get('/alertas/{alerta_id}', ['controlador' => AlertaController::class, 'accion' => 'mostrar']);
    $router->post('/alertas', ['controlador' => AlertaController::class, 'accion' => 'crear']);
    $router->post('/alertas/{alerta_id}', ['controlador' => AlertaController::class, 'accion' => 'actualizar']);
    $router->post('/alertas/{alerta_id}/estado', ['controlador' => AlertaController::class, 'accion' => 'cambiarEstado']);
    $router->delete('/alertas/{alerta_id}', ['controlador' => AlertaController::class, 'accion' => 'eliminar']);

    // endpoints de notificaciones
    $router->get('/notifications', ['controlador' => NotificationController::class, 'accion' => 'listar']);
    $router->get('/notifications/mias', ['controlador' => NotificationController::class, 'accion' => 'listarDeSesion']);
    $router->get('/notifications/mias/conteos', ['controlador' => NotificationController::class, 'accion' => 'obtenerConteosDeSesion']);
    $router->get('/notifications/{notifications_id}', ['controlador' => NotificationController::class, 'accion' => 'mostrar']);
    $router->post('/notifications', ['controlador' => NotificationController::class, 'accion' => 'crear']);
    $router->post('/notifications/{notifications_id}/flag/new', ['controlador' => NotificationController::class, 'accion' => 'actualizarNew']);
    $router->post('/notifications/{notifications_id}/flag/read_unread', ['controlador' => NotificationController::class, 'accion' => 'actualizarReadUnread']);
    $router->delete('/notifications/{notifications_id}', ['controlador' => NotificationController::class, 'accion' => 'eliminar']);
    $router->post('/notifications/marcar_todas_vistas', ['controlador' => NotificationController::class, 'accion' => 'marcarTodasComoVistas']);
    $router->post('/notifications/marcar_todas_leidas', ['controlador' => NotificationController::class, 'accion' => 'marcarTodasComoLeidas']);

    // tutorial
    $router->get('/tutorial/state', ['controlador' => TutorialController::class, 'accion' => 'state']);

    // bcv
    $router->get('/bcv/refresh', ['controlador' => SystemUserController::class, 'accion' => 'bcvRefresh']);
});


// Ruta pública para cron (sin middleware, accesible sin sesión)
$router->get('/api/cron/actualizar-estatus', ['controlador' => CronController::class, 'accion' => 'actualizarEstatus']);

// --- Ejecutar el Router ---
$router->route();
