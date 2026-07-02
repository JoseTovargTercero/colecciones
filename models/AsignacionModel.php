<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class AsignacionModel
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(?string $empresa_id = null): array
    {
        $sql = "SELECT ac.id, ac.fecha_asignacion, ac.estado, ac.created_at,
                    v.nombre as vendedor_nombre, v.cedula as vendedor_cedula,
                    cc.nombre as coleccion_nombre, cc.tipo as coleccion_tipo,
                    cc.precio_venta_vendedor,
                    t.nombre as temporada_nombre,
                    e.nombre as empresa_nombre
             FROM asignaciones_colecciones ac
             LEFT JOIN vendedores v ON ac.vendedor_id = v.id
             LEFT JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
             LEFT JOIN temporadas t ON ac.temporada_id = t.id
             LEFT JOIN empresas e ON cc.empresa_id = e.id";
        if ($empresa_id) {
            $sql .= " WHERE cc.empresa_id = '" . $this->db->real_escape_string($empresa_id) . "'";
        }
        $sql .= " ORDER BY ac.created_at DESC";
        $r = $this->db->query($sql);
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function crear(array $d): string
    {
        $vendedor_id   = isset($d['vendedor_id']) ? (int)$d['vendedor_id'] : 0;
        $coleccion_id  = isset($d['coleccion_id']) ? (int)$d['coleccion_id'] : 0;
        $temporada_id  = isset($d['temporada_id']) ? (int)$d['temporada_id'] : 0;



        // OBTENER LA INFO DE LA COLECCION
        // OBTENER LA INFO DE LA COLECCION
        $stmt = $this->db->prepare(
            "SELECT precio_venta_vendedor, ganancia_vendedor, precio_base
                FROM colecciones_combos
                WHERE id = ?");
        $stmt->bind_param('s', $coleccion_id);
        $stmt->execute();
        $r = $stmt->get_result();

        // Usamos fetch_assoc para obtener una sola fila como arreglo asociativo
        $row = $r->fetch_assoc();
        $stmt->close();

        // Verificamos que se haya encontrado el registro antes de acceder a las variables
        if ($row) {
            $precio_venta_vendedor = $row['precio_venta_vendedor'];
            $ganancia_vendedor = $row['ganancia_vendedor'];
            $ganancia_gerente = (float)$row['precio_venta_vendedor'] - (float)$row['precio_base'];
        } else {
            throw new Exception('No existe la coleccion');
        }
        // OBTENER LA INFO DE LA COLECCION



        $fecha_asig    = trim($d['fecha_asignacion'] ?? '');
        $cuotas        = $d['cuotas'] ?? [];
        $cantidad      = isset($d['cantidad']) ? max(1, (int)$d['cantidad']) : 1;
        $u             = $_SESSION['user_id'] ?? '';

        if (!$vendedor_id || !$coleccion_id || !$temporada_id || !$fecha_asig)
            throw new Exception('Faltan datos obligatorios o son inválidos');
        if (empty($cuotas)) throw new Exception('Debe definir al menos una cuota');

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO asignaciones_colecciones
                 (vendedor_id, coleccion_combo_id, temporada_id, estado, aplica_premio_especial, fecha_asignacion, usuario_id, costo, ganancia_vendedor, ganancia_gerente)
                 VALUES (?, ?, ?, 'activa', 0, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iiisssss', $vendedor_id, $coleccion_id, $temporada_id, $fecha_asig, $u, $precio_venta_vendedor, $ganancia_vendedor, $ganancia_gerente);

            $stmt2 = $this->db->prepare(
                "INSERT INTO cuotas_coleccion
                 (asignacion_id, numero_cuota, porcentaje, monto_a_pagar, monto_pendiente, fecha_pago, estatus_pago, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)"
            );

            $last_id = null;
            for ($j = 0; $j < $cantidad; $j++) {
                if (!$stmt->execute()) {
                    throw new Exception("Error al insertar asignación: " . $stmt->error);
                }
                $asignacion_id = $this->db->insert_id;
                $last_id = $asignacion_id;

                foreach ($cuotas as $c) {
                    $num           = (int)$c['numero'];
                    $porcentaje    = (float)$c['porcentaje'];
                    $monto         = (float)$c['monto'];
                    $fecha_pago    = $c['fecha_pago'];

                    $stmt2->bind_param('iidddsss', $asignacion_id, $num, $porcentaje, $monto, $monto, $fecha_pago, $u);
                    $stmt2->execute();
                }
            }
            $stmt->close();
            $stmt2->close();

            $this->db->commit();
            return (string)$last_id;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    public function listarCuotas(string $asignacion_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, numero_cuota, porcentaje, monto_a_pagar, fecha_pago, estatus_pago, comprobante
             FROM cuotas_coleccion
             WHERE asignacion_id = ?
             ORDER BY numero_cuota ASC"
        );
        $stmt->bind_param('s', $asignacion_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$row) {
            $row['comprobante_rutas'] = [];
            $row['comprobante_ids'] = [];
            if (!empty($row['comprobante'])) {
                $ids = array_filter(explode('|', $row['comprobante']), 'is_numeric');
                if (!empty($ids)) {
                    $row['comprobante_ids'] = array_map('intval', $ids);
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $types = str_repeat('i', count($ids));
                    $s2 = $this->db->prepare("SELECT id, comprobante FROM comprobantes WHERE id IN ($ph)");
                    $s2->bind_param($types, ...$ids);
                    $s2->execute();
                    $r2 = $s2->get_result();
                    while ($c = $r2->fetch_assoc()) {
                        $row['comprobante_rutas'][] = $c;
                    }
                    $s2->close();
                }
            }
        }
        unset($row);

        return $rows;
    }

    public function eliminar(string $id): bool
    {
        $this->db->begin_transaction();
        try {
            $s1 = $this->db->prepare("DELETE FROM cuotas_coleccion WHERE asignacion_id=?");
            $s1->bind_param('s', $id);
            $s1->execute();
            $s1->close();

            $s2 = $this->db->prepare("DELETE FROM asignaciones_colecciones WHERE id=?");
            $s2->bind_param('s', $id);
            $s2->execute();
            $aff = $s2->affected_rows;
            $s2->close();

            $this->db->commit();
            return $aff > 0;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
