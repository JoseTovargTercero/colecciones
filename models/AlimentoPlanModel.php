<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class AlimentoPlanModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
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
        $sql = "
            SELECT p.id, p.nombre, p.cantidad_animales_estimados, p.activo,
                   CONCAT(a.nombre_personalizado,' - ',a.tipo_area) ubicacion
            FROM alimentos_planes_alimenticios p
            JOIN areas a ON a.area_id = p.ubicacion_id
            WHERE p.activo = 1
        ";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function obtener(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM alimentos_planes_alimenticios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $plan = $stmt->get_result()->fetch_assoc();

        if (!$plan) throw new RuntimeException("Plan no encontrado");

        $stmt = $this->db->prepare("
            SELECT d.*, a.nombre alimento
            FROM alimentos_planes_alimenticios_detalle d
            JOIN alimentos a ON a.id = d.alimento_id
            WHERE d.plan_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $plan['detalles'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return $plan;
    }

    public function crear(array $data): void
    {
        if (empty($data['detalles'])) {
            throw new RuntimeException("Debe registrar al menos un horario de alimentación");
        }

        [$now, $user] = $this->nowWithAudit();
        $this->db->begin_transaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO alimentos_planes_alimenticios
                (ubicacion_id, nombre, observacion, cantidad_animales_estimados, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "issis",
                $data['ubicacion_id'],
                $data['nombre'],
                $data['observacion'],
                $data['cantidad_animales_estimados'],
                $now
            );
            $stmt->execute();
            $planId = $this->db->insert_id;

            $stmtDet = $this->db->prepare("
                INSERT INTO alimentos_planes_alimenticios_detalle
                (plan_id, alimento_id, consumo_diario_kg, hora)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($data['detalles'] as $d) {
                $stmtDet->bind_param(
                    "iids",
                    $planId,
                    $d['alimento_id'],
                    $d['consumo_diario_kg'],
                    $d['hora']
                );
                $stmtDet->execute();
            }

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE alimentos_planes_alimenticios SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}
