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
        $cuota_id       = isset($d['cuota_id']) ? (int)$d['cuota_id'] : null;
        $comprobante    = $this->upload();

        if (!$empresa_id || !$temporada_id || !$vendedor_id || !$tipo_pago || $monto <= 0) {
            throw new Exception('Faltan datos obligatorios o monto inválido.');
        }

        $this->db->begin_transaction();

        try {
            // Guardar comprobante primero para obtener el ID
            $stmt = $this->db->prepare(
                "INSERT INTO comprobantes (empresa_id, temporada_id, vendedor_id, cuota_id, monto, numero_operacion, comprobante)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ississs', $empresa_id, $temporada_id, $vendedor_id, $cuota_id, $monto, $numero_operacion, $comprobante);
            $stmt->execute();
            $comp_id = $this->db->insert_id;
            $stmt->close();

            $comp_str = $comp_id ? (string)$comp_id : null;

            if ($tipo_pago === 'total') {
                $this->procesarTotal($empresa_id, $temporada_id, $vendedor_id, $monto, $numero_operacion, $comp_str, $u);
            } elseif ($tipo_pago === 'cuota_exacta') {
                if (!$cuota_id) throw new Exception('Debe seleccionar una cuota.');
                $this->procesarCuotaExacta($cuota_id, $monto, $numero_operacion, $comp_str, $u);
            } elseif ($tipo_pago === 'abono') {
                $this->procesarAbono($empresa_id, $temporada_id, $vendedor_id, $monto, $numero_operacion, $comp_str, $u);
            } else {
                throw new Exception('Tipo de pago no implementado.');
            }

            $this->db->commit();
            return ['id' => $comp_id, 'message' => 'Pago registrado correctamente.'];
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function procesarTotal(int $empresa_id, string $temporada_id, int $vendedor_id, float $monto, ?string $numOp, ?string $comprobante, string $u): void
    {
        // Calcular deuda total
        $stmt = $this->db->prepare(
            "SELECT SUM(c.monto_pendiente) as total_deuda
             FROM asignaciones_colecciones ac
             INNER JOIN cuotas_coleccion c ON c.asignacion_id = ac.id
             WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND ac.estado = 'activa'
             AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')"
        );
        $stmt->bind_param('is', $vendedor_id, $temporada_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $row = $r->fetch_assoc();
        $stmt->close();

        $total_deuda = (float)($row['total_deuda'] ?? 0);
        if ($monto < $total_deuda) {
            throw new Exception("El monto ($monto) no cubre la deuda total ($total_deuda).");
        }

        // Actualizar todas las cuotas pendientes del vendedor en esta temporada
        $stmt = $this->db->prepare(
            "UPDATE cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             SET c.monto_pagado = c.monto_a_pagar,
                  c.monto_pendiente = 0,
                  c.estatus_pago = 'realizado',
                  c.fecha_pago = CURDATE(),
                  c.comprobante = IF(? IS NULL, c.comprobante, IF(c.comprobante IS NULL OR c.comprobante = '', ?, CONCAT(c.comprobante, '|', ?)))
             WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND ac.estado = 'activa'
              AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')"
        );
        $stmt->bind_param('sssis', $comprobante, $comprobante, $comprobante, $vendedor_id, $temporada_id);
        $stmt->execute();
        $stmt->close();

        // Finalizar asignaciones
        $stmt = $this->db->prepare(
            "UPDATE asignaciones_colecciones
             SET estado = 'finalizada'
             WHERE vendedor_id = ? AND temporada_id = ? AND estado = 'activa'"
        );
        $stmt->bind_param('is', $vendedor_id, $temporada_id);
        $stmt->execute();
        $stmt->close();
    }

    private function procesarCuotaExacta(int $cuota_id, float $monto, ?string $numOp, ?string $comprobante, string $u): void
    {
        // Obtener cuota
        $stmt = $this->db->prepare(
            "SELECT c.id, c.monto_a_pagar, c.monto_pendiente, c.asignacion_id
             FROM cuotas_coleccion c
             WHERE c.id = ? AND c.estatus_pago IN ('pendiente','vencido','dentro_de_margen')"
        );
        $stmt->bind_param('i', $cuota_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $cuota = $r->fetch_assoc();
        $stmt->close();

        if (!$cuota) throw new Exception('Cuota no encontrada o ya está pagada.');

        if ($monto < (float)$cuota['monto_pendiente']) {
            throw new Exception("El monto ($monto) no cubre el pendiente de la cuota ({$cuota['monto_pendiente']}).");
        }

        // Actualizar cuota
        $stmt = $this->db->prepare(
            "UPDATE cuotas_coleccion
             SET monto_pagado = monto_a_pagar,
                  monto_pendiente = 0,
                  estatus_pago = 'realizado',
                  fecha_pago = CURDATE(),
                  comprobante = IF(? IS NULL, comprobante, IF(comprobante IS NULL OR comprobante = '', ?, CONCAT(comprobante, '|', ?)))
             WHERE id = ?"
        );
        $stmt->bind_param('sssi', $comprobante, $comprobante, $comprobante, $cuota_id);
        $stmt->execute();
        $stmt->close();

        // Verificar si todas las cuotas de la asignación están pagadas
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
        }
    }

    private function procesarAbono(int $empresa_id, string $temporada_id, int $vendedor_id, float $monto, ?string $numOp, ?string $comprobante, string $u): void
    {
        $restante = $monto;

        // Obtener cuotas pendientes/no realizadas ordenadas por fecha_pago ASC
        $stmt = $this->db->prepare(
            "SELECT c.id, c.asignacion_id, c.monto_a_pagar, c.monto_pendiente, c.fecha_pago
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

        $stmtFull = $this->db->prepare(
            "UPDATE cuotas_coleccion
             SET monto_pagado = monto_a_pagar,
                 monto_pendiente = 0,
                 estatus_pago = 'realizado',
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

        foreach ($cuotas as $cuota) {
            if ($restante <= 0) break;

            $pendiente = (float)$cuota['monto_pendiente'];
            $cuotaId = (int)$cuota['id'];
            $asigId = (int)$cuota['asignacion_id'];

            if ($restante >= $pendiente) {
                $pagadoAhora = $pendiente;
                $restante -= $pagadoAhora;
                $stmtFull->bind_param('sssi', $comprobante, $comprobante, $comprobante, $cuotaId);
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

        // Finalizar asignaciones donde todas las cuotas estén pagadas
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
                    cc.nombre as coleccion_nombre
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
             WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND cc.empresa_id = ?
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
            "SELECT cp.id, cp.cuota_id, cp.monto, cp.numero_operacion, cp.comprobante, cp.created_at,
                    c.numero_cuota
             FROM comprobantes cp
             LEFT JOIN cuotas_coleccion c ON cp.cuota_id = c.id
             WHERE cp.empresa_id = ? AND cp.temporada_id = ? AND cp.vendedor_id = ?
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
}
