<?php
require_once __DIR__ . '/../models/ControlPagosModel.php';

class ControlPagosController
{
    private $m;

    public function __construct()
    {
        $this->m = new ControlPagosModel();
    }

    private function res($v, $m, $d = null, $c = 200)
    {
        http_response_code($c);
        header('Content-Type: application/json');
        echo json_encode(['value' => $v, 'message' => $m, 'data' => $d]);
        exit;
    }

    public function listar()
    {
        $empresa_id   = $_GET['empresa_id'] ?? '';
        $temporada_id = $_GET['temporada_id'] ?? '';

        if (!$empresa_id || !$temporada_id) {
            $this->res(false, 'empresa_id y temporada_id requeridos.', null, 400);
        }

        try {
            $data = $this->m->listar($empresa_id, $temporada_id);
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }

    public function historial()
    {
        $empresa_id   = (int)($_GET['empresa_id'] ?? 0);
        $temporada_id = trim($_GET['temporada_id'] ?? '');

        if (!$empresa_id || !$temporada_id) {
            $this->res(false, 'Faltan parámetros empresa_id y temporada_id.', null, 400);
        }

        try {
            $data = $this->m->historial($empresa_id, $temporada_id);
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }

    public function premioInfo()
    {
        $vendedor_id = $_GET['vendedor_id'] ?? '';
        $empresa_id = $_GET['empresa_id'] ?? '';
        $temporada_id = $_GET['temporada_id'] ?? '';

        if (!$vendedor_id || !$empresa_id || !$temporada_id) {
            $this->res(false, 'Faltan parámetros requeridos.', null, 400);
        }

        try {
            $data = $this->m->getPremioInfo($vendedor_id, $empresa_id, $temporada_id);
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }

    public function solicitarPremio()
    {
        $vendedor_id = $_POST['vendedor_id'] ?? '';
        $empresa_id = $_POST['empresa_id'] ?? '';
        $temporada_id = $_POST['temporada_id'] ?? '';
        $premio_id = $_POST['premio_id'] ?? '';

        if (!$vendedor_id || !$empresa_id || !$temporada_id || !$premio_id) {
            $this->res(false, 'Faltan parámetros requeridos.', null, 400);
        }

        try {
            $u = $_SESSION['user_id'] ?? '';
            $data = $this->m->solicitarPremio($vendedor_id, $empresa_id, $temporada_id, $premio_id, $u);
            $this->res(true, 'Premio solicitado correctamente.', $data);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }
}
