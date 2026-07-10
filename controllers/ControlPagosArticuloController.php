<?php
require_once __DIR__ . '/../models/ControlPagosArticuloModel.php';

class ControlPagosArticuloController {
    private $m;
    public function __construct() { $this->m = new ControlPagosArticuloModel(); }
    private function res($v,$m,$d=null,$c=200) { http_response_code($c); header('Content-Type: application/json'); echo json_encode(['value'=>$v,'message'=>$m,'data'=>$d]); exit; }

    public function listar() {
        try {
            $empresa_id = $_GET['empresa_id'] ?? '';
            $temporada_id = $_GET['temporada_id'] ?? '';
            if (!$empresa_id || !$temporada_id) $this->res(false, 'empresa_id y temporada_id requeridos', null, 400);
            $this->res(true, 'OK', $this->m->listar($empresa_id, $temporada_id));
        } catch (Throwable $e) { $this->res(false, $e->getMessage(), null, 500); }
    }

    public function historial() {
        try {
            $empresa_id = (int)($_GET['empresa_id'] ?? 0);
            $temporada_id = $_GET['temporada_id'] ?? '';
            if (!$empresa_id || !$temporada_id) $this->res(false, 'empresa_id y temporada_id requeridos', null, 400);
            $this->res(true, 'OK', $this->m->historial($empresa_id, $temporada_id));
        } catch (Throwable $e) { $this->res(false, $e->getMessage(), null, 500); }
    }
}
