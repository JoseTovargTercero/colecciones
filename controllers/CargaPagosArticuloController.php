<?php
require_once __DIR__ . '/../models/CargaPagosArticuloModel.php';

class CargaPagosArticuloController {
    private $m;
    public function __construct() { $this->m = new CargaPagosArticuloModel(); }
    private function res($v,$m,$d=null,$c=200) { http_response_code($c); header('Content-Type: application/json'); echo json_encode(['value'=>$v,'message'=>$m,'data'=>$d]); exit; }

    public function procesar() {
        try {
            $d = $_POST;
            if (empty($d)) $d = json_decode(file_get_contents('php://input'), true) ?: [];
            $r = $this->m->procesar($d);
            $this->res(true, $r['message'], $r);
        } catch (Throwable $e) { $this->res(false, $e->getMessage(), null, 400); }
    }

    public function cuotas() {
        try {
            $empresa_id = (int)($_GET['empresa_id'] ?? 0);
            $temporada_id = $_GET['temporada_id'] ?? '';
            $vendedor_id = (int)($_GET['vendedor_id'] ?? 0);
            if (!$empresa_id || !$temporada_id || !$vendedor_id) $this->res(false, 'Faltan parametros', null, 400);

$cuotas = $this->m->obtenerCuotasVendedor($empresa_id, $temporada_id, $vendedor_id);
            $deuda_total = $this->m->obtenerDeudaTotal($empresa_id, $temporada_id, $vendedor_id);

            $this->res(true, 'OK', [
                'cuotas' => $cuotas,
                'deuda_total' => $deuda_total,
                'deuda_efectiva' => $deuda_total,
            ]);
        } catch (Throwable $e) { $this->res(false, $e->getMessage(), null, 500); }
    }

    public function deuda() {
        try {
            $empresa_id = (int)($_GET['empresa_id'] ?? 0);
            $temporada_id = $_GET['temporada_id'] ?? '';
            $vendedor_id = (int)($_GET['vendedor_id'] ?? 0);
            if (!$empresa_id || !$temporada_id || !$vendedor_id) $this->res(false, 'Faltan parametros', null, 400);

            $cuotas = $this->m->obtenerCuotasCompletasVendedor($empresa_id, $temporada_id, $vendedor_id);
            $comprobantes = $this->m->obtenerComprobantesVendedor($empresa_id, $temporada_id, $vendedor_id);

            $total_deuda = 0; $total_pagado = 0; $pendiente = 0;
            foreach ($cuotas as $c) {
                $total_deuda += (float)$c['monto_a_pagar'];
                $total_pagado += (float)$c['monto_pagado'];
                $pendiente += (float)$c['monto_pendiente'];
            }

            $this->res(true, 'OK', [
                'cuotas' => $cuotas,
                'comprobantes' => $comprobantes,
                'total_deuda' => $total_deuda,
                'total_pagado' => $total_pagado,
                'pendiente' => $pendiente,
                'pendiente_efectivo' => $pendiente,
                'total_ganancia_vendedor' => 0,
            ]);
        } catch (Throwable $e) { $this->res(false, $e->getMessage(), null, 500); }
    }
}
