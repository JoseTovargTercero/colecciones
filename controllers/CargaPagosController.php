<?php
require_once __DIR__ . '/../models/CargaPagosModel.php';

class CargaPagosController
{
    private $m;

    public function __construct()
    {
        $this->m = new CargaPagosModel();
    }

    private function res($v, $m, $d = null, $c = 200)
    {
        http_response_code($c);
        header('Content-Type: application/json');
        echo json_encode(['value' => $v, 'message' => $m, 'data' => $d]);
        exit;
    }

    public function procesar()
    {
        try {
            $d = $_POST;
            if (empty($d)) $d = json_decode(file_get_contents('php://input'), true) ?: [];
            $result = $this->m->procesar($d);
            $this->res(true, $result['message'], $result, 201);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 400);
        }
    }

    public function cuotas()
    {
        $empresa_id   = (int)($_GET['empresa_id'] ?? 0);
        $temporada_id = trim($_GET['temporada_id'] ?? '');
        $vendedor_id  = (int)($_GET['vendedor_id'] ?? 0);

        if (!$empresa_id || !$temporada_id || !$vendedor_id) {
            $this->res(false, 'Faltan parámetros.', null, 400);
        }

        try {
            $cuotas = $this->m->obtenerCuotasVendedor($empresa_id, $temporada_id, $vendedor_id);
            $deudaTotal = $this->m->obtenerDeudaTotal($empresa_id, $temporada_id, $vendedor_id);

            // Calcular deuda efectiva restando ganancia_vendedor de la última cuota de cada asignación
            $lastByAsig = [];
            foreach ($cuotas as $c) {
                $lastByAsig[(int)$c['asignacion_id']] = $c;
            }
            $descuentoTotal = 0;
            foreach ($cuotas as $c) {
                if ($lastByAsig[(int)$c['asignacion_id']]['id'] === $c['id']) {
                    $descuentoTotal += min((float)($c['ganancia_vendedor'] ?? 0), (float)$c['monto_pendiente']);
                }
            }
            $deudaEfectiva = max(0, $deudaTotal - $descuentoTotal);

            $data = [
                'cuotas' => $cuotas,
                'deuda_total' => $deudaTotal,
                'deuda_efectiva' => $deudaEfectiva,
            ];
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
            $data = $this->m->obtenerHistorialPagos($empresa_id, $temporada_id);
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }

    public function deuda()
    {
        $empresa_id   = (int)($_GET['empresa_id'] ?? 0);
        $temporada_id = trim($_GET['temporada_id'] ?? '');
        $vendedor_id  = (int)($_GET['vendedor_id'] ?? 0);

        if (!$empresa_id || !$temporada_id || !$vendedor_id) {
            $this->res(false, 'Faltan parámetros.', null, 400);
        }

        try {
            $cuotas = $this->m->obtenerCuotasCompletasVendedor($empresa_id, $temporada_id, $vendedor_id);
            $comprobantes = $this->m->obtenerComprobantesVendedor($empresa_id, $temporada_id, $vendedor_id);
            $premios = $this->m->obtenerPremiosVendedor($empresa_id, $temporada_id, $vendedor_id);
            $total = 0;
            $pagado = 0;
            $gananciaPorAsignacion = [];
            foreach ($cuotas as &$c) {
                $total += (float)$c['monto_a_pagar'];
                $pagado += (float)$c['monto_pagado'];
                $asigId = (int)$c['asignacion_id'];
                if (!isset($gananciaPorAsignacion[$asigId])) {
                    $gananciaPorAsignacion[$asigId] = (float)($c['ganancia_vendedor'] ?? 0);
                }
            }
            unset($c);
            $totalGanancia = array_sum($gananciaPorAsignacion);
            $pendiente = $total - $pagado;
            $pendienteEfectivo = max(0, $pendiente - $totalGanancia);
            $this->res(true, 'OK', [
                'cuotas' => $cuotas,
                'comprobantes' => $comprobantes,
                'premios' => $premios,
                'total_deuda' => $total,
                'total_pagado' => $pagado,
                'pendiente' => $pendiente,
                'total_ganancia_vendedor' => $totalGanancia,
                'pendiente_efectivo' => $pendienteEfectivo,
            ]);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }
}
