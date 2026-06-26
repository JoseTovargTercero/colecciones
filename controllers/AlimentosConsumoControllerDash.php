<?php
require_once __DIR__ . '/../models/AlimentosConsumoModelDash.php';

class AlimentosConsumoControllerDash
{
    private $model;

    public function __construct()
    {
        $this->model = new AlimentosConsumoModelDash();
    }

    /**
     * Vista principal del dashboard
     */
    public function dashboard()
    {
        require __DIR__ . '/../views/alimentos/dashboard.php';
    }

    /**
     * API para consumo de planes (JSON para graficar)
     */
    public function apiConsumoPlanes()
    {
        try {
            $planes = $this->model->obtenerConsumoPlanes();
            header('Content-Type: application/json');
            echo json_encode(['value' => true, 'data' => $planes]);
        } catch (Exception $e) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['value' => false, 'message' => $e->getMessage()]);
        }
    }
}
