<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';
require_once __DIR__ . '/CamadaModel.php'; // <--- AÑADIR ESTO
require_once __DIR__ . '/../helpers/NotificationTemplateHelper.php'; // <--- AÑADIR
require_once __DIR__ . '/AlertaModel.php';                              // <--- AÑADIR
require_once __DIR__ . '/NotificationModel.php';                        // <--- AÑADIR
require_once __DIR__ . '/AnimalMovimientoModel.php';                    // <--- AÑADIR (tu modelo de movimientos)

class PartoModel
{
    private $db;
    private $table = 'partos';

    private $movimientoModel; // <--- YA EXISTÍA EN TU NOTA, SE USA ABAJO
    private $camadaModel;

    // Nuevos: para alertas y notificaciones
    private $alertaModel;            // <--- AÑADIR
    private $notificationModel;      // <--- AÑADIR

    // ======= Config rápida (ajústala si tu SRS cambia) =======

    private const INC_MAP_AUTOGEN = [
        'DISTOCIA' => ['tipo' => 'OTRA', 'descripcion' => 'Parto con distocia'],
        'MUERTE_PERINATAL' => ['tipo' => 'OTRA', 'descripcion' => 'Muerte perinatal'],
    ];
    private const ALERT_MAP_AUTOGEN = [
        'DISTOCIA' => ['tipo_alerta' => 'POSTPARTO_REVISION', 'dias_offset' => 3, 'detalle' => 'Revisión postparto por distocia'],
        'MUERTE_PERINATAL' => ['tipo_alerta' => 'INVESTIGACION_MUERTE_PERINATAL', 'dias_offset' => 1, 'detalle' => 'Investigar causa de muerte perinatal'],
    ];
    private const ALERT_ESTADO_DEFAULT = 'PENDIENTE';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->camadaModel = new CamadaModel();
        $this->movimientoModel = new AnimalMovimientoModel();

        // Instancias añadidas
        $this->alertaModel = new AlertaModel();           // <--- AÑADIR
        $this->notificationModel = new NotificationModel(); // <--- AÑADIR
    }

    /* ============ Utilidades ============ */

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

    // Igual que en tu IncidenciaModel: wrapper para crear notificaciones por plantilla
    private function notificar(
        string $templateKey,
        array $params,
        ?string $route,
        ?string $legacyUnused = null, // retrocompatibilidad (no se usa)
        ?string $userId = null,       // user_id real (actor)
        ?string $role = null          // rol
    ): void {
        // Módulo sugerido por meta; fallback razonable
        $meta = NotificationTemplateHelper::getMeta($templateKey);
        $module = $meta ? ($meta['module'] ?? 'reproduccion') : 'reproduccion';

        $finalUserId = $userId ?: (string) ($_SESSION['user_id'] ?? '0');
        $finalRole = $role ?: (string) ($_SESSION['user_type'] ?? 'user');

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
            error_log("[PartoModel] Error al notificar ($templateKey): " . $e->getMessage());
        }
    }

    private function validarEstadoParto(?string $v): void
    {
        if ($v === null)
            return;
        $validos = ['NORMAL', 'DISTOCIA', 'MUERTE_PERINATAL', 'OTRO'];
        if (!in_array($v, $validos, true)) {
            throw new InvalidArgumentException("estado_parto inválido. Use: " . implode(', ', $validos));
        }
    }

    private function periodoExiste(string $id): bool
    {
        $sql = "SELECT 1 FROM periodos_servicio WHERE periodo_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception($this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
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

    private function normalizarTipoIncidencia(string $tipo): string
    {
        // Enum esperado en tu BD: RINA, APLASTAMIENTO, HERIDA, MORDIDA, FUGA, OTRA
        $map = [
            'RIÑA' => 'RINA',
            'RINA' => 'RINA',
            'APLASTAMIENTO' => 'APLASTAMIENTO',
            'HERIDA' => 'HERIDA',
            'MORDIDA' => 'MORDIDA',
            'FUGA' => 'FUGA',
            'OTRA' => 'OTRA',
            'OTRO' => 'OTRA',
            'OTRAS' => 'OTRA',
        ];
        $key = strtoupper(trim($tipo));
        $key = str_replace('Ñ', 'N', $key);
        if (!isset($map[$key])) {
            throw new InvalidArgumentException("tipo de incidencia inválido. Use: RINA, APLASTAMIENTO, HERIDA, MORDIDA, FUGA, OTRA");
        }
        return $map[$key];
    }

    private function getAnimalIdentificador(string $animalId): string
    {
        $q = $this->db->prepare("SELECT identificador FROM animales WHERE animal_id = ? LIMIT 1");
        if (!$q)
            return $animalId;
        $q->bind_param('s', $animalId);
        $q->execute();
        $r = $q->get_result()->fetch_assoc();
        $q->close();
        return $r['identificador'] ?? $animalId;
    }

    /* ============ Lecturas ============ */

    // Listar hembras con preñez confirmada que no tienen parto.
    public function listarGestantes(): array
    {
        $sql = "SELECT
                    a.animal_id AS hembra_id,
                    a.identificador AS hembra_identificador,
                    ps.periodo_id,
                    ps.fecha_inicio AS fecha_monta,
                    rs.fecha_realizada AS fecha_confirmacion,
                    DATEDIFF(CURDATE(), rs.fecha_realizada) AS dias_gestacion
                FROM
                    animales a
                JOIN
                    periodos_servicio ps ON a.animal_id = ps.hembra_id
                JOIN
                    revisiones_servicio rs ON ps.periodo_id = rs.periodo_id
                WHERE
                    a.sexo = 'HEMBRA'
                    AND a.especie = 'PORCINO'
                    AND a.deleted_at IS NULL
                    AND rs.resultado = 'CONFIRMADA_PREÑEZ'
                    AND NOT EXISTS (
                        SELECT 1 FROM partos p 
                        WHERE p.periodo_id = ps.periodo_id AND p.deleted_at IS NULL
                    )
                ORDER BY
                    dias_gestacion DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar listado gestantes: " . $this->db->error);

        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    public function listar(
        int $limit = 10000,
        int $offset = 0,
        bool $incluirEliminados = false,
        ?string $periodoId = null,
        ?string $estado = null,
        ?string $desde = null,
        ?string $hasta = null
    ): array {
        $w = [];
        $p = [];
        $t = '';

        $w[] = $incluirEliminados ? 'p.deleted_at IS NOT NULL OR p.deleted_at IS NULL' : 'p.deleted_at IS NULL';

        if ($periodoId) {
            $w[] = 'p.periodo_id = ?';
            $p[] = $periodoId;
            $t .= 's';
        }
        if ($estado) {
            $this->validarEstadoParto($estado);
            $w[] = 'p.estado_parto = ?';
            $p[] = $estado;
            $t .= 's';
        }
        if ($desde) {
            $w[] = 'p.fecha_parto >= ?';
            $p[] = $desde;
            $t .= 's';
        }
        if ($hasta) {
            $w[] = 'p.fecha_parto <= ?';
            $p[] = $hasta;
            $t .= 's';
        }

        $whereSql = implode(' AND ', $w);

        $sql = "SELECT
                    p.parto_id,
                    p.periodo_id,
                    ps.hembra_id,
                    ps.verraco_id,
                    p.fecha_parto,
                    p.crias_machos,
                    p.crias_hembras,
                    p.peso_promedio_kg,
                    p.estado_parto,
                    p.observaciones,
                    p.fotografia_url,
                    p.created_at, p.created_by, p.updated_at, p.updated_by
                FROM {$this->table} p
                LEFT JOIN periodos_servicio ps ON ps.periodo_id = p.periodo_id
                WHERE {$whereSql}
                ORDER BY p.fecha_parto DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar listado: " . $this->db->error);

        $t .= 'ii';
        $p[] = $limit;
        $p[] = $offset;
        $stmt->bind_param($t, ...$p);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    public function obtenerPorId(string $partoId): ?array
    {
        $sql = "SELECT
                    p.parto_id,
                    p.periodo_id,
                    ps.hembra_id,
                    ps.verraco_id,
                    p.fecha_parto,
                    p.crias_machos,
                    p.crias_hembras,
                    p.peso_promedio_kg,
                    p.estado_parto,
                    p.observaciones,
                    p.fotografia_url,
                    p.created_at, p.created_by, p.updated_at, p.updated_by,
                    p.deleted_at, p.deleted_by
                FROM {$this->table} p
                LEFT JOIN periodos_servicio ps ON ps.periodo_id = p.periodo_id
                WHERE p.parto_id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar consulta: " . $this->db->error);

        $stmt->bind_param('s', $partoId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /* ============ Helpers (Incidencias & Alertas) ============ */

    private function insertIncidencia(array $ctx, string $animalId, string $tipo, string $fechaEvento, ?string $descripcion = null, ?string $responsable = null, ?string $areaId = null): string
    {
        if (!$this->animalExiste($animalId)) {
            throw new RuntimeException('El animal de la incidencia no existe o está eliminado.');
        }
        if (!$this->areaExiste($areaId)) {
            throw new RuntimeException('El área indicada en la incidencia no existe o está eliminada.');
        }

        $tipo = $this->normalizarTipoIncidencia($tipo);

        $sql = "INSERT INTO incidencias
                (incidencia_id, animal_id, tipo, fecha_evento, descripcion, responsable, area_id,
                 created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error preparar inserción de incidencia: " . $this->db->error);

        $uuid = UuidHelper::generateUUIDv4();
        $stmt->bind_param(
            'ssssssss',
            $uuid,
            $animalId,
            $tipo,
            $fechaEvento,
            $descripcion,
            $responsable,
            $areaId,
            $ctx['now']
        );
        if (!$stmt->execute()) {
            $e = $stmt->error;
            $stmt->close();
            throw new mysqli_sql_exception("Error al insertar incidencia: " . $e);
        }
        $stmt->close();
        return $uuid;
    }

    private function insertAlerta(array $ctx, string $tipoAlerta, string $fechaObjetivo, ?string $periodoId = null, ?string $animalId = null, ?string $detalle = null, string $estado = self::ALERT_ESTADO_DEFAULT): string
    {
        // Tabla 'alertas' acorde a tu patrón (tipo_alerta, fecha_objetivo, periodo_id?, animal_id?, detalle?, estado_alerta)
        $sql = "INSERT INTO alertas
                (alerta_id, tipo_alerta, fecha_objetivo, periodo_id, animal_id, detalle, estado_alerta,
                 created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error preparar inserción de alerta: " . $this->db->error);

        $uuid = UuidHelper::generateUUIDv4();
        $stmt->bind_param(
            'sssssssss',
            $uuid,
            $tipoAlerta,
            $fechaObjetivo,
            $periodoId,
            $animalId,
            $detalle,
            $estado,
            $ctx['now'],
            $ctx['actorId']
        );
        if (!$stmt->execute()) {
            $e = $stmt->error;
            $stmt->close();
            throw new mysqli_sql_exception("Error al insertar alerta: " . $e);
        }
        $stmt->close();
        return $uuid;
    }

    private function fechaPlusDias(string $ymd, int $dias): string
    {
        // Acepta 'YYYY-MM-DD' o 'YYYY-MM-DD HH:MM:SS' -> devuelve 'Y-m-d'
        $base = strlen($ymd) > 10 ? substr($ymd, 0, 10) : $ymd;
        $dt = new DateTime($base);
        if ($dias !== 0)
            $dt->modify(($dias > 0 ? '+' : '') . $dias . ' days');
        return $dt->format('Y-m-d');
    }

    /* ============ Escrituras ============ */

    /**
     * Crea un registro de parto + incidencias opcionales + alertas automáticas.
     * Requeridos: periodo_id, fecha_parto
     * Opcionales: crias_machos, crias_hembras, peso_promedio_kg, estado_parto (default NORMAL), observaciones, fotografia_url
     *
     * Extras opcionales:
     * - generar_alertas (bool, default true)
     * - incidencias: array<{
     *       animal_id, tipo (RINA/APLASTAMIENTO/HERIDA/MORDIDA/FUGA/OTRA),
     *       fecha_evento(YYYY-MM-DD[ HH:MM[:SS]]),
     *       descripcion?, responsable?, area_id?
     *   }>
     */
    public function crear(array $data): string
    {
        // 1. Validaciones de Parto (de tu archivo)
        $periodoId = trim((string) ($data['periodo_id'] ?? ''));
        $fechaParto = trim((string) ($data['fecha_parto'] ?? ''));

        if ($periodoId === '' || $fechaParto === '') {
            throw new InvalidArgumentException('Faltan campos requeridos: periodo_id, fecha_parto.');
        }

        if (!$this->periodoExiste($periodoId)) {
            throw new RuntimeException('El periodo de servicio no existe o está eliminado.');
        }

        $stmtPS = $this->db->prepare("SELECT hembra_id, verraco_id FROM periodos_servicio WHERE periodo_id = ? AND deleted_at IS NULL");
        if (!$stmtPS)
            throw new mysqli_sql_exception("Error preparar PS: " . $this->db->error);
        $stmtPS->bind_param('s', $periodoId);
        $stmtPS->execute();
        $ps = $stmtPS->get_result()->fetch_assoc() ?: null;
        $stmtPS->close();
        if (!$ps)
            throw new RuntimeException('Periodo de servicio inválido o eliminado.');
        $hembraId = (string) $ps['hembra_id'];
        $verracoId = (string) $ps['verraco_id'];

        $criasM = isset($data['crias_machos']) ? (int) $data['crias_machos'] : 0;
        $criasH = isset($data['crias_hembras']) ? (int) $data['crias_hembras'] : 0;
        $pesoProm = isset($data['peso_promedio_kg']) && $data['peso_promedio_kg'] !== '' ? (float) $data['peso_promedio_kg'] : null;
        $fotoUrl = isset($data['fotografia_url']) ? (string) $data['fotografia_url'] : null;

        if ($criasM < 0 || $criasH < 0) {
            throw new InvalidArgumentException('Las crías no pueden ser negativas.');
        }
        if ($pesoProm !== null && $pesoProm < 0) {
            throw new InvalidArgumentException('El peso promedio no puede ser negativo.');
        }

        $estado = isset($data['estado_parto']) ? (string) $data['estado_parto'] : 'NORMAL';
        $this->validarEstadoParto($estado);
        $observ = isset($data['observaciones']) ? trim((string) $data['observaciones']) : null;

        // 2. Validación de Movimiento (de tu archivo)
        if (empty($data['animal_id']) || empty($data['recinto_id_destino']) || empty($data['recinto_id_origen'])) {
            throw new InvalidArgumentException('Faltan datos de origen o destino para la transferencia de la madre.');
        }

        // 3. Validaciones de Incidencias/Alertas (de tu archivo)
        $generarAlertas = !isset($data['generar_alertas']) || (bool) $data['generar_alertas'] === true;
        $incidenciasIn = is_array($data['incidencias'] ?? null) ? $data['incidencias'] : [];

        // === INICIO DE TRANSACCIÓN ===
        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();
            $uuid = UuidHelper::generateUUIDv4(); // ID del Parto
            $actorId = (string) ($_SESSION['user_id'] ?? $uuid);
            $role = (string) ($_SESSION['user_type'] ?? 'user');
            $ctx = ['now' => $now, 'actorId' => $actorId];

            // --- ACCIÓN 1: Insertar el Parto ---
            $sql = "INSERT INTO {$this->table}
                    (parto_id, periodo_id, fecha_parto, crias_machos, crias_hembras, peso_promedio_kg,
                     estado_parto, observaciones, fotografia_url, created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception("Error al preparar inserción: " . $this->db->error);

            // Tipos correctos: s s s i i d/s s s s s s s
            // (peso_promedio_kg puede ser NULL, el resto son strings/ints)
            $types = 'sssii' . ($pesoProm === null ? 's' : 'd') . 'sssssss';

            $args = [
                $uuid,
                $periodoId,
                $fechaParto,
                $criasM,
                $criasH,
                $pesoProm === null ? null : $pesoProm,
                $estado,
                $observ,
                $fotoUrl,
                $now,
                $actorId, // created
                $now,
                $actorId  // updated
            ];

            $stmt->bind_param($types, ...$args);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new mysqli_sql_exception("Error al ejecutar inserción de parto: " . $err);
            }
            $stmt->close();

            // --- ACCIÓN 2: Crear la Camada asociada ---
            $cantidadTotal = $criasM + $criasH;
            if ($cantidadTotal > 0) {
                $this->camadaModel->crearDesdeParto(
                    $uuid,       // $parto_id
                    $hembraId,   // $madre_id
                    $cantidadTotal,
                    $ctx
                );
            }

            // --- ACCIÓN 3: Crear el Movimiento de la Madre ---
            $movimientoData = [
                "animal_id" => $data['animal_id'], // El ID de la madre (validado arriba)
                "fecha_mov" => $data['fecha_parto'],
                "tipo_movimiento" => "TRASLADO",
                "motivo" => "TRASLADO", // Tu AnimalMovimientoModel requiere 'TRASLADO'
                "observaciones" => "Traslado por parto a maternidad.",

                // Origen (validado arriba)
                "finca_origen_id" => $data['finca_origen_id'] ?? null,
                "aprisco_origen_id" => $data['aprisco_origen_id'] ?? null,
                "area_origen_id" => $data['area_origen_id'] ?? null,
                "recinto_id_origen" => $data['recinto_id_origen'] ?? null,

                // Destino (validado arriba)
                "finca_destino_id" => $data['finca_destino_id'] ?? null,
                "aprisco_destino_id" => $data['aprisco_destino_id'] ?? null,
                "area_destino_id" => $data['area_destino_id'] ?? null,
                "recinto_id_destino" => $data['recinto_id_destino'] ?? null,
            ];
            // Esta llamada se une a la transacción principal
            $this->movimientoModel->crear($movimientoData);

            // --- ACCIÓN 4: Incidencias (Tu lógica) + NOTIFICACIONES ---
            // Si el estado del parto dispara incidencia automática
            if (isset(self::INC_MAP_AUTOGEN[$estado])) {
                $cfg = self::INC_MAP_AUTOGEN[$estado];
                $incId = $this->insertIncidencia(
                    $ctx,
                    $hembraId,
                    $cfg['tipo'],
                    $fechaParto,
                    $cfg['descripcion'],
                    'sistema',
                    null
                );

                // Notificación por incidencia creada (usa plantillas provistas)
                $this->notificar(
                    'incidencia_registrada',
                    [
                        'tipo_incidencia'      => $cfg['tipo'],
                        'animal_identificador' => $this->getAnimalIdentificador($hembraId),
                        'fecha_evento'         => substr($fechaParto, 0, 10)
                    ],
                    '/animales/incidencias?incidencia_id=' . $incId,
                    null,
                    $actorId,
                    $role
                );
            }

            // Incidencias manuales incluidas en el payload
            foreach ($incidenciasIn as $ix => $inc) {
                if (!is_array($inc))
                    continue;
                $incAnimal = isset($inc['animal_id']) ? (string) $inc['animal_id'] : $hembraId;
                $incTipo = $this->normalizarTipoIncidencia((string) ($inc['tipo'] ?? 'OTRA'));
                $incFecha = isset($inc['fecha_evento']) ? (string) $inc['fecha_evento'] : $fechaParto;
                $incDesc = isset($inc['descripcion']) ? trim((string) $inc['descripcion']) : null;
                $incResp = isset($inc['responsable']) ? trim((string) $inc['responsable']) : null;
                $incArea = $inc['area_id'] ?? null;

                $incId = $this->insertIncidencia($ctx, $incAnimal, $incTipo, $incFecha, $incDesc, $incResp, $incArea);

                // Notificación por incidencia manual creada
                $this->notificar(
                    'incidencia_registrada',
                    [
                        'tipo_incidencia'      => $incTipo,
                        'animal_identificador' => $this->getAnimalIdentificador($incAnimal),
                        'fecha_evento'         => substr($incFecha, 0, 16)
                    ],
                    '/animales/incidencias?incidencia_id=' . $incId,
                    null,
                    $actorId,
                    $role
                );
            }

            // --- ACCIÓN 5: Alertas (Tu lógica) ---
            if ($generarAlertas && isset(self::ALERT_MAP_AUTOGEN[$estado])) {
                $cfgA = self::ALERT_MAP_AUTOGEN[$estado];
                $fechaObj = $this->fechaPlusDias($fechaParto, (int) $cfgA['dias_offset']);
                $alertaId = $this->insertAlerta(
                    $ctx,
                    $cfgA['tipo_alerta'],
                    $fechaObj,
                    $periodoId,
                    $hembraId,
                    (string) $cfgA['detalle'],
                    self::ALERT_ESTADO_DEFAULT
                );

                // Ver comentario en tu versión anterior:
                // aquí podrías disparar notificaciones específicas si creas nuevas plantillas.
            }

            // --- COMMIT ---
            $this->db->commit();
            return $uuid; // Retorna el ID del Parto

        } catch (\Throwable $e) {
            // --- ROLLBACK ---
            $this->db->rollback();
            // Lanzamos una excepción genérica para que el controlador la capture
            throw new RuntimeException("Error en la transacción de parto: " . $e->getMessage());
        }
    }

    public function actualizar(string $partoId, array $data): bool
    {

        if (isset($data['crias_machos']) || isset($data['crias_hembras'])) {
            // Permitimos la edición SÓLO SI AÚN NO se ha creado una camada
            // (lo que implica que el parto se guardó con 0 crías inicialmente)
            $stmtCheck = $this->db->prepare("SELECT 1 FROM camadas WHERE parto_id = ?");
            $stmtCheck->bind_param('s', $partoId);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            $camadaExiste = $stmtCheck->num_rows > 0;
            $stmtCheck->close();

            if ($camadaExiste) {
                throw new RuntimeException('La cantidad de crías nacidas no puede modificarse después de registrada la camada.');
            }
            // Si la camada no existe (ej. se guardó con 0), sí permitimos actualizar las crías
            // y, en ese caso, deberíamos crear la camada. (Se deja para una lógica v2, por ahora solo bloqueamos)
        }

        $set = [];
        $p = [];
        $t = '';

        if (array_key_exists('periodo_id', $data)) {
            $v = $data['periodo_id'];
            if ($v !== null && $v !== '' && !$this->periodoExiste($v)) {
                throw new InvalidArgumentException('periodo_id inválido.');
            }
            $set[] = 'periodo_id = ?';
            $p[] = ($v !== '' ? $v : null);
            $t .= 's';
        }
        if (isset($data['fecha_parto'])) {
            $set[] = 'fecha_parto = ?';
            $p[] = (string) $data['fecha_parto'];
            $t .= 's';
        }
        if (isset($data['crias_machos'])) {
            $cm = (int) $data['crias_machos'];
            if ($cm < 0)
                throw new InvalidArgumentException('crias_machos no puede ser negativo.');
            $set[] = 'crias_machos = ?';
            $p[] = $cm;
            $t .= 'i';
        }
        if (isset($data['crias_hembras'])) {
            $ch = (int) $data['crias_hembras'];
            if ($ch < 0)
                throw new InvalidArgumentException('crias_hembras no puede ser negativo.');
            $set[] = 'crias_hembras = ?';
            $p[] = $ch;
            $t .= 'i';
        }
        if (array_key_exists('peso_promedio_kg', $data)) {
            $pp = $data['peso_promedio_kg'];
            if ($pp === '' || $pp === null) {
                $set[] = 'peso_promedio_kg = ?';
                $p[] = null;
                $t .= 's';
            } else {
                $pp = (float) $pp;
                if ($pp < 0)
                    throw new InvalidArgumentException('peso_promedio_kg no puede ser negativo.');
                $set[] = 'peso_promedio_kg = ?';
                $p[] = $pp;
                $t .= 'd';
            }
        }
        if (isset($data['estado_parto'])) {
            $this->validarEstadoParto((string) $data['estado_parto']);
            $set[] = 'estado_parto = ?';
            $p[] = (string) $data['estado_parto'];
            $t .= 's';
        }
        if (array_key_exists('observaciones', $data)) {
            $set[] = 'observaciones = ?';
            $p[] = ($data['observaciones'] !== '' ? (string) $data['observaciones'] : null);
            $t .= 's';
        }
        if (array_key_exists('fotografia_url', $data)) {
            $set[] = 'fotografia_url = ?';
            $p[] = ($data['fotografia_url'] !== '' ? (string) $data['fotografia_url'] : null);
            $t .= 's';
        }

        if (empty($set))
            throw new InvalidArgumentException('No hay campos para actualizar.');

        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $partoId;

        $set[] = 'updated_at = ?';
        $p[] = $now;
        $t .= 's';
        $set[] = 'updated_by = ?';
        $p[] = $actorId;
        $t .= 's';

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE parto_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar actualización: " . $this->db->error);

        $t .= 's';
        $p[] = $partoId;
        $stmt->bind_param($t, ...$p);
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();

        if (!$ok) {
            if (str_contains(strtolower($err), 'foreign key')) {
                throw new RuntimeException('Referencia inválida a periodo de servicio.');
            }
            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }
        return true;
    }

    public function actualizarEstado(string $partoId, string $estado): bool
    {
        $this->validarEstadoParto($estado);

        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $partoId;

        $sql = "UPDATE {$this->table}
                SET estado_parto = ?, updated_at = ?, updated_by = ?
                WHERE parto_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar actualización de estado: " . $this->db->error);

        $stmt->bind_param('ssss', $estado, $now, $actorId, $partoId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function eliminar(string $partoId): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $partoId;

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE parto_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar eliminación: " . $this->db->error);

        $stmt->bind_param('sss', $now, $actorId, $partoId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
