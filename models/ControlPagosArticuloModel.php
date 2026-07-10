<?php
require_once __DIR__ . '/../config/Database.php';

class ControlPagosArticuloModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(string $empresa_id, string $temporada_id): array
    {
        $u = $_SESSION['user_id'] ?? '';

        $sql = "SELECT aa.id as asignacion_id,
                       v.id as vendedor_id,
                       v.nombre as vendedor_nombre,
                       v.cedula as vendedor_cedula,
                       (SELECT GROUP_CONCAT(a.nombre SEPARATOR ', ') FROM asignacion_articulo_detalle d JOIN articulos a ON d.articulo_id = a.id WHERE d.asignacion_id = aa.id) as articulos_nombre,
                       aa.fecha_asignacion,
                       aa.monto_total,
                       c.id as cuota_id,
                       c.numero_cuota,
                       c.fecha_pago,
                       c.fecha_vencimiento,
                       c.estatus_pago,
c.monto_a_pagar,
                        c.monto_pendiente,
                        e.dias_retraso_permitido
                 FROM asignaciones_articulos aa
                INNER JOIN vendedores v ON aa.vendedor_id = v.id
                INNER JOIN cuotas_articulo c ON c.asignacion_id = aa.id
                INNER JOIN empresas e ON aa.empresa_id = e.id
                WHERE aa.estado = 'activa'
                  AND aa.empresa_id = ?
                  AND aa.temporada_id = ?
                  AND aa.usuario_id = ?
                ORDER BY v.nombre ASC, c.fecha_pago ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $empresa_id, $temporada_id, $u);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $cuotasFechas = [];
        $asignaciones = [];
        $montoSum = [];

        foreach ($rows as $row) {
            $key = $row['asignacion_id'];
            if (!isset($asignaciones[$key])) {
                $asignaciones[$key] = [
                    'vendedor_id'           => $row['vendedor_id'],
                    'vendedor_nombre'       => $row['vendedor_nombre'],
                    'vendedor_cedula'       => $row['vendedor_cedula'],
                    'articulos_nombre'      => $row['articulos_nombre'],
                    'fecha_asignacion'      => $row['fecha_asignacion'],
                    'monto_total'           => $row['monto_total'],
                    'monto'                 => 0,
                    'cuotas'                => [],
                ];
                $montoSum[$key] = 0;
            }

            $montoSum[$key] += (float)($row['monto_pendiente'] ?? 0);

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

        foreach ($montoSum as $k => $v) {
            $asignaciones[$k]['monto'] = $v;
        }

        return [
            'rows'                     => array_values($asignaciones),
            'cuotas_fechas'            => $cuotasFechas,
            'dias_retraso_permitido'   => $diasRetraso ?? 3,
        ];
    }

    public function historial(int $empresa_id, string $temporada_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT aa.id as asignacion_id,
                    (SELECT GROUP_CONCAT(a.nombre SEPARATOR ', ') FROM asignacion_articulo_detalle d JOIN articulos a ON d.articulo_id = a.id WHERE d.asignacion_id = aa.id) as articulos_nombre,
                    aa.monto_total,
                    v.id as vendedor_id, v.nombre as vendedor_nombre,
                    v.cedula as vendedor_cedula,
                    c.id as cuota_id, c.numero_cuota, c.monto_a_pagar,
                    c.monto_pagado, c.fecha_pago, c.fecha_vencimiento,
                    c.estatus_pago, c.pagado_a_tiempo,
                    c.comprobante as comprobante_ids
             FROM cuotas_articulo c
             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
             INNER JOIN vendedores v ON aa.vendedor_id = v.id
             WHERE aa.empresa_id = ? AND aa.temporada_id = ?
               AND c.estatus_pago = 'realizado'
             ORDER BY aa.id, c.numero_cuota"
        );
        $stmt->bind_param('is', $empresa_id, $temporada_id);
        $stmt->execute();
        $cuotas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $allIds = [];
        foreach ($cuotas as $c) {
            if ($c['comprobante_ids']) {
                foreach (explode('|', $c['comprobante_ids']) as $id) {
                    $id = trim($id);
                    if ($id !== '') $allIds[(int)$id] = true;
                }
            }
        }

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

        $grupos = [];
        foreach ($cuotas as $c) {
            $aid = (int)$c['asignacion_id'];
            if (!isset($grupos[$aid])) {
                $grupos[$aid] = [
                    'asignacion_id' => $aid,
                    'articulos_nombre' => $c['articulos_nombre'],
                    'monto_total' => $c['monto_total'],
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
}
