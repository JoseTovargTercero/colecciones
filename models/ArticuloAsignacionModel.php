<?php
require_once __DIR__ . '/../config/Database.php';

class ArticuloAsignacionModel
{
    private $db, $user;
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->user = $_SESSION['user_id'] ?? '';
    }

    public function listar(?string $empresa_id = null): array
    {
        $sql = "SELECT aa.id, aa.fecha_asignacion, aa.estado, aa.created_at, aa.monto_total,
                    v.nombre as vendedor_nombre, v.cedula as vendedor_cedula,
                    t.nombre as temporada_nombre,
                    e.nombre as empresa_nombre,
                    (SELECT GROUP_CONCAT(a.nombre SEPARATOR ', ') FROM asignacion_articulo_detalle d
                     JOIN articulos a ON d.articulo_id = a.id
                     WHERE d.asignacion_id = aa.id) as articulos_nombres
             FROM asignaciones_articulos aa
             LEFT JOIN vendedores v ON aa.vendedor_id = v.id
             LEFT JOIN temporadas t ON aa.temporada_id = t.id
             LEFT JOIN empresas e ON aa.empresa_id = e.id
             WHERE aa.usuario_id = ?";
        $params = [$this->user];
        $types = 's';
        if ($empresa_id) {
            $sql .= " AND aa.empresa_id = ?";
            $params[] = $empresa_id;
            $types .= 's';
        }
        $sql .= " ORDER BY aa.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function crear(array $d): string
    {
        $vendedor_id  = isset($d['vendedor_id']) ? (int)$d['vendedor_id'] : 0;
        $temporada_id = $d['temporada_id'] ?? '';
        $empresa_id   = $d['empresa_id'] ?? '';
        $fecha_asig   = trim($d['fecha_asignacion'] ?? '');
        $items        = $d['items'] ?? [];
        $cuotas       = $d['cuotas'] ?? [];

        if (!$vendedor_id || !$temporada_id || !$empresa_id || !$fecha_asig)
            throw new Exception('Faltan datos obligatorios');
        if (empty($items)) throw new Exception('Debe agregar al menos un artículo');
        if (empty($cuotas)) throw new Exception('Debe definir al menos una cuota');

        $stmt = $this->db->prepare("SELECT id FROM vendedores WHERE id=? AND usuario_id=?");
        $stmt->bind_param('is', $vendedor_id, $this->user);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new Exception('Vendedor no encontrado');
        $stmt->close();

        $stmt = $this->db->prepare("SELECT id FROM temporadas WHERE id=? AND empresa_id=? AND usuario_id=?");
        $stmt->bind_param('sss', $temporada_id, $empresa_id, $this->user);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new Exception('Temporada no válida para esta empresa');
        $stmt->close();

        // Validate and calculate total
        $montoTotal = 0;
        $validatedItems = [];
        foreach ($items as $item) {
            $artId = $item['articulo_id'] ?? '';
            $cantidad = max(1, (int)($item['cantidad'] ?? 1));
            $stmt = $this->db->prepare(
                "SELECT a.precio_final FROM articulos a
                 INNER JOIN empresas e ON a.empresa_id = e.id
                 WHERE a.id = ? AND e.usuario_id = ? AND a.empresa_id = ?");
            $stmt->bind_param('sss', $artId, $this->user, $empresa_id);
            $stmt->execute();
            $r = $stmt->get_result();
            $row = $r->fetch_assoc();
            $stmt->close();
            if (!$row) throw new Exception("Artículo ID $artId no encontrado");
            $price = (float)$row['precio_final'];
            $validatedItems[] = ['articulo_id' => $artId, 'cantidad' => $cantidad, 'precio_unitario' => $price];
            $montoTotal += $price * $cantidad;
        }

        $this->db->begin_transaction();
        try {
            $stmt1 = $this->db->prepare(
                "INSERT INTO asignaciones_articulos
                 (vendedor_id, temporada_id, empresa_id, estado, fecha_asignacion, usuario_id, monto_total)
                 VALUES (?, ?, ?, 'activa', ?, ?, ?)"
            );
            $stmt1->bind_param('issssd', $vendedor_id, $temporada_id, $empresa_id, $fecha_asig, $this->user, $montoTotal);
            if (!$stmt1->execute()) throw new Exception("Error al insertar asignación: " . $stmt1->error);
            $asignacion_id = $this->db->insert_id;
            $stmt1->close();

            $stmt2 = $this->db->prepare(
                "INSERT INTO asignacion_articulo_detalle (asignacion_id, articulo_id, cantidad, precio_unitario)
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($validatedItems as $it) {
                $stmt2->bind_param('siid', $asignacion_id, $it['articulo_id'], $it['cantidad'], $it['precio_unitario']);
                if (!$stmt2->execute()) throw new Exception("Error al insertar detalle: " . $stmt2->error);
            }
            $stmt2->close();

            $stmt3 = $this->db->prepare(
                "INSERT INTO cuotas_articulo
                 (asignacion_id, numero_cuota, porcentaje, monto_a_pagar, monto_pendiente, fecha_pago, estatus_pago, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)"
            );
            foreach ($cuotas as $c) {
                $num        = (int)$c['numero'];
                $porcentaje = (float)$c['porcentaje'];
                $monto      = (float)$c['monto'];
                $fecha_pago = $c['fecha_pago'];
                $stmt3->bind_param('iidddss', $asignacion_id, $num, $porcentaje, $monto, $monto, $fecha_pago, $this->user);
                if (!$stmt3->execute()) throw new Exception("Error al insertar cuota: " . $stmt3->error);
            }
            $stmt3->close();

            $this->db->commit();
            return (string)$asignacion_id;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function listarCuotas(string $asignacion_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.numero_cuota, c.porcentaje, c.monto_a_pagar, c.fecha_pago, c.estatus_pago, c.comprobante
             FROM cuotas_articulo c
             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
             WHERE c.asignacion_id = ? AND aa.usuario_id = ?
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

    public function listarDetalle(string $asignacion_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.id, d.articulo_id, d.cantidad, d.precio_unitario,
                    a.nombre as articulo_nombre, a.foto as articulo_foto
             FROM asignacion_articulo_detalle d
             JOIN articulos a ON d.articulo_id = a.id
             WHERE d.asignacion_id = ?
             ORDER BY d.id ASC"
        );
        $stmt->bind_param('s', $asignacion_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function eliminar(string $id): bool
    {
        $this->db->begin_transaction();
        try {
            $s1 = $this->db->prepare(
                "DELETE c FROM cuotas_articulo c
                 INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
                 WHERE aa.id = ? AND aa.usuario_id = ?"
            );
            $s1->bind_param('ss', $id, $this->user);
            $s1->execute();
            $s1->close();

            $s2 = $this->db->prepare("DELETE FROM asignaciones_articulos WHERE id=? AND usuario_id=?");
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