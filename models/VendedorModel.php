<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class VendedorModel {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function listar(): array {
        $u = $_SESSION['user_id'] ?? '';
        $r = $this->db->query("SELECT id, nombre, cedula, telefono, nivel FROM vendedores WHERE usuario_id='$u' ORDER BY created_at DESC");
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function fill(&$d) {
        $d['nombre'] = trim($d['nombre'] ?? '');
        $d['cedula'] = trim($d['cedula'] ?? '');
        $d['telefono'] = trim($d['telefono'] ?? '');
        $d['nivel'] = (int)($d['nivel'] ?? 1);
        if (!$d['nombre'] || !$d['cedula']) throw new Exception('Nombre y cédula requeridos');
        if ($d['nivel'] < 1 || $d['nivel'] > 4) throw new Exception('Nivel inválido');
    }

    public function crear(array $d): string {
        $this->fill($d);
        $u = $_SESSION['user_id'] ?? '';
        
        $stmt = $this->db->prepare("INSERT INTO vendedores (nombre, cedula, telefono, nivel, created_at, usuario_id) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param('sssis', $d['nombre'], $d['cedula'], $d['telefono'], $d['nivel'], $u);
        $stmt->execute();
        return (string)$this->db->insert_id;
    }

    public function actualizar(string $id, array $d): bool {
        $this->fill($d);
        $stmt = $this->db->prepare("UPDATE vendedores SET nombre=?, cedula=?, telefono=?, nivel=? WHERE id=?");
        $stmt->bind_param('sssis', $d['nombre'], $d['cedula'], $d['telefono'], $d['nivel'], $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function eliminar(string $id): bool {
        $stmt = $this->db->prepare("DELETE FROM vendedores WHERE id=?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function buscarPorCedula(string $query): ?array
    {
        $u = $_SESSION['user_id'] ?? '';

        // Detect if query is a cédula (mostly digits) or a name
        if (preg_match('/^[\d\.\-]+$/', $query)) {
            $stmt = $this->db->prepare(
                "SELECT id, nombre, cedula FROM vendedores WHERE cedula = ? AND usuario_id = ? LIMIT 1"
            );
            $stmt->bind_param('ss', $query, $u);
        } else {
            $like = '%' . $query . '%';
            $stmt = $this->db->prepare(
                "SELECT id, nombre, cedula FROM vendedores WHERE nombre LIKE ? AND usuario_id = ? LIMIT 1"
            );
            $stmt->bind_param('ss', $like, $u);
        }
        $stmt->execute();
        $r = $stmt->get_result();
        $vendedor = $r->fetch_assoc();
        $stmt->close();

        if (!$vendedor) return null;

        $vid = (int)$vendedor['id'];

        // Asignaciones no finalizadas agrupadas por empresa
        $r = $this->db->query(
            "SELECT e.id as empresa_id, e.nombre as empresa_nombre, COUNT(ac.id) as total_asignaciones
             FROM asignaciones_colecciones ac
             INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
             INNER JOIN empresas e ON cc.empresa_id = e.id
             WHERE ac.vendedor_id = $vid AND ac.estado != 'finalizada'
             GROUP BY e.id, e.nombre
             ORDER BY e.nombre"
        );
        $asignaciones = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        // Premios solicitados no entregados agrupados por empresa
        $r = $this->db->query(
            "SELECT e.id as empresa_id, e.nombre as empresa_nombre, COUNT(ps.id) as total_premios
             FROM premios_solicitados ps
             INNER JOIN empresas e ON ps.empresa_id = e.id
             WHERE ps.vendedor_id = $vid AND ps.status != 'entregado'
             GROUP BY e.id, e.nombre
             ORDER BY e.nombre"
        );
        $premios = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        return [
            'vendedor' => $vendedor,
            'asignaciones' => $asignaciones,
            'premios' => $premios,
        ];
    }
}
