<?php
require_once __DIR__ . '/../config/Database.php';

class ArticuloModel
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(?string $empresa_id = null): array
    {
        $u = $_SESSION['user_id'] ?? '';
        $sql = "SELECT id, empresa_id, coleccion_id, nombre, foto, precio_final FROM articulos WHERE usuario_id='$u'";
        if ($empresa_id) {
            $sql .= " AND empresa_id='" . $this->db->real_escape_string($empresa_id) . "'";
        }
        $r = $this->db->query($sql);
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function fill(&$d)
    {
        $d['nombre'] = trim($d['nombre'] ?? '');
        $d['foto'] = trim($d['foto'] ?? '');
        $d['coleccion_id'] = trim($d['coleccion_id'] ?? '');
        $d['precio_final'] = (float)($d['precio_final'] ?? 0);
        if (!$d['nombre']) throw new Exception('Nombre requerido');
    }

    public function crear(array $d): string
    {
        $this->fill($d);
        $e = trim($d['empresa_id'] ?? '');
        if (!$e) throw new Exception('Empresa requerida');
        $u = $_SESSION['user_id'] ?? '';

        $colId = $d['coleccion_id'] ?: null;

        $stmt = $this->db->prepare("INSERT INTO articulos (empresa_id, coleccion_id, nombre, foto, precio_final, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssds', $e, $colId, $d['nombre'], $d['foto'], $d['precio_final'], $u);
        $stmt->execute();
        return (string)$this->db->insert_id;
    }

    public function actualizar(string $id, array $d): bool
    {
        $this->fill($d);
        $colId = $d['coleccion_id'] ?: null;

        $stmt = $this->db->prepare("UPDATE articulos SET nombre=?, foto=?, coleccion_id=?, precio_final=? WHERE id=?");
        $stmt->bind_param('sssds', $d['nombre'], $d['foto'], $colId, $d['precio_final'], $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function eliminar(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM articulos WHERE id=?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}