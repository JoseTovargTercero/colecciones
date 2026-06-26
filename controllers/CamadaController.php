<?php
require_once __DIR__ . '/../models/CamadaModel.php';

class CamadaController
{
    private $model;

    public function __construct()
    {
        $this->model = new CamadaModel();
    }

    // ... (getJsonInput, jsonResponse sin cambios) ...

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
    // GET /camadas?limit=&offset=&madre_id=
    public function listar(): void
    {
        // ... (sin cambios)
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10000;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $madreId = $_GET['madre_id'] ?? null;

        try {
            $data = $this->model->listar($limit, $offset, $madreId);
            $this->jsonResponse(true, 'Listado de camadas activas obtenido.', $data);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar camadas: ' . $e->getMessage(), null, 500);
        }
    }

    // GET /camadas/{camada_id}
    public function mostrar(array $params): void
    {
        $camadaId = $params['camada_id'] ?? '';
        if ($camadaId === '') {
            $this->jsonResponse(false, 'Parámetro camada_id es obligatorio.', null, 400);
        }
        try {
            $row = $this->model->obtenerPorId($camadaId);
            if (!$row)
                $this->jsonResponse(false, 'Camada no encontrada o inactiva.', null, 404);

            // <--- INICIO: LÓGICA CORREGIDA Y ACTIVADA --->
            // Si los pendientes son 0, cerramos la camada
            if ($row['pendientes_count'] <= 0 && $row['estado_camada'] === 'ACTIVA') {
                $this->model->actualizarEstado($camadaId, 'CERRADA');
                $row['estado_camada'] = 'CERRADA';
            }
            // <--- FIN: LÓGICA CORREGIDA Y ACTIVADA --->

            $this->jsonResponse(true, 'Camada encontrada.', $row);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al obtener camada: ' . $e->getMessage(), null, 500);
        }
    }

    // <--- INICIO: MÉTODO AÑADIDO (PARA SOFT DELETE) --->
    // DELETE /camadas/{camada_id}
    public function eliminar(array $params): void
    {
        $camadaId = $params['camada_id'] ?? '';
        if ($camadaId === '') {
            $this->jsonResponse(false, 'Parámetro camada_id es obligatorio.', null, 400);
        }
        try {
            $ok = $this->model->eliminar($camadaId);
            if (!$ok)
                $this->jsonResponse(false, 'No se pudo eliminar la camada.', null, 400);
            $this->jsonResponse(true, 'Camada eliminada correctamente.', ['deleted' => true]);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al eliminar camada: ' . $e->getMessage(), null, 500);
        }
    }
    // <--- FIN: MÉTODO AÑADIDO --->
}