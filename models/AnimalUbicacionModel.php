<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class AnimalUbicacionModel
{
    private $db;
    private $table = 'animal_ubicaciones';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ============ Utilidades ============ */

    private function nowWithAudit(): array
    {
        $env = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');
        $uuid = UuidHelper::generateUUIDv4();
        $actorId = $_SESSION['user_id'] ?? $uuid;
        $env->applyAuditContext($this->db, $actorId);
        $tzManager = new TimezoneManager($this->db);
        $tzManager->applyTimezone();
        return [$env->getCurrentDatetime(), $env];
    }

    private function validarFecha(string $ymd, string $campo = 'fecha'): void
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd) !== 1) {
            throw new InvalidArgumentException("$campo inválida. Formato esperado YYYY-MM-DD.");
        }
        [$y, $m, $d] = array_map('intval', explode('-', $ymd));
        if (!checkdate($m, $d, $y)) {
            throw new InvalidArgumentException("$campo no es una fecha válida.");
        }
    }

    // ... (animalExiste, fincaExiste, apriscoExiste, areaExiste, recintoExiste siguen igual) ...

    private function animalExiste(string $animalId): bool
    {
        $sql = "SELECT 1 FROM animales WHERE animal_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar verificación de animal: " . $this->db->error);
        $stmt->bind_param('s', $animalId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }

    private function fincaExiste(?string $fincaId): bool
    {
        if ($fincaId === null)
            return true;
        $sql = "SELECT 1 FROM fincas WHERE finca_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error verificación finca: " . $this->db->error);
        $stmt->bind_param('s', $fincaId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }

    private function apriscoExiste(?string $apriscoId): bool
    {
        if ($apriscoId === null)
            return true;
        $sql = "SELECT 1 FROM apriscos WHERE aprisco_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error verificación aprisco: " . $this->db->error);
        $stmt->bind_param('s', $apriscoId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }

    private function areaExiste(?string $areaId): bool
    {
        if ($areaId === null)
            return true;
        $sql = "SELECT aprisco_id FROM areas WHERE area_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error verificación área: " . $this->db->error);
        $stmt->bind_param('s', $areaId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return isset($row['aprisco_id']);
    }

    private function recintoExiste(?string $recintoId): bool
    {
        if ($recintoId === null)
            return true;
        $sql = "SELECT area_id FROM recintos WHERE recinto_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error verificación recinto: " . $this->db->error);
        $stmt->bind_param('s', $recintoId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return isset($row['area_id']);
    }

    /**
     * Verifica la consistencia jerárquica y propaga IDs padres.
     * (Versión con paso por referencia que te envié anteriormente)
     */
    private function validarJerarquia(?string &$fincaId, ?string &$apriscoId, ?string &$areaId, ?string $recintoId): void
    {
        if ($recintoId !== null) {
            $sql = "SELECT r.area_id, a.aprisco_id, ap.finca_id
                    FROM recintos r
                    JOIN areas a    ON a.area_id = r.area_id
                    JOIN apriscos ap ON ap.aprisco_id = a.aprisco_id
                    WHERE r.recinto_id = ? AND r.deleted_at IS NULL AND a.deleted_at IS NULL AND ap.deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception("Error jerarquía recinto: " . $this->db->error);
            $stmt->bind_param('s', $recintoId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();

            if (!$row)
                throw new InvalidArgumentException("El recinto no existe o su jerarquía padre (área/aprisco) está eliminada.");
            if ($areaId !== null && $row['area_id'] !== $areaId) {
                throw new InvalidArgumentException("El recinto no pertenece al área especificada.");
            }
            if ($apriscoId !== null && $row['aprisco_id'] !== $apriscoId) {
                throw new InvalidArgumentException("El recinto no pertenece al aprisco especificado.");
            }
            if ($fincaId !== null && $row['finca_id'] !== $fincaId) {
                throw new InvalidArgumentException("El recinto no pertenece a la finca especificada.");
            }
            if ($areaId === null) $areaId = $row['area_id'];
            if ($apriscoId === null) $apriscoId = $row['aprisco_id'];
            if ($fincaId === null) $fincaId = $row['finca_id'];
        }

        if ($areaId !== null) {
            $sql = "SELECT ap.aprisco_id, ap.finca_id
                    FROM areas a
                    JOIN apriscos ap ON ap.aprisco_id = a.aprisco_id
                    WHERE a.area_id = ? AND a.deleted_at IS NULL AND ap.deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception("Error jerarquía área: " . $this->db->error);
            $stmt->bind_param('s', $areaId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if (!$row)
                throw new InvalidArgumentException("El área no existe, está eliminada o su aprisco padre está eliminado.");
            if ($apriscoId !== null && $row['aprisco_id'] !== $apriscoId) {
                throw new InvalidArgumentException("El área no pertenece al aprisco especificado.");
            }
            if ($fincaId !== null && $row['finca_id'] !== $fincaId) {
                throw new InvalidArgumentException("El área no pertenece a la finca especificada.");
            }
            if ($apriscoId === null) $apriscoId = $row['aprisco_id'];
            if ($fincaId === null) $fincaId = $row['finca_id'];
        }

        if ($apriscoId !== null) {
            $sql = "SELECT finca_id FROM apriscos WHERE aprisco_id = ? AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception("Error jerarquía aprisco: " . $this->db->error);
            $stmt->bind_param('s', $apriscoId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if (!$row)
                throw new InvalidArgumentException("El aprisco no existe o está eliminado.");
            if ($fincaId !== null && $row['finca_id'] !== $fincaId) {
                throw new InvalidArgumentException("El aprisco no pertenece a la finca especificada.");
            }
            if ($fincaId === null) $fincaId = $row['finca_id'];
        }
    }


    /* ============ Lecturas ============ */

    public function listar(
        int $limit = 10000,
        int $offset = 0,
        bool $incluirEliminados = false,
        ?string $animalId = null,
        ?string $fincaId = null,
        ?string $apriscoId = null,
        ?string $areaId = null,
        ?string $recintoId = null,
        ?string $desde = null,
        ?string $hasta = null
    ): array {
        $where = [];
        $params = [];
        $types = '';

        if (!$incluirEliminados) $where[] = 'u.deleted_at IS NULL';
        if ($animalId) { $where[] = 'u.animal_id = ?'; $params[] = $animalId; $types .= 's'; }
        if ($fincaId) { $where[] = 'u.finca_id  = ?'; $params[] = $fincaId; $types .= 's'; }
        if ($apriscoId) { $where[] = 'u.aprisco_id= ?'; $params[] = $apriscoId; $types .= 's'; }
        if ($areaId) { $where[] = 'u.area_id   = ?'; $params[] = $areaId; $types .= 's'; }
        if ($recintoId) { $where[] = 'u.recinto_id= ?'; $params[] = $recintoId; $types .= 's'; }
        
        // MODIFICADO: Filtros 'desde' y 'hasta' aplicados solo a fecha_desde
        if ($desde) { $this->validarFecha($desde, 'desde'); $where[] = 'u.fecha_desde >= ?'; $params[] = $desde; $types .= 's'; }
        if ($hasta) { $this->validarFecha($hasta, 'hasta'); $where[] = 'u.fecha_desde <= ?'; $params[] = $hasta; $types .= 's'; }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // MODIFICADO: Eliminados fecha_hasta y estado del SELECT
        $sql = "SELECT
                    u.animal_ubicacion_id,
                    u.animal_id,
                    a.identificador AS animal_identificador,
                    u.finca_id,   f.nombre AS nombre_finca,
                    u.aprisco_id, ap.nombre AS nombre_aprisco,
                    u.area_id,    ar.nombre_personalizado AS nombre_area, ar.numeracion AS area_numeracion,
                    u.recinto_id,
                    r.codigo_recinto AS codigo_recinto,
                    u.fecha_desde,
                    u.motivo, u.observaciones,
                    u.created_at, u.created_by, u.updated_at, u.updated_by,
                    u.deleted_at, u.deleted_by
                FROM {$this->table} u
                LEFT JOIN animales a ON a.animal_id = u.animal_id
                LEFT JOIN fincas   f ON f.finca_id  = u.finca_id
                LEFT JOIN apriscos ap ON ap.aprisco_id = u.aprisco_id
                LEFT JOIN areas    ar ON ar.area_id  = u.area_id
                LEFT JOIN recintos r ON r.recinto_id = u.recinto_id
                $whereSql
                ORDER BY u.fecha_desde DESC, u.created_at DESC
                LIMIT ? OFFSET ?"; // MODIFICADO: ORDER BY simplificado

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar listado: " . $this->db->error);

        $types .= 'ii'; $params[] = $limit; $params[] = $offset;

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }


    public function obtenerPorId(string $id): ?array
    {
        // MODIFICADO: Eliminados fecha_hasta y estado del SELECT
        $sql = "SELECT
                    u.animal_ubicacion_id,
                    u.animal_id,
                    a.identificador AS animal_identificador,
                    u.finca_id,   f.nombre AS nombre_finca,
                    u.aprisco_id, ap.nombre AS nombre_aprisco,
                    u.area_id,    ar.nombre_personalizado AS nombre_area, ar.numeracion AS area_numeracion,
                    u.recinto_id,
                    r.codigo_recinto AS codigo_recinto,
                    u.fecha_desde,
                    u.motivo, u.observaciones,
                    u.created_at, u.created_by, u.updated_at, u.updated_by,
                    u.deleted_at, u.deleted_by
                FROM {$this->table} u
                LEFT JOIN animales a ON a.animal_id = u.animal_id
                LEFT JOIN fincas   f ON f.finca_id  = u.finca_id
                LEFT JOIN apriscos ap ON ap.aprisco_id = u.aprisco_id
                LEFT JOIN areas    ar ON ar.area_id  = u.area_id
                LEFT JOIN recintos r ON r.recinto_id = u.recinto_id
                WHERE u.animal_ubicacion_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar consulta: " . $this->db->error);

        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getActual(string $animalId): ?array
    {
        // MODIFICADO: Eliminados fecha_hasta y estado del SELECT
        $sql = "SELECT
                    u.animal_ubicacion_id,
                    u.animal_id,
                    a.identificador AS animal_identificador,
                    u.finca_id,   f.nombre AS nombre_finca,
                    u.aprisco_id, ap.nombre AS nombre_aprisco,
                    u.area_id,    ar.nombre_personalizado AS nombre_area, ar.numeracion AS area_numeracion,
                    u.recinto_id,
                    r.codigo_recinto AS codigo_recinto,
                    u.fecha_desde,
                    u.motivo, u.observaciones
                FROM {$this->table} u
                LEFT JOIN animales a ON a.animal_id = u.animal_id
                LEFT JOIN fincas   f ON f.finca_id  = u.finca_id
                LEFT JOIN apriscos ap ON ap.aprisco_id = u.aprisco_id
                LEFT JOIN areas    ar ON ar.area_id  = u.area_id
                LEFT JOIN recintos r ON r.recinto_id = u.recinto_id
                WHERE u.animal_id = ?
                    AND u.deleted_at IS NULL
                ORDER BY u.fecha_desde DESC, u.created_at DESC
                LIMIT 1"; // MODIFICADO: Eliminado 'AND u.fecha_hasta IS NULL'
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar getActual: " . $this->db->error);
        $stmt->bind_param('s', $animalId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }


    /* ============ Escrituras ============ */

    /**
     * Crear ubicación (movimiento).
     * MODIFICADO: Simplemente añade un registro al historial.
     * La ubicación "actual" se determina por la última fecha_desde (ver getActual).
     */
    public function crear(array $data): string
    {
        if (empty($data['animal_id']) || empty($data['fecha_desde'])) {
            throw new InvalidArgumentException("Faltan campos requeridos: animal_id, fecha_desde.");
        }

        $animalId = (string) trim($data['animal_id']);
        $fincaId = isset($data['finca_id']) ? (string) trim((string) $data['finca_id']) : null;
        $apriscoId = isset($data['aprisco_id']) ? (string) trim((string) $data['aprisco_id']) : null;
        $areaId = isset($data['area_id']) ? (string) trim((string) $data['area_id']) : null;
        $recintoId = isset($data['recinto_id']) ? (string) trim((string) $data['recinto_id']) : null;

        $fechaDesde = (string) trim((string) $data['fecha_desde']);
        $this->validarFecha($fechaDesde, 'fecha_desde');

        // MODIFICADO: Eliminada toda la lógica de fecha_hasta y estado

        $motivo = isset($data['motivo']) ? strtoupper(trim((string) $data['motivo'])) : 'OTRO';
        if (!in_array($motivo, ['TRASLADO', 'INGRESO', 'EGRESO', 'AISLAMIENTO', 'VENTA', 'OTRO'], true)) {
            throw new InvalidArgumentException("motivo inválido. Use: TRASLADO, INGRESO, EGRESO, AISLAMIENTO, VENTA, OTRO.");
        }

        if (!$this->animalExiste($animalId)) {
            throw new RuntimeException('El animal especificado no existe o está eliminado.');
        }
        if (
            !$this->fincaExiste($fincaId) ||
            !$this->apriscoExiste($apriscoId) ||
            !$this->areaExiste($areaId) ||
            !$this->recintoExiste($recintoId)
        ) {
            throw new RuntimeException('Finca, aprisco, área o recinto no existen o están eliminados.');
        }

        // Validar jerarquía Y propagar IDs padres (paso por referencia)
        $this->validarJerarquia($fincaId, $apriscoId, $areaId, $recintoId);

        // MODIFICADO: Eliminada la validación de 'existeActiva'
        
        $observaciones = isset($data['observaciones']) ? trim((string) $data['observaciones']) : null;

        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();
            $uuid = UuidHelper::generateUUIDv4();
            $actorId = $_SESSION['user_id'] ?? $uuid;

            // MODIFICADO: Query sin fecha_hasta ni estado
            $sql = "INSERT INTO {$this->table}
                    (animal_ubicacion_id, animal_id, finca_id, aprisco_id, area_id, recinto_id,
                     fecha_desde, motivo, observaciones,
                     created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception("Error al preparar inserción: " . $this->db->error);

            // MODIFICADO: bind_param con 11 strings ('sssssssssss')
            $stmt->bind_param(
                'sssssssssss',
                $uuid,
                $animalId,
                $fincaId,
                $apriscoId,
                $areaId,
                $recintoId,
                $fechaDesde,
                $motivo,
                $observaciones,
                $now,
                $actorId
            );

            if (!$stmt->execute()) {
                $err = strtolower($stmt->error);
                $stmt->close();
                $this->db->rollback();
                if (str_contains($err, 'foreign key')) {
                    throw new RuntimeException('FK inválida (animal/finca/aprisco/area/recinto).');
                }
                throw new mysqli_sql_exception("Error al ejecutar inserción: " . $err);
            }

            $stmt->close();
            $this->db->commit();
            return $uuid;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Actualizar ubicación.
     * MODIFICADO: Ya no maneja lógica de 'estado' ni 'fecha_hasta'.
     */
    public function actualizar(string $id, array $data): bool
    {
        $campos = [];
        $params = [];
        $types = '';

        $current = $this->obtenerPorId($id);
        if (!$current || $current['deleted_at'] !== null) {
            throw new RuntimeException("La ubicación no existe o está eliminada.");
        }

        $fincaId = $current['finca_id'];
        $apriscoId = $current['aprisco_id'];
        $areaId = $current['area_id'];
        $recintoId = $current['recinto_id'];
        $fechaDesde = $current['fecha_desde'];

        if (array_key_exists('finca_id', $data)) {
            $fincaId = $data['finca_id'] !== null ? (string) $data['finca_id'] : null;
        }
        if (array_key_exists('aprisco_id', $data)) {
            $apriscoId = $data['aprisco_id'] !== null ? (string) $data['aprisco_id'] : null;
        }
        if (array_key_exists('area_id', $data)) {
            $areaId = $data['area_id'] !== null ? (string) $data['area_id'] : null;
        }
        if (array_key_exists('recinto_id', $data)) {
            $recintoId = $data['recinto_id'] !== null ? (string) $data['recinto_id'] : null;
        }

        if (isset($data['fecha_desde'])) {
            $this->validarFecha((string) $data['fecha_desde'], 'fecha_desde');
            $fechaDesde = (string) $data['fecha_desde'];
            $campos[] = 'fecha_desde = ?';
            $params[] = $fechaDesde;
            $types .= 's';
        }

        // MODIFICADO: Eliminado bloque de 'fecha_hasta'

        if (array_key_exists('observaciones', $data)) {
            $campos[] = 'observaciones = ?';
            $params[] = $data['observaciones'] !== null ? trim((string) $data['observaciones']) : null;
            $types .= 's';
        }

        // Validar FKs y jerarquía si cambiaron
        if (
            !$this->fincaExiste($fincaId) ||
            !$this->apriscoExiste($apriscoId) ||
            !$this->areaExiste($areaId) ||
            !$this->recintoExiste($recintoId)
        ) {
            throw new RuntimeException('Finca, aprisco, área o recinto no existen o están eliminados.');
        }
        $this->validarJerarquia($fincaId, $apriscoId, $areaId, $recintoId);

        // MODIFICADO: Eliminada regla de 'única activa' y normalización de 'estado'

        // Aplicar cambios en FKs (que pudieron ser propagados)
        if (array_key_exists('finca_id', $data) || $fincaId !== $current['finca_id']) {
            $campos[] = 'finca_id = ?';
            $params[] = $fincaId;
            $types .= 's';
        }
        if (array_key_exists('aprisco_id', $data) || $apriscoId !== $current['aprisco_id']) {
            $campos[] = 'aprisco_id = ?';
            $params[] = $apriscoId;
            $types .= 's';
        }
        if (array_key_exists('area_id', $data) || $areaId !== $current['area_id']) {
            $campos[] = 'area_id = ?';
            $params[] = $areaId;
            $types .= 's';
        }
        if (array_key_exists('recinto_id', $data) || $recintoId !== $current['recinto_id']) {
            $campos[] = 'recinto_id = ?';
            $params[] = $recintoId;
            $types .= 's';
        }

        if (empty($campos)) {
             if ($fincaId === $current['finca_id'] && $apriscoId === $current['aprisco_id'] && $areaId === $current['area_id']) {
                 throw new InvalidArgumentException('No hay campos para actualizar.');
             }
        }

        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $id;

        $campos[] = 'updated_at = ?';
        $params[] = $now;
        $types .= 's';
        $campos[] = 'updated_by = ?';
        $params[] = $actorId;
        $types .= 's';

        $sql = "UPDATE {$this->table}
                SET " . implode(', ', $campos) . "
                WHERE animal_ubicacion_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar actualización: " . $this->db->error);

        $types .= 's';
        $params[] = $id;

        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $err = strtolower($stmt->error);
        $stmt->close();

        if (!$ok) {
            if (str_contains($err, 'foreign key')) {
                throw new RuntimeException('FK inválida (animal/finca/aprisco/area/recinto).');
            }
            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }
        return true;
    }

    // MODIFICADO: Eliminados 'cerrar' y 'cerrarUbicacion'

    /**
     * Soft delete
     */
    public function eliminar(string $id): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $id;

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE animal_ubicacion_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar eliminación: " . $this->db->error);

        $stmt->bind_param('sss', $now, $actorId, $id);
        $ok = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $ok && $affected > 0;
    }
}