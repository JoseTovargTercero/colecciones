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

    public function misDeudas()
    {
        try {
            $userId = $_SESSION['user_id'] ?? '';
            if (!$userId) {
                $this->res(false, 'Sesión no iniciada.', null, 401);
            }

            $data = $this->m->obtenerMisDeudas($userId);

            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, 'Error al cargar tus deudas: ' . $e->getMessage(), null, 500);
        }
    }

    public function solicitarPendiente()
    {
        try {
            $d = $_POST;
            if (empty($d)) $d = json_decode(file_get_contents('php://input'), true) ?: [];

            $empresa_id       = (int)($d['empresa_id'] ?? 0);
            $temporada_id     = trim($d['temporada_id'] ?? '');
            $vendedor_id      = (int)($d['vendedor_id'] ?? 0);
            $monto            = (float)($d['monto'] ?? 0);
            $numero_operacion = trim($d['numero_operacion'] ?? '');
            $fecha_pago       = trim($d['fecha_pago_comprobante'] ?? '');
            $cuota_id         = $d['cuota_id'];
            $monto_bs         = (float)($d['monto_bs'] ?? 0);
            $tasa_dia         = (float)($d['tasa_dia'] ?? 0);

            // Upload comprobante
            $comprobante = null;
            if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
                $name = uniqid() . '.' . $ext;
                $dir = __DIR__ . '/../uploads/comprobantes/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $dir . $name)) {
                    $comprobante = 'uploads/comprobantes/' . $name;
                }
            }

            if (!$empresa_id || !$temporada_id || !$vendedor_id || $monto <= 0) {
                throw new Exception('Faltan datos obligatorios.');
            }

            // Verificar duplicado de numero_operacion
            if ($numero_operacion !== '') {
                $check = $this->m->getDb()->prepare("SELECT COUNT(*) FROM comprobantes_pendientes WHERE numero_operacion = ?");
                $check->bind_param('s', $numero_operacion);
                $check->execute();
                $cnt = 0;
                $check->bind_result($cnt);
                $check->fetch();
                $check->close();
                if ($cnt > 0) {
                    throw new Exception("El numero de operacion «{$numero_operacion}» ya fue registrado.");
                }
            }

            // Obtener gerente_id del vendedor
            $gerente_id = null;
            $stmtG = $this->m->getDb()->prepare("SELECT usuario_id FROM vendedores WHERE id = ?");
            $stmtG->bind_param('i', $vendedor_id);
            $stmtG->execute();
            $rG = $stmtG->get_result();
            if ($rowG = $rG->fetch_assoc()) {
                $gerente_id = $rowG['usuario_id'];
            }
            $stmtG->close();

            $stmt = $this->m->getDb()->prepare(
                "INSERT INTO comprobantes_pendientes (empresa_id, temporada_id, vendedor_id, cuota_id, monto, numero_operacion, comprobante, fecha_pago_comprobante, monto_bs, tasa_dia, status, gerente_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?)"
            );
            $stmt->bind_param('isssssssdsi', $empresa_id, $temporada_id, $vendedor_id, $cuota_id, $monto, $numero_operacion, $comprobante, $fecha_pago, $monto_bs, $tasa_dia, $gerente_id);
            $stmt->execute();
            $id = $this->m->getDb()->insert_id;
            $stmt->close();

            $this->res(true, 'Solicitud de pago registrada. Pendiente de aprobaci\u00f3n.', ['id' => $id], 201);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 400);
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
