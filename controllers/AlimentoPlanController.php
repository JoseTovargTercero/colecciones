<?php

require_once __DIR__ . '/../models/AlimentoPlanModel.php';

class AlimentoPlanController
{
    private $model;

    public function __construct()
    {
        $this->model = new AlimentoPlanModel();
    }

    private function jsonResponse($value, string $message = '', $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(compact('value', 'message', 'data'));
        exit;
    }

    private function getJsonInput(): array
    {
        return json_decode(file_get_contents('php://input') ?: '', true) ?? [];
    }

    public function listar(): void
    {
        try {
            $this->jsonResponse(true, 'Planes obtenidos', $this->model->listar());
        } catch (Throwable $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    public function crear(): void
    {
        try {
            $this->model->crear($this->getJsonInput());
            $this->jsonResponse(true, 'Plan creado correctamente');
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al crear plan: ' . $e->getMessage(), null, 500);
        }
    }

    public function mostrar($id): void
    {
        $this->jsonResponse(true, 'Detalle', $this->model->obtener((int)$id));
    }

    public function eliminar($id): void
    {
        $this->model->eliminar((int)$id);
        $this->jsonResponse(true, 'Plan eliminado');
    }
}
