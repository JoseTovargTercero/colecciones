<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/FechasHelper.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

require_once __DIR__ . '/../helpers/NotificationTemplateHelper.php'; // <-- AÑADIDO
require_once __DIR__ . '/AlertaModel.php';                           // <-- AÑADIDO
require_once __DIR__ . '/NotificationModel.php';                     // <-- AÑADIDO

class PeriodoServicioModel
{
    private $db;
    private $table = 'periodos_servicio';

    // ==== NUEVO: para alertas y notificaciones ====
    private $alertaModel;
    private $notificationModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        // ==== NUEVO: instancias auxiliares ====
        $this->alertaModel = new AlertaModel();
        $this->notificationModel = new NotificationModel();
    }

    /* ============ Utilidades ============ */

    private function hembraDisponible(string $id): bool
    {
        $sql = "SELECT 1 
        FROM periodos_servicio 
        WHERE hembra_id = ? 
          AND estado_periodo = 'ABIERTO' 
          AND deleted_at IS NULL 
        LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new mysqli_sql_exception($this->db->error);
        }

        $stmt->bind_param('s', $id);
        $stmt->execute();
        $stmt->store_result();

        // Si no hay registros, la hembra está disponible
        $disponible = ($stmt->num_rows === 0);

        $stmt->close();
        return $disponible;
    }

    private function nowWithAudit(): array
    {
        $env = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');
        // userId=0 si aún no hay sesión; lo importante es setear contexto y tz
        $uuid    = UuidHelper::generateUUIDv4();
        $actorId = $_SESSION['user_id'] ?? $uuid;
        $env->applyAuditContext($this->db, $actorId);
        $tzManager = new TimezoneManager($this->db);
        $tzManager->applyTimezone();
        return [$env->getCurrentDatetime(), $env];
    }

    private function validarEstadoPeriodo(?string $v): void
    {
        if ($v === null) return;
        $validos = ['ABIERTO', 'CERRADO'];
        if (!in_array($v, $validos, true)) {
            throw new InvalidArgumentException("estado_periodo inválido. Use: " . implode(', ', $validos));
        }
    }

    private function animalExiste(string $id): bool
    {
        $sql = "SELECT 1 FROM animales WHERE animal_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception($this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }

    private function esHembra(string $id): bool
    {
        $sql = "SELECT sexo FROM animales WHERE animal_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception($this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return isset($row['sexo']) && $row['sexo'] === 'HEMBRA';
    }

    private function esMacho(string $id): bool
    {
        $sql = "SELECT sexo FROM animales WHERE animal_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception($this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return isset($row['sexo']) && $row['sexo'] === 'MACHO';
    }

    // ==== NUEVO: helper para notificar (misma firma que en tu IncidenciaModel) ====
    private function notificar(
        string $templateKey,
        array $params,
        ?string $route,
        ?string $legacyUnused = null,
        ?string $userId = null,
        ?string $role = null
    ): void {
        $meta   = NotificationTemplateHelper::getMeta($templateKey);
        $module = $meta ? ($meta['module'] ?? 'reproduccion') : 'reproduccion';

        $finalUserId = $userId ?: (string)($_SESSION['user_id'] ?? '0');
        $finalRole   = $role   ?: (string)($_SESSION['user_type'] ?? 'user');

        $data = [
            'template_key'    => $templateKey,
            'template_params' => $params,
            'route'           => $route,
            'module'          => $module,
            'rol'             => $finalRole,
            'user_id'         => $finalUserId
        ];

        try {
            $this->notificationModel->crear($data);
        } catch (\Throwable $e) {
            error_log("[PeriodoServicioModel] notificar() error: " . $e->getMessage());
        }
    }

    // ==== NUEVO: helper para obtener identificador de hembra ====
    private function getIdentificadorAnimal(string $animalId): string
    {
        $sql = "SELECT COALESCE(identificador, arete, codigo, nombre, animal_id) AS ident FROM animales WHERE animal_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $animalId);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stmt->close();
                return (string)($row['ident'] ?? $animalId);
            }
            $stmt->close();
        }
        return $animalId;
    }

    /* ============ Lecturas ============ */

    /**
     * Lista servicios (excluye eliminados por defecto).
     * Filtros: periodo_id, numero_monta, fecha_monta (desde/hasta).
     */
    public function listar(
        int $limit = 10000,
        int $offset = 0,
        bool $incluirEliminados = false,
        ?string $periodoId = null,
        ?int $numeroMonta = null,
        ?string $desde = null,
        ?string $hasta = null
    ): array {
        $w = [];
        $p = [];
        $t = '';

        $w[] = $incluirEliminados ? 'm.deleted_at IS NOT NULL OR m.deleted_at IS NULL' : 'm.deleted_at IS NULL';

        if ($periodoId) {
            $w[] = 'm.periodo_id = ?';
            $p[] = $periodoId;
            $t .= 's';
        }
        if ($numeroMonta !== null) {
            $w[] = 'm.numero_monta = ?';
            $p[] = $numeroMonta;
            $t .= 'i';
        }
        if ($desde) {
            $w[] = 'm.fecha_monta >= ?';
            $p[] = $desde;
            $t .= 's';
        }
        if ($hasta) {
            $w[] = 'm.fecha_monta <= ?';
            $p[] = $hasta;
            $t .= 's';
        }

        $whereSql = implode(' AND ', $w);

        $sql = "SELECT
            m.periodo_id,
            m.hembra_id,
            m.verraco_id,
            m.fecha_inicio,
            m.observaciones,
            m.estado_periodo,
            h.identificador AS hembra_identificador,
            v.identificador AS verraco_identificador,
            m.created_at,
            m.created_by,
            m.updated_at,
            m.updated_by,
            rs.resultado AS resultado_revision,
            COUNT(mt.monta_id) AS cantidad_montas,
            MAX(CASE WHEN mt.estatus = 'REALIZADO' THEN mt.fecha_monta END) AS fecha_ultima_monta
        FROM {$this->table} m
        LEFT JOIN animales h ON m.hembra_id = h.animal_id
        LEFT JOIN animales v ON m.verraco_id = v.animal_id
        LEFT JOIN servicios mt ON mt.periodo_id = m.periodo_id
        LEFT JOIN revisiones_servicio rs ON rs.periodo_id = m.periodo_id
        WHERE {$whereSql}
        GROUP BY 
            m.periodo_id,
            m.hembra_id,
            m.verraco_id,
            m.fecha_inicio,
            m.observaciones,
            m.estado_periodo,
            h.identificador,
            v.identificador,
            m.created_at,
            m.created_by,
            m.updated_at,
            m.updated_by
        LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar listado: " . $this->db->error);

        $t .= 'ii';
        $p[] = $limit;
        $p[] = $offset;
        $stmt->bind_param($t, ...$p);
        $stmt->execute();
        $res  = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($data as &$periodo) {
            $periodoId = $periodo['periodo_id'];

            $sql = "SELECT 
                        COUNT(*) AS total_servicios,
                        SUM(CASE WHEN estatus = 'PENDIENTE' THEN 1 ELSE 0 END) AS servicios_pendientes
                    FROM servicios
                    WHERE periodo_id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) throw new mysqli_sql_exception("Error al preparar conteo de servicios: " . $this->db->error);

            $stmt->bind_param('s', $periodoId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();

            $periodo['total_servicios'] = (int)($row['total_servicios'] ?? 0);
            $periodo['servicios_pendientes'] = (int)($row['servicios_pendientes'] ?? 0);
            $periodo['estatuss'] = [
                'PENDIENTE' => $periodo['servicios_pendientes'],
                'REALIZADO' => $periodo['total_servicios'] - $periodo['servicios_pendientes']
            ];
            $periodo['servicios_totales'] = $periodo['total_servicios'];
        }
        unset($periodo); // muy importante para evitar referencias inesperadas

        return $data;
    }

    public function obtenerPorId(string $periodoId): ?array
    {
        $sql = "SELECT 
                    p.periodo_id,
                    p.hembra_id, h.identificador AS hembra_identificador, h.sexo AS hembra_sexo,
                    p.verraco_id, v.identificador AS verraco_identificador, v.sexo AS verraco_sexo,
                    p.fecha_inicio,
                    p.observaciones,
                    p.hora_servicio,
                    p.estado_periodo,
                    p.created_at, p.created_by, p.updated_at, p.updated_by,
                    p.deleted_at, p.deleted_by
                FROM {$this->table} p
                LEFT JOIN animales h ON h.animal_id = p.hembra_id
                LEFT JOIN animales v ON v.animal_id = p.verraco_id
                WHERE p.periodo_id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar consulta: " . $this->db->error);
        $stmt->bind_param('s', $periodoId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        $serviciosPendientes = 0;

        $sql = "SELECT * FROM servicios WHERE periodo_id = ? ORDER BY numero_monta";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error al preparar consulta: " . $this->db->error);
        }

        $stmt->bind_param('s', $periodoId);
        $stmt->execute();
        $res = $stmt->get_result();
        $servicios = $res->fetch_all(MYSQLI_ASSOC);

        // --- RECORRER CADA SERVICIO Y CALCULAR SU ESTADO ---
        foreach ($servicios as &$servicio) {
            $servicio['estado_servicio'] = FechaHelper::estado(explode(' ', $servicio['fecha_monta'])[0]);
            $serviciosPendientes == ($servicio['estado_servicio'] === 'PENDIENTE') ? $serviciosPendientes + 1 : $serviciosPendientes;
        }

        $stmt->close();

        // Asignar los servicios al registro principal
        if ($row) {
            $row['servicios'] = $servicios ?: [];
        }
        $row['servicios_pendientes'] = $serviciosPendientes;

        return $row ?: null;
    }

    /* ============ Escrituras ============ */

    /**
     * Crea una monta.
     * Requeridos: verraco_id, hembra_id, periodo_id, fecha_inicio, observaciones (Y-m-d),
     */
    public function crear(array $data): string
    {
        $verraco_id             = trim((string)($data['verraco_id'] ?? ''));
        $hembra_id              = trim((string)($data['hembra_id'] ?? ''));
        $fecha_inicio           = trim((string)($data['fecha_inicio'] ?? ''));
        $observaciones          = trim((string)($data['observaciones'] ?? ''));
        $numero_servicios       = trim((string)($data['numero_servicios'] ?? ''));
        $frecuencia_servicios   = trim((string)($data['frecuencia_servicios'] ?? ''));
        $hora_servicio          = trim((string)($data['hora_servicio'] ?? ''));

        if ($verraco_id  === '' || $hembra_id  === '' || $fecha_inicio  === '' ||  $numero_servicios === '' || $frecuencia_servicios === '' || $hora_servicio === '') {
            throw new InvalidArgumentException('Faltan campos requeridos para crear la monta.');
        }

        if (!$this->hembraDisponible($hembra_id)) {
            throw new InvalidArgumentException('La hembra ya tiene un periodo de monta abierto.');
        }

        $this->db->begin_transaction();
        try {
            $uuid    = UuidHelper::generateUUIDv4();
            $actorId = $_SESSION['user_id'] ?? $uuid;
            $now = (new DateTime())->format('Y-m-d H:i:s');

            $sql = "INSERT INTO {$this->table}
                    (periodo_id, hembra_id, verraco_id, fecha_inicio, hora_servicio, frecuencia_servicios, numero_servicios, observaciones, created_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) throw new mysqli_sql_exception("Error al preparar inserción: " . $this->db->error);

            $stmt->bind_param(
                'ssssssssss',
                $uuid,
                $hembra_id,
                $verraco_id,
                $fecha_inicio,
                $hora_servicio,
                $frecuencia_servicios,
                $numero_servicios,
                $observaciones,
                $now,
                $actorId
            );

            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                $this->db->rollback();

                $errLow = strtolower($err);
                if (str_contains($errLow, 'foreign key')) {
                    throw new RuntimeException('Referencia inválida a periodo de servicio.');
                }
                if (str_contains($errLow, 'duplicate') || str_contains($errLow, 'unique')) {
                    // Por si tienes una restricción única por (periodo_id, numero_monta)
                    throw new RuntimeException('Ya existe una monta con ese número en este periodo.');
                }
                throw new mysqli_sql_exception("Error al ejecutar inserción: " . $err);
            }
            $stmt->close();

            // =====================================================
            // CALCULAR LAS FECHAS Y CREAR LOS REGISTROS DE SERVICIO
            // =====================================================
            $frecuencias = [
                'diaria'        => 1,
                'cada_2_dias'   => 2,
                'cada_3_dias'   => 3,
                'cada_4_dias'   => 4,
                'cada_5_dias'   => 5,
            ];

            $diasFrecuencia = $frecuencias[$frecuencia_servicios] ?? 1;

            $sql2 = "INSERT INTO servicios
                (monta_id, periodo_id, numero_monta, fecha_monta, created_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
            $stmt2 = $this->db->prepare($sql2);
            if (!$stmt2) throw new mysqli_sql_exception("Error al preparar inserción de servicio: " . $this->db->error);

            $fecha_inicio = str_replace(['T', 'Z'], ' ', $fecha_inicio);
            $fechaBase = new DateTime($fecha_inicio, new DateTimeZone('America/Caracas'));
            $fechaBase->modify("+1 days");

            for ($i = 1; $i <= (int)$numero_servicios; $i++) {
                $uuid_monta = UuidHelper::generateUUIDv4();
                $fechaMonta = $fechaBase->format('Y-m-d') . ' ' . $hora_servicio;

                $stmt2->bind_param(
                    'ssssss',
                    $uuid_monta,
                    $uuid,
                    $i,
                    $fechaMonta,
                    $now,
                    $actorId
                );

                if (!$stmt2->execute()) {
                    $err = $stmt2->error;
                    $stmt2->close();
                    $this->db->rollback();

                    $errLow = strtolower($err);
                    if (str_contains($errLow, 'foreign key')) {
                        throw new RuntimeException('Referencia inválida al crear un servicio de la monta.');
                    }
                    if (str_contains($errLow, 'duplicate') || str_contains($errLow, 'unique')) {
                        throw new RuntimeException("Ya existe un servicio con el número {$i} en esta monta.");
                    }
                    throw new mysqli_sql_exception("Error al ejecutar inserción del servicio {$i}: " . $err);
                }

                // Incrementar fecha según frecuencia
                $fechaBase->modify("+{$diasFrecuencia} days");
            }

            $stmt2->close();

            $this->db->commit();
            return $uuid;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // Cierra el periodo u servicios
    public function revisionPeriodo($id)
    {
        // Iniciar la transacción
        $this->db->begin_transaction();

        try {
            // 1️ Pasar el periodo a seguimiento
            $sqlPeriodo = "UPDATE {$this->table} 
                           SET estado_periodo = 'SEGUIMIENTO' 
                           WHERE periodo_id = ?";
            $stmt = $this->db->prepare($sqlPeriodo);
            if (!$stmt) {
                throw new RuntimeException("Error al preparar actualización de periodo: " . $this->db->error);
            }

            $stmt->bind_param("s", $id);
            if (!$stmt->execute()) {
                throw new RuntimeException("Error al ejecutar actualización de periodo: " . $stmt->error);
            }

            $filasPeriodo = $stmt->affected_rows;
            $stmt->close();

            // 2 Actualizar los servicios relacionados
            $sqlServicios = "UPDATE servicios 
                             SET estatus = 'REALIZADO' 
                             WHERE periodo_id = ?";
            $stmt = $this->db->prepare($sqlServicios);
            if (!$stmt) {
                throw new RuntimeException("Error al preparar actualización de servicios: " . $this->db->error);
            }

            $stmt->bind_param("s", $id);
            if (!$stmt->execute()) {
                throw new RuntimeException("Error al ejecutar actualización de servicios: " . $stmt->error);
            }

            $filasServicios = $stmt->affected_rows;
            $stmt->close();

            // 3 Registrar la revisión a los 21 días desde fecha_inicio
            $sqlRegistrarPeriodo = "INSERT INTO revisiones_servicio 
            (revision_id, periodo_id, fecha_programada, created_by, created_at)
            SELECT ?, periodo_id, DATE_ADD(fecha_inicio, INTERVAL 21 DAY), ?, NOW()
            FROM {$this->table}
            WHERE periodo_id = ?";

            $stmt = $this->db->prepare($sqlRegistrarPeriodo);
            if (!$stmt) {
                throw new RuntimeException("Error al preparar inserción en revisiones_servicio: " . $this->db->error);
            }

            $uid = UuidHelper::generateUUIDv4();  // ID único para la revisión
            $actorId = $_SESSION['user_id'] ?? '';

            $stmt->bind_param("sss", $uid, $actorId, $id);

            if (!$stmt->execute()) {
                throw new RuntimeException("Error al ejecutar inserción en revisiones_servicio: " . $stmt->error);
            }

            $stmt->close();

            // === NUEVO 3.bis) Crear ALERTA + NOTIFICACIÓN (revisión 20/21) ===
            // Obtenemos datos necesarios: fecha_programada y hembra_id/identificador
            $sqlInfo = "SELECT p.hembra_id,
                               a.identificador AS hembra_identificador,
                               DATE_ADD(p.fecha_inicio, INTERVAL 21 DAY) AS fecha_programada
                        FROM {$this->table} p
                        LEFT JOIN animales a ON a.animal_id = p.hembra_id
                        WHERE p.periodo_id = ?
                        LIMIT 1";
            $stmt = $this->db->prepare($sqlInfo);
            if (!$stmt) {
                throw new RuntimeException("Error al preparar info de periodo: " . $this->db->error);
            }
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $infoRes = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $hembraId   = (string)($infoRes['hembra_id'] ?? '');
            $hembraIdent= $infoRes['hembra_identificador'] ?: $this->getIdentificadorAnimal($hembraId);
            $fechaProg  = (string)($infoRes['fecha_programada'] ?? date('Y-m-d'));

            // Crear ALERTA en tabla alertas
            [$now, $env] = $this->nowWithAudit();
            $alertaId = UuidHelper::generateUUIDv4();
            $detalle  = "Revisión 20/21 programada para la hembra {$hembraIdent} el {$fechaProg}.";
            $sqlAlerta = "INSERT INTO alertas
                (alerta_id, tipo_alerta, fecha_objetivo, periodo_id, animal_id, detalle, estado_alerta,
                 created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
            VALUES
                (?, 'REVISION_20_21', ?, ?, ?, ?, 'PENDIENTE',
                 ?, ?, NULL, NULL, NULL, NULL)";
            $stmt = $this->db->prepare($sqlAlerta);
            if (!$stmt) {
                throw new RuntimeException("Error al preparar alerta de revisión 20/21: " . $this->db->error);
            }
            $stmt->bind_param(
                'sssssss',
                $alertaId,
                $fechaProg,
                $id,
                $hembraId,
                $detalle,
                $now,
                $actorId
            );
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new RuntimeException("Error al guardar alerta de revisión 20/21: " . $err);
            }
            $stmt->close();

            // Disparar NOTIFICACIÓN usando plantilla 'repro_revision_20_21_due'
            // dia = 21; fecha_programada; hembra_identificador
            $this->notificar(
                'repro_revision_20_21_due',
                [
                    'dia'                  => '21',
                    'fecha_programada'     => $fechaProg,
                    'hembra_identificador' => $hembraIdent
                ],
                '/revisiones_servicio?periodo_id=' . $id,
                null,
                $actorId,
                (string)($_SESSION['user_type'] ?? 'user')
            );

            // 4 Confirmar la transacción si todo va bien
            $this->db->commit();

            // Devuelve true si se afectó al menos una fila en cualquiera de las tablas
            return ($filasPeriodo + $filasServicios) > 0;
        } catch (Throwable $e) {
            // Revertir los cambios si algo falla
            $this->db->rollback();
            throw $e; // Re-lanzar la excepción para que el controlador superior la maneje
        }
    }

    /**
     * Eliminación lógica (soft delete).
     */
    public function eliminar(string $periodoId): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $periodoId;

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE periodo_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar eliminación: " . $this->db->error);

        $stmt->bind_param('sss', $now, $actorId, $periodoId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } // TODO: REVISADO
}
