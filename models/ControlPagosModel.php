<?php
require_once __DIR__ . '/../config/Database.php';

class ControlPagosModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(string $empresa_id, string $temporada_id): array
    {
        $u = $_SESSION['user_id'] ?? '';

        // Obtener todas las cuotas con sus asignaciones
        $sql = "SELECT ac.id as asignacion_id,
                       v.id as vendedor_id,
                       v.nombre as vendedor_nombre,
                       v.cedula as vendedor_cedula,
                       cc.nombre as coleccion_nombre,
                       cc.tipo as coleccion_tipo,
                       ac.fecha_asignacion,
                       cc.precio_venta_vendedor,
                       c.id as cuota_id,
                       c.numero_cuota,
                       c.fecha_pago,
                       c.fecha_vencimiento,
                       c.estatus_pago,
                       c.monto_a_pagar,
                       e.dias_retraso_permitido
                FROM asignaciones_colecciones ac
                INNER JOIN vendedores v ON ac.vendedor_id = v.id
                INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
                INNER JOIN cuotas_coleccion c ON c.asignacion_id = ac.id
                INNER JOIN empresas e ON cc.empresa_id = e.id
                WHERE ac.estado = 'activa'
                  AND cc.empresa_id = ?
                  AND ac.temporada_id = ?
                  AND ac.usuario_id = ?
                ORDER BY v.nombre ASC, c.fecha_pago ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $empresa_id, $temporada_id, $u);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Agrupar por asignacion y construir estructura pivot
        $cuotasFechas = [];
        $asignaciones = [];

        foreach ($rows as $row) {
            $key = $row['asignacion_id'];
            if (!isset($asignaciones[$key])) {
                $asignaciones[$key] = [
                    'vendedor_id'           => $row['vendedor_id'],
                    'vendedor_nombre'       => $row['vendedor_nombre'],
                    'vendedor_cedula'       => $row['vendedor_cedula'],
                    'coleccion_nombre'      => $row['coleccion_nombre'],
                    'coleccion_tipo'        => $row['coleccion_tipo'],
                    'fecha_asignacion'      => $row['fecha_asignacion'],
                    'precio_venta_vendedor' => $row['precio_venta_vendedor'],
                    'cuotas'                => [],
                ];
            }

            $fechaPago = $row['fecha_pago'];
            if (!in_array($fechaPago, $cuotasFechas)) {
                $cuotasFechas[] = $fechaPago;
            }

            $asignaciones[$key]['cuotas'][] = [
                'fecha_pago'        => $fechaPago,
                'fecha_vencimiento' => $row['fecha_vencimiento'],
                'estatus_pago'      => $row['estatus_pago'],
                'monto_a_pagar'     => $row['monto_a_pagar'],
                'cuota_id'          => $row['cuota_id'],
            ];

            if (!isset($diasRetraso)) {
                $diasRetraso = (int)($row['dias_retraso_permitido'] ?? 3);
            }
        }

        sort($cuotasFechas);

        return [
            'rows'                     => array_values($asignaciones),
            'cuotas_fechas'            => $cuotasFechas,
            'dias_retraso_permitido'   => $diasRetraso ?? 3,
        ];
    }

    public function historial(int $empresa_id, string $temporada_id): array
    {
        // 1. Get all paid cuotas with comprobante IDs
        $stmt = $this->db->prepare(
            "SELECT ac.id as asignacion_id, cc.nombre as coleccion_nombre,
                    cc.tipo as coleccion_tipo, cc.precio_venta_vendedor,
                    v.id as vendedor_id, v.nombre as vendedor_nombre,
                    v.cedula as vendedor_cedula,
                    c.id as cuota_id, c.numero_cuota, c.monto_a_pagar,
                    c.monto_pagado, c.fecha_pago, c.fecha_vencimiento,
                    c.estatus_pago, c.pagado_a_tiempo,
                    c.comprobante as comprobante_ids
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
             INNER JOIN vendedores v ON ac.vendedor_id = v.id
             WHERE cc.empresa_id = ? AND ac.temporada_id = ?
               AND c.estatus_pago = 'realizado'
             ORDER BY ac.id, c.numero_cuota"
        );
        $stmt->bind_param('is', $empresa_id, $temporada_id);
        $stmt->execute();
        $cuotas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // 2. Collect all comprobante IDs
        $allIds = [];
        foreach ($cuotas as $c) {
            if ($c['comprobante_ids']) {
                foreach (explode('|', $c['comprobante_ids']) as $id) {
                    $id = trim($id);
                    if ($id !== '') $allIds[(int)$id] = true;
                }
            }
        }

        // 3. Fetch comprobantes
        $comprobantes = [];
        if ($allIds) {
            $ids = implode(',', array_keys($allIds));
            $r = $this->db->query(
                "SELECT id, monto, numero_operacion, comprobante,
                        fecha_pago_comprobante, created_at,
                        monto_bs, tasa_dia
                 FROM comprobantes WHERE id IN ($ids)"
            );
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $comprobantes[(int)$row['id']] = $row;
                }
            }
        }

        // 4. Attach comprobantes to each cuota, group by asignacion
        $grupos = [];
        foreach ($cuotas as $c) {
            $aid = (int)$c['asignacion_id'];
            if (!isset($grupos[$aid])) {
                $grupos[$aid] = [
                    'asignacion_id' => $aid,
                    'coleccion_nombre' => $c['coleccion_nombre'],
                    'coleccion_tipo' => $c['coleccion_tipo'],
                    'precio_venta_vendedor' => $c['precio_venta_vendedor'],
                    'vendedor_nombre' => $c['vendedor_nombre'],
                    'vendedor_cedula' => $c['vendedor_cedula'],
                    'cuotas' => [],
                ];
            }

            $cuotaComps = [];
            if ($c['comprobante_ids']) {
                foreach (explode('|', $c['comprobante_ids']) as $id) {
                    $id = (int)trim($id);
                    if ($id && isset($comprobantes[$id])) {
                        $cuotaComps[] = $comprobantes[$id];
                    }
                }
            }

            $grupos[$aid]['cuotas'][] = [
                'cuota_id' => $c['cuota_id'],
                'numero_cuota' => $c['numero_cuota'],
                'monto_a_pagar' => $c['monto_a_pagar'],
                'monto_pagado' => $c['monto_pagado'],
                'fecha_pago' => $c['fecha_pago'],
                'fecha_vencimiento' => $c['fecha_vencimiento'],
                'estatus_pago' => $c['estatus_pago'],
                'pagado_a_tiempo' => $c['pagado_a_tiempo'],
                'comprobantes' => $cuotaComps,
            ];
        }

        return array_values($grupos);
    }

    public function getPremioInfo($vendedor_id, $empresa_id, $temporada_id)
    {
        // 1. Obtener total ganancias y cantidad de asignaciones activas
        $stmt = $this->db->prepare("SELECT COUNT(*) as total_asignaciones, SUM(ganancia_vendedor) as ganancia_total FROM asignaciones_colecciones WHERE vendedor_id = ? AND temporada_id = ? AND estado = 'activa' AND coleccion_combo_id IN (SELECT id FROM colecciones_combos WHERE empresa_id = ?)");
        $stmt->bind_param('sss', $vendedor_id, $temporada_id, $empresa_id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 2. Obtener vendedor y empresa
        $stmt = $this->db->prepare("SELECT nombre FROM vendedores WHERE id = ?");
        $stmt->bind_param('s', $vendedor_id);
        $stmt->execute();
        $vendedor_nombre = $stmt->get_result()->fetch_assoc()['nombre'] ?? '';
        $stmt->close();

        $stmt = $this->db->prepare("SELECT nombre FROM empresas WHERE id = ?");
        $stmt->bind_param('s', $empresa_id);
        $stmt->execute();
        $empresa_nombre = $stmt->get_result()->fetch_assoc()['nombre'] ?? '';
        $stmt->close();

        // 3. Obtener premios (solo tipo comprado)
        $stmt = $this->db->prepare("SELECT id, nombre, foto, valor, tipo FROM premios WHERE empresa_id = ? AND tipo = 'comprado'");
        $stmt->bind_param('s', $empresa_id);
        $stmt->execute();
        $premios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            'total_asignaciones' => (int)($r['total_asignaciones'] ?? 0),
            'ganancia_total' => (float)($r['ganancia_total'] ?? 0),
            'vendedor_nombre' => $vendedor_nombre,
            'empresa_nombre' => $empresa_nombre,
            'premios' => $premios
        ];
    }

    public function solicitarPremio($vendedor_id, $empresa_id, $temporada_id, $premio_id, $u)
    {

        $this->db->begin_transaction();
        try {
            // 1. Obtener valor del premio
            $stmt = $this->db->prepare("SELECT valor FROM premios WHERE id = ?");
            $stmt->bind_param('s', $premio_id);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$p) throw new Exception("Premio no encontrado.");
            $valor_premio = (float)$p['valor'];

            // 2. Obtener asignaciones activas ordenadas
            $stmt = $this->db->prepare("SELECT id, ganancia_vendedor FROM asignaciones_colecciones WHERE vendedor_id = ? AND temporada_id = ? AND estado = 'activa' AND coleccion_combo_id IN (SELECT id FROM colecciones_combos WHERE empresa_id = ?) ORDER BY id ASC");
            $stmt->bind_param('sss', $vendedor_id, $temporada_id, $empresa_id);
            $stmt->execute();
            $asignaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $ganancia_total = array_sum(array_column($asignaciones, 'ganancia_vendedor'));

            if ($valor_premio > $ganancia_total) {
                throw new Exception("El valor del premio ($$valor_premio) supera la ganancia disponible ($$ganancia_total).");
            }

            // 3. Descontar progresivamente
            $restante = $valor_premio;
            $stmtUpdate = $this->db->prepare("UPDATE asignaciones_colecciones SET ganancia_vendedor = ? WHERE id = ?");
            foreach ($asignaciones as $asig) {
                if ($restante <= 0) break;

                $ganancia = (float)$asig['ganancia_vendedor'];
                if ($ganancia >= $restante) {
                    $nueva_ganancia = $ganancia - $restante;
                    $restante = 0;
                } else {
                    $restante -= $ganancia;
                    $nueva_ganancia = 0;
                }

                $stmtUpdate->bind_param('ds', $nueva_ganancia, $asig['id']);
                $stmtUpdate->execute();
            }
            $stmtUpdate->close();

            // 4. Insertar en premios_solicitados
            $stmtInsert = $this->db->prepare("INSERT INTO premios_solicitados (vendedor_id, empresa_id, temporada_id, premio_id, usuario_id) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->bind_param('sssss', $vendedor_id, $empresa_id, $temporada_id, $premio_id, $u);
            $stmtInsert->execute();
            $stmtInsert->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
