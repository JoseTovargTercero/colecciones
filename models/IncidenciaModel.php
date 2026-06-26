<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';
require_once __DIR__ . '/../helpers/NotificationTemplateHelper.php';
require_once __DIR__ . '/AlertaModel.php';
require_once __DIR__ . '/NotificationModel.php';
require_once __DIR__ . '/AnimalSaludModel.php';

class IncidenciaModel
{
    private $db;
    private $table = 'incidencias';
    private $notificationModel; // <-- 2. Propiedad para el modelo
    private $alertaModel;

    private $saludModel; // <-- Para registrar consecuencias

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->alertaModel = new AlertaModel();
        $this->notificationModel = new NotificationModel();
        $this->saludModel = new AnimalSaludModel();
    }

    /* ========= Utilidades ========= */

    private function nowWithAudit(): array
    {
        $env = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');
        // userId=0 si aún no hay sesión; lo importante es setear contexto y tz
        $uuid = UuidHelper::generateUUIDv4();
        $actorId = $_SESSION['user_id'] ?? $uuid;
        $env->applyAuditContext($this->db, $actorId);
        $tzManager = new TimezoneManager($this->db);
        $tzManager->applyTimezone();
        return [$env->getCurrentDatetime(), $env];
    }

    private function validarFechaHora(string $value, string $campo = 'fecha_evento'): string
    {
        $val = trim($value);
        $val = str_replace('T', ' ', $val); // <-- AÑADE ESTA LÍNEA

        // Acepta: YYYY-MM-DD o YYYY-MM-DD HH:MM o YYYY-MM-DD HH:MM:SS
        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?$/', $val) !== 1) {
            throw new InvalidArgumentException("$campo inválido. Formato esperado 'YYYY-MM-DD[ HH:MM[:SS]]'.");
        }
        // Normalizar a 'Y-m-d H:i:s'
        if (strlen($val) === 10)
            $val .= ' 00:00:00';
        elseif (strlen($val) === 16)
            $val .= ':00';

        // Validación fecha/hora real
        // Ahora $val SIEMPRE tendrá un espacio si tiene hora
        [$ymd, $hms] = explode(' ', $val);
        [$y, $m, $d] = array_map('intval', explode('-', $ymd));
        if (!checkdate($m, $d, $y))
            throw new InvalidArgumentException("$campo no es una fecha válida.");
        [$H, $i, $s] = array_map('intval', explode(':', $hms));
        if ($H < 0 || $H > 23 || $i < 0 || $i > 59 || $s < 0 || $s > 59)
            throw new InvalidArgumentException("$campo no es una hora válida.");
        return $val;
    }

    private function obtenerAreaActualDelAnimal(string $animalId): ?string
    {
        // Busca la ubicación ACTIVA más reciente
        $sql = "SELECT area_id FROM animal_ubicaciones 
                WHERE animal_id = ? AND estado = 'ACTIVA' 
                ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $animalId);
        $stmt->execute();
        $stmt->bind_result($areaId);
        $stmt->fetch();
        $stmt->close();
        return $areaId;
    }

    // --- MODIFICADO: Nuevos Tipos ---
    private function normalizarTipo(string $tipo): string
    {
        $map = [
            'RECHAZO DE CRIAS' => 'RECHAZO_CRIAS',
            'RECHAZO_CRIAS' => 'RECHAZO_CRIAS',
            'FUGA' => 'FUGA',
            'INTENTO DE ESCAPE' => 'FUGA',
            'APLASTAMIENTO' => 'APLASTAMIENTO',
            'AGRESIVIDAD' => 'AGRESIVIDAD',
            'CONDUCTA AGRESIVA' => 'AGRESIVIDAD',
            'RIÑA' => 'RIÑA',
            'PELEA' => 'RIÑA',
            'OTRA' => 'OTRA',
            'OTRO' => 'OTRA'
        ];
        $key = strtoupper(trim($tipo));
        // Normalizar acentos básicos
        $key = str_replace(['Í'], ['I'], $key);



        if (!isset($map[$key])) {
            throw new InvalidArgumentException("Tipo inválido. Opciones: RECHAZO_CRIAS, FUGA, APLASTAMIENTO, AGRESIVIDAD, RIÑA, OTRA.");
        }
        return $map[$key];
    }

    private function prepararDatosSalud(string $incidenciaTipo, array $victima, string $fecha, string $uuidIncidencia): array
    {
        // Descripción detallada automática
        $desc = "Consecuencia de Incidencia ($incidenciaTipo).";
        if (!empty($victima['descripcion'])) {
            $desc .= " Detalle: " . $victima['descripcion'];
        }

        return [
            'animal_id' => $victima['animal_id'],
            'fecha_evento' => substr($fecha, 0, 10), // YYYY-MM-DD

            // *** CAMBIO CLAVE 1: El tipo de evento de salud es el mismo que la incidencia ***
            'tipo_evento' => $incidenciaTipo,

            'diagnostico' => $desc,

            // *** CAMBIO CLAVE 2: Usamos la severidad específica de la víctima ***
            'severidad' => $victima['severidad'] ?? 'LEVE',

            'estado' => 'ABIERTO',
            'incidencia_id' => $uuidIncidencia
        ];
    }

    private function animalExiste(string $animalId): bool
    {
        $sql = "SELECT 1 FROM animales WHERE animal_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error verificación animal: " . $this->db->error);
        $stmt->bind_param('s', $animalId);
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
        $sql = "SELECT 1 FROM areas WHERE area_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error verificación área: " . $this->db->error);
        $stmt->bind_param('s', $areaId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }

    /** Cuenta incidencias por tipo para un animal (sin eliminadas) */
    private function contarIncidenciasPorTipo(string $animalId, string $tipo): int
    {
        $sql = "SELECT COUNT(*) c FROM {$this->table} WHERE animal_id = ? AND tipo = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error conteo incidencias: " . $this->db->error);
        $stmt->bind_param('ss', $animalId, $tipo);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Genera el próximo correlativo con formato INC-000001, usando un lock
     * de aplicación en MySQL para evitar condiciones de carrera.
     */
    private function generarCorrelativo(): string
    {
        // Intentar obtener un candado de aplicación para serializar la generación
        $lockOk = false;
        $lockRes = $this->db->query("SELECT GET_LOCK('incidencias_correlativo', 10) AS l");
        if ($lockRes && ($row = $lockRes->fetch_assoc())) {
            $lockOk = (int) $row['l'] === 1;
        }

        try {
            // Tomamos el mayor sufijo numérico actual
            $sql = "SELECT MAX(CAST(SUBSTRING(correlativo, 5) AS UNSIGNED)) AS maxnum
                    FROM {$this->table}
                    WHERE correlativo LIKE 'INC-%'";
            $res = $this->db->query($sql);
            $maxnum = 0;
            if ($res) {
                $r = $res->fetch_assoc();
                $maxnum = (int) ($r['maxnum'] ?? 0);
                $res->close();
            }
            $next = $maxnum + 1;
            // Formatear con 6 dígitos (ajusta si quieres más)
            $correlativo = 'INC-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            return $correlativo;
        } finally {
            if ($lockOk) {
                $this->db->query("SELECT RELEASE_LOCK('incidencias_correlativo')");
            }
        }
    }

    /* ========= Notificaciones ========= */

    // Firma compatible con llamadas existentes: (..., null, $actorId, $role)
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

    /* ========= Lecturas ========= */

    /**
     * Filtros: animal_id, tipo, desde..hasta (por fecha_evento), area_id, responsable (LIKE),
     *          incluirEliminados
     */
    public function listar(
        int $limit = 10000,
        int $offset = 0,
        bool $incluirEliminados = false,
        ?string $animalId = null,
        ?string $tipo = null,
        ?string $desde = null,
        ?string $hasta = null,
        ?string $areaId = null,
        ?string $responsable = null
    ): array {
        $w = [];
        $p = [];
        $t = '';
        $w[] = $incluirEliminados ? 'i.deleted_at IS NOT NULL OR i.deleted_at IS NULL' : 'i.deleted_at IS NULL';

        if ($animalId) {
            $w[] = 'i.animal_id = ?';
            $p[] = $animalId;
            $t .= 's';
        }
        if ($tipo) {
            $w[] = 'i.tipo = ?';
            $p[] = $this->normalizarTipo($tipo);
            $t .= 's';
        }
        if ($desde) {
            $w[] = 'i.fecha_evento >= ?';
            $p[] = $this->validarFechaHora($desde, 'desde');
            $t .= 's';
        }
        if ($hasta) {
            $w[] = 'i.fecha_evento <= ?';
            $p[] = $this->validarFechaHora($hasta, 'hasta');
            $t .= 's';
        }
        if ($areaId) {
            $w[] = 'i.area_id = ?';
            $p[] = $areaId;
            $t .= 's';
        }
        if ($responsable) {
            $w[] = 'i.responsable LIKE ?';
            $p[] = '%' . trim($responsable) . '%';
            $t .= 's';
        }

        $where = implode(' AND ', $w);

        $sql = "SELECT
                    i.incidencia_id,
                    i.correlativo,
                    i.animal_id,
                    a.identificador AS animal_identificador,
                    i.tipo,
                    i.fecha_evento,
                    i.descripcion,
                    i.fotografia_url,
                    i.responsable,
                    i.area_id,
                    ar.nombre_personalizado AS area_nombre, ar.tipo_area, ar.numeracion,
                    i.created_at, i.created_by, i.updated_at, i.updated_by
                FROM {$this->table} i
                JOIN animales a ON a.animal_id = i.animal_id
                LEFT JOIN areas ar ON ar.area_id = i.area_id
                WHERE {$where}
                ORDER BY i.fecha_evento DESC, i.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error preparar listado: " . $this->db->error);
        $t .= 'ii';
        $p[] = $limit;
        $p[] = $offset;

        $stmt->bind_param($t, ...$p);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function obtenerPorId(string $id): ?array
    {
        // a) Datos de la incidencia
        $sql = "SELECT i.*, a.identificador AS animal_identificador, 
                       ar.nombre_personalizado AS area_nombre 
                FROM {$this->table} i
                JOIN animales a ON a.animal_id = i.animal_id
                LEFT JOIN areas ar ON ar.area_id = i.area_id
                WHERE i.incidencia_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $incidencia = $res->fetch_assoc();
        $stmt->close();

        if (!$incidencia)
            return null;

        // b) Datos de las consecuencias (Salud) vinculadas
        // Solo traemos las que NO están eliminadas
        $sqlSalud = "SELECT s.animal_salud_id, s.animal_id, 
                            a.identificador AS animal_identificador,
                            s.severidad, s.diagnostico AS descripcion
                     FROM animal_salud s
                     JOIN animales a ON a.animal_id = s.animal_id
                     WHERE s.incidencia_id = ? AND s.deleted_at IS NULL";

        $stmtS = $this->db->prepare($sqlSalud);
        $stmtS->bind_param('s', $id);
        $stmtS->execute();
        $resS = $stmtS->get_result();

        $consecuencias = [];
        while ($row = $resS->fetch_assoc()) {
            $consecuencias[] = $row;
        }
        $stmtS->close();

        // Inyectamos el array al resultado principal
        $incidencia['consecuencias_salud'] = $consecuencias;

        return $incidencia;
    }

    /* ========= Escrituras ========= */

    /**
     * Reglas base:
     * - animal_id requerido y existente.
     * - tipo en enum de la tabla.
     * - fecha_evento requerido (datetime).
     * - area_id opcional pero debe existir si viene.
     * - responsable/descripcion opcionales.
     * - fotografia_url opcional.
     * Efectos colaterales:
     * - Crea notificación 'incidencia_registrada'.
     * - Si tipo = APLASTAMIENTO y hay antecedentes, crea 'inc_reincidencia_aplastamiento'.
     */
    public function crear(array $data): string
    {
        // 1. Validaciones básicas
        foreach (['animal_id', 'tipo', 'fecha_evento'] as $k) {
            if (empty($data[$k]))
                throw new InvalidArgumentException("Falta campo requerido: {$k}.");
        }

        $animalId = trim((string) $data['animal_id']);
        if (!$this->animalExiste($animalId))
            throw new RuntimeException('El animal no existe.');

        $tipo = $this->normalizarTipo((string) $data['tipo']);
        $fecha = $this->validarFechaHora((string) $data['fecha_evento']);

        // 2. Lógica del Área: Si no viene, buscar la actual
        $areaId = !empty($data['area_id']) ? $data['area_id'] : $this->obtenerAreaActualDelAnimal($animalId);

        // Si aún así no hay área (animal sin ubicación), se permite NULL o lanza error según tu regla de negocio.
        // Aquí permitiremos NULL si no tiene ubicación, pero validamos si enviaron una ID invalida.
        if (!empty($data['area_id']) && !$this->areaExiste($areaId)) {
            throw new RuntimeException('El área especificada no existe.');
        }

        $desc = isset($data['descripcion']) ? trim((string) $data['descripcion']) : null;
        $resp = isset($data['responsable']) ? trim((string) $data['responsable']) : null;
        $foto = isset($data['fotografia_url']) ? trim((string) $data['fotografia_url']) : null;

        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();
            $uuid = UuidHelper::generateUUIDv4();
            $actorId = (string) ($_SESSION['user_id'] ?? '0');
            $role = (string) ($_SESSION['user_type'] ?? 'user');
            $correlativo = $this->generarCorrelativo();

            // 3. Insertar Incidencia
            $sql = "INSERT INTO {$this->table}
                    (incidencia_id, correlativo, animal_id, tipo, fecha_evento, descripcion, fotografia_url, responsable, area_id,
                     created_at, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new mysqli_sql_exception("Error al preparar inserción de incidencia: " . $this->db->error);
            }

            $stmt->bind_param(
                'sssssssssss',
                $uuid,
                $correlativo,
                $animalId,
                $tipo,
                $fecha,
                $desc,
                $foto,
                $resp,
                $areaId,
                $now,
                $actorId
            );

            if (!$stmt->execute()) {
                throw new mysqli_sql_exception("Error al crear incidencia: " . $stmt->error);
            }
            $stmt->close();

            $consecuencias = $data['consecuencias_salud'] ?? [];

            if (!empty($consecuencias) && is_array($consecuencias)) {
                foreach ($consecuencias as $victima) {
                    // Si la víctima no tiene ID (es el animal principal), asignamos el ID principal
                    if (empty($victima['animal_id'])) {
                        $victima['animal_id'] = $animalId;
                    }

                    // Usamos el helper para preparar los datos
                    $datosSalud = $this->prepararDatosSalud($tipo, $victima, $fecha, $uuid);

                    // Guardamos en animal_salud
                    $this->saludModel->crear($datosSalud);
                }
            }

            // 5. Notificaciones y Alertas (Lógica existente)
            // ... Recuperar identificador animal ...
            $qA = $this->db->prepare("SELECT identificador FROM animales WHERE animal_id = ?");
            $qA->bind_param('s', $animalId);
            $qA->execute();
            $resA = $qA->get_result()->fetch_assoc();
            $qA->close();
            $animalIdentificador = $resA['identificador'] ?? 'Desconocido';

            $this->notificar('incidencia_registrada', [
                'tipo_incidencia' => $tipo,
                'animal_identificador' => $animalIdentificador,
                'fecha_evento' => substr($fecha, 0, 16),
                'correlativo' => $correlativo
            ], '/animales/incidencias?incidencia_id=' . $uuid, null, $actorId, $role);

            // Alerta de Aplastamiento
            if ($tipo === 'APLASTAMIENTO') {
                $conteo = $this->contarIncidenciasPorTipo($animalId, 'APLASTAMIENTO');
                if ($conteo > 1) {
                    $this->alertaModel->crear([
                        'tipo_alerta' => 'REINCIDENCIA_APLASTAMIENTO',
                        'animal_id' => $animalId,
                        'fecha_objetivo' => date('Y-m-d'),
                        'detalle' => "Reincidencia aplastamiento ($conteo). Correlativo: $correlativo",
                        'origen_modulo' => 'INCIDENCIA',
                        'referencia_id' => $uuid,
                        'severidad' => 'ALTA'
                    ]);
                }
            }

            $this->db->commit();
            return $uuid;

        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Efecto: genera notificación 'incidencia_actualizada'
     */
    public function actualizar(string $id, array $data): bool
    {



        $this->db->begin_transaction();

        $row = $this->obtenerPorId($id);
        if (!$row || $row['deleted_at'] !== null) {
            throw new RuntimeException("Incidencia no existe o está eliminada.");
        }

        $campos = [];
        $params = [];
        $types = '';



        if (isset($data['tipo'])) {
            $tipo = $this->normalizarTipo((string) $data['tipo']);
            $campos[] = 'tipo = ?';
            $params[] = $tipo;
            $types .= 's';
        }
        if (isset($data['fecha_evento'])) {
            $fecha = $this->validarFechaHora((string) $data['fecha_evento']);
            $campos[] = 'fecha_evento = ?';
            $params[] = $fecha;
            $types .= 's';
        }
        if (array_key_exists('descripcion', $data)) {
            $campos[] = 'descripcion = ?';
            $params[] = $data['descripcion'] !== null ? trim((string) $data['descripcion']) : null;
            $types .= 's';
        }
        if (array_key_exists('fotografia_url', $data)) {
            $campos[] = 'fotografia_url = ?';
            $params[] = $data['fotografia_url'] !== null && $data['fotografia_url'] !== ''
                ? trim((string) $data['fotografia_url'])
                : null;
            $types .= 's';
        }
        if (array_key_exists('responsable', $data)) {
            $campos[] = 'responsable = ?';
            $params[] = $data['responsable'] !== null ? trim((string) $data['responsable']) : null;
            $types .= 's';
        }
        if (array_key_exists('area_id', $data)) {
            $areaId = $data['area_id'] ?? null;
            if (!$this->areaExiste($areaId)) {
                throw new RuntimeException('El área indicada no existe o está eliminada.');
            }
            $campos[] = 'area_id = ?';
            $params[] = $areaId;
            $types .= 's';
        }

        if (empty($campos))
            throw new InvalidArgumentException('No hay campos para actualizar.');

        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $id;

        $campos[] = 'updated_at = ?';
        $params[] = $now;
        $types .= 's';
        $campos[] = 'updated_by = ?';
        $params[] = $actorId;
        $types .= 's';

        $sql = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE incidencia_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error preparar actualización: " . $this->db->error);
        $types .= 's';
        $params[] = $id;

        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $err = strtolower($stmt->error);
        $stmt->close();
        if (!$ok) {
            if (strpos($err, 'foreign key') !== false)
                throw new RuntimeException('Violación de clave foránea (área).');
            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }


        if (isset($data['consecuencias_salud']) && is_array($data['consecuencias_salud'])) {

            $this->db->begin_transaction(); // Asegurar atomicidad si no estaba ya iniciada

            try {
                // 1. Obtener los registros actuales de salud para esta incidencia
                $existentes = [];
                $q = $this->db->prepare("SELECT animal_salud_id FROM animal_salud WHERE incidencia_id = ? AND deleted_at IS NULL");
                $q->bind_param('s', $id);
                $q->execute();
                $res = $q->get_result();
                while ($r = $res->fetch_assoc()) {
                    $existentes[] = $r['animal_salud_id'];
                }
                $q->close();

                $nuevosIds = []; // IDs que vienen del front

                foreach ($data['consecuencias_salud'] as $victima) {
                    $tipoIncidencia = $data['tipo'] ?? ($this->obtenerPorId($id)['tipo'] ?? 'OTRA');
                    $fechaIncidencia = $data['fecha_evento'] ?? ($this->obtenerPorId($id)['fecha_evento'] ?? date('Y-m-d'));

                    // Datos para salud
                    $datosSalud = [
                        'animal_id' => $victima['animal_id'],
                        'tipo_evento' => $tipoIncidencia, // <--- Tipo dinámico
                        'severidad' => $victima['severidad'] ?? 'LEVE', // <--- Severidad dinámica
                        'diagnostico' => "Consecuencia de Incidencia ($tipoIncidencia). " . ($victima['descripcion'] ?? '')
                    ];



                    if (!empty($victima['animal_salud_id'])) {
                        // Actualizar existente
                        $this->saludModel->actualizar($victima['animal_salud_id'], $datosSalud);
                        $nuevosIds[] = $victima['animal_salud_id'];
                    } else {
                        // Crear nuevo en edición
                        $datosSalud['fecha_evento'] = substr($fechaIncidencia, 0, 10);
                        $datosSalud['estado'] = 'ABIERTO';
                        $datosSalud['incidencia_id'] = $id;

                        $this->saludModel->crear($datosSalud);
                    }
                }

                // 2. Detectar y Eliminar los que ya no están
                // Si un ID estaba en $existentes pero NO está en $nuevosIds, el usuario lo borró del front -> Eliminamos
                $paraEliminar = array_diff($existentes, $nuevosIds);
                foreach ($paraEliminar as $deleteId) {
                    $this->saludModel->eliminar($deleteId);
                }

                $this->db->commit(); // Si abriste transacción aquí
            } catch (Throwable $ex) {
                $this->db->rollback();
                throw $ex;
            }
        }


        // Notificación de actualización (usa correlativo)
        $animalIdentificador = $row['animal_identificador'] ?? ($row['animal_id'] ?? '');
        $tipoFinal = isset($tipo) ? $tipo : (string) $row['tipo'];
        $correlativo = (string) ($row['correlativo'] ?? '');

        $this->notificar(
            'incidencia_actualizada',
            [
                'correlativo' => $correlativo,
                'tipo_incidencia' => $tipoFinal,
                'animal_identificador' => $animalIdentificador,
                'fecha_actualizacion' => substr($now, 0, 16)
            ],
            '/animales/incidencias?incidencia_id=' . $id,
            null,
            $actorId
        );

        return true;
    }

    /**
     * Efecto: genera notificación 'incidencia_eliminada'
     * Efecto: elimina lógicamente la incidencia
     */
    public function eliminar(string $id): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId = (string) ($_SESSION['user_id'] ?? $id);
        $role = (string) ($_SESSION['user_type'] ?? 'user');

        $row = $this->obtenerPorId($id);
        if (!$row || $row['deleted_at'] !== null)
            return false;

        $this->db->begin_transaction();
        try {

            // A. Eliminar Incidencia
            $sql = "UPDATE {$this->table} SET deleted_at = ?, deleted_by = ? WHERE incidencia_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sss', $now, $actorId, $id);
            $stmt->execute();
            $stmt->close();

            // B. Eliminar Consecuencias de Salud (Cascada)
            // "Si borro el reporte de la pelea, borro los registros médicos asociados a esa pelea"
            $sqlSalud = "UPDATE animal_salud SET deleted_at = ?, deleted_by = ? 
                         WHERE incidencia_id = ? AND deleted_at IS NULL";
            $stmtS = $this->db->prepare($sqlSalud);
            $stmtS->bind_param('sss', $now, $actorId, $id);
            $stmtS->execute();
            $stmtS->close();

            // 2) Notificación de eliminación (usa correlativo, no el id)
            $animalIdentificador = $row['animal_identificador'] ?? ($row['animal_id'] ?? '');
            $tipoFinal = (string) ($row['tipo'] ?? 'OTRA');
            $correlativo = (string) ($row['correlativo'] ?? '');

            $this->notificar(
                'incidencia_eliminada',
                [
                    'correlativo' => $correlativo,
                    'tipo_incidencia' => $tipoFinal,
                    'animal_identificador' => $animalIdentificador
                ],
                '/incidencias', // ruta genérica a listado de incidencias
                null,
                $actorId,
                $role
            );

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

    }

}
