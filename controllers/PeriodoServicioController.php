<?php
require_once __DIR__ . '/../models/PeriodoServicioModel.php';

class PeriodoServicioController
{
    private $model;

    public function __construct()
    {
        $this->model = new PeriodoServicioModel();
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
        $limit   = isset($_GET['limit']) ? (int)$_GET['limit'] : 10000;
        $offset  = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $incluir = isset($_GET['incluirEliminados']) ? ((int)$_GET['incluirEliminados'] === 1) : false;

        $periodoId   = $_GET['periodo_id']   ?? null;
        $numeroMonta = isset($_GET['numero_monta']) && $_GET['numero_monta'] !== '' ? (int)$_GET['numero_monta'] : null;
        $desde       = $_GET['desde'] ?? null; // YYYY-mm-dd
        $hasta       = $_GET['hasta'] ?? null; // YYYY-mm-dd

        try {
            $data = $this->model->listar($limit, $offset, $incluir, $periodoId, $numeroMonta, $desde, $hasta);
            $this->jsonResponse(true, 'Listado de montas obtenido correctamente.', $data);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar montas: ' . $e->getMessage(), null, 500);
        }
    }



    // GET /periodos_servicio/{periodo_id}
    public function mostrar(array $params): void
    {
        $periodoId = $params['periodo'] ?? '';
        if ($periodoId === '') {
            $this->jsonResponse(false, 'Parámetro periodo_id es obligatorio.', null, 400);
        }
        try {
            $row = $this->model->obtenerPorId($periodoId);
            if (!$row) $this->jsonResponse(false, 'Periodo no encontrado.', null, 404);
            $this->jsonResponse(true, 'Periodo encontrado.', $row);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al obtener periodo: ' . $e->getMessage(), null, 500);
        }
    }

    // POST /montas
    // JSON: { verraco_id, hembra_id, fecha_inicio,  observaciones, estado_periodo = ABIERTO }

    public function crear(): void
    {
        $in = [
            'verraco_id'     => $_POST['verraco_id']    ?? null,
            'hembra_id'      => $_POST['hembra_id']     ?? null,
            'fecha_inicio'   => $_POST['fecha_inicio']  ?? null,
            'observaciones'  => $_POST['observaciones'] ?? null,
            'numero_servicios'  => $_POST['numero_servicios'] ?? null,
            'frecuencia_servicios'  => $_POST['frecuencia_servicios'] ?? null,
            'hora_servicio'  => $_POST['hora_servicio'] ?? null
        ];

        try {
            $uuid = $this->model->crear($in);
            $this->jsonResponse(true, 'Monta creada correctamente.', ['monta_id' => $uuid]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al crear monta: ' . $e->getMessage(), null, 500);
        }
    }



    // PUT /montas/cerrar/{monta_id}
    public function revision($params)
    {
        try {
            $this->model->revisionPeriodo($params['periodo']);
            $this->jsonResponse(true, 'Periodo cerrado correctamente');
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al modificar: ' . $e->getMessage(), null, 500);
        }
    }



    // DELETE /periodos_servicio/{periodo_id}
    public function eliminar(array $params): void
    {
        $periodo_id  = $params['periodo'] ?? '';
        if ($periodo_id  === '') {
            $this->jsonResponse(false, 'Parámetro periodo_id  es obligatorio.', null, 400);
        }
        try {
            $ok = $this->model->eliminar($periodo_id);
            if (!$ok) $this->jsonResponse(false, 'No se pudo eliminar (o ya estaba eliminada).', null, 400);
            $this->jsonResponse(true, 'Monta eliminada correctamente.', ['deleted' => true]);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al eliminar periodo: ' . $e->getMessage(), null, 500);
        }
    }
}
