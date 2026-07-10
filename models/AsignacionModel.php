<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class AsignacionModel
{
    private $db, $user;
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->user = $_SESSION['user_id'] ?? '';
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
             LEFT JOIN empresas e ON cc.empresa_id = e.id
             WHERE ac.usuario_id = ?";
        $params = [$this->user];
        $types = 's';
        if ($empresa_id) {
            $sql .= " AND cc.empresa_id = ?";
            $params[] = $empresa_id;
            $types .= 's';
        }
        $sql .= " ORDER BY ac.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function crear(array $d): string
    {
        $vendedor_id   = isset($d['vendedor_id']) ? (int)$d['vendedor_id'] : 0;
        $colecciones   = $d['colecciones'] ?? [];
        $temporada_id  = $d['temporada_id'] ?? '';

        if (!$colecciones || !is_array($colecciones)) throw new Exception('Debe seleccionar al menos una colección');

        $stmt = $this->db->prepare("SELECT id FROM vendedores WHERE id=? AND usuario_id=?");
        $stmt->bind_param('is', $vendedor_id, $this->user);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new Exception('Vendedor no encontrado');
        $stmt->close();

        $fecha_asig    = trim($d['fecha_asignacion'] ?? '');
        $cuotas        = $d['cuotas'] ?? [];

        if (!$vendedor_id || !$temporada_id || !$fecha_asig)
            throw new Exception('Faltan datos obligatorios o son inválidos');
        if (empty($cuotas)) throw new Exception('Debe definir al menos una cuota');

        $this->db->begin_transaction();
        try {
            $last_id = null;

            $stmt = $this->db->prepare(
                "INSERT INTO asignaciones_colecciones
                 (vendedor_id, coleccion_combo_id, temporada_id, estado, aplica_premio_especial, fecha_asignacion, usuario_id, costo, ganancia_vendedor, ganancia_gerente)
                 VALUES (?, ?, ?, 'activa', 0, ?, ?, ?, ?, ?)"
            );

            $stmt2 = $this->db->prepare(
                "INSERT INTO cuotas_coleccion
                 (asignacion_id, numero_cuota, porcentaje, monto_a_pagar, monto_pendiente, fecha_pago, estatus_pago, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)"
            );

            foreach ($colecciones as $item) {
                $coleccion_id = $item['id'] ?? '';
                $cantidad = isset($item['cantidad']) ? max(1, (int)$item['cantidad']) : 1;
                if (!$coleccion_id) continue;

                $cs = $this->db->prepare(
                    "SELECT cc.precio_venta_vendedor, cc.ganancia_vendedor, cc.precio_base
                     FROM colecciones_combos cc
                     INNER JOIN empresas e ON cc.empresa_id = e.id
                     WHERE cc.id = ? AND e.usuario_id = ?");
                $cs->bind_param('ss', $coleccion_id, $this->user);
                $cs->execute();
                $r = $cs->get_result();
                $row = $r->fetch_assoc();
                $cs->close();

                if (!$row) throw new Exception('No existe la colección ' . $coleccion_id);

                $precio_venta_vendedor = (float)$row['precio_venta_vendedor'];
                $ganancia_vendedor = $row['ganancia_vendedor'];
                $ganancia_gerente = (float)$row['precio_venta_vendedor'] - (float)$row['precio_base'];

                for ($j = 0; $j < $cantidad; $j++) {
                    $stmt->bind_param('issssddd', $vendedor_id, $coleccion_id, $temporada_id, $fecha_asig, $this->user, $precio_venta_vendedor, $ganancia_vendedor, $ganancia_gerente);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al insertar asignación: " . $stmt->error);
                    }
                    $asignacion_id = $this->db->insert_id;
                    $last_id = $asignacion_id;

                    foreach ($cuotas as $c) {
                        $num           = (int)$c['numero'];
                        $porcentaje    = (float)$c['porcentaje'];
                        $monto         = $precio_venta_vendedor * $porcentaje / 100;
                        $fecha_pago    = $c['fecha_pago'];

                        $stmt2->bind_param('iidddss', $asignacion_id, $num, $porcentaje, $monto, $monto, $fecha_pago, $this->user);
                        $stmt2->execute();
                    }
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
            "SELECT c.id, c.numero_cuota, c.porcentaje, c.monto_a_pagar, c.fecha_pago, c.estatus_pago, c.comprobante
             FROM cuotas_coleccion c
             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
             WHERE c.asignacion_id = ? AND ac.usuario_id = ?
             ORDER BY c.numero_cuota ASC"
        );
        $stmt->bind_param('ss', $asignacion_id, $this->user);
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
            $s1 = $this->db->prepare(
                "DELETE c FROM cuotas_coleccion c
                 INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
                 WHERE ac.id = ? AND ac.usuario_id = ?"
            );
            $s1->bind_param('ss', $id, $this->user);
            $s1->execute();
            $s1->close();

            $s2 = $this->db->prepare("DELETE FROM asignaciones_colecciones WHERE id=? AND usuario_id=?");
            $s2->bind_param('ss', $id, $this->user);
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
