<?php
require_once __DIR__ . '/../models/AlimentosConsumoModel.php';

class AlimentosConsumoController
{
    private $model;

    public function __construct()
    {
        $this->model = new AlimentosConsumoModel();
    }

    private function jsonResponse($value, $message = '', $data = null, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(compact('value','message','data'));
        exit;
    }

    public function estado(): void
    {
        try {
            $data = $this->model->estadoHoy();
            $this->jsonResponse(true, 'Estado cargado', $data);
        } catch (Throwable $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    public function ejecutarHorario()
{
      
    try {
        $planId    = $_GET['plan_id']    ?? null;
        $horarioId = $_GET['horario_id'] ?? null;

        if (!$planId || !$horarioId) {
            echo json_encode([
                'value' => false,
                'message' => 'Parámetros incompletos'
            ]);
            return;
        }




        $userUuid = $_SESSION['user_id'] ?? null;
        if (!$userUuid) {
            throw new RuntimeException('Usuario no autenticado');
        }

        $model = new AlimentosConsumoModel();
        $model->ejecutarHorario(
            (int) $planId,
            (int) $horarioId,
            $userUuid
        );

        echo json_encode([
            'value' => true,
            'message' => 'Horario ejecutado correctamente'
        ]);

    } catch (\Throwable $e) {
        http_response_code(400);
        echo json_encode([
            'value' => false,
            'message' => $e->getMessage()
        ]);
    }
}


    public function registrar(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $this->model->registrarConsumoPlan(
                (int)$input['plan_id'],
                (int)$input['ubicacion_id']
            );
            $this->jsonResponse(true, 'Consumo registrado');
        } catch (Throwable $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    public function detallePlan($id): void
{
    try {
        if (empty($id['plan'])) {
            throw new RuntimeException('ID del plan no enviado.');
        }

        $detalle = $this->model->detallePlan($id['plan']);

        $this->jsonResponse(
            true,
            'Detalle del plan obtenido correctamente.',
            $detalle
        );

    } catch (RuntimeException $e) {
        $this->jsonResponse(false, $e->getMessage(), null, 400);
    } catch (Throwable $e) {
        $this->jsonResponse(false, 'Error al obtener detalle: ' . $e->getMessage(), null, 500);
    }
}

    public function registrarTodos(): void
    {
        try {
            $this->model->registrarTodosHoy();
            $this->jsonResponse(true, 'Todos los consumos registrados');
        } catch (Throwable $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 500);
        }
    }
}
