<?php
require_once __DIR__ . '/../models/AgendaReproductivaModel.php';

class AgendaReproductivaController
{
    private $model;

    public function __construct()
    {
        $this->model = new AgendaReproductivaModel();
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    private function jsonResponse($value, string $message = '', $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['value' => $value, 'message' => $message, 'data' => $data]);
        exit;
    }

    // GET /montas?limit=&offset=&incluirEliminados=0|1&periodo_id=&numero_monta=&desde=&hasta=
    public function listar(): void
    {

        try {
            $data = $this->model->listar();
            $this->jsonResponse(true, 'Agenda recuperada', $data);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar montas: ' . $e->getMessage(), null, 500);
        }
    }
}
