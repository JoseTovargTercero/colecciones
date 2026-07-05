<?php
require_once __DIR__ . '/../config/Database.php';

class PreferenciasPremiosModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(): array
    {
        $sql = "SELECT 
                    ps.id,
                    ps.status,
                    ps.created_at as fecha_solicitud,
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
                WHERE ps.status != 'entregado'
                ORDER BY ps.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function obtenerPremiosPorEmpresa(int $empresa_id): array
    {
        $sql = "SELECT id, nombre, valor FROM premios WHERE empresa_id = ? ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $empresa_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function asignarPremiosPagosTiempo(int $empresa_id, string $temporada_id, int $vendedor_id, array $premio_ids, string $u): void
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO premios_solicitados (vendedor_id, empresa_id, temporada_id, premio_id, status, usuario_id) VALUES (?, ?, ?, ?, 'completado', ?)"
            );
            foreach ($premio_ids as $pid) {
                $stmt->bind_param('iisis', $vendedor_id, $empresa_id, $temporada_id, $pid, $u);
                $stmt->execute();
            }
            $stmt->close();

            $stmt2 = $this->db->prepare(
                "UPDATE cuotas_coleccion c
                 INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
                 INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
                 SET c.premiado = 1
                 WHERE ac.vendedor_id = ? AND ac.temporada_id = ? AND cc.empresa_id = ?
                   AND c.pagado_a_tiempo = 1 AND c.premiado = 0"
            );
            $stmt2->bind_param('isi', $vendedor_id, $temporada_id, $empresa_id);
            $stmt2->execute();
            $stmt2->close();

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
                GROUP BY v.id, v.nombre, v.cedula
                ORDER BY cuotas_pagadas_tiempo DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $temporada_id, $empresa_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function entregar(int $id): bool
    {
        $sql = "UPDATE premios_solicitados SET status = 'entregado' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;
    }
}
