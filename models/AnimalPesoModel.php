<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';
require_once __DIR__ . '/../helpers/NotificationTemplateHelper.php'; // ← AÑADIDO
require_once __DIR__ . '/AlertaModel.php';                           // ← AÑADIDO
require_once __DIR__ . '/NotificationModel.php';                     // ← AÑADIDO

class AnimalPesoModel
{
    private $db;
    private $table = 'animal_pesos';
    private $notificationModel; // ← AÑADIDO
    private $alertaModel;       // ← AÑADIDO

    public function __construct()
    {
        $this->db = Database::getInstance();
        // Instancias para alertas y notificaciones (mismo patrón que IncidenciaModel)
        $this->alertaModel = new AlertaModel();            // ← AÑADIDO
        $this->notificationModel = new NotificationModel(); // ← AÑADIDO
    }

    /* ============ Utilidades ============ */

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

    // ==== AÑADIDO: helper de notificaciones (misma firma/estilo que IncidenciaModel) ====
    private function notificar(
        string $templateKey,
        array $params,
        ?string $route,
        ?string $legacyUnused = null, // compat
        ?string $userId = null,       // actor
        ?string $role = null          // rol
    ): void
    {
        // 1) Módulo desde metadata de la plantilla; fallback a 'pesos'
        $meta   = NotificationTemplateHelper::getMeta($templateKey);
        $module = $meta ? ($meta['module'] ?? 'pesos') : 'pesos';

        // 2) user/role desde parámetro o sesión
        $finalUserId = $userId ?: (string)($_SESSION['user_id']  ?? '0');
        $finalRole   = $role   ?: (string)($_SESSION['user_type'] ?? 'user');

        // 3) payload para NotificationModel->crear()
        $data_para_crear = [
            'template_key'    => $templateKey,
            'template_params' => $params,
            'route'           => $route,
            'module'          => $module,
            'rol'             => $finalRole,
            'user_id'         => $finalUserId
        ];

        // 4) persistir/emitir
        try {
            $this->notificationModel->crear($data_para_crear);
        } catch (\Throwable $e) {
            error_log("Error al crear notificación en AnimalPesoModel: " . $e->getMessage());
        }
    }

    private function animalExiste(string $animalId): bool
    {
        $sql = "SELECT 1 FROM animales WHERE animal_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar verificación de animal: " . $this->db->error);
        $stmt->bind_param('s', $animalId);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    private function validarFecha(string $fechaYmd): void
    {
        // YYYY-MM-DD
        $ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaYmd) === 1;
        if (!$ok) throw new InvalidArgumentException("fecha_peso inválida. Formato esperado YYYY-MM-DD.");

        [$y,$m,$d] = array_map('intval', explode('-', $fechaYmd));
        if (!checkdate($m,$d,$y)) {
            throw new InvalidArgumentException("fecha_peso no es una fecha válida.");
        }
    }

    private function normalizarPeso(float $valor, string $unidad): float
    {
        $unidad = strtoupper(trim($unidad));
        if (!in_array($unidad, ['KG','LB'], true)) {
            throw new InvalidArgumentException("unidad inválida. Use 'KG' o 'LB'.");
        }
        if ($valor <= 0 || $valor > 9999) {
            throw new InvalidArgumentException("peso fuera de rango razonable.");
        }
        // Convertir a KG si viene en LB
        return $unidad === 'LB' ? $valor * 0.45359237 : $valor;
    }

    /**
     * Evalúa el peso contra el tabulador (raza+edad) e implementa la lógica del SP en línea:
     * - Carga animal/peso.
     * - Verifica especie PORCINO y datos necesarios.
     * - Calcula edad (días) al momento del peso.
     * - Busca tabulador aplicable; si no hay, no genera alerta.
     * - Limpia alerta previa para este registro (tipo PESO_FUERA_RANGO / origen PESO).
     * - Si fuera de rango: crea alerta en `alertas` (vía AlertaModel) y notificación 'peso_fuera_rango'.
     */
    private function evaluarPesoDestete(string $animalPesoId): void
    {
        // ==== Cargar datos del peso y del animal ====
        $sql = "SELECT ap.animal_id, ap.fecha_peso, ap.peso_kg,
                       a.raza_id, a.fecha_nacimiento, a.especie, a.identificador
                  FROM animal_pesos ap
                  JOIN animales a ON a.animal_id = ap.animal_id
                 WHERE ap.animal_peso_id = ?
                 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error cargar peso/animal: " . $this->db->error);
        $stmt->bind_param('s', $animalPesoId);
        $stmt->execute();
        $res  = $stmt->get_result();
        $row  = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            // No hay datos — no hacemos nada (comportamiento equivalente a LEAVE main;)
            return;
        }

        $v_animal_id   = $row['animal_id'] ?? null;
        $v_fecha_peso  = $row['fecha_peso'] ?? null;
        $v_peso        = isset($row['peso_kg']) ? (float)$row['peso_kg'] : null;
        $v_raza_id     = $row['raza_id'] ?? null;
        $v_fecha_nac   = $row['fecha_nacimiento'] ?? null;
        $v_especie     = $row['especie'] ?? null;
        $identificador = $row['identificador'] ?? $v_animal_id;

        // ==== Validaciones mínimas ====
        if ($v_animal_id === null) return;
        if ($v_especie === null || strtoupper($v_especie) !== 'PORCINO') return;
        if ($v_raza_id === null || $v_fecha_nac === null || $v_fecha_peso === null || $v_peso === null) return;

        // Edad en días al momento del registro de peso
        $qEdad = $this->db->prepare("SELECT DATEDIFF(?, ?) AS edad_dias");
        $qEdad->bind_param('ss', $v_fecha_peso, $v_fecha_nac);
        $qEdad->execute();
        $rEdad = $qEdad->get_result()->fetch_assoc();
        $qEdad->close();

        $v_edad = isset($rEdad['edad_dias']) ? (int)$rEdad['edad_dias'] : null;
        if ($v_edad === null || $v_edad < 0) return;

        // Buscar rango del tabulador para esa edad y raza
        $qTab = $this->db->prepare("
            SELECT t.peso_ideal, t.margen_min, t.margen_max
              FROM tabuladores_peso t
             WHERE t.raza_id = ?
               AND ? BETWEEN t.edad_min_dias AND t.edad_max_dias
             ORDER BY (t.edad_max_dias - t.edad_min_dias) ASC
             LIMIT 1
        ");
        if (!$qTab) throw new mysqli_sql_exception("Error preparar tabulador: " . $this->db->error);
        $qTab->bind_param('si', $v_raza_id, $v_edad);
        $qTab->execute();
        $rTab = $qTab->get_result()->fetch_assoc();
        $qTab->close();

        if (!$rTab || $rTab['peso_ideal'] === null) {
            // Sin tabulador aplicable → no generamos alerta
            return;
        }

        $v_peso_ideal   = (float)$rTab['peso_ideal'];
        $v_margen_min   = (float)$rTab['margen_min'];
        $v_margen_max   = (float)$rTab['margen_max'];
        $v_min_acceptable = $v_peso_ideal - $v_margen_min;
        $v_max_acceptable = $v_peso_ideal + $v_margen_max;

        // ==== Limpiar posible alerta previa para este registro de peso ====
        $del = $this->db->prepare("
            DELETE FROM alertas
             WHERE tipo_alerta = 'PESO_FUERA_RANGO'
               AND referencia_id = ?
               AND origen_modulo = 'PESO'
        ");
        if ($del) {
            $del->bind_param('s', $animalPesoId);
            $del->execute();
            $del->close();
        }

        // ==== Si está fuera de rango, crear alerta y notificación ====
        $fueraDeRango = ($v_peso < $v_min_acceptable) || ($v_peso > $v_max_acceptable);

        if ($fueraDeRango) {
            // 1) Crear alerta (usamos AlertaModel para unificar auditoría/estilo)
            $detalle = "Peso {$v_peso} kg fuera de rango [{$v_min_acceptable} - {$v_max_acceptable}] (ideal {$v_peso_ideal} kg, edad {$v_edad} d).";

            // severidad simple según desviación
            $desviacion = max($v_min_acceptable - $v_peso, $v_peso - $v_max_acceptable, 0);
            $severidad  = ($desviacion >= 2.0) ? 'ALTA' : 'MEDIA';

            try {
                $alertaId = $this->alertaModel->crear([
                    'tipo_alerta'    => 'PESO_FUERA_RANGO',
                    'animal_id'      => $v_animal_id,
                    'fecha_objetivo' => date('Y-m-d'),
                    'detalle'        => $detalle,
                    'origen_modulo'  => 'PESO',
                    'referencia_id'  => $animalPesoId,
                    'severidad'      => $severidad
                ]);
            } catch (\Throwable $e) {
                // Si hubiera un problema con el modelo, como fallback podríamos insertar directo,
                // pero conservamos el manejo centralizado. Solo registramos error.
                error_log("Error creando alerta PESO_FUERA_RANGO: " . $e->getMessage());
                $alertaId = null;
            }

            // 2) Notificación usando plantilla (mismo estilo que IncidenciaModel->notificar)
            $actorId = (string)($_SESSION['user_id']  ?? '0');
            $role    = (string)($_SESSION['user_type'] ?? 'user');

            $rangoTxt = number_format($v_min_acceptable, 2) . ' - ' . number_format($v_max_acceptable, 2);
            $this->notificar(
                'peso_fuera_rango',
                [
                    'animal_identificador' => (string)$identificador,
                    'peso'                  => (string)number_format($v_peso, 2),
                    'rango'                 => $rangoTxt,
                    'edad_dias'             => (string)$v_edad,
                    'ideal'                 => (string)number_format($v_peso_ideal, 2)
                ],
                // Ruta sugerida al detalle del peso o historial del animal:
                '/animales/pesos?animal_id=' . $v_animal_id . '&peso_id=' . $animalPesoId,
                null,
                $actorId,
                $role
            );
        }
        // Si está en rango, ya lo limpiamos arriba (DELETE). No notificamos nada.
    }

    /* ============ Lecturas ============ */

    /**
     * Lista registros de peso.
     * Filtros: animal_id, desde (YYYY-MM-DD), hasta (YYYY-MM-DD), incluirEliminados.
     */
public function listar(
    int $limit = 10000,
    int $offset = 0,
    bool $incluirEliminados = false,
    ?string $animalId = null,
    ?string $desde = null,
    ?string $hasta = null
): array {
    $where  = [];
    $params = [];
    $types  = '';

    // Eliminados
    $where[] = $incluirEliminados
        ? '(p.deleted_at IS NOT NULL OR p.deleted_at IS NULL)'
        : 'p.deleted_at IS NULL';

    if ($animalId) {
        $where[]  = 'p.animal_id = ?';
        $params[] = $animalId;
        $types   .= 's';
    }
    if ($desde) {
        $this->validarFecha($desde);
        $where[]  = 'p.fecha_peso >= ?';
        $params[] = $desde;
        $types   .= 's';
    }
    if ($hasta) {
        $this->validarFecha($hasta);
        $where[]  = 'p.fecha_peso <= ?';
        $params[] = $hasta;
        $types   .= 's';
    }

    $whereSql = implode(' AND ', $where);

    $sql = "SELECT
                p.animal_peso_id,
                p.animal_id,
                a.identificador AS animal_identificador,
                p.fecha_peso,
                p.peso_kg,
                p.metodo,
                p.observaciones,
                p.created_at,
                p.created_by,
                p.updated_at,
                p.updated_by,
                p.deleted_at,
                p.deleted_by,

                /* ====== EDAD ====== */
                TIMESTAMPDIFF(DAY,   a.fecha_nacimiento, p.fecha_peso) AS edad_dias,
                TIMESTAMPDIFF(MONTH, a.fecha_nacimiento, p.fecha_peso) AS edad_meses,
                /* edad_valor y edad_unidad: si < 90 días, se muestra en días; si no, en meses */
                CASE
                    WHEN TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso) < 90
                        THEN TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso)
                    ELSE TIMESTAMPDIFF(MONTH, a.fecha_nacimiento, p.fecha_peso)
                END AS edad_valor,
                CASE
                    WHEN TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso) < 90
                        THEN 'DIAS'
                    ELSE 'MESES'
                END AS edad_unidad,

                /* ====== PESO IDEAL / RANGO POR RAZA Y EDAD ====== */
                /* Se elige el rango que cubra la edad en días; si hay varios, el de menor amplitud */
                (
                    SELECT t.peso_ideal
                    FROM tabuladores_peso t
                    WHERE t.raza_id = a.raza_id
                      AND TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso)
                          BETWEEN t.edad_min_dias AND t.edad_max_dias
                    ORDER BY (t.edad_max_dias - t.edad_min_dias) ASC
                    LIMIT 1
                ) AS peso_ideal,

                /* Rango aceptable calculado desde el mismo tabulador (min/max) */
                (
                    SELECT (t.peso_ideal - t.margen_min)
                    FROM tabuladores_peso t
                    WHERE t.raza_id = a.raza_id
                      AND TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso)
                          BETWEEN t.edad_min_dias AND t.edad_max_dias
                    ORDER BY (t.edad_max_dias - t.edad_min_dias) ASC
                    LIMIT 1
                ) AS rango_min,

                (
                    SELECT (t.peso_ideal + t.margen_max)
                    FROM tabuladores_peso t
                    WHERE t.raza_id = a.raza_id
                      AND TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso)
                          BETWEEN t.edad_min_dias AND t.edad_max_dias
                    ORDER BY (t.edad_max_dias - t.edad_min_dias) ASC
                    LIMIT 1
                ) AS rango_max

            FROM {$this->table} p
            LEFT JOIN animales a ON a.animal_id = p.animal_id
            WHERE {$whereSql}
            /* Orden sugerido: por edad visible (desc), luego por fecha de peso y creación */
            ORDER BY edad_valor DESC, p.fecha_peso DESC, p.created_at DESC
            LIMIT ? OFFSET ?";

    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
        throw new mysqli_sql_exception("Error al preparar listado: " . $this->db->error);
    }

    // Paginación
    $types   .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res  = $stmt->get_result();
    $data = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}


public function obtenerPorId(string $id): ?array
{
    $sql = "SELECT
                p.animal_peso_id,
                p.animal_id,
                a.identificador AS animal_identificador,
                p.fecha_peso,
                p.peso_kg,
                p.metodo,
                p.observaciones,
                p.created_at,
                p.created_by,
                p.updated_at,
                p.updated_by,
                p.deleted_at,
                p.deleted_by,

                /* ====== EDAD ====== */
                TIMESTAMPDIFF(DAY,   a.fecha_nacimiento, p.fecha_peso) AS edad_dias,
                TIMESTAMPDIFF(MONTH, a.fecha_nacimiento, p.fecha_peso) AS edad_meses,
                /* edad_valor y edad_unidad: si < 90 días, se muestra en días; si no, en meses */
                CASE
                    WHEN TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso) < 90
                        THEN TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso)
                    ELSE TIMESTAMPDIFF(MONTH, a.fecha_nacimiento, p.fecha_peso)
                END AS edad_valor,
                CASE
                    WHEN TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso) < 90
                        THEN 'DIAS'
                    ELSE 'MESES'
                END AS edad_unidad,

                /* ====== PESO IDEAL POR RAZA/EDAD (TABULADOR) ====== */
                /* Se elige el rango que cubra la edad en días; si hay varios, el de menor amplitud */
                (
                    SELECT t.peso_ideal
                    FROM tabuladores_peso t
                    WHERE t.raza_id = a.raza_id
                      AND TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso)
                          BETWEEN t.edad_min_dias AND t.edad_max_dias
                    ORDER BY (t.edad_max_dias - t.edad_min_dias) ASC
                    LIMIT 1
                ) AS peso_ideal,

                /* Rango aceptable calculado desde el mismo tabulador (min/max) */
                (
                    SELECT (t.peso_ideal - t.margen_min)
                    FROM tabuladores_peso t
                    WHERE t.raza_id = a.raza_id
                      AND TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso)
                          BETWEEN t.edad_min_dias AND t.edad_max_dias
                    ORDER BY (t.edad_max_dias - t.edad_min_dias) ASC
                    LIMIT 1
                ) AS rango_min,

                (
                    SELECT (t.peso_ideal + t.margen_max)
                    FROM tabuladores_peso t
                    WHERE t.raza_id = a.raza_id
                      AND TIMESTAMPDIFF(DAY, a.fecha_nacimiento, p.fecha_peso)
                          BETWEEN t.edad_min_dias AND t.edad_max_dias
                    ORDER BY (t.edad_max_dias - t.edad_min_dias) ASC
                    LIMIT 1
                ) AS rango_max

            FROM {$this->table} p
            LEFT JOIN animales a ON a.animal_id = p.animal_id
            WHERE p.animal_peso_id = ?
            /* Aunque devuelve 1 registro por ID, mantenemos el orden solicitado */
            ORDER BY edad_valor DESC";

    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
        throw new mysqli_sql_exception("Error al preparar consulta: " . $this->db->error);
    }

    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}


    /* ============ Escrituras ============ */

    /**
     * Crear registro de peso.
     * Requeridos: animal_id, fecha_peso (YYYY-MM-DD), peso_kg (numérico), unidad ('KG'|'LB')
     * Opcionales: metodo (string corto), observaciones (texto)
     * Nota: se guarda en peso_kg (conversión automática si unidad=LB)
     * Tras crear, se evalúa contra tabulador y se genera alerta/notificación si corresponde.
     */
    public function crear(array $data): string
    {
        foreach (['animal_id','fecha_peso','peso_kg','unidad'] as $k) {
            if (!isset($data[$k]) || $data[$k] === '') {
                throw new InvalidArgumentException("Falta campo requerido: {$k}.");
            }
        }

        $animalId   = (string) trim((string)$data['animal_id']);
        $fechaPeso  = (string) trim((string)$data['fecha_peso']);
        $pesoInput  = (float) $data['peso_kg'];
        $unidad     = (string) $data['unidad'];

        if (!$this->animalExiste($animalId)) {
            throw new RuntimeException('El animal especificado no existe o está eliminado.');
        }
        $this->validarFecha($fechaPeso);

        // Convertir a KG si es necesario y validar rango
        $pesoKg = $this->normalizarPeso($pesoInput, $unidad);

        $metodo        = isset($data['metodo']) ? (string) trim((string)$data['metodo']) : null;
        $observaciones = isset($data['observaciones']) ? (string) trim((string)$data['observaciones']) : null;

        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();
            $uuid    = UuidHelper::generateUUIDv4();
            $actorId = $_SESSION['user_id'] ?? $uuid;

            $sql = "INSERT INTO {$this->table}
                    (animal_peso_id, animal_id, fecha_peso, peso_kg, metodo, observaciones,
                     created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) throw new mysqli_sql_exception("Error al preparar inserción: " . $this->db->error);

            // Tipos: uuid(s), animal_id(s), fecha(s), peso(d), metodo(s), observ(s), created_at(s), created_by(s)
            $types = 'sssdssss';
            $stmt->bind_param(
                $types,
                $uuid,
                $animalId,
                $fechaPeso,
                $pesoKg,
                $metodo,
                $observaciones,
                $now,
                $actorId
            );

            if (!$stmt->execute()) {
                $err = strtolower($stmt->error);
                $stmt->close();
                $this->db->rollback();

                if (str_contains($err, 'foreign key')) {
                    throw new RuntimeException('El animal no existe (violación de clave foránea).');
                }
                if (str_contains($err, 'duplicate')) {
                    throw new RuntimeException('Ya existe un registro de peso para este animal en esa fecha.');
                }
                throw new mysqli_sql_exception("Error al ejecutar inserción: " . $err);
            }
            $stmt->close();

            // === Evaluar contra tabulador y generar/limpiar alerta + notificación ===
            $this->evaluarPesoDestete($uuid);

            $this->db->commit();
            return $uuid;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Actualiza campos: fecha_peso?, peso/unidad?, metodo?, observaciones?
     * Si se envían 'peso' y 'unidad', se recalcula y guarda en peso_kg.
     * Si cambian peso/unidad o fecha_peso, se re-evalúa el rango (inline).
     */
    public function actualizar(string $id, array $data): bool
    {
        $campos = [];
        $params = [];
        $types  = '';

        $recalcular = false;

        if (isset($data['fecha_peso'])) {
            $this->validarFecha((string)$data['fecha_peso']);
            $campos[] = 'fecha_peso = ?';
            $params[] = (string)$data['fecha_peso'];
            $types   .= 's';
            $recalcular = true;
        }

        if (isset($data['peso']) && isset($data['unidad'])) {
            $pesoKg = $this->normalizarPeso((float)$data['peso'], (string)$data['unidad']);
            $campos[] = 'peso_kg = ?';
            $params[] = $pesoKg;
            $types   .= 'd';
            $recalcular = true;
        } elseif (isset($data['peso']) xor isset($data['unidad'])) {
            throw new InvalidArgumentException("Si actualizas el peso debes enviar ambos campos: 'peso' y 'unidad'.");
        }

        if (array_key_exists('metodo', $data)) {
            $campos[] = 'metodo = ?';
            $params[] = $data['metodo'] !== null ? trim((string)$data['metodo']) : null;
            $types   .= 's';
        }
        if (array_key_exists('observaciones', $data)) {
            $campos[] = 'observaciones = ?';
            $params[] = $data['observaciones'] !== null ? trim((string)$data['observaciones']) : null;
            $types   .= 's';
        }

        if (empty($campos)) {
            throw new InvalidArgumentException('No hay campos para actualizar.');
        }

        [$now, $env] = $this->nowWithAudit();
        $actorId     = $_SESSION['user_id'] ?? $id;

        $campos[] = 'updated_at = ?';
        $params[] = $now;    $types .= 's';
        $campos[] = 'updated_by = ?';
        $params[] = $actorId; $types .= 's';

        $sql = "UPDATE {$this->table}
                SET " . implode(', ', $campos) . "
                WHERE animal_peso_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar actualización: " . $this->db->error);

        $types   .= 's';
        $params[] = $id;

        $stmt->bind_param($types, ...$params);
        $ok  = $stmt->execute();
        $err = strtolower($stmt->error);
        $stmt->close();

        if (!$ok) {
            if (str_contains($err, 'duplicate')) {
                throw new RuntimeException('Conflicto de unicidad (ej: mismo animal y fecha).');
            }
            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }

        // === Re-evaluar rango si cambió peso/unidad o fecha_peso ===
        if ($recalcular) {
            $this->evaluarPesoDestete($id);
        }

        return true;
    }

    /**
     * Eliminación lógica (soft delete)
     */
    public function eliminar(string $id): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId     = $_SESSION['user_id'] ?? $id;

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE animal_peso_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar eliminación: " . $this->db->error);

        $stmt->bind_param('sss', $now, $actorId, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
