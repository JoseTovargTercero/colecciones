<?php
require_once __DIR__ . '/../config/Database.php';

class CargaPagosArticuloModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function procesar(array $d): array
    {
        $u = $_SESSION['user_id'] ?? '';

        $empresa_id     = (int)($d['empresa_id'] ?? 0);
        $temporada_id   = trim($d['temporada_id'] ?? '');
        $vendedor_id    = (int)($d['vendedor_id'] ?? 0);
        $tipo_pago      = $d['tipo_pago'] ?? '';
        $monto          = (float)($d['monto'] ?? 0);
        $numero_operacion = trim($d['numero_operacion'] ?? '');
        $fecha_pago     = trim($d['fecha_pago_comprobante'] ?? '');
        $cuota_id       = isset($d['cuota_id']) ? (int)$d['cuota_id'] : null;
        $monto_bs       = (float)($d['monto_bs'] ?? 0);
        $tasa_dia       = (float)($d['tasa_dia'] ?? 0);
        $comprobante    = $this->upload();
        if (!$comprobante && !empty($d['comprobante_existente'])) {
            $comprobante = $d['comprobante_existente'];
        }
        $pendiente_id = !empty($d['pendiente_id']) ? (int)$d['pendiente_id'] : null;

        $campos = [[$empresa_id, 'empresa'], [$temporada_id, 'temporada'], [$vendedor_id, 'vendedor'], [$tipo_pago, 'tipo_pago'], [$monto, 'monto']];

        foreach ($campos as $item) {
            if (!$item[0]) {
                throw new Exception($item[1] . ' Incorrecto: ' . $item[0]);
            }
        }

        $this->db->begin_transaction();

        try {
            if ($numero_operacion !== '') {
                $check = $this->db->prepare("SELECT COUNT(*) FROM comprobantes WHERE numero_operacion = ?");
                $check->bind_param('s', $numero_operacion);
                $check->execute();
                $cnt = 0;
                $check->bind_result($cnt);
                $check->fetch();
                $check->close();
                if ($cnt > 0) {
                    throw new Exception("El numero de operacionn {$numero_operacion} ya fue registrado en otro comprobante.");
                }
            }

            $stmt = $this->db->prepare(
                "INSERT INTO comprobantes (empresa_id, temporada_id, vendedor_id, cuota_id, monto, numero_operacion, comprobante, fecha_pago_comprobante, monto_bs, tasa_dia)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ississssdd', $empresa_id, $temporada_id, $vendedor_id, $cuota_id, $monto, $numero_operacion, $comprobante, $fecha_pago, $monto_bs, $tasa_dia);
            try {
                $stmt->execute();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062 && $numero_operacion !== null && $numero_operacion !== '') {
                    throw new Exception("El n\u00famero de operaci\u00f3n \u00ab{$numero_operacion}\u00bb ya fue registrado en otro comprobante.");
                }
                throw $e;
            }
            $comp_id = $this->db->insert_id;
            $stmt->close();

            $comp_str = $comp_id ? (string)$comp_id : null;

            $pagadoATiempo = false;

            if ($tipo_pago === 'total') {
                $pagadoATiempo = $this->procesarTotal($empresa_id, $temporada_id, $vendedor_id, $monto, $numero_operacion, $comp_str, $u, $fecha_pago);
            } elseif ($tipo_pago === 'cuota_exacta') {
                if (!$cuota_id) throw new Exception('Debe seleccionar una cuota.');
                $pagadoATiempo = $this->procesarCuotaExacta($cuota_id, $monto, $numero_operacion, $comp_str, $u, $fecha_pago);
            } elseif ($tipo_pago === 'abono') {
                $pagadoATiempo = $this->procesarAbono($empresa_id, $temporada_id, $vendedor_id, $monto, $numero_operacion, $comp_str, $u, $fecha_pago);
            } else {
                throw new Exception('Tipo de pago no implementado.');
            }

            if ($pendiente_id) {
                $del = $this->db->prepare("DELETE FROM comprobantes_pendientes WHERE id = ?");
                $del->bind_param('i', $pendiente_id);
                $del->execute();
                $del->close();
            }

            $this->db->commit();

            $message = 'Pago registrado correctamente.';
            if ($pagadoATiempo) {
                $message .= ' El vendedor cumpli\u00f3 con su pago a tiempo.';
            }
            return ['id' => $comp_id, 'message' => $message];
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function procesarTotal(int $empresa_id, string $temporada_id, int $vendedor_id, float $monto, ?string $numOp, ?string $comprobante, string $u, ?string $fecha_pago = null): bool
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.asignacion_id, c.numero_cuota, c.monto_a_pagar, c.monto_pendiente, c.fecha_pago
             FROM cuotas_articulo c
             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
             WHERE aa.vendedor_id = ? AND aa.temporada_id = ? AND aa.empresa_id = ? AND aa.usuario_id = ? AND aa.estado = 'activa'
             AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')
             ORDER BY c.fecha_pago ASC"
        );
        $stmt->bind_param('isss', $vendedor_id, $temporada_id, $empresa_id, $u);
        $stmt->execute();
        $r = $stmt->get_result();
        $cuotas = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($cuotas)) {
            throw new Exception('No hay cuotas pendientes.');
        }

        $totalPendiente = 0;
        foreach ($cuotas as $c) {
            $totalPendiente += (float)$c['monto_pendiente'];
        }

        $deudaEfectiva = $totalPendiente;
        if ($monto < $deudaEfectiva) {
            throw new Exception("El monto ($monto) no cubre la deuda total ($deudaEfectiva).");
        }

        $algunaATiempo = false;

        foreach ($cuotas as $c) {
            $cuotaId = (int)$c['id'];
            $pagadoATiempo = ($fecha_pago && $fecha_pago <= $c['fecha_pago']) ? 1 : 0;
            if ($pagadoATiempo) $algunaATiempo = true;

            $stmt = $this->db->prepare(
                "UPDATE cuotas_articulo
                 SET monto_pagado = monto_a_pagar,
                     monto_pendiente = 0,
                     estatus_pago = 'realizado',
                     fecha_pago = CURDATE(),
                     pagado_a_tiempo = ?,
                     comprobante = IF(? IS NULL, comprobante, IF(comprobante IS NULL OR comprobante = '', ?, CONCAT(comprobante, '|', ?)))
                 WHERE id = ?"
            );
            $stmt->bind_param('isssi', $pagadoATiempo, $comprobante, $comprobante, $comprobante, $cuotaId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $this->db->prepare(
            "UPDATE asignaciones_articulos
             SET estado = 'finalizada'
             WHERE vendedor_id = ? AND temporada_id = ? AND empresa_id = ? AND usuario_id = ? AND estado = 'activa'"
        );
        $stmt->bind_param('isss', $vendedor_id, $temporada_id, $empresa_id, $u);
        $stmt->execute();
        $stmt->close();

        return $algunaATiempo;
    }

    private function procesarCuotaExacta(int $cuota_id, float $monto, ?string $numOp, ?string $comprobante, string $u, ?string $fecha_pago = null): bool
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.fecha_pago, c.monto_a_pagar, c.monto_pendiente, c.asignacion_id,
                    aa.empresa_id, aa.temporada_id, aa.vendedor_id
             FROM cuotas_articulo c
             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
             WHERE c.id = ? AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')"
        );
        $stmt->bind_param('i', $cuota_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $cuota = $r->fetch_assoc();
        $stmt->close();

        if (!$cuota) throw new Exception('Cuota no encontrada o ya est\u00e1 pagada.');

        if ($monto < (float)$cuota['monto_pendiente']) {
            throw new Exception("El monto ($monto) no cubre el pendiente de la cuota ({$cuota['monto_pendiente']}).");
        }

        $pagadoATiempo = ($fecha_pago && $fecha_pago <= $cuota['fecha_pago']) ? 1 : 0;

        $stmt = $this->db->prepare(
            "UPDATE cuotas_articulo
             SET monto_pagado = monto_a_pagar,
                 monto_pendiente = 0,
                 estatus_pago = 'realizado',
                 fecha_pago = CURDATE(),
                 pagado_a_tiempo = ?,
                 comprobante = IF(? IS NULL, comprobante, IF(comprobante IS NULL OR comprobante = '', ?, CONCAT(comprobante, '|', ?)))
             WHERE id = ?"
        );
        $stmt->bind_param('isssi', $pagadoATiempo, $comprobante, $comprobante, $comprobante, $cuota_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total, SUM(CASE WHEN estatus_pago = 'realizado' THEN 1 ELSE 0 END) as pagadas
             FROM cuotas_articulo WHERE asignacion_id = ?"
        );
        $stmt->bind_param('i', $cuota['asignacion_id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $row = $r->fetch_assoc();
        $stmt->close();

        if ($row['total'] === $row['pagadas']) {
            $stmt = $this->db->prepare("UPDATE asignaciones_articulos SET estado = 'finalizada' WHERE id = ?");
            $stmt->bind_param('i', $cuota['asignacion_id']);
            $stmt->execute();
            $stmt->close();
        }

        return $pagadoATiempo;
    }

    private function procesarAbono(int $empresa_id, string $temporada_id, int $vendedor_id, float $monto, ?string $numOp, ?string $comprobante, string $u, ?string $fecha_pago = null): bool
    {
        $restante = $monto;

        $stmt = $this->db->prepare(
            "SELECT c.id, c.asignacion_id, c.monto_a_pagar, c.monto_pendiente, c.fecha_pago
             FROM cuotas_articulo c
             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
             WHERE aa.vendedor_id = ? AND aa.temporada_id = ? AND aa.empresa_id = ? AND aa.usuario_id = ? AND aa.estado = 'activa'
             AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')
             ORDER BY c.fecha_pago ASC"
        );
        $stmt->bind_param('isss', $vendedor_id, $temporada_id, $empresa_id, $u);
        $stmt->execute();
        $res = $stmt->get_result();
        $cuotas = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($cuotas)) throw new Exception('No hay cuotas pendientes para abonar.');

        $stmtFull = $this->db->prepare(
            "UPDATE cuotas_articulo
             SET monto_pagado = monto_a_pagar,
                 monto_pendiente = 0,
                 estatus_pago = 'realizado',
                 pagado_a_tiempo = ?,
                 comprobante = IF(? IS NULL, comprobante, IF(comprobante IS NULL OR comprobante = '', ?, CONCAT(comprobante, '|', ?)))
             WHERE id = ?"
        );

        $stmtPartial = $this->db->prepare(
            "UPDATE cuotas_articulo
             SET monto_pagado = monto_pagado + ?,
                 monto_pendiente = GREATEST(0, monto_pendiente - ?),
                 estatus_pago = 'pendiente',
                 comprobante = IF(? IS NULL, comprobante, IF(comprobante IS NULL OR comprobante = '', ?, CONCAT(comprobante, '|', ?)))
             WHERE id = ?"
        );

        $asignacionesFinalizadas = [];
        $algunaATiempo = false;

        foreach ($cuotas as $cuota) {
            if ($restante <= 0) break;

            $cuotaId = (int)$cuota['id'];
            $asigId = (int)$cuota['asignacion_id'];
            $pendienteReal = (float)$cuota['monto_pendiente'];

            if ($restante >= $pendienteReal) {
                $restante -= $pendienteReal;
                $pagadoATiempo = ($fecha_pago && $fecha_pago <= $cuota['fecha_pago']) ? 1 : 0;
                if ($pagadoATiempo) $algunaATiempo = true;
                $stmtFull->bind_param('isssi', $pagadoATiempo, $comprobante, $comprobante, $comprobante, $cuotaId);
                $stmtFull->execute();

                if (!isset($asignacionesFinalizadas[$asigId])) {
                    $asignacionesFinalizadas[$asigId] = 0;
                }
                $asignacionesFinalizadas[$asigId]++;
            } else {
                $pagadoAhora = $restante;
                $restante = 0;
                $stmtPartial->bind_param('ddsssi', $pagadoAhora, $pagadoAhora, $comprobante, $comprobante, $comprobante, $cuotaId);
                $stmtPartial->execute();
            }
        }
        $stmtFull->close();
        $stmtPartial->close();

        foreach ($asignacionesFinalizadas as $asigId => $pagadas) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as total, SUM(CASE WHEN estatus_pago = 'realizado' THEN 1 ELSE 0 END) as pagadas
                 FROM cuotas_articulo WHERE asignacion_id = ?"
            );
            $stmt->bind_param('i', $asigId);
            $stmt->execute();
            $r = $stmt->get_result();
            $row = $r->fetch_assoc();
            $stmt->close();

            if ((int)$row['total'] === (int)$row['pagadas']) {
                $stmt2 = $this->db->prepare("UPDATE asignaciones_articulos SET estado = 'finalizada' WHERE id = ?");
                $stmt2->bind_param('i', $asigId);
                $stmt2->execute();
                $stmt2->close();
            }
        }

        return $algunaATiempo;
    }

    private function upload(): ?string
    {
        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
            $name = uniqid() . '.' . $ext;
            $dir = __DIR__ . '/../uploads/comprobantes/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $dir . $name)) {
                return 'uploads/comprobantes/' . $name;
            }
        }
        return null;
    }

    public function obtenerCuotasVendedor(int $empresa_id, string $temporada_id, int $vendedor_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.numero_cuota, c.monto_a_pagar, c.monto_pendiente, c.fecha_pago, c.fecha_vencimiento, c.estatus_pago,
                    aa.id as asignacion_id, aa.monto_total,
                    (SELECT GROUP_CONCAT(a.nombre SEPARATOR ', ') FROM asignacion_articulo_detalle d JOIN articulos a ON d.articulo_id = a.id WHERE d.asignacion_id = aa.id) as articulos_nombre
             FROM cuotas_articulo c
             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
             WHERE aa.vendedor_id = ? AND aa.temporada_id = ? AND aa.empresa_id = ?
             AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')
             ORDER BY c.fecha_pago ASC"
        );
        $stmt->bind_param('isi', $vendedor_id, $temporada_id, $empresa_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function obtenerCuotasCompletasVendedor(int $empresa_id, string $temporada_id, int $vendedor_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.numero_cuota, c.monto_a_pagar, c.monto_pagado, c.monto_pendiente,
                    c.fecha_pago, c.fecha_vencimiento, c.estatus_pago,
                    aa.id as asignacion_id, aa.monto_total,
                    (SELECT GROUP_CONCAT(a.nombre SEPARATOR ', ') FROM asignacion_articulo_detalle d JOIN articulos a ON d.articulo_id = a.id WHERE d.asignacion_id = aa.id) as articulos_nombre
             FROM cuotas_articulo c
             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
             WHERE aa.vendedor_id = ? AND aa.temporada_id = ? AND aa.empresa_id = ?
             AND aa.estado IN ('activa','finalizada')
             ORDER BY c.fecha_pago ASC"
        );
        $stmt->bind_param('isi', $vendedor_id, $temporada_id, $empresa_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function obtenerComprobantesVendedor(int $empresa_id, string $temporada_id, int $vendedor_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT cp.id, cp.cuota_id, cp.monto, cp.numero_operacion, cp.comprobante, cp.fecha_pago_comprobante, cp.created_at,
                    cp.monto_bs, cp.tasa_dia,
                    c.numero_cuota
             FROM comprobantes cp
             INNER JOIN cuotas_articulo c ON cp.cuota_id = c.id
             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
             WHERE cp.empresa_id = ? AND cp.temporada_id = ? AND cp.vendedor_id = ?
             AND aa.estado IN ('activa','finalizada')
             ORDER BY cp.created_at DESC"
        );
        $stmt->bind_param('isi', $empresa_id, $temporada_id, $vendedor_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function obtenerDeudaTotal(int $empresa_id, string $temporada_id, int $vendedor_id): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(c.monto_pendiente), 0) as total
             FROM cuotas_articulo c
             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
             WHERE aa.vendedor_id = ? AND aa.temporada_id = ? AND aa.empresa_id = ?
             AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')"
        );
        $stmt->bind_param('isi', $vendedor_id, $temporada_id, $empresa_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $row = $r->fetch_assoc();
        $stmt->close();
        return (float)$row['total'];
    }
}
