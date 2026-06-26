<?php
require_once __DIR__ . '/../models/EmpresaModel.php';

class EmpresaController
{
    private $model;

    public function __construct()
    {
        $this->model = new EmpresaModel();
    }

    // ponytail: helpers reutilizados de MenuController
    private function json($value, string $msg = '', $data = null, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['value' => $value, 'message' => $msg, 'data' => $data]);
        exit;
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $j   = json_decode($raw, true);
        return is_array($j) ? $j : [];
    }

    // GET /api/empresas
    public function listar(): void
    {
        try {
            $this->json(true, 'OK', $this->model->listar());
        } catch (Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // GET /api/empresas/{id}
    public function mostrar(array $p): void
    {
        $id = $p['id'] ?? '';
        if (!$id) $this->json(false, 'ID requerido.', null, 400);
        $row = $this->model->obtenerPorId($id);
        $row ? $this->json(true, 'OK', $row) : $this->json(false, 'No encontrado.', null, 404);
    }

    // POST /api/empresas
    public function crear(): void
    {
        try {
            $id = $this->model->crear($this->input());
            $this->json(true, 'Empresa creada.', ['id' => $id], 201);
        } catch (InvalidArgumentException $e) {
            $this->json(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // POST /api/empresas/{id}
    public function actualizar(array $p): void
    {
        $id = $p['id'] ?? '';
        if (!$id) $this->json(false, 'ID requerido.', null, 400);
        try {
            $ok = $this->model->actualizar($id, $this->input());
            $this->json(true, 'Empresa actualizada.', ['updated' => $ok]);
        } catch (InvalidArgumentException $e) {
            $this->json(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // DELETE /api/empresas/{id}
    public function eliminar(array $p): void
    {
        $id = $p['id'] ?? '';
        if (!$id) $this->json(false, 'ID requerido.', null, 400);
        try {
            $ok = $this->model->eliminar($id);
            $ok
                ? $this->json(true, 'Empresa eliminada.', ['deleted' => true])
                : $this->json(false, 'No encontrada o ya eliminada.', null, 404);
        } catch (Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }
}

