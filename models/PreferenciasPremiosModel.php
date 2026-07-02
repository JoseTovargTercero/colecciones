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
