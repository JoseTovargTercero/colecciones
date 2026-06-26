<?php
require_once __DIR__ . '/../models/RazaModel.php';

class RazaController
{
    private RazaModel $model;

    public function __construct()
    {
        $this->model = new RazaModel();
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

    // GET /razas?especie=&estado=&q=&incluirEliminados=0|1&limit=&offset=
    public function listar(): void
    {
        $limit   = isset($_GET['limit']) ? (int)$_GET['limit'] : 10000;
        $offset  = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $especie = isset($_GET['especie']) ? (string)$_GET['especie'] : null;
        $estado  = isset($_GET['estado'])  ? (string)$_GET['estado']  : null;
        $q       = isset($_GET['q'])       ? (string)$_GET['q']       : null;
        $incDel  = isset($_GET['incluirEliminados']) ? ((int)$_GET['incluirEliminados'] === 1) : false;

        try {
            $rows = $this->model->listar($limit, $offset, $especie, $estado, $q, $incDel);
            $this->jsonResponse(true, 'Listado de razas obtenido correctamente.', $rows);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar razas: '.$e->getMessage(), null, 500);
        }
    }

    // GET /razas/{raza_id}
    public function mostrar(array $params): void
    {
        $id = $params['raza_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro raza_id es obligatorio.', null, 400);
        }
        try {
            $row = $this->model->obtenerPorId($id);
            if (!$row) $this->jsonResponse(false, 'Raza no encontrada.', null, 404);
            $this->jsonResponse(true, 'Raza encontrada.', $row);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al obtener raza: '.$e->getMessage(), null, 500);
        }
    }

    // POST /razas
    // JSON: { especie, nombre, estado, codigo?, descripcion? }
    public function crear(): void
    {
        $in = $this->getJsonInput();
        try {
            $uuid = $this->model->crear($in);
            $this->jsonResponse(true, 'Raza creada correctamente.', ['raza_id' => $uuid]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al crear raza: '.$e->getMessage(), null, 500);
        }
    }

    // POST /razas/{raza_id}
    // JSON: { especie?, nombre?, estado?, codigo?, descripcion? }
    public function actualizar(array $params): void
    {
        $id = $params['raza_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro raza_id es obligatorio.', null, 400);
        }
        $in = $this->getJsonInput();
        try {
            $ok = $this->model->actualizar($id, $in);
            $this->jsonResponse(true, 'Raza actualizada correctamente.', ['updated' => $ok]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al actualizar raza: '.$e->getMessage(), null, 500);
        }
    }

    // DELETE /razas/{raza_id}  (soft delete)
    public function eliminar(array $params): void
    {
        $id = $params['raza_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro raza_id es obligatorio.', null, 400);
        }
        try {
            $ok = $this->model->eliminar($id);
            if (!$ok) $this->jsonResponse(false, 'No se pudo eliminar (o ya estaba eliminada).', null, 400);
            $this->jsonResponse(true, 'Raza eliminada correctamente.', ['deleted' => true]);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al eliminar raza: '.$e->getMessage(), null, 500);
        }
    }

    // POST /razas/{raza_id}/restaurar
    public function restaurar(array $params): void
    {
        $id = $params['raza_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro raza_id es obligatorio.', null, 400);
        }
        try {
            $ok = $this->model->restaurar($id);
            if (!$ok) $this->jsonResponse(false, 'No se pudo restaurar (no está eliminada).', null, 400);
            $this->jsonResponse(true, 'Raza restaurada correctamente.', ['restored' => true]);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al restaurar raza: '.$e->getMessage(), null, 500);
        }
    }
}
