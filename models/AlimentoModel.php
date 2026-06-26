<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class AlimentoModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance(); // mysqli
    }

    private function nowWithAudit(): array
    {
        $env = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');
        $uuid = UuidHelper::generateUUIDv4();
        $actorId = $_SESSION['user_id'] ?? $uuid;
        $env->applyAuditContext($this->db, $actorId);
        (new TimezoneManager($this->db))->applyTimezone();
        return [$env->getCurrentDatetime(), $actorId];
    }

    public function listar(): array
    {
        $sql = "SELECT * FROM alimentos WHERE activo = 1 ORDER BY nombre";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtener(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM alimentos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: [];
    }

    public function crear(array $data): int
    {
        [$now, $user] = $this->nowWithAudit();

        $this->db->begin_transaction();

        try {
            $sql = "
                INSERT INTO alimentos 
                (nombre, tipo, stock_minimo_kg, created_at, created_by)
                VALUES (?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($sql);
            $stockMin = (float)($data['stock_minimo_kg'] ?? 0);

            $stmt->bind_param(
                "ssdss",
                $data['nombre'],
                $data['tipo'],
                $stockMin,
                $now,
                $user
            );

            $stmt->execute();
            $id = $this->db->insert_id;

            $this->db->commit();
            return $id;

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function actualizar(int $id, array $data): void
    {
        $this->db->begin_transaction();

        try {
            $sql = "
                UPDATE alimentos 
                SET nombre = ?, tipo = ?, stock_minimo_kg = ?
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stockMin = (float)($data['stock_minimo_kg'] ?? 0);

            $stmt->bind_param(
                "ssdi",
                $data['nombre'],
                $data['tipo'],
                $stockMin,
                $id
            );

            $stmt->execute();
            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function registrarIngreso(array $data): void
    {
        [$now, $user] = $this->nowWithAudit();

        $this->db->begin_transaction();

        try {
            $alimentoId = (int)$data['alimento_id'];
            $cantidad = (float)$data['cantidad_kg'];
            $observacion = $data['observacion'] ?? null;

            // 1. Actualizar stock
            $sqlUpdate = "UPDATE alimentos SET stock_kg = stock_kg + ? WHERE id = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->bind_param("di", $cantidad, $alimentoId);
            $stmtUpdate->execute();

            // 2. Registrar movimiento
            $sqlMov = "
                INSERT INTO alimentos_movimientos 
                (alimento_id, tipo_movimiento, cantidad_kg, fecha, observacion, created_by, created_at)
                VALUES (?, 'INGRESO', ?, CURDATE(), ?, ?, ?)
            ";
            $stmtMov = $this->db->prepare($sqlMov);
            $stmtMov->bind_param("idsis", $alimentoId, $cantidad, $observacion, $user, $now);
            $stmtMov->execute();

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE alimentos SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}
