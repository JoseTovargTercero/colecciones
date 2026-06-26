<?php
require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardController
{
    /**
     * @var DashboardModel
     */
    private $model;

    public function __construct()
    {
        $this->model = new DashboardModel();
    }

    private function jsonResponse($value, string $message = '', $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'value'   => $value,
            'message' => $message,
            'data'    => $data,
        ]);
        exit;
    }

    /**
     * GET /dashboard
     *
     * Devuelve el JSON completo del dashboard, con la estructura:
     * {
     *   "animales": {...},
     *   "salud": {...},
     *   "pesos": {...},
     *   "camadas_bajas": {...},
     *   "infraestructura": {...},
     *   "decesos": {...},
     *   "camadas": {...},
     *   "incidencias": {...}
     * }
     */
    public function resumen(): void
    {
        try {
            $data = $this->model->obtenerResumen();
            $this->jsonResponse(true, 'Resumen de dashboard obtenido correctamente.', $data, 200);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al obtener resumen de dashboard: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Si en el futuro quieres endpoints separados, podrías hacer algo como:
     * - public function animales(): void { ... }
     * - public function salud(): void { ... }
     * etc., reutilizando métodos internos del modelo.
     */
}
