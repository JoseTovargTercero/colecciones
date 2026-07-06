<?php
require_once __DIR__ . '/Middleware.php';
require_once __DIR__ . '/../models/SuscripcionModel.php';

/**
 * Verifica que el usuario tenga suscripción/trial vigente.
 * Se encadena DESPUÉS de LoginRequiredMiddleware.
 * Rutas exentas: /suscripcion/* (para no crear un bucle de redirección).
 */
class SuscripcionMiddleware implements Middleware
{
    // Rutas que no deben ser interceptadas (pasarela de pago, vencida, pendiente)
    private const EXCLUIDAS = ['/suscripcion/plan', '/suscripcion/vencida', '/suscripcion/pendiente', '/suscripcion/pagar'];

    public function handle($ruta)
    {
        // Normalizar
        $ruta = '/' . ltrim($ruta, '/');

        foreach (self::EXCLUIDAS as $ex) {
            if (strpos($ruta, $ex) === 0) return;
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        $model = new SuscripcionModel();
        // Actualizar vencidas en cada request (ligero, 1 UPDATE)
        $model->marcarVencidas();

        $suscripcion = $model->activa($_SESSION['user_id']);

        if (!$suscripcion) {
            // Si no tiene activa, revisar si tiene pendiente
            $pendiente = $model->pendiente($_SESSION['user_id']);
            if ($pendiente) {
                header('Location: ' . BASE_URL . 'suscripcion/pendiente');
                exit();
            }

            // Sin suscripción activa ni pendiente → vencida/cancelada
            header('Location: ' . BASE_URL . 'suscripcion/vencida');
            exit();
        }

        // Trial activo: dejar pasar pero guardar info en sesión
        $_SESSION['suscripcion'] = [
            'tipo'       => $suscripcion['tipo_pago'],
            'fecha_fin'  => $suscripcion['fecha_fin'],
            'estatus'    => $suscripcion['estatus'],
        ];
    }
}
