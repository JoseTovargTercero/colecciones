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
        try {
            $data = $this->m->kpis();
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }
}
