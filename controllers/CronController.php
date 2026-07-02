<?php
require_once __DIR__ . '/../cron/actualizar_estatus_cuotas.php';

class CronController
{
    public function actualizarEstatus(): void
    {
        header('Content-Type: application/json');
        $resultado = ejecutarActualizacionEstatusCuotas(false);
        echo json_encode($resultado);
    }
}
