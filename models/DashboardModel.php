<?php
require_once __DIR__ . '/../config/Database.php';

class DashboardModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function getTemporadaActual($extraJoin = '', $extraWhere = ''): ?array
    {
        $u = $_SESSION['user_id'] ?? '';

        $r = $this->db->query("SELECT t.id, t.nombre FROM temporadas t $extraJoin WHERE t.usuario_id='$u' AND CURDATE() BETWEEN t.fecha_inicio AND t.fecha_fin $extraWhere LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) return $row;

        $r = $this->db->query("SELECT t.id, t.nombre FROM temporadas t $extraJoin WHERE t.usuario_id='$u' $extraWhere ORDER BY t.created_at DESC LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) return $row;

        // Fallback: cualquier temporada activa global
        $r = $this->db->query("SELECT id, nombre FROM temporadas WHERE CURDATE() BETWEEN fecha_inicio AND fecha_fin LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) return $row;

        // Fallback: última temporada creada
        $r = $this->db->query("SELECT id, nombre FROM temporadas ORDER BY created_at DESC LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) return $row;

        return null;
    }

    private function getVendedorId(): ?int
    {
        $u = $_SESSION['user_id'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        if (!$nombre) return null;

        $escaped = $this->db->real_escape_string($nombre);

        $r = $this->db->query("SELECT id FROM vendedores WHERE usuario_id = '$u' LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) return (int)$row['id'];

        $r = $this->db->query("SELECT id FROM vendedores WHERE '$escaped' LIKE CONCAT('%', nombre, '%') OR nombre LIKE '%$escaped%' LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) return (int)$row['id'];

        $parts = explode(' ', trim($nombre));
        if (count($parts) > 0) {
            $firstName = $this->db->real_escape_string($parts[0]);
            $r = $this->db->query("SELECT id FROM vendedores WHERE nombre LIKE '%$firstName%' LIMIT 1");
            if ($r && $row = $r->fetch_assoc()) return (int)$row['id'];
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

        $userFilter = "a.usuario_id = '$u'";

        $tableAsignaciones = 'asignaciones_colecciones';
        $tableCuotas = 'cuotas_coleccion';

        $empresaJoin = '';
        $empresaWhere = '';
        if ($empresa_id) {
            $eid = (int)$empresa_id;
            $empresaJoin = " INNER JOIN colecciones_combos cc ON a.coleccion_combo_id = cc.id";
            $empresaWhere = " AND cc.empresa_id = $eid";
        }

        $activaFilter = "$userFilter AND a.estado='activa' AND a.temporada_id=$tid $empresaWhere";
        $baseFrom = "FROM $tableAsignaciones a $empresaJoin";

        $r = $this->db->query("SELECT COUNT(*) c $baseFrom WHERE $activaFilter");
        $asignaciones_activas = $r ? (int)$r->fetch_assoc()['c'] : 0;

        $r = $this->db->query("SELECT COALESCE(SUM(a.costo),0) s $baseFrom WHERE $activaFilter");
        $volumen_ventas = $r ? (float)$r->fetch_assoc()['s'] : 0;

        $r = $this->db->query("SELECT COALESCE(SUM(a.ganancia_gerente),0) s $baseFrom WHERE $activaFilter");
        $ganancia_proyectada = $r ? (float)$r->fetch_assoc()['s'] : 0;

        $r = $this->db->query("SELECT COUNT(*) total, SUM(CASE WHEN c.estatus_pago='realizado' THEN 1 ELSE 0 END) realizadas FROM $tableCuotas c INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id $empresaJoin WHERE $userFilter AND a.temporada_id=$tid $empresaWhere");
        $conversion = ['tasa' => 0, 'total' => 0, 'realizadas' => 0];
        if ($r && $row = $r->fetch_assoc()) {
            $conversion['total'] = (int)$row['total'];
            $conversion['realizadas'] = (int)$row['realizadas'];
            $conversion['tasa'] = $row['total'] > 0 ? round(($row['realizadas'] / $row['total']) * 100, 1) : 0;
        }

        $r = $this->db->query("SELECT v.id, v.nombre, COUNT(a.id) as total_asignaciones, COALESCE(SUM(a.costo),0) as total_valor FROM $tableAsignaciones a $empresaJoin INNER JOIN vendedores v ON a.vendedor_id=v.id WHERE $userFilter AND a.temporada_id=$tid $empresaWhere GROUP BY a.vendedor_id ORDER BY total_asignaciones DESC LIMIT 10");
        $top_vendedores = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        $r = $this->db->query("SELECT COALESCE(SUM(c.monto_pendiente),0) s FROM $tableCuotas c INNER JOIN $tableAsignaciones a ON c.asignacion_id=a.id $empresaJoin WHERE $userFilter AND a.temporada_id=$tid $empresaWhere AND c.estatus_pago != 'realizado'");
        $monto_pendiente = $r ? (float)$r->fetch_assoc()['s'] : 0;

        $r = $this->db->query("SELECT COUNT(*) total, SUM(CASE WHEN c.estatus_pago='vencido' THEN 1 ELSE 0 END) vencidas FROM $tableCuotas c INNER JOIN $tableAsignaciones a ON c.asignacion_id=a.id $empresaJoin WHERE $userFilter AND a.temporada_id=$tid $empresaWhere");
        $morosidad = ['porcentaje' => 0, 'total' => 0, 'vencidas' => 0];
        if ($r && $row = $r->fetch_assoc()) {
            $morosidad['total'] = (int)$row['total'];
            $morosidad['vencidas'] = (int)$row['vencidas'];
            $morosidad['porcentaje'] = $row['total'] > 0 ? round(($row['vencidas'] / $row['total']) * 100, 1) : 0;
        }

        $r = $this->db->query(
            "SELECT c.fecha_pago,
                    SUM(c.monto_pendiente -
                        CASE WHEN c.id = (
                            SELECT MAX(c2.id) FROM $tableCuotas c2 WHERE c2.asignacion_id = c.asignacion_id
                        ) THEN COALESCE(a.ganancia_vendedor, 0) ELSE 0 END
                    ) as total
             FROM $tableCuotas c
             INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id $empresaJoin
             WHERE $userFilter
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

        $r = $this->db->query(
            "SELECT v.id, v.nombre,
                    COUNT(c.id) as cuotas_pagadas_tiempo,
                    (SELECT COUNT(*) FROM $tableCuotas c2
                     INNER JOIN $tableAsignaciones a2 ON c2.asignacion_id = a2.id
                     WHERE a2.vendedor_id = v.id AND $userFilter AND a2.temporada_id = $tid
                    ) as total_cuotas
             FROM $tableCuotas c
             INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id $empresaJoin
             INNER JOIN vendedores v ON a.vendedor_id = v.id
             WHERE $userFilter AND a.temporada_id = $tid $empresaWhere AND c.pagado_a_tiempo = 1
             GROUP BY a.vendedor_id
             ORDER BY cuotas_pagadas_tiempo DESC
             LIMIT 10"
        );
        $ranking_responsabilidad = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        $proyeccion = [];

        $r = $this->db->query(
            "SELECT c.fecha_pago, COUNT(*) as total
             FROM $tableCuotas c
             INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id $empresaJoin
             WHERE $userFilter AND a.temporada_id=$tid $empresaWhere AND c.pagado_a_tiempo = 1
             GROUP BY c.fecha_pago
             ORDER BY c.fecha_pago DESC
             LIMIT 4"
        );
        $cuotas_pagadas_por_fecha = $r ? array_reverse($r->fetch_all(MYSQLI_ASSOC)) : [];

        $r = $this->db->query(
            "SELECT c.fecha_pago, COUNT(*) as total
             FROM $tableCuotas c
             INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id
             WHERE $userFilter
               AND a.temporada_id = $tid
               $empresaWhere
               AND c.estatus_pago != 'realizado'
             GROUP BY c.fecha_pago
             ORDER BY c.fecha_pago ASC"
        );
        $cuotas_pendientes_fecha = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

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
            'cuotas_pendientes_fecha' => $cuotas_pendientes_fecha,
        ];
    }

    public function kpisVendedor($empresa_id = null, $temporada_id = null): array
    {
        $u = $_SESSION['user_id'] ?? '';

        if ($temporada_id) {
            $t = ['id' => $temporada_id, 'nombre' => ''];
            $tid = "'" . $this->db->real_escape_string($temporada_id) . "'";
        } else {
            $t = $this->getTemporadaActual();
            $tid = $t ? "'" . $this->db->real_escape_string($t['id']) . "'" : 'NULL';
        }

        $vid = $this->getVendedorId();
        $userFilter = $vid ? "a.usuario_id = $vid" : "1=0";

        $tableAsignaciones = 'asignaciones_articulos';
        $tableCuotas = 'cuotas_articulo';

        $empresaWhere = '';
        if ($empresa_id) {
            $eid = (int)$empresa_id;
            $empresaWhere = " AND a.empresa_id = $eid";
        }

        // debug
        $__debug = ['usuario_id' => $vid, 'temporada_id' => $tid, 'userFilter' => $userFilter, 'empresa_id' => $empresa_id];

        $activaFilter = "$userFilter AND a.estado='activa' AND a.temporada_id=$tid $empresaWhere";
        $baseFrom = "FROM $tableAsignaciones a";

        $r = $this->db->query("SELECT COUNT(*) c $baseFrom WHERE $activaFilter");
        $asignaciones_activas = $r ? (int)$r->fetch_assoc()['c'] : 0;

        $r = $this->db->query("SELECT COALESCE(SUM(a.monto_total),0) s $baseFrom WHERE $activaFilter");
        $volumen_ventas = $r ? (float)$r->fetch_assoc()['s'] : 0;

        $ganancia_proyectada = 0;

        $r = $this->db->query("SELECT COUNT(*) total, SUM(CASE WHEN c.estatus_pago='realizado' THEN 1 ELSE 0 END) realizadas FROM $tableCuotas c INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id WHERE $userFilter AND a.temporada_id=$tid $empresaWhere");
        $conversion = ['tasa' => 0, 'total' => 0, 'realizadas' => 0];
        if ($r && $row = $r->fetch_assoc()) {
            $conversion['total'] = (int)$row['total'];
            $conversion['realizadas'] = (int)$row['realizadas'];
            $conversion['tasa'] = $row['total'] > 0 ? round(($row['realizadas'] / $row['total']) * 100, 1) : 0;
        }

        $r = $this->db->query("SELECT v.id, v.nombre, COUNT(a.id) as total_asignaciones, COALESCE(SUM(a.monto_total),0) as total_valor FROM $tableAsignaciones a INNER JOIN vendedores v ON a.vendedor_id=v.id WHERE $userFilter AND a.temporada_id=$tid $empresaWhere GROUP BY a.vendedor_id ORDER BY total_asignaciones DESC LIMIT 10");
        $top_vendedores = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        $r = $this->db->query("SELECT COALESCE(SUM(c.monto_pendiente),0) s FROM $tableCuotas c INNER JOIN $tableAsignaciones a ON c.asignacion_id=a.id WHERE $userFilter AND a.temporada_id=$tid $empresaWhere AND c.estatus_pago != 'realizado'");
        $monto_pendiente = $r ? (float)$r->fetch_assoc()['s'] : 0;

        $r = $this->db->query("SELECT COUNT(*) total, SUM(CASE WHEN c.estatus_pago='vencido' THEN 1 ELSE 0 END) vencidas FROM $tableCuotas c INNER JOIN $tableAsignaciones a ON c.asignacion_id=a.id WHERE $userFilter AND a.temporada_id=$tid $empresaWhere");
        $morosidad = ['porcentaje' => 0, 'total' => 0, 'vencidas' => 0];
        if ($r && $row = $r->fetch_assoc()) {
            $morosidad['total'] = (int)$row['total'];
            $morosidad['vencidas'] = (int)$row['vencidas'];
            $morosidad['porcentaje'] = $row['total'] > 0 ? round(($row['vencidas'] / $row['total']) * 100, 1) : 0;
        }

        $r = $this->db->query(
            "SELECT c.fecha_pago, SUM(c.monto_pendiente) as total
             FROM $tableCuotas c
             INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id
             WHERE $userFilter AND a.temporada_id = $tid $empresaWhere AND c.estatus_pago = 'pendiente'
             GROUP BY c.fecha_pago
             ORDER BY c.fecha_pago ASC"
        );
        $pagos_pendientes_raw = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        $pagos_pendientes = [];
        foreach ($pagos_pendientes_raw as $row) {
            $pagos_pendientes[$row['fecha_pago']] = (float)$row['total'];
        }

        $r = $this->db->query(
            "SELECT v.id, v.nombre,
                    COUNT(c.id) as cuotas_pagadas_tiempo,
                    (SELECT COUNT(*) FROM $tableCuotas c2
                     INNER JOIN $tableAsignaciones a2 ON c2.asignacion_id = a2.id
                     WHERE a2.vendedor_id = v.id AND $userFilter AND a2.temporada_id = $tid $empresaWhere
                    ) as total_cuotas
             FROM $tableCuotas c
             INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id
             INNER JOIN vendedores v ON a.vendedor_id = v.id
             WHERE $userFilter AND a.temporada_id = $tid $empresaWhere AND c.pagado_a_tiempo = 1
             GROUP BY a.vendedor_id
             ORDER BY cuotas_pagadas_tiempo DESC
             LIMIT 10"
        );
        $ranking_responsabilidad = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        $proyeccion = [];

        $r = $this->db->query(
            "SELECT c.fecha_pago, COUNT(*) as total
             FROM $tableCuotas c
             INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id
             WHERE $userFilter AND a.temporada_id=$tid $empresaWhere AND c.pagado_a_tiempo = 1
             GROUP BY c.fecha_pago
             ORDER BY c.fecha_pago DESC
             LIMIT 4"
        );
        $cuotas_pagadas_por_fecha = $r ? array_reverse($r->fetch_all(MYSQLI_ASSOC)) : [];

        $r = $this->db->query(
            "SELECT c.fecha_pago, COUNT(*) as total
             FROM $tableCuotas c
             INNER JOIN $tableAsignaciones a ON c.asignacion_id = a.id
             WHERE $userFilter AND a.temporada_id = $tid AND c.estatus_pago != 'realizado'
             GROUP BY c.fecha_pago
             ORDER BY c.fecha_pago ASC"
        );
        $cuotas_pendientes_fecha = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        return [
            'temporada' => $t,
            '_debug' => $__debug,
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
            'cuotas_pendientes_fecha' => $cuotas_pendientes_fecha,
        ];
    }
}
