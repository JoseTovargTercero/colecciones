<?php
require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardController
{
    private $m;

    public function __construct()
    {
        $this->m = new DashboardModel();
    }

    private function res($v, $m, $d = null, $c = 200)
    {
        http_response_code($c);
        header('Content-Type: application/json');
        echo json_encode(['value' => $v, 'message' => $m, 'data' => $d]);
        exit;
    }

    public function kpis()
    {
        $empresa_id   = isset($_GET['empresa_id'])   ? (int)$_GET['empresa_id']   : null;
        $temporada_id = isset($_GET['temporada_id']) ? trim($_GET['temporada_id']) : null;
        try {
            $data = $this->m->kpis($empresa_id, $temporada_id);
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }

    public function kpisVendedor()
    {
        $empresa_id   = isset($_GET['empresa_id'])   ? (int)$_GET['empresa_id']   : null;
        $temporada_id = isset($_GET['temporada_id']) ? trim($_GET['temporada_id']) : null;
        try {
            $data = $this->m->kpisVendedor($empresa_id, $temporada_id);
            $data['_debug'] = [
                'session_user_id' => $_SESSION['user_id'] ?? '',
                'session_nombre'  => $_SESSION['nombre'] ?? '',
                'session_tipo'    => $_SESSION['tipo'] ?? '',
            ];
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }
}
