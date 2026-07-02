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
}
