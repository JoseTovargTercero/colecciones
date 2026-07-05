<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class EmpresaModel
{
    private $db;
    private $table = 'empresas';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ponytail: audit helper reutilizado de otros modelos
    private function nowWithAudit(): string
    {
        $env     = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');
        $actorId = $_SESSION['user_id'] ?? UuidHelper::generateUUIDv4();
        $env->applyAuditContext($this->db, $actorId);
        (new TimezoneManager($this->db))->applyTimezone();
        return $env->getCurrentDatetime();
    }

    public function listar(): array
    {
        $sql = "SELECT e.id, e.nombre, e.telefono, e.dias_retraso_permitido, e.created_at, e.usuario_id,
                       (SELECT c.cantidad_cuetas FROM configuracion_cuotas_empresas c WHERE c.empresa_id = e.id LIMIT 1) as cantidad_cuotas,
                       (SELECT c.cuotas FROM configuracion_cuotas_empresas c WHERE c.empresa_id = e.id LIMIT 1) as cuotas
                FROM {$this->table} e
                ORDER BY e.created_at DESC";
        $res = $this->db->query($sql);
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as &$r) {
            if ($r['cuotas']) $r['cuotas'] = json_decode($r['cuotas'], true);
        }
        return $rows;
    }

    public function obtenerPorId(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id, e.nombre, e.telefono, e.dias_retraso_permitido, e.created_at, e.usuario_id,
                    (SELECT c.cantidad_cuetas FROM configuracion_cuotas_empresas c WHERE c.empresa_id = e.id LIMIT 1) as cantidad_cuotas,
                    (SELECT c.cuotas FROM configuracion_cuotas_empresas c WHERE c.empresa_id = e.id LIMIT 1) as cuotas
             FROM {$this->table} e
             WHERE e.id = ?"
        );
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && $row['cuotas']) {
            $row['cuotas'] = json_decode($row['cuotas'], true);
        }
        return $row ?: null;
    }

    public function crear(array $in): string
    {
        $nombre   = trim($in['nombre'] ?? '');
        $telefono = trim($in['telefono'] ?? '');
        $dias     = (int)($in['dias_retraso_permitido'] ?? 0);
        $cantidad = (int)($in['cantidad_cuotas'] ?? 0);
        $cuotas   = $in['cuotas'] ?? [];

        if ($nombre === '') throw new InvalidArgumentException('El nombre es obligatorio.');
        if ($cantidad <= 0) throw new InvalidArgumentException('Cantidad de cuotas obligatoria.');

        $now       = $this->nowWithAudit();
        $usuarioId = $_SESSION['user_id'] ?? '';

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (nombre, telefono, dias_retraso_permitido, created_at, usuario_id) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssiss', $nombre, $telefono, $dias, $now, $usuarioId);
            $stmt->execute();
            $stmt->close();
            $id = $this->db->insert_id;

            $json_cuotas = json_encode($cuotas);
            $stmt2 = $this->db->prepare("INSERT INTO configuracion_cuotas_empresas (empresa_id, cantidad_cuetas, cuotas) VALUES (?, ?, ?)");
            $stmt2->bind_param('iis', $id, $cantidad, $json_cuotas);
            $stmt2->execute();
            $stmt2->close();

            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function actualizar(string $id, array $in): bool
    {
        $nombre   = trim($in['nombre'] ?? '');
        $telefono = trim($in['telefono'] ?? '');
        $dias     = (int)($in['dias_retraso_permitido'] ?? 0);
        $cantidad = (int)($in['cantidad_cuotas'] ?? 0);
        $cuotas   = $in['cuotas'] ?? [];

        if ($nombre === '') throw new InvalidArgumentException('El nombre es obligatorio.');
        if ($cantidad <= 0) throw new InvalidArgumentException('Cantidad de cuotas obligatoria.');

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET nombre=?, telefono=?, dias_retraso_permitido=? WHERE id=?");
            $stmt->bind_param('ssis', $nombre, $telefono, $dias, $id);
            $stmt->execute();
            $stmt->close();

            $json_cuotas = json_encode($cuotas);
            $stmtCheck = $this->db->prepare("SELECT id FROM configuracion_cuotas_empresas WHERE empresa_id=?");
            $stmtCheck->bind_param('s', $id);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();

            if ($res) {
                $stmt2 = $this->db->prepare("UPDATE configuracion_cuotas_empresas SET cantidad_cuetas=?, cuotas=? WHERE empresa_id=?");
                $stmt2->bind_param('iss', $cantidad, $json_cuotas, $id);
            } else {
                $id_cuotas = UuidHelper::generateUUIDv4();
                $stmt2 = $this->db->prepare("INSERT INTO configuracion_cuotas_empresas (id, empresa_id, cantidad_cuetas, cuotas) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param('ssis', $id_cuotas, $id, $cantidad, $json_cuotas);
            }
            $stmt2->execute();
            $stmt2->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function eliminar(string $id): bool
    {
        $this->db->begin_transaction();
        try {
            $stmt2 = $this->db->prepare("DELETE FROM configuracion_cuotas_empresas WHERE empresa_id=?");
            $stmt2->bind_param('s', $id);
            $stmt2->execute();
            $stmt2->close();

            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id=?");
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            $this->db->commit();
            return $affected > 0;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}

