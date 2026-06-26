<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';
require_once __DIR__ . '/../helpers/NotificationTemplateHelper.php';

class AreaModel
{
    private $db;
    private $table = 'areas';
    private $notificationModel; // <-- 2. Propiedad para el modelo
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->notificationModel = new NotificationModel();
    }

    /* ============ Utilidades ============ */

    private function nowWithAudit(): array
    {
        $env = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');
        // actorId desde la sesión; si no hay sesión, usar '0' para no romper FKs
        $actorId = (string) ($_SESSION['user_id'] ?? '0');
        $env->applyAuditContext($this->db, $actorId);
        $tzManager = new TimezoneManager($this->db);
        $tzManager->applyTimezone();
        return [$env->getCurrentDatetime(), $env];
    }

    private function apriscoExiste(string $apriscoId): bool
    {
        $sql = "SELECT 1 FROM apriscos WHERE aprisco_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar verificación de aprisco: " . $this->db->error);
        $stmt->bind_param('s', $apriscoId);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    private function validarTipoArea(string $tipo): void
    {
        $validos = ['LEVANTE_CEBA', 'GESTACION', 'MATERNIDAD', 'REPRODUCCION', 'CHIQUERO', 'CUARENTENA'];
        if (!in_array($tipo, $validos, true)) {
            throw new InvalidArgumentException(
                "tipo_area inválido. Use uno de: " . implode(', ', $validos)
            );
        }
    }

    /** Carga contexto (labels) del área para plantillas */
    private function cargarContextoArea(string $areaId): array
    {
        $sql = "SELECT 
                    a.area_id,
                    COALESCE(a.nombre_personalizado, a.numeracion, a.area_id) AS area_nombre,
                    a.tipo_area,
                    a.numeracion,
                    a.estado,
                    ap.aprisco_id,
                    ap.nombre AS aprisco_nombre,
                    f.finca_id,
                    f.nombre AS finca_nombre
                FROM {$this->table} a
                LEFT JOIN apriscos ap ON ap.aprisco_id = a.aprisco_id
                LEFT JOIN fincas   f  ON f.finca_id    = ap.finca_id
                WHERE a.area_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error contexto de área: " . $this->db->error);
        $stmt->bind_param('s', $areaId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        // Etiquetas compuestas
        $row['aprisco_full'] = trim(($row['aprisco_nombre'] ?? '') !== '' ? $row['aprisco_nombre'] : (string) ($row['aprisco_id'] ?? ''));
        $row['finca_full'] = trim(($row['finca_nombre'] ?? '') !== '' ? $row['finca_nombre'] : (string) ($row['finca_id'] ?? ''));
        return $row;
    }

    /* ============ Notificaciones ============ */

    /** Crea notificación a partir de una plantilla */
    private function notificar(
        string $templateKey,
        array $params,
        ?string $route,
        ?string $legacyUnused = null, // ← se mantiene por retrocompatibilidad (no se usa)
        ?string $userId = null,       // ← 5to parámetro: user_id real (actor)
        ?string $role = null          // ← 6to parámetro: rol
    ): void {
        // 1) Módulo sugerido por la plantilla (fallback a 'incidencias')
        $meta = NotificationTemplateHelper::getMeta($templateKey);
        $module = $meta ? ($meta['module'] ?? 'incidencias') : 'incidencias';

        // 2) Resolver user_id y rol con fallback a sesión
        $finalUserId = $userId ?: (string) ($_SESSION['user_id'] ?? '0');
        $finalRole = $role ?: (string) ($_SESSION['user_type'] ?? 'user');

        // 3) Armar payload para NotificationModel->crear()
        $data_para_crear = [
            'template_key' => $templateKey,
            'template_params' => $params,
            'route' => $route,
            'module' => $module,
            'rol' => $finalRole,    // ← rol desde parámetro o sesión
            'user_id' => $finalUserId   // ← user_id desde parámetro o sesión
            // 'created_by' lo maneja internamente el modelo con el actor de sesión
        ];

        // 4) Guardar y despachar
        try {
            $this->notificationModel->crear($data_para_crear);
            // El modelo se encarga de: persistir, renderizar y disparar push si aplica
        } catch (Exception $e) {
            error_log("Error al crear notificación desde notificar(): " . $e->getMessage());
        }
    }

    /* ============ Lecturas ============ */

    /**
     * Lista áreas (por defecto excluye eliminadas).
     * Filtros opcionales: aprisco_id, tipo_area, finca_id
     */
    public function listar(
        int $limit = 10000,
        int $offset = 0,
        bool $incluirEliminados = false,
        ?string $apriscoId = null,
        ?string $tipoArea = null,
        ?string $fincaId = null
    ): array {
        $where = [];
        $params = [];
        $types = '';

        $where[] = $incluirEliminados ? 'a.deleted_at IS NOT NULL OR a.deleted_at IS NULL' : 'a.deleted_at IS NULL';

        if ($apriscoId) {
            $where[] = 'a.aprisco_id = ?';
            $params[] = $apriscoId;
            $types .= 's';
        }
        if ($tipoArea) {
            $this->validarTipoArea($tipoArea);
            $where[] = 'a.tipo_area = ?';
            $params[] = $tipoArea;
            $types .= 's';
        }
        if ($fincaId) {
            // filtra por finca del aprisco
            $where[] = 'ap.finca_id = ?';
            $params[] = $fincaId;
            $types .= 's';
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT 
                    a.area_id,
                    a.aprisco_id,
                    ap.nombre AS nombre_aprisco,
                    ap.finca_id,
                    f.nombre AS nombre_finca,
                    a.nombre_personalizado,
                    a.tipo_area,
                    a.numeracion,
                    a.estado,
                    a.created_at,
                    a.created_by,
                    a.updated_at,
                    a.updated_by
                FROM {$this->table} a
                LEFT JOIN apriscos ap ON ap.aprisco_id = a.aprisco_id
                LEFT JOIN fincas f    ON f.finca_id    = ap.finca_id
                WHERE {$whereSql}
                ORDER BY a.created_at DESC, a.tipo_area ASC, a.numeracion ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar listado: " . $this->db->error);

        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    public function getOptions($apriscoId = null)
    {
        $sql = "SELECT area_id,
                       COALESCE(nombre_personalizado, numeracion, area_id) AS label
                FROM {$this->table}
                WHERE deleted_at IS NULL";
        if ($apriscoId) {
            $sql .= " AND aprisco_id = ?";
            $stmt = $this->db->prepare($sql . " ORDER BY label ASC");
            $stmt->bind_param("s", $apriscoId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $sql .= " ORDER BY label ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetch_all(MYSQLI_ASSOC);
        }
    }

    public function obtenerPorId(string $areaId): ?array
    {
        $sql = "SELECT 
                    a.area_id,
                    a.aprisco_id,
                    ap.nombre AS nombre_aprisco,
                    ap.finca_id,
                    f.nombre AS nombre_finca,
                    a.nombre_personalizado,
                    a.tipo_area,
                    a.numeracion,
                    a.estado,
                    a.created_at,
                    a.created_by,
                    a.updated_at,
                    a.updated_by,
                    a.deleted_at,
                    a.deleted_by
                FROM {$this->table} a
                LEFT JOIN apriscos ap ON ap.aprisco_id = a.aprisco_id
                LEFT JOIN fincas f    ON f.finca_id    = ap.finca_id
                WHERE a.area_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar consulta: " . $this->db->error);

        $stmt->bind_param('s', $areaId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }


    /* ============ Generar Numericion ========= */
    private function obtenerSiguienteNumeracion(string $apriscoId, string $tipoArea): string
{
    $sql = "
        SELECT COALESCE(MAX(CAST(numeracion AS UNSIGNED)), 0) + 1 AS next_num
        FROM {$this->table}
        WHERE aprisco_id = ?
          AND tipo_area = ?
          AND deleted_at IS NULL
    ";

    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
        throw new mysqli_sql_exception($this->db->error);
    }

    $stmt->bind_param('ss', $apriscoId, $tipoArea);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (string) $result['next_num'];
}

    /* ============ Escrituras ============ */

    /**
     * Crea un área.
     * Requeridos: aprisco_id, tipo_area
     * Opcionales: nombre_personalizado, numeracion, estado('ACTIVA'|'INACTIVA')
     * Notificación: area_creada
     */
    public function crear(array $data): string
    {
        if (empty($data['aprisco_id']) || empty($data['tipo_area'])) {
            throw new InvalidArgumentException('Faltan campos requeridos: aprisco_id, tipo_area.');
        }

        $apriscoId = trim((string) $data['aprisco_id']);
        $tipoArea = trim((string) $data['tipo_area']);
        $this->validarTipoArea($tipoArea);

        if (!$this->apriscoExiste($apriscoId)) {
            throw new RuntimeException('El aprisco especificado no existe o está eliminado.');
        }

        $nombrePers = isset($data['nombre_personalizado']) ? trim((string) $data['nombre_personalizado']) : null;
     //   $numeracion = isset($data['numeracion']) ? trim((string) $data['numeracion']) : null;

         $numeracion = isset($data['numeracion']) && $data['numeracion'] !== ''
            ? trim((string) $data['numeracion'])
            : null;

        // Si no viene numeración, generar la siguiente automáticamente
        if ($numeracion === null) {
            $numeracion = $this->obtenerSiguienteNumeracion($apriscoId, $tipoArea);
        }






        $estado = isset($data['estado']) && in_array($data['estado'], ['ACTIVA', 'INACTIVA'], true)
            ? $data['estado'] : 'ACTIVA';

        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();

            $uuid = UuidHelper::generateUUIDv4();
            $actorId = (string) ($_SESSION['user_id'] ?? '0');
            $role = (string) ($_SESSION['user_type'] ?? 'user');

            $sql = "INSERT INTO {$this->table}
                    (area_id, aprisco_id, nombre_personalizado, tipo_area, numeracion, estado,
                     created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception("Error al preparar inserción: " . $this->db->error);

            $stmt->bind_param(
                'ssssssss',
                $uuid,
                $apriscoId,
                $nombrePers,
                $tipoArea,
                $numeracion,
                $estado,
                $now,
                $actorId
            );

            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                $this->db->rollback();

                $errl = strtolower($err);
                if (strpos($errl, 'foreign key') !== false) {
                    throw new RuntimeException('El aprisco no existe (violación de clave foránea).');
                }
                if (strpos($errl, 'duplicate') !== false) {
                    throw new RuntimeException('Ya existe un área con esa combinación (ver índice único).');
                }
                throw new mysqli_sql_exception("Error al ejecutar inserción: " . $err);
            }

            $stmt->close();

            // Notificación: área creada
            $ctx = $this->cargarContextoArea($uuid);
            $this->notificar(
                'area_creada',
                [
                    'area_nombre' => $ctx['area_nombre'] ?? $uuid,
                    'tipo_area' => $ctx['tipo_area'] ?? $tipoArea,
                    'aprisco' => $ctx['aprisco_full'] ?? '',
                    'finca' => $ctx['finca_full'] ?? ''
                ],
                '/areas?area_id=' . $uuid,
                null,
                $actorId,
                $role
            );

            $this->db->commit();
            return $uuid;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Actualiza campos: aprisco_id, nombre_personalizado, tipo_area, numeracion, estado.
     * Notificación: area_actualizada (+ area_estado_cambiado si aplica)
     */
    public function actualizar(string $areaId, array $data): bool
    {
        $rowBefore = $this->obtenerPorId($areaId);
        if (!$rowBefore || $rowBefore['deleted_at'] !== null) {
            throw new RuntimeException('Área no existe o está eliminada.');
        }

        $campos = [];
        $params = [];
        $types = '';

        if (isset($data['aprisco_id'])) {
            $nuevoAprisco = trim((string) $data['aprisco_id']);
            if (!$this->apriscoExiste($nuevoAprisco)) {
                throw new InvalidArgumentException('aprisco_id no válido (no existe o está eliminado).');
            }
            $campos[] = 'aprisco_id = ?';
            $params[] = $nuevoAprisco;
            $types .= 's';
        }
        if (array_key_exists('nombre_personalizado', $data)) {
            $campos[] = 'nombre_personalizado = ?';
            $params[] = $data['nombre_personalizado'] !== null ? trim((string) $data['nombre_personalizado']) : null;
            $types .= 's';
        }
        if (isset($data['tipo_area'])) {
            $this->validarTipoArea((string) $data['tipo_area']);
            $campos[] = 'tipo_area = ?';
            $params[] = (string) $data['tipo_area'];
            $types .= 's';
        }
        if (array_key_exists('numeracion', $data)) {
            $campos[] = 'numeracion = ?';
            $params[] = $data['numeracion'] !== null ? trim((string) $data['numeracion']) : null;
            $types .= 's';
        }
        $estadoNuevo = null;
        if (isset($data['estado'])) {
            $estado = (string) $data['estado'];
            if (!in_array($estado, ['ACTIVA', 'INACTIVA'], true)) {
                throw new InvalidArgumentException("Valor de estado inválido. Use 'ACTIVA' o 'INACTIVA'.");
            }
            $campos[] = 'estado = ?';
            $params[] = $estado;
            $types .= 's';
            $estadoNuevo = $estado;
        }

        if (empty($campos)) {
            throw new InvalidArgumentException('No hay campos para actualizar.');
        }

        [$now, $env] = $this->nowWithAudit();
        $actorId = (string) ($_SESSION['user_id'] ?? '0');
        $role = (string) ($_SESSION['user_type'] ?? 'user');

        $campos[] = 'updated_at = ?';
        $params[] = $now;
        $types .= 's';
        $campos[] = 'updated_by = ?';
        $params[] = $actorId;
        $types .= 's';

        $sql = "UPDATE {$this->table}
                SET " . implode(', ', $campos) . "
                WHERE area_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar actualización: " . $this->db->error);

        $types .= 's';
        $params[] = $areaId;

        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();

        if (!$ok) {
            $errl = strtolower($err);
            if (strpos($errl, 'foreign key') !== false) {
                throw new RuntimeException('El aprisco no existe (violación de clave foránea).');
            }
            if (strpos($errl, 'duplicate') !== false) {
                throw new RuntimeException('Conflicto de unicidad (ver índice único).');
            }
            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }

        // Notificaciones
        $ctx = $this->cargarContextoArea($areaId);
        $this->notificar(
            'area_actualizada',
            [
                'area_nombre' => $ctx['area_nombre'] ?? $areaId,
                'tipo_area' => $ctx['tipo_area'] ?? '',
                'aprisco' => $ctx['aprisco_full'] ?? '',
                'finca' => $ctx['finca_full'] ?? '',
                'fecha' => substr($now, 0, 16)
            ],
            '/areas?area_id=' . $areaId,
            null,
            $actorId,
            $role
        );

        // Si cambió el estado, mandar notificación específica
        if ($estadoNuevo !== null && $estadoNuevo !== ($rowBefore['estado'] ?? null)) {
            $this->notificar(
                'area_estado_cambiado',
                [
                    'area_nombre' => $ctx['area_nombre'] ?? $areaId,
                    'nuevo_estado' => $estadoNuevo
                ],
                '/areas?area_id=' . $areaId,
                null,
                $actorId,
                $role
            );
        }

        return true;
    }

    /**
     * Actualiza solo el estado ('ACTIVA'|'INACTIVA').
     * Notificación: area_estado_cambiado
     */
    public function actualizarEstado(string $areaId, string $estado): bool
    {
        if (!in_array($estado, ['ACTIVA', 'INACTIVA'], true)) {
            throw new InvalidArgumentException("Valor de estado inválido. Use 'ACTIVA' o 'INACTIVA'.");
        }

        [$now, $env] = $this->nowWithAudit();
        $actorId = (string) ($_SESSION['user_id'] ?? '0');
        $role = (string) ($_SESSION['user_type'] ?? 'user');

        $sql = "UPDATE {$this->table}
                SET estado = ?, updated_at = ?, updated_by = ?
                WHERE area_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar actualización de estado: " . $this->db->error);

        $stmt->bind_param('ssss', $estado, $now, $actorId, $areaId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $ctx = $this->cargarContextoArea($areaId);
            $this->notificar(
                'area_estado_cambiado',
                [
                    'area_nombre' => $ctx['area_nombre'] ?? $areaId,
                    'nuevo_estado' => $estado
                ],
                '/areas?area_id=' . $areaId,
                null,
                $actorId,
                $role
            );
        }

        return $ok;
    }

    /**
     * Eliminación lógica (soft delete).
     * Notificación: area_eliminada
     */
    public function eliminar(string $areaId): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId = (string) ($_SESSION['user_id'] ?? '0');
        $role = (string) ($_SESSION['user_type'] ?? 'user');

        $row = $this->obtenerPorId($areaId);
        if (!$row || $row['deleted_at'] !== null) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE area_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar eliminación: " . $this->db->error);

        $stmt->bind_param('sss', $now, $actorId, $areaId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $ctx = $this->cargarContextoArea($areaId);
            $this->notificar(
                'area_eliminada',
                [
                    'area_nombre' => $ctx['area_nombre'] ?? $areaId,
                    'aprisco' => $ctx['aprisco_full'] ?? '',
                    'finca' => $ctx['finca_full'] ?? ''
                ],
                '/areas',
                null,
                $actorId,
                $role
            );
        }

        return $ok;
    }
}
