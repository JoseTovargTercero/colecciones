<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/middlewares/SessionRedirectMiddleware.php';
require_once __DIR__ . '/middlewares/LoginRequiredMiddleware.php';
require_once __DIR__ . '/helpers/helpers.php'; // Permission helpers

require_once __DIR__ . '/controllers/SystemUserController.php';

require_once __DIR__ . '/controllers/MenuController.php';
require_once __DIR__ . '/controllers/MenuCategoriaController.php';

require_once __DIR__ . '/controllers/UsersPermisosController.php';

require_once __DIR__ . '/controllers/AlertaController.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/RecoveryPasswordController.php';


use App\Core\ViewRenderer;

use App\Router;

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Iniciar sesión si no está iniciada
} else {
    echo "la sesión ya estaba iniciada";
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


// Login
$router->post('system_users/login', ['controlador' => SystemUserController::class, 'accion' => 'login']);
$router->post('/recovery/verify-email', [
    'controlador' => RecoveryPasswordController::class,
    'accion' => 'verifyEmail'
]);

$router->post('/recovery/update-password', [
    'controlador' => RecoveryPasswordController::class,
    'accion' => 'updatePassword'
]);



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
    $router->get('/logout', ['controlador' => SystemUserController::class, 'accion' => 'logout']);
    // endpoints de menús
    $router->get('/menus', ['controlador' => MenuController::class, 'accion' => 'listar']);
    $router->get('/menus/{menu_id}', ['controlador' => MenuController::class, 'accion' => 'mostrar']);
    $router->post('/menus', ['controlador' => MenuController::class, 'accion' => 'crear']);
    $router->post('/menus/{menu_id}', ['controlador' => MenuController::class, 'accion' => 'actualizar']);
    $router->delete('/menus/{menu_id}', ['controlador' => MenuController::class, 'accion' => 'eliminar']);
    $router->post('/menus-reordenar', ['controlador' => MenuController::class, 'accion' => 'reordenar']);

    // Endpoints para la gestión de categorías del menú
    $router->get('/menus-categorias', ['controlador' => MenuCategoriaController::class, 'accion' => 'listar']);
    $router->post('/menus-categorias/reordenar', ['controlador' => MenuCategoriaController::class, 'accion' => 'reordenar']);

    // endpoints de permisos de usuarios
    $router->post('/users-permisos', ['controlador' => UsersPermisosController::class, 'accion' => 'asignar']);
    $router->get('/users-permisos/user/{user_id}', ['controlador' => UsersPermisosController::class, 'accion' => 'listarPorUsuario']);
    $router->delete('/users-permisos/{users_permisos_id}', ['controlador' => UsersPermisosController::class, 'accion' => 'eliminarUno']);
    $router->delete('/users-permisos/user/{user_id}', ['controlador' => UsersPermisosController::class, 'accion' => 'eliminarPorUsuario']);

    // endpoint de login
    $router->post('/system_users/login', ['controlador' => SystemUserController::class, 'accion' => 'login']);
    $router->post('/system_users/login_app', ['controlador' => SystemUserController::class, 'accion' => 'loginApp']);
    $router->post('/system_users/verificar_login', ['controlador' => SystemUserController::class, 'accion' => 'verificarLoginApp']);
    //gestion de animales

    // endpoints de session_management
    $router->get('/session_management', ['controlador' => \App\Controllers\SessionManagementController::class, 'accion' => 'showAll']);
    $router->get('/session_management/{id}', ['controlador' => \App\Controllers\SessionManagementController::class, 'accion' => 'showById']);
    $router->post('/session_management', ['controlador' => \App\Controllers\SessionManagementController::class, 'accion' => 'create']);
    $router->post('/session_management/kick', ['controlador' => \App\Controllers\SessionManagementController::class, 'accion' => 'kick']);
    $router->post('/session_management/store-status', ['controlador' => \App\Controllers\SessionManagementController::class, 'accion' => 'storeStatus']);
    $router->get('/session_management/check-status', ['controlador' => \App\Controllers\SessionManagementController::class, 'accion' => 'checkStatus']);
    $router->get('/session_management/export', ['controlador' => \App\Controllers\SessionManagementController::class, 'accion' => 'export']);

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
});


// --- Ejecutar el Router ---
$router->route();
