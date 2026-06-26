<?php
require_once __DIR__ . '/../models/TabuladorPesoModel.php';

class TabuladorPesoController
{
    private $model;

    public function __construct()
    {
        $this->model = new TabuladorPesoModel();
    }

    private function getJsonInput(): array
    {
        $raw  = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    private function jsonResponse($value, string $message = '', $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'value'   => $value,
            'message' => $message,
            'data'    => $data
        ]);
        exit;
    }

    // GET /tabuladores_peso?raza_id=&edad_dias=&limit=&offset=
    public function listar(): void
    {
        $limit   = isset($_GET['limit']) ? (int)$_GET['limit'] : 10000;
        $offset  = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $razaId  = isset($_GET['raza_id']) ? trim((string)$_GET['raza_id']) : null;
        $edad    = isset($_GET['edad_dias']) && $_GET['edad_dias'] !== '' ? (int)$_GET['edad_dias'] : null;

        try {
            $data = $this->model->listar($limit, $offset, $razaId, $edad);
            $this->jsonResponse(true, 'Listado de tabuladores obtenido correctamente.', $data);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar tabuladores: '.$e->getMessage(), null, 500);
        }
    }

    // GET /tabuladores_peso/{tab_peso_id}
    public function mostrar(array $params): void
    {
        $id = $params['tab_peso_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro tab_peso_id es obligatorio.', null, 400);
        }
        try {
            $row = $this->model->obtenerPorId($id);
            if (!$row) $this->jsonResponse(false, 'Tabulador no encontrado.', null, 404);
            $this->jsonResponse(true, 'Tabulador encontrado.', $row);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al obtener tabulador: '.$e->getMessage(), null, 500);
        }
    }

    // POST /tabuladores_peso
    // JSON: { raza_id, edad_min_dias, edad_max_dias, peso_ideal, margen_min, margen_max }
    public function crear(): void
    {
        $in = $this->getJsonInput();
        try {
            $uuid = $this->model->crear($in);
            $this->jsonResponse(true, 'Tabulador creado correctamente.', ['tab_peso_id' => $uuid]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al crear tabulador: '.$e->getMessage(), null, 500);
        }
    }

    // POST /tabuladores_peso/{tab_peso_id}
    // JSON (cualquiera de estos): { raza_id?, edad_min_dias?, edad_max_dias?, peso_ideal?, margen_min?, margen_max? }
    public function actualizar(array $params): void
    {
        $id = $params['tab_peso_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro tab_peso_id es obligatorio.', null, 400);
        }

        $in = $this->getJsonInput();
        try {
            $ok = $this->model->actualizar($id, $in);
            $this->jsonResponse(true, 'Tabulador actualizado correctamente.', ['updated' => $ok]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al actualizar tabulador: '.$e->getMessage(), null, 500);
        }
    }

    // DELETE /tabuladores_peso/{tab_peso_id}
    public function eliminar(array $params): void
    {
        $id = $params['tab_peso_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro tab_peso_id es obligatorio.', null, 400);
        }

        try {
            $ok = $this->model->eliminar($id);
            if (!$ok) $this->jsonResponse(false, 'No se pudo eliminar (no existe).', null, 400);
            $this->jsonResponse(true, 'Tabulador eliminado correctamente.', ['deleted' => true]);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al eliminar tabulador: '.$e->getMessage(), null, 500);
        }
    }
}
