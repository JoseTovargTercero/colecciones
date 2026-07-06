<?php
require_once __DIR__ . '/../config/Database.php';

class CargaPagosModel
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


        $campos = [[$empresa_id, 'empresa'], [$temporada_id, 'temporada'], [$vendedor_id, 'vendedor'], [$tipo_pago, 'tipo_pago'], [$monto, 'monto']];

        foreach ($campos as $item) {
            if (!$item[0]) {
                throw new Exception($item[1] . ' Incorrecto: ' . $item[0]);
            }
        }

        /*
        if (!$empresa_id || !$temporada_id || !$vendedor_id || !$tipo_pago || $monto <= 0) {
            throw new Exception('Faltan datos obligatorios o monto invalido.');
        }
*/
        $this->db->begin_transaction();

        try {
            // Verificar si el numero_operacion ya existe (si fue proporcionado)
            if ($numero_operacion !== '') {
                $check = $this->db->prepare("SELECT COUNT(*) FROM comprobantes WHERE numero_operacion = ?");
                $check->bind_param('s', $numero_operacion);
                $check->execute();
                $cnt = 0;
                $check->bind_result($cnt);
                $check->fetch();
                $check->close();
                if ($cnt > 0) {
                    throw new Exception("El número de operación «{$numero_operacion}» ya fue registrado en otro comprobante.");
                }
            }

            // Guardar comprobante primero para obtener el ID
            $stmt = $this->db->prepare(
                "INSERT INTO comprobantes (empresa_id, temporada_id, vendedor_id, cuota_id, monto, numero_operacion, comprobante, fecha_pago_comprobante, monto_bs, tasa_dia)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ississssdd', $empresa_id, $temporada_id, $vendedor_id, $cuota_id, $monto, $numero_operacion, $comprobante, $fecha_pago, $monto_bs, $tasa_dia);
            try {
                $stmt->execute();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062 && $numero_operacion !== null && $numero_operacion !== '') {
                    throw new Exception("El número de operación «{$numero_operacion}» ya fue registrado en otro comprobante.");
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

            $this->db->commit();

            $message = 'Pago registrado correctamente.';
            if ($pagadoATiempo) {
                $message .= ' El vendedor cumplió con su pago a tiempo.';
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
            "SELECT c.id, c.asignacion_id, c.numero_cuota, c.monto_a_pagar, c.monto_pendiente, c.fecha_pago,
                    ac.ganancia_vendedor
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND ac.estado = 'activa'
             AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')
             ORDER BY c.fecha_pago ASC"
        );
        $stmt->bind_param('is', $vendedor_id, $temporada_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $cuotas = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($cuotas)) {
            throw new Exception('No hay cuotas pendientes.');
        }

        $lastCuotaPorAsignacion = [];
        foreach ($cuotas as $c) {
            $lastCuotaPorAsignacion[(int)$c['asignacion_id']] = $c;
        }

        $totalPendiente = 0;
        $totalDescuento = 0;
        foreach ($cuotas as $c) {
            $pe = (float)$c['monto_pendiente'];
            $totalPendiente += $pe;
            $asigId = (int)$c['asignacion_id'];
            if ($lastCuotaPorAsignacion[$asigId]['id'] === $c['id']) {
                $gv = (float)($c['ganancia_vendedor'] ?? 0);
                $totalDescuento += min($gv, $pe);
            }
        }

        $deudaEfectiva = $totalPendiente - $totalDescuento;
        if ($monto < $deudaEfectiva) {
            throw new Exception("El monto ($monto) no cubre la deuda total efectiva ($deudaEfectiva).");
        }

        $algunaATiempo = false;

        foreach ($cuotas as $c) {
            $cuotaId = (int)$c['id'];
            $asigId = (int)$c['asignacion_id'];
            $montoAPagar = (float)$c['monto_a_pagar'];
            $gv = (float)($c['ganancia_vendedor'] ?? 0);
            $esUltima = $lastCuotaPorAsignacion[$asigId]['id'] === $cuotaId;
            $pagadoATiempo = ($fecha_pago && $fecha_pago <= $c['fecha_pago']) ? 1 : 0;
            if ($pagadoATiempo) $algunaATiempo = true;

            if ($esUltima && $gv > 0) {
                $nuevoPagado = max(0, $montoAPagar - $gv);
                $stmt = $this->db->prepare(
                    "UPDATE cuotas_coleccion
                     SET monto_pagado = ?,
                         monto_pendiente = 0,
                         estatus_pago = 'realizado',
                         fecha_pago = CURDATE(),
                         pagado_a_tiempo = ?,
                         comprobante = IF(? IS NULL, comprobante, IF(comprobante IS NULL OR comprobante = '', ?, CONCAT(comprobante, '|', ?)))
                     WHERE id = ?"
                );
                $stmt->bind_param('disssi', $nuevoPagado, $pagadoATiempo, $comprobante, $comprobante, $comprobante, $cuotaId);
            } else {
                $stmt = $this->db->prepare(
                    "UPDATE cuotas_coleccion
                     SET monto_pagado = monto_a_pagar,
                         monto_pendiente = 0,
                         estatus_pago = 'realizado',
                         fecha_pago = CURDATE(),
                         pagado_a_tiempo = ?,
                         comprobante = IF(? IS NULL, comprobante, IF(comprobante IS NULL OR comprobante = '', ?, CONCAT(comprobante, '|', ?)))
                     WHERE id = ?"
                );
                $stmt->bind_param('isssi', $pagadoATiempo, $comprobante, $comprobante, $comprobante, $cuotaId);
            }
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $this->db->prepare(
            "UPDATE asignaciones_colecciones
             SET estado = 'finalizada'
             WHERE vendedor_id = ? AND temporada_id = ? AND estado = 'activa'"
        );
        $stmt->bind_param('is', $vendedor_id, $temporada_id);
        $stmt->execute();
        $stmt->close();

        $this->verificarYCompletarPremios($empresa_id, $temporada_id, $vendedor_id);

        return $algunaATiempo;
    }

    private function procesarCuotaExacta(int $cuota_id, float $monto, ?string $numOp, ?string $comprobante, string $u, ?string $fecha_pago = null): bool
    {
        // Obtener cuota
        $stmt = $this->db->prepare(
            "SELECT c.id, c.fecha_pago, c.monto_a_pagar, c.monto_pendiente, c.asignacion_id,
                    cc.empresa_id, ac.temporada_id, ac.vendedor_id
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
             WHERE c.id = ? AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')"
        );
        $stmt->bind_param('i', $cuota_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $cuota = $r->fetch_assoc();
        $stmt->close();

        if (!$cuota) throw new Exception('Cuota no encontrada o ya estÃ¡ pagada.');

        if ($monto < (float)$cuota['monto_pendiente']) {
            throw new Exception("El monto ($monto) no cubre el pendiente de la cuota ({$cuota['monto_pendiente']}).");
        }

        $pagadoATiempo = ($fecha_pago && $fecha_pago <= $cuota['fecha_pago']) ? 1 : 0;

        // Actualizar cuota
        $stmt = $this->db->prepare(
            "UPDATE cuotas_coleccion
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

        // Verificar si todas las cuotas de la asignaciÃ³n estÃ¡n pagadas
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total, SUM(CASE WHEN estatus_pago = 'realizado' THEN 1 ELSE 0 END) as pagadas
             FROM cuotas_coleccion WHERE asignacion_id = ?"
        );
        $stmt->bind_param('i', $cuota['asignacion_id']);
        $stmt->execute();
        $r = $stmt->get_result();
        $row = $r->fetch_assoc();
        $stmt->close();

        if ($row['total'] === $row['pagadas']) {
            $stmt = $this->db->prepare("UPDATE asignaciones_colecciones SET estado = 'finalizada' WHERE id = ?");
            $stmt->bind_param('i', $cuota['asignacion_id']);
            $stmt->execute();
            $stmt->close();

            $this->verificarYCompletarPremios((int)$cuota['empresa_id'], $cuota['temporada_id'], (int)$cuota['vendedor_id']);
        }

        return $pagadoATiempo;
    }

    private function procesarAbono(int $empresa_id, string $temporada_id, int $vendedor_id, float $monto, ?string $numOp, ?string $comprobante, string $u, ?string $fecha_pago = null): bool
    {
        $restante = $monto;

        // Obtener cuotas pendientes con ganancia_vendedor
        $stmt = $this->db->prepare(
            "SELECT c.id, c.asignacion_id, c.monto_a_pagar, c.monto_pendiente, c.fecha_pago,
                    ac.ganancia_vendedor
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND ac.estado = 'activa'
             AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')
             ORDER BY c.fecha_pago ASC"
        );
        $stmt->bind_param('is', $vendedor_id, $temporada_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $cuotas = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($cuotas)) throw new Exception('No hay cuotas pendientes para abonar.');

        // Identificar la Ãºltima cuota de cada asignaciÃ³n
        $lastCuotaPorAsignacion = [];
        foreach ($cuotas as $c) {
            $lastCuotaPorAsignacion[(int)$c['asignacion_id']] = $c;
        }

        $stmtFull = $this->db->prepare(
            "UPDATE cuotas_coleccion
             SET monto_pagado = ?,
                 monto_pendiente = 0,
                 estatus_pago = 'realizado',
                 pagado_a_tiempo = ?,
                 comprobante = IF(? IS NULL, comprobante, IF(comprobante IS NULL OR comprobante = '', ?, CONCAT(comprobante, '|', ?)))
             WHERE id = ?"
        );

        $stmtPartial = $this->db->prepare(
            "UPDATE cuotas_coleccion
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
            $montoAPagar = (float)$cuota['monto_a_pagar'];
            $pendienteReal = (float)$cuota['monto_pendiente'];
            $ganancia = (float)($cuota['ganancia_vendedor'] ?? 0);
            $esUltima = $lastCuotaPorAsignacion[$asigId]['id'] === $cuotaId;
            $descuento = ($esUltima && $ganancia > 0) ? min($ganancia, $pendienteReal) : 0;
            $pendienteEfectivo = $pendienteReal - $descuento;

            if ($restante >= $pendienteEfectivo) {
                $pagadoAhora = $pendienteEfectivo;
                $restante -= $pagadoAhora;
                $nuevoPagado = $montoAPagar - $descuento;
                $pagadoATiempo = ($fecha_pago && $fecha_pago <= $cuota['fecha_pago']) ? 1 : 0;
                if ($pagadoATiempo) $algunaATiempo = true;
                $stmtFull->bind_param('disssi', $nuevoPagado, $pagadoATiempo, $comprobante, $comprobante, $comprobante, $cuotaId);
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

        // Finalizar asignaciones donde todas las cuotas estÃ©n pagadas
        foreach ($asignacionesFinalizadas as $asigId => $pagadas) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as total, SUM(CASE WHEN estatus_pago = 'realizado' THEN 1 ELSE 0 END) as pagadas
                 FROM cuotas_coleccion WHERE asignacion_id = ?"
            );
            $stmt->bind_param('i', $asigId);
            $stmt->execute();
            $r = $stmt->get_result();
            $row = $r->fetch_assoc();
            $stmt->close();

            if ((int)$row['total'] === (int)$row['pagadas']) {
                $stmt2 = $this->db->prepare("UPDATE asignaciones_colecciones SET estado = 'finalizada' WHERE id = ?");
                $stmt2->bind_param('i', $asigId);
                $stmt2->execute();
                $stmt2->close();
            }
        }

        $this->verificarYCompletarPremios($empresa_id, $temporada_id, $vendedor_id);

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
                    ac.id as asignacion_id, ac.ganancia_vendedor,
                    cc.nombre as coleccion_nombre
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
             WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND cc.empresa_id = ?
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
                    ac.id as asignacion_id, ac.ganancia_vendedor,
                    cc.nombre as coleccion_nombre
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
             WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND cc.empresa_id = ?
             AND ac.estado IN ('activa','finalizada')
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
             INNER JOIN cuotas_coleccion c ON cp.cuota_id = c.id
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             WHERE cp.empresa_id = ? AND cp.temporada_id = ? AND cp.vendedor_id = ?
             AND ac.estado IN ('activa','finalizada')
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
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
             WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND cc.empresa_id = ?
             AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')"
        );
        $stmt->bind_param('isi', $vendedor_id, $temporada_id, $empresa_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $row = $r->fetch_assoc();
        $stmt->close();
        return (float)$row['total'];
    }

    private function verificarYCompletarPremios(int $empresa_id, string $temporada_id, int $vendedor_id): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as activas
             FROM asignaciones_colecciones ac
             INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
             WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND cc.empresa_id = ?
             AND ac.estado != 'finalizada'"
        );
        $stmt->bind_param('isi', $vendedor_id, $temporada_id, $empresa_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $row = $r->fetch_assoc();
        $stmt->close();

        if ((int)$row['activas'] === 0) {
            $stmt = $this->db->prepare(
                "UPDATE premios_solicitados
                 SET status = 'completado'
                 WHERE empresa_id = ? AND temporada_id = ? AND vendedor_id = ?
                 AND status = 'pendiente'"
            );
            $stmt->bind_param('isi', $empresa_id, $temporada_id, $vendedor_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function obtenerPremiosVendedor(int $empresa_id, string $temporada_id, int $vendedor_id): array
    {
        // Prevenir error si la tabla no ha sido creada aÃºn
        $this->db->query("CREATE TABLE IF NOT EXISTS premios_solicitados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendedor_id INT NOT NULL,
            empresa_id INT NOT NULL,
            temporada_id INT NOT NULL,
            premio_id INT NOT NULL,
            status VARCHAR(50) DEFAULT 'pendiente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $this->db->prepare(
            "SELECT ps.id, ps.status, ps.created_at, p.nombre, p.valor
             FROM premios_solicitados ps
             INNER JOIN premios p ON ps.premio_id = p.id
             WHERE ps.empresa_id = ? AND ps.temporada_id = ? AND ps.vendedor_id = ?
             ORDER BY ps.created_at DESC"
        );
        $stmt->bind_param('isi', $empresa_id, $temporada_id, $vendedor_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
