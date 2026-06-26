<?php
require_once __DIR__ . '/../models/BeneficioModel.php';

class BeneficioController
{
    private $model;

    public function __construct()
    {
        $this->model = new BeneficioModel();
    }

    private function jsonResponse($value, string $message = '', $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'value' => $value,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * GET /api/beneficios
     * Lista matanzas agrupadas por created_at
     */
    public function listar(): void
    {
        try {

            $data = $this->model->listarMatanzas();
            $this->jsonResponse(true, 'Listado de beneficios obtenido.', $data);
        } catch (Throwable $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * GET /api/beneficios/{created_at}
     * Detalle de animales beneficiados
     */
    public function detalle(array $params): void
    {
        $createdAt = $params['created_at'] ?? null;

        if (!$createdAt) {
            $this->jsonResponse(false, 'Parámetro inválido.', null, 400);
        }

        try {
            $data = $this->model->detalleMatanza($createdAt);
            $this->jsonResponse(true, 'Detalle de beneficio.', $data);
        } catch (Throwable $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * GET /api/beneficios/grafico/resumen
     */
    public function graficoResumen(): void
    {
        try {
            $data = $this->model->graficoMensual();
            $this->jsonResponse(true, 'Resumen mensual.', $data);
        } catch (Throwable $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }
}
