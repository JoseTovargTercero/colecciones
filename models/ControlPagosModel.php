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
}
