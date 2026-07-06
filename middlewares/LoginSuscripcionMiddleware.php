<?php
require_once __DIR__ . '/LoginRequiredMiddleware.php';
require_once __DIR__ . '/SuscripcionMiddleware.php';

/**
 * ponytail: encadena LoginRequired + Suscripcion en un solo middleware
 * para rutas que requieren sesión Y suscripción vigente.
 */
class LoginSuscripcionMiddleware implements Middleware
{
    public function handle($ruta)
    {
        (new LoginRequiredMiddleware())->handle($ruta);
        (new SuscripcionMiddleware())->handle($ruta);
    }
}
