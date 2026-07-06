<?php
require_once __DIR__ . '/../config/Database.php';

class PreferenciasPremiosModel
{
    private $db, $user;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->user = $_SESSION['user_id'] ?? '';
    }

    public function listar(): array
    {
        $sql = "SELECT 
                    ps.id,
                    ps.status,
                    ps.created_at as fecha_solicitud,
                    ps.entregado as fecha_entrega,
                    p.nombre as premio_nombre,
                    p.valor as premio_valor,
                    v.nombre as vendedor_nombres,
                    v.cedula as vendedor_cedula,
                    e.nombre as empresa_nombre,
                    t.nombre as temporada_nombre
                FROM premios_solicitados ps
                INNER JOIN premios p ON ps.premio_id = p.id
                INNER JOIN vendedores v ON ps.vendedor_id = v.id
                INNER JOIN empresas e ON ps.empresa_id = e.id
                INNER JOIN temporadas t ON ps.temporada_id = t.id
                WHERE ps.status != 'entregado' AND ps.usuario_id = ?
                ORDER BY ps.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $this->user);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function obtenerPremiosPorEmpresa(int $empresa_id): array
    {
        $sql = "SELECT p.id, p.nombre, p.valor, p.tipo
                FROM premios p
                INNER JOIN empresas e ON p.empresa_id = e.id
                WHERE p.empresa_id = ? AND e.usuario_id = ? AND p.tipo = 'incentivo'
                ORDER BY p.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $empresa_id, $this->user);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function pagosTiempoCuotas(int $vendedor_id, int $empresa_id, string $temporada_id): array
    {
        $sql = "SELECT c.id, c.numero_cuota, c.fecha_pago, c.monto_a_pagar, cc.nombre as coleccion
                FROM cuotas_coleccion c
                INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
                INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
                WHERE c.pagado_a_tiempo = 1 AND c.premiado = 0
                  AND ac.vendedor_id = ?
                  AND ac.temporada_id = ?
                  AND cc.empresa_id = ?
                  AND ac.usuario_id = ?
                ORDER BY c.numero_cuota ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isis', $vendedor_id, $temporada_id, $empresa_id, $this->user);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function asignarPremiosPagosTiempo(int $empresa_id, string $temporada_id, int $vendedor_id, array $asignaciones): void
    {
        $u = $this->user;
        if (empty($asignaciones)) throw new Exception('No hay asignaciones de premios.');

        $stmt = $this->db->prepare("SELECT id FROM vendedores WHERE id=? AND usuario_id=?");
        $stmt->bind_param('is', $vendedor_id, $u);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new Exception('Vendedor no encontrado');
        $stmt->close();

        $stmt = $this->db->prepare("SELECT id FROM empresas WHERE id=? AND usuario_id=?");
        $stmt->bind_param('is', $empresa_id, $u);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new Exception('Empresa no encontrada');
        $stmt->close();

        $this->db->begin_transaction();
        try {
            $stmtIns = $this->db->prepare(
                "INSERT INTO premios_solicitados (vendedor_id, empresa_id, temporada_id, premio_id, status, usuario_id) VALUES (?, ?, ?, ?, 'completado', ?)"
            );
            $stmtUpd = $this->db->prepare(
                "UPDATE cuotas_coleccion SET premiado = ? WHERE id = ? AND pagado_a_tiempo = 1 AND premiado = 0"
            );
            foreach ($asignaciones as $a) {
                $cuota_id  = (int)($a['cuota_id'] ?? 0);
                $premio_id = (int)($a['premio_id'] ?? 0);
                if (!$cuota_id || !$premio_id) continue;

                $stmtIns->bind_param('iisis', $vendedor_id, $empresa_id, $temporada_id, $premio_id, $u);
                $stmtIns->execute();
                $inserted_id = $this->db->insert_id;

                $stmtUpd->bind_param('ii', $inserted_id, $cuota_id);
                $stmtUpd->execute();
            }
            $stmtIns->close();
            $stmtUpd->close();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function pagosTiempo(int $empresa_id, string $temporada_id): array
    {
        $sql = "SELECT v.id, v.nombre, v.cedula, COUNT(c.id) as cuotas_pagadas_tiempo
                FROM cuotas_coleccion c
                INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
                INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
                INNER JOIN vendedores v ON ac.vendedor_id = v.id
                WHERE c.pagado_a_tiempo = 1 AND c.premiado = 0
                  AND ac.temporada_id = ?
                  AND cc.empresa_id = ?
                  AND ac.usuario_id = ?
                GROUP BY v.id, v.nombre, v.cedula
                ORDER BY cuotas_pagadas_tiempo DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sis', $temporada_id, $empresa_id, $this->user);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function historial(): array
    {
        $sql = "SELECT 
                    ps.id,
                    ps.status,
                    ps.created_at as fecha_solicitud,
                    ps.entregado as fecha_entrega,
                    p.nombre as premio_nombre,
                    p.valor as premio_valor,
                    v.nombre as vendedor_nombres,
                    v.cedula as vendedor_cedula,
                    e.nombre as empresa_nombre,
                    t.nombre as temporada_nombre
                FROM premios_solicitados ps
                INNER JOIN premios p ON ps.premio_id = p.id
                INNER JOIN vendedores v ON ps.vendedor_id = v.id
                INNER JOIN empresas e ON ps.empresa_id = e.id
                INNER JOIN temporadas t ON ps.temporada_id = t.id
                WHERE ps.status = 'entregado' AND ps.usuario_id = ?
                ORDER BY ps.entregado DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $this->user);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function entregar(int $id): bool
    {
        $sql = "UPDATE premios_solicitados SET status = 'entregado', entregado = CURDATE() WHERE id = ? AND usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $id, $this->user);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;
    }
}
