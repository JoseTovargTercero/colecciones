<?php
require_once __DIR__ . '/../models/AlimentoModel.php';

class AlimentoController
{
    private $model;

    public function __construct()
    {
        $this->model = new AlimentoModel();
    }

    private function jsonResponse($value, string $message = '', $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['value' => $value, 'message' => $message, 'data' => $data]);
        exit;
    }

    private function getJsonInput(): array
    {
        return json_decode(file_get_contents('php://input') ?: '', true) ?? [];
    }

    public function listar(): void
    {
        try {
            $this->jsonResponse(true, 'Listado obtenido', $this->model->listar());
        } catch (Throwable $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    public function mostrar($id): void
    {
        $this->jsonResponse(true, 'Detalle', $this->model->obtener((int)$id));
    }

    public function crear(): void
    {

        $this->jsonResponse(true, 'Alimento creado', $this->model->crear($this->getJsonInput()));
    }

    public function actualizar($id): void
    {
        $this->jsonResponse(true, 'Alimento actualizado', $this->model->actualizar((int)$id, $this->getJsonInput()));
    }

    public function registrarIngreso(): void
    {
        $input = $this->getJsonInput();
        
        if (empty($input['alimento_id']) || empty($input['cantidad_kg'])) {
            $this->jsonResponse(false, 'Datos incompletos', null, 400); 
            return;
        }

        if ($input['cantidad_kg'] < 1) {
             $this->jsonResponse(false, 'La cantidad debe ser mayor o igual a 1kg', null, 400);
             return;
        }

        try {
            $this->model->registrarIngreso($input);
            $this->jsonResponse(true, 'Ingreso registrado correctamente');
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al registrar ingreso: ' . $e->getMessage(), null, 500);
        }
    }

    public function eliminar($id): void
    {
        $this->model->eliminar((int)$id);
        $this->jsonResponse(true, 'Alimento eliminado');
    }
}
