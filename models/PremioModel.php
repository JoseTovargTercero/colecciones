<?php
require_once __DIR__ . '/../config/Database.php';

class PremioModel {
    private $db, $user;
    public function __construct() {
        $this->db = Database::getInstance();
        $this->user = $_SESSION['user_id'] ?? '';
    }

    public function listar(?string $empresa_id = null): array {
        $sql = "SELECT p.id, p.empresa_id, p.nombre, p.foto, p.valor, p.tipo, e.nombre as empresa_nombre 
                FROM premios p 
                INNER JOIN empresas e ON p.empresa_id = e.id
                WHERE e.usuario_id = ?";
        $params = [$this->user];
        $types = 's';
        if ($empresa_id) {
            $sql .= " AND p.empresa_id = ?";
            $params[] = $empresa_id;
            $types .= 's';
        }
        $sql .= " ORDER BY e.nombre ASC, p.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
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

        $stmt = $this->db->prepare("SELECT id FROM empresas WHERE id=? AND usuario_id=?");
        $stmt->bind_param('ss', $e, $this->user);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$exists) throw new Exception('Empresa no encontrada');

        $tipo = $d['tipo'] ?? null;
        $stmt = $this->db->prepare("INSERT INTO premios (empresa_id, nombre, foto, valor, tipo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssds', $e, $d['nombre'], $d['foto'], $d['valor'], $tipo);
        $stmt->execute();
        return (string)$this->db->insert_id;
    }

    public function actualizar(string $id, array $d): bool {
        $this->fill($d);
        $tipo = $d['tipo'] ?? null;
        $stmt = $this->db->prepare(
            "UPDATE premios p
             INNER JOIN empresas e ON p.empresa_id = e.id
             SET p.nombre=?, p.foto=?, p.valor=?, p.tipo=?
             WHERE p.id=? AND e.usuario_id=?"
        );
        $stmt->bind_param('ssdsss', $d['nombre'], $d['foto'], $d['valor'], $tipo, $id, $this->user);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function eliminar(string $id): bool {
        $stmt = $this->db->prepare(
            "DELETE p FROM premios p
             INNER JOIN empresas e ON p.empresa_id = e.id
             WHERE p.id=? AND e.usuario_id=?"
        );
        $stmt->bind_param('ss', $id, $this->user);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
