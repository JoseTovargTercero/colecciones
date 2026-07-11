<?php
require_once __DIR__ . '/../config/Database.php';

class GerenciaModel {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function listar(): array {
        $u = $_SESSION['user_id'] ?? '';
        $r = $this->db->query("SELECT id, nombre FROM gerencias WHERE usuario_id='$u' ORDER BY created_at DESC");
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function crear(array $d): string {
        $n = trim($d['nombre'] ?? '');
        $u = $_SESSION['user_id'] ?? '';
        if (!$n) throw new Exception('El nombre es requerido');

        $stmt = $this->db->prepare("INSERT INTO gerencias (nombre, created_at, usuario_id) VALUES (?, NOW(), ?)");
        $stmt->bind_param('ss', $n, $u);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function actualizar(string $id, array $d): bool {
        $n = trim($d['nombre'] ?? '');
        if (!$n) throw new Exception('El nombre es requerido');

        $stmt = $this->db->prepare("UPDATE gerencias SET nombre=? WHERE id=?");
        $stmt->bind_param('ss', $n, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function eliminar(string $id): bool {
        $stmt = $this->db->prepare("DELETE FROM gerencias WHERE id=?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
