<?php
require_once __DIR__ . '/../config/Database.php';

class DashboardModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function getTemporadaActual(): ?array
    {
        $u = $_SESSION['user_id'] ?? '';
        $r = $this->db->query("SELECT id, nombre FROM temporadas WHERE usuario_id='$u' AND CURDATE() BETWEEN fecha_inicio AND fecha_fin LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) {
            return $row;
        }
        // Fallback: última temporada creada
        $r = $this->db->query("SELECT id, nombre FROM temporadas WHERE usuario_id='$u' ORDER BY created_at DESC LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) {
            return $row;
        }
        return null;
    }

    public function kpis($empresa_id = null, $temporada_id = null): array
    {
        $u = $_SESSION['user_id'] ?? '';

        if ($temporada_id) {
            $t = ['id' => $temporada_id, 'nombre' => ''];
            $tid = "'" . $this->db->real_escape_string($temporada_id) . "'";
        } else {
            $t = $this->getTemporadaActual();
            $tid = $t ? "'" . $this->db->real_escape_string($t['id']) . "'" : 'NULL';
        }

        $empresaJoin = '';
        $empresaWhere = '';
        if ($empresa_id) {
            $eid = (int)$empresa_id;
            $empresaJoin = " INNER JOIN colecciones_combos cc ON a.coleccion_combo_id = cc.id";
            $empresaWhere = " AND cc.empresa_id = $eid";
        }

        // 1. Total Asignaciones Activas
        $r = $this->db->query("SELECT COUNT(*) c FROM asignaciones_colecciones a $empresaJoin WHERE a.usuario_id='$u' AND a.estado='activa' AND a.temporada_id=$tid $empresaWhere");
        $asignaciones_activas = $r ? (int)$r->fetch_assoc()['c'] : 0;

        // 2. Volumen Total de Ventas (suma costo activas)
        $r = $this->db->query("SELECT COALESCE(SUM(a.costo),0) s FROM asignaciones_colecciones a $empresaJoin WHERE a.usuario_id='$u' AND a.estado='activa' AND a.temporada_id=$tid $empresaWhere");
        $volumen_ventas = $r ? (float)$r->fetch_assoc()['s'] : 0;

        // 3. Ganancia Proyectada
        $r = $this->db->query("SELECT COALESCE(SUM(a.ganancia_gerente),0) s FROM asignaciones_colecciones a $empresaJoin WHERE a.usuario_id='$u' AND a.estado='activa' AND a.temporada_id=$tid $empresaWhere");
        $ganancia_proyectada = $r ? (float)$r->fetch_assoc()['s'] : 0;

        // 4. Tasa de Conversión (cuotas con estatus_pago = 'realizado')
        $r = $this->db->query("SELECT COUNT(*) total, SUM(CASE WHEN c.estatus_pago='realizado' THEN 1 ELSE 0 END) realizadas FROM cuotas_coleccion c INNER JOIN asignaciones_colecciones a ON c.asignacion_id = a.id $empresaJoin WHERE a.usuario_id='$u' AND a.temporada_id=$tid $empresaWhere");
        $conversion = ['tasa' => 0, 'total' => 0, 'realizadas' => 0];
        if ($r && $row = $r->fetch_assoc()) {
            $conversion['total'] = (int)$row['total'];
            $conversion['realizadas'] = (int)$row['realizadas'];
            $conversion['tasa'] = $row['total'] > 0 ? round(($row['realizadas'] / $row['total']) * 100, 1) : 0;
        }

        // 5. Top Vendedores (por cantidad de asignaciones)
        $r = $this->db->query("SELECT v.id, v.nombre, COUNT(a.id) as total_asignaciones, COALESCE(SUM(a.costo),0) as total_valor FROM asignaciones_colecciones a $empresaJoin INNER JOIN vendedores v ON a.vendedor_id=v.id WHERE a.usuario_id='$u' AND a.temporada_id=$tid $empresaWhere GROUP BY a.vendedor_id ORDER BY total_asignaciones DESC LIMIT 10");
        $top_vendedores = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        // 6. Monto Total Pendiente
        $r = $this->db->query("SELECT COALESCE(SUM(c.monto_pendiente),0) s FROM cuotas_coleccion c INNER JOIN asignaciones_colecciones a ON c.asignacion_id=a.id $empresaJoin WHERE a.usuario_id='$u' AND a.temporada_id=$tid $empresaWhere AND c.estatus_pago != 'realizado'");
        $monto_pendiente = $r ? (float)$r->fetch_assoc()['s'] : 0;

        // 7. KPI Morosidad (% cuotas vencidas)
        $r = $this->db->query("SELECT COUNT(*) total, SUM(CASE WHEN c.estatus_pago='vencido' THEN 1 ELSE 0 END) vencidas FROM cuotas_coleccion c INNER JOIN asignaciones_colecciones a ON c.asignacion_id=a.id $empresaJoin WHERE a.usuario_id='$u' AND a.temporada_id=$tid $empresaWhere");
        $morosidad = ['porcentaje' => 0, 'total' => 0, 'vencidas' => 0];
        if ($r && $row = $r->fetch_assoc()) {
            $morosidad['total'] = (int)$row['total'];
            $morosidad['vencidas'] = (int)$row['vencidas'];
            $morosidad['porcentaje'] = $row['total'] > 0 ? round(($row['vencidas'] / $row['total']) * 100, 1) : 0;
        }

        // 8. Pagos Pendientes por fecha (cuotas con estatus = pendiente)
        $r = $this->db->query(
            "SELECT c.fecha_pago,
                    SUM(c.monto_pendiente -
                        CASE WHEN c.id = (
                            SELECT MAX(c2.id) FROM cuotas_coleccion c2 WHERE c2.asignacion_id = c.asignacion_id
                        ) THEN COALESCE(a.ganancia_vendedor, 0) ELSE 0 END
                    ) as total
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones a ON c.asignacion_id = a.id $empresaJoin
             WHERE a.usuario_id = '$u'
               AND a.temporada_id = $tid
               $empresaWhere
               AND c.estatus_pago = 'pendiente'
             GROUP BY c.fecha_pago
             ORDER BY c.fecha_pago ASC"
        );
        $pagos_pendientes_raw = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        $pagos_pendientes = [];
        foreach ($pagos_pendientes_raw as $row) {
            $pagos_pendientes[$row['fecha_pago']] = (float)$row['total'];
        }

        // 9. Ranking Responsabilidad (cuotas pagadas a tiempo)
        $r = $this->db->query(
            "SELECT v.id, v.nombre,
                    COUNT(c.id) as cuotas_pagadas_tiempo,
                    (SELECT COUNT(*) FROM cuotas_coleccion c2
                     INNER JOIN asignaciones_colecciones a2 ON c2.asignacion_id = a2.id
                     WHERE a2.vendedor_id = v.id AND a2.usuario_id = '$u' AND a2.temporada_id = $tid
                    ) as total_cuotas
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones a ON c.asignacion_id = a.id $empresaJoin
             INNER JOIN vendedores v ON a.vendedor_id = v.id
             WHERE a.usuario_id = '$u' AND a.temporada_id = $tid $empresaWhere AND c.pagado_a_tiempo = 1
             GROUP BY a.vendedor_id
             ORDER BY cuotas_pagadas_tiempo DESC
             LIMIT 10"
        );
        $ranking_responsabilidad = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        $proyeccion = [];

        // 10. Cuotas pagadas a tiempo agrupadas por fecha (últimos 4 grupos)
        $r = $this->db->query(
            "SELECT c.fecha_pago, COUNT(*) as total
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones a ON c.asignacion_id = a.id $empresaJoin
             WHERE a.usuario_id='$u' AND a.temporada_id=$tid $empresaWhere AND c.pagado_a_tiempo = 1
             GROUP BY c.fecha_pago
             ORDER BY c.fecha_pago DESC
             LIMIT 4"
        );
        $cuotas_pagadas_por_fecha = $r ? array_reverse($r->fetch_all(MYSQLI_ASSOC)) : [];

        return [
            'temporada' => $t,
            'empresa_id' => $empresa_id,
            'asignaciones_activas' => $asignaciones_activas,
            'volumen_ventas' => $volumen_ventas,
            'ganancia_proyectada' => $ganancia_proyectada,
            'conversion' => $conversion,
            'top_vendedores' => $top_vendedores,
            'monto_pendiente' => $monto_pendiente,
            'morosidad' => $morosidad,
            'proyeccion' => $proyeccion,
            'pagos_pendientes' => $pagos_pendientes,
            'ranking_responsabilidad' => $ranking_responsabilidad,
            'cuotas_pagadas_por_fecha' => $cuotas_pagadas_por_fecha,
        ];
    }
}
