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

    public function kpis(): array
    {
        $u = $_SESSION['user_id'] ?? '';
        $t = $this->getTemporadaActual();
        $tid = $t ? "'" . $this->db->real_escape_string($t['id']) . "'" : 'NULL';

        // 1. Total Asignaciones Activas
        $r = $this->db->query("SELECT COUNT(*) c FROM asignaciones_colecciones WHERE usuario_id='$u' AND estado='activa' AND temporada_id=$tid");
        $asignaciones_activas = $r ? (int)$r->fetch_assoc()['c'] : 0;

        // 2. Volumen Total de Ventas (suma costo activas)
        $r = $this->db->query("SELECT COALESCE(SUM(costo),0) s FROM asignaciones_colecciones WHERE usuario_id='$u' AND estado='activa' AND temporada_id=$tid");
        $volumen_ventas = $r ? (float)$r->fetch_assoc()['s'] : 0;

        // 3. Ganancia Proyectada
        $r = $this->db->query("SELECT COALESCE(SUM(ganancia_vendedor + ganancia_gerente),0) s FROM asignaciones_colecciones WHERE usuario_id='$u' AND estado='activa' AND temporada_id=$tid");
        $ganancia_proyectada = $r ? (float)$r->fetch_assoc()['s'] : 0;

        // 4. Tasa de Conversión
        $r = $this->db->query("SELECT COUNT(*) total, SUM(CASE WHEN estado='finalizada' THEN 1 ELSE 0 END) finalizadas FROM asignaciones_colecciones WHERE usuario_id='$u' AND temporada_id=$tid");
        $conversion = ['tasa' => 0, 'total' => 0, 'finalizadas' => 0];
        if ($r && $row = $r->fetch_assoc()) {
            $conversion['total'] = (int)$row['total'];
            $conversion['finalizadas'] = (int)$row['finalizadas'];
            $conversion['tasa'] = $row['total'] > 0 ? round(($row['finalizadas'] / $row['total']) * 100, 1) : 0;
        }

        // 5. Top Vendedores (por cantidad de asignaciones)
        $r = $this->db->query("SELECT v.id, v.nombre, COUNT(a.id) as total_asignaciones, COALESCE(SUM(a.costo),0) as total_valor FROM asignaciones_colecciones a INNER JOIN vendedores v ON a.vendedor_id=v.id WHERE a.usuario_id='$u' AND a.temporada_id=$tid GROUP BY a.vendedor_id ORDER BY total_asignaciones DESC LIMIT 10");
        $top_vendedores = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        // 6. Monto Total Pendiente
        $r = $this->db->query("SELECT COALESCE(SUM(c.monto_pendiente),0) s FROM cuotas_coleccion c INNER JOIN asignaciones_colecciones a ON c.asignacion_id=a.id WHERE a.usuario_id='$u' AND a.temporada_id=$tid AND c.estatus_pago != 'realizado'");
        $monto_pendiente = $r ? (float)$r->fetch_assoc()['s'] : 0;

        // 7. KPI Morosidad (% cuotas vencidas)
        $r = $this->db->query("SELECT COUNT(*) total, SUM(CASE WHEN c.estatus_pago='vencido' THEN 1 ELSE 0 END) vencidas FROM cuotas_coleccion c INNER JOIN asignaciones_colecciones a ON c.asignacion_id=a.id WHERE a.usuario_id='$u' AND a.temporada_id=$tid");
        $morosidad = ['porcentaje' => 0, 'total' => 0, 'vencidas' => 0];
        if ($r && $row = $r->fetch_assoc()) {
            $morosidad['total'] = (int)$row['total'];
            $morosidad['vencidas'] = (int)$row['vencidas'];
            $morosidad['porcentaje'] = $row['total'] > 0 ? round(($row['vencidas'] / $row['total']) * 100, 1) : 0;
        }

        // 8. Proyección de Ingresos (semanal)
        $r = $this->db->query("SELECT c.fecha_vencimiento, SUM(c.monto_a_pagar) as total FROM cuotas_coleccion c INNER JOIN asignaciones_colecciones a ON c.asignacion_id=a.id WHERE a.usuario_id='$u' AND a.temporada_id=$tid AND c.estatus_pago != 'realizado' GROUP BY c.fecha_vencimiento ORDER BY c.fecha_vencimiento ASC");
        $proyeccion_raw = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        // Agrupar por semana
        $proyeccion = [];
        foreach ($proyeccion_raw as $row) {
            $week = date('Y-\WW', strtotime($row['fecha_vencimiento']));
            if (!isset($proyeccion[$week])) {
                $proyeccion[$week] = 0;
            }
            $proyeccion[$week] += (float)$row['total'];
        }

        return [
            'temporada' => $t,
            'asignaciones_activas' => $asignaciones_activas,
            'volumen_ventas' => $volumen_ventas,
            'ganancia_proyectada' => $ganancia_proyectada,
            'conversion' => $conversion,
            'top_vendedores' => $top_vendedores,
            'monto_pendiente' => $monto_pendiente,
            'morosidad' => $morosidad,
            'proyeccion' => $proyeccion,
        ];
    }
}
