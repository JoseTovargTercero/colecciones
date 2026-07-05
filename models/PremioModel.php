<?php
require_once __DIR__ . '/../config/Database.php';

class PremioModel {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function listar(?string $empresa_id = null): array {
        $sql = "SELECT p.id, p.empresa_id, p.nombre, p.foto, p.valor, e.nombre as empresa_nombre 
                FROM premios p 
                LEFT JOIN empresas e ON p.empresa_id = e.id";
        if ($empresa_id) {
            $sql .= " WHERE p.empresa_id='" . $this->db->real_escape_string($empresa_id) . "'";
        }
        $sql .= " ORDER BY e.nombre ASC, p.nombre ASC";
        $r = $this->db->query($sql);
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function fill(&$d) {
        $d['nombre'] = trim($d['nombre'] ?? '');
        $d['foto'] = trim($d['foto'] ?? '');
        $d['valor'] = (float)($d['valor'] ?? 0);
        if (!$d['nombre']) throw new Exception('Nombre requerido');
    }

    public function crear(array $d): string {
        $this->fill($d);
        $e = trim($d['empresa_id'] ?? '');
        if (!$e) throw new Exception('Empresa requerida');
        
        $stmt = $this->db->prepare("INSERT INTO premios (empresa_id, nombre, foto, valor) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssd', $e, $d['nombre'], $d['foto'], $d['valor']);
        $stmt->execute();
        return (string)$this->db->insert_id;
    }

    public function actualizar(string $id, array $d): bool {
        $this->fill($d);
        $stmt = $this->db->prepare("UPDATE premios SET nombre=?, foto=?, valor=? WHERE id=?");
        $stmt->bind_param('ssds', $d['nombre'], $d['foto'], $d['valor'], $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function eliminar(string $id): bool {
        $stmt = $this->db->prepare("DELETE FROM premios WHERE id=?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
