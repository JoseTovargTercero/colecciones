<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/FechasHelper.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class AlimentosConsumoModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Contexto de auditoría + timezone
     */
    private function nowWithAudit(): array
    {
        $env = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');

        // userId=0 si aún no hay sesión
        $uuid    = UuidHelper::generateUUIDv4();
        $actorId = $_SESSION['user_id'] ?? $uuid;

        $env->applyAuditContext($this->db, $actorId);

        $tzManager = new TimezoneManager($this->db);
        $tzManager->applyTimezone();

        return [$env->getCurrentDatetime(), $env];
    }


    /**
     * 🔒 Anti-duplicados
     */
    private function consumoYaRegistrado(
        int $ubicacionId,
        string $alimentoId,
        string $fecha,
        ?string $hora,
        string $planId
    ): bool {
        $sql = "
            SELECT id
            FROM alimentos_consumo_diario_ubicacion
            WHERE ubicacion_id = ?
              AND alimento_id  = ?
              AND fecha        = ?
              AND plan_id      = ?
              AND (
                    (hora IS NULL AND ? IS NULL)
                    OR hora = ?
                  )
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Error preparando validación de duplicado');
        }

        $stmt->bind_param(
            "isssss",
            $ubicacionId,
            $alimentoId,
            $fecha,
            $planId,
            $hora,
            $hora
        );

        $stmt->execute();
        $stmt->store_result();

        return $stmt->num_rows > 0;
    }

    /**
     * STUB – ajustar cuando tengas lógica real
     */
    private function obtenerAnimalesPorUbicacion(int $ubicacionId): int
    {
        return 50;
    }

    /**
     * Estado diario de planes
     */
    public function estadoHoy(): array
    {
        $fecha = date('Y-m-d');

        $sql = "
            SELECT
                p.id   AS plan_id,
                p.nombre,
                p.ubicacion_id,
                a.nombre_personalizado AS ubicacion,
                COUNT(c.id) AS consumos_hoy
            FROM alimentos_planes_alimenticios p
            JOIN areas a ON a.area_id = p.ubicacion_id
            LEFT JOIN alimentos_consumo_diario_ubicacion c
                ON c.plan_id = p.id AND c.fecha = ?
            WHERE p.activo = 1
            GROUP BY p.id
            ORDER BY a.nombre_personalizado
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $fecha);
        $stmt->execute();

        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as &$r) {
            $r['estado'] = $r['consumos_hoy'] > 0 ? 'APLICADO' : 'PENDIENTE';
        }

        return $rows;
    }


    public function ejecutarHorario(int $planId, int $horarioId, string $userUuid): bool
{
    $this->db->begin_transaction();

    try {
        // 1️⃣ Obtener info del horario
        $sqlHorario = "
            SELECT 
                d.plan_id,
                d.alimento_id,
                d.consumo_diario_kg,
                d.hora,
                p.ubicacion_id,
                p.cantidad_animales_estimados
            FROM alimentos_planes_alimenticios_detalle d
            INNER JOIN alimentos_planes_alimenticios p ON p.id = d.plan_id
            WHERE d.id = ? AND d.plan_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sqlHorario);
        $stmt->bind_param('ii', $horarioId, $planId);
        $stmt->execute();
        $horario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$horario) {
            throw new RuntimeException('Horario no encontrado');
        }

        // 2️⃣ Anti-duplicado (mismo día / hora)
        $sqlCheck = "
            SELECT id
            FROM alimentos_consumo_diario_ubicacion
            WHERE plan_id = ?
              AND alimento_id = ?
              AND fecha = CURDATE()
              AND hora = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sqlCheck);
        $stmt->bind_param(
            'iis',
            $planId,
            $horario['alimento_id'],
            $horario['hora']
        );
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            throw new RuntimeException('Este horario ya fue ejecutado hoy');
        }

        // 3️⃣ Insertar consumo diario
        $sqlConsumo = "
            INSERT INTO alimentos_consumo_diario_ubicacion (
                ubicacion_id,
                alimento_id,
                fecha,
                hora,
                animales_presentes,
                consumo_kg,
                plan_id,
                created_by
            ) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sqlConsumo);
        $stmt->bind_param(
            'iisidis',
            $horario['ubicacion_id'],
            $horario['alimento_id'],
            $horario['hora'],
            $horario['cantidad_animales_estimados'],
            $horario['consumo_diario_kg'],
            $planId,
            $userUuid
        );
        $stmt->execute();
        $stmt->close();

        // 4️⃣ Registrar movimiento de inventario
        $sqlMovimiento = "
            INSERT INTO alimentos_movimientos (
                alimento_id,
                tipo_movimiento,
                cantidad_kg,
                fecha,
                referencia,
                created_by
            ) VALUES (?, 'CONSUMO', ?, CURDATE(), ?, ?)
        ";

        $referencia = "Plan {$planId} - Horario {$horario['hora']}";

        $stmt = $this->db->prepare($sqlMovimiento);
        $stmt->bind_param(
            'idss',
            $horario['alimento_id'],
            $horario['consumo_diario_kg'],
            $referencia,
            $userUuid
        );
        $stmt->execute();
        $stmt->close();

        // 5️⃣ Actualizar stock
        $sqlStock = "
            UPDATE alimentos
            SET stock_kg = stock_kg - ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sqlStock);
        $stmt->bind_param(
            'di',
            $horario['consumo_diario_kg'],
            $horario['alimento_id']
        );
        $stmt->execute();
        $stmt->close();

        $this->db->commit();
        return true;

    } catch (\Throwable $e) {
        $this->db->rollback();
        throw $e;
    }
}
   /**
     * Devuelve el detalle completo de un plan alimenticio
     * con estado por horario (ejecutado / pendiente)
     */
    public function detallePlan($planId): array
    {

        // 1️⃣ Datos generales del plan + ubicación
        $sqlPlan = "
            SELECT 
                p.id,
                p.nombre,
                p.ubicacion_id,
                a.nombre_personalizado AS ubicacion,
                DATE(p.created_at) AS fecha
            FROM alimentos_planes_alimenticios p
            INNER JOIN areas a ON a.area_id = p.ubicacion_id
            WHERE p.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sqlPlan);
        $stmt->bind_param('i', $planId);
        $stmt->execute();
        $plan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$plan) {
            throw new RuntimeException('Plan no encontrado');
        }

        // 2️⃣ Detalle por horario + estado actual
        $sqlDetalle = "
            SELECT
                d.id                AS horario_id,
                d.alimento_id,
                d.consumo_diario_kg AS cantidad_planificada,
                d.hora,

                c.id                AS consumo_id,
                c.consumo_kg        AS cantidad_ejecutada

            FROM alimentos_planes_alimenticios_detalle d

            LEFT JOIN alimentos_consumo_diario_ubicacion c
                ON c.plan_id     = d.plan_id
                AND c.alimento_id = d.alimento_id
                AND c.hora        = d.hora
                AND c.fecha       = CURDATE()

            WHERE d.plan_id = ?
            ORDER BY d.hora ASC
        ";

        $stmt = $this->db->prepare($sqlDetalle);
        $stmt->bind_param('i', $planId);
        $stmt->execute();
        $res = $stmt->get_result();

        $horarios = [];

        while ($row = $res->fetch_assoc()) {
            $horarios[] = [
                'horario_id' => (int) $row['horario_id'],
                'alimento_id' => (int) $row['alimento_id'],
                'hora' => $row['hora'],
                'cantidad' => (float) $row['cantidad_planificada'],
                'ejecutado' => $row['consumo_id'] !== null,
                'consumo_real' => $row['cantidad_ejecutada']
                    ? (float) $row['cantidad_ejecutada']
                    : null
            ];
        }

        $stmt->close();

        // 3️⃣ Respuesta final
        return [
            'id' => (int) $plan['id'],
            'nombre' => $plan['nombre'],
            'ubicacion_id' => $plan['ubicacion_id'],
            'ubicacion' => $plan['ubicacion'],
            'fecha' => $plan['fecha'],
            'horarios' => $horarios
        ];
    }
    /**
     * Registro de consumo de un plan
     */
    public function registrarConsumoPlan(string $planId, int $ubicacionId): void
    {
        [$now] = $this->nowWithAudit();
        $fecha = date('Y-m-d');

        $this->db->begin_transaction();

        try {
            $animales = $this->obtenerAnimalesPorUbicacion($ubicacionId);

            $sql = "
                SELECT d.*, a.nombre AS alimento
                FROM alimentos_planes_alimenticios_detalle d
                JOIN alimentos a ON a.id = d.alimento_id
                WHERE d.plan_id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $planId);
            $stmt->execute();

            $detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            if (empty($detalles)) {
                throw new RuntimeException('El plan no tiene alimentos configurados');
            }

            foreach ($detalles as $d) {

                if ($this->consumoYaRegistrado(
                    $ubicacionId,
                    $d['alimento_id'],
                    $fecha,
                    $d['hora'],
                    $planId
                )) {
                    throw new RuntimeException(
                        "Consumo duplicado: {$d['alimento']} ({$d['hora']})"
                    );
                }

                $consumoId = UuidHelper::generateUUIDv4();

                // 1️⃣ Consumo diario
                $stmt = $this->db->prepare("
                    INSERT INTO alimentos_consumo_diario_ubicacion
                    (id, ubicacion_id, alimento_id, fecha, animales_presentes,
                     consumo_kg, plan_id, hora, created_at, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "sissidssss",
                    $consumoId,
                    $ubicacionId,
                    $d['alimento_id'],
                    $fecha,
                    $animales,
                    $d['consumo_diario_kg'],
                    $planId,
                    $d['hora'],
                    $now,
                    $_SESSION['user_id']
                );
                $stmt->execute();

                // 2️⃣ Movimiento
                $movId = UuidHelper::generateUUIDv4();
                $ref   = "Plan {$planId} - Ubicación {$ubicacionId}";

                $stmt = $this->db->prepare("
                    INSERT INTO alimentos_movimientos
                    (id, alimento_id, tipo_movimiento, cantidad_kg, fecha,
                     referencia, created_at, created_by)
                    VALUES (?, ?, 'CONSUMO', ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "ssdssss",
                    $movId,
                    $d['alimento_id'],
                    $d['consumo_diario_kg'],
                    $fecha,
                    $ref,
                    $now,
                    $_SESSION['user_id']
                );
                $stmt->execute();

                // 3️⃣ Descuento de stock
                $stmt = $this->db->prepare("
                    UPDATE alimentos
                    SET stock_kg = stock_kg - ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "ds",
                    $d['consumo_diario_kg'],
                    $d['alimento_id']
                );
                $stmt->execute();
            }

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Ejecuta todos los planes activos (UI o CRON)
     */
    public function registrarTodosHoy(): void
    {
        $sql = "
            SELECT id, ubicacion_id
            FROM alimentos_planes_alimenticios
            WHERE activo = 1
        ";

        $planes = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        foreach ($planes as $p) {
            $this->registrarConsumoPlan(
                $p['id'],
                (int)$p['ubicacion_id']
            );
        }
    }
}
