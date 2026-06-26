<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/ConfiguracionModel.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';
require_once __DIR__ . '/AnimalUbicacionModel.php';
class AnimalModel
{
    private $db;
    private $table = 'animales';
    private $configModel;

    private $ubicacionModel; // <--- AÑADIR ESTO


    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->configModel = new ConfiguracionModel();
        $this->ubicacionModel = new AnimalUbicacionModel(); // <--- AÑADIR ESTO
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

    private function validarFecha(?string $ymd, string $campo = 'fecha'): void
    {
        if ($ymd === null)
            return;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd) !== 1) {
            throw new InvalidArgumentException("$campo inválida. Formato esperado YYYY-MM-DD.");
        }
        [$y, $m, $d] = array_map('intval', explode('-', $ymd));
        if (!checkdate($m, $d, $y)) {
            throw new InvalidArgumentException("$campo no es una fecha válida.");
        }
    }

    private function validarEnum(string $valor, array $permitidos, string $campo): string
    {
        $v = strtoupper(trim($valor));
        if (!in_array($v, $permitidos, true)) {
            throw new InvalidArgumentException("$campo inválido. Use uno de: " . implode(', ', $permitidos));
        }
        return $v;
    }

    private function animalExistePorId(string $animalId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE animal_id = ? AND deleted_at IS NULL LIMIT 1";
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

    private function razaExistePorId(string $razaId): bool
    {
        $sql = "SELECT 1 FROM razas WHERE raza_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error verificación raza: " . $this->db->error);
        $stmt->bind_param('s', $razaId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }

    // NUEVO: Helper para validar camada_id
    private function camadaExistePorId(string $camadaId): bool
    {
        $sql = "SELECT 1 FROM camadas WHERE camada_id = ? AND estado_camada = 'ACTIVA' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error verificación camada: " . $this->db->error);
        $stmt->bind_param('s', $camadaId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }

    public function identificadorDisponible(string $identificador, ?string $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE identificador = ? AND deleted_at IS NULL";
        if ($exceptId)
            $sql .= " AND animal_id <> ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error verificación identificador: " . $this->db->error);
        if ($exceptId)
            $stmt->bind_param('ss', $identificador, $exceptId);
        else
            $stmt->bind_param('s', $identificador);
        $stmt->execute();
        $stmt->store_result();
        $ocupado = $stmt->num_rows > 0;
        $stmt->close();
        return !$ocupado;
    }

    /* ============ Lecturas ============ */

    /**
     * Verifica compatibilidad de cruce entre dos animales.
     * Regla: si comparten madre o comparten padre => NO compatibles.
     * Retorna: ['compatible'=>bool, 'motivo'=>string|null, 'a'=>array|null, 'b'=>array|null]
     */
    public function puedenCruzar(string $animalIdA, string $animalIdB, int $maxGenerations = 4): array
    {
        $animalIdA = trim($animalIdA);
        $animalIdB = trim($animalIdB);

        if ($animalIdA === '' || $animalIdB === '') {
            throw new InvalidArgumentException('Se requieren ambos animal_id.');
        }
        if ($animalIdA === $animalIdB) {
            return ['compatible' => false, 'motivo' => 'Es el mismo animal.', 'a' => null, 'b' => null];
        }

        // 1) Cargar ambos animales (verifica existencia)
        $sql = "SELECT animal_id, madre_id, padre_id
                FROM {$this->table}
                WHERE animal_id IN (?, ?) AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new \mysqli_sql_exception("Error preparando verificación: " . $this->db->error);
        $stmt->bind_param('ss', $animalIdA, $animalIdB);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (count($rows) < 2) {
            throw new RuntimeException('Uno o ambos animales no existen o están eliminados.');
        }

        // Normalizar
        $a = $rows[0]['animal_id'] === $animalIdA ? $rows[0] : $rows[1];
        $b = $rows[0]['animal_id'] === $animalIdB ? $rows[0] : $rows[1];

        // 2) Reglas de primer grado
        $mismaMadre = ($a['madre_id'] && $a['madre_id'] === $b['madre_id']);
        $mismoPadre = ($a['padre_id'] && $a['padre_id'] === $b['padre_id']);
        if ($mismaMadre && $mismoPadre) {
            return ['compatible' => false, 'motivo' => 'Parentesco prohibido: hermanos completos (misma madre y padre).', 'a' => $a, 'b' => $b];
        }
        if ($mismaMadre) {
            return ['compatible' => false, 'motivo' => 'Parentesco prohibido: comparten la misma madre (medio-hermanos).', 'a' => $a, 'b' => $b];
        }
        if ($mismoPadre) {
            return ['compatible' => false, 'motivo' => 'Parentesco prohibido: comparten el mismo padre (medio-hermanos).', 'a' => $a, 'b' => $b];
        }

        // 3) Ancestros
        $ancA = $this->getAncestorsMap($animalIdA, $maxGenerations);
        $ancB = $this->getAncestorsMap($animalIdB, $maxGenerations);

        // ¿Alguno es ancestro del otro?
        if (isset($ancA[$animalIdB])) {
            $d = $ancA[$animalIdB];
            return ['compatible' => false, 'motivo' => 'Parentesco prohibido: B es ancestro de A (' . $this->labelAncestor($d) . ').', 'a' => $a, 'b' => $b];
        }
        if (isset($ancB[$animalIdA])) {
            $d = $ancB[$animalIdA];
            return ['compatible' => false, 'motivo' => 'Parentesco prohibido: A es ancestro de B (' . $this->labelAncestor($d) . ').', 'a' => $a, 'b' => $b];
        }

        // ¿Comparten ancestros?
        $comunes = array_intersect_key($ancA, $ancB);
        if (!empty($comunes)) {
            $mejor = null;
            $minSum = PHP_INT_MAX;
            foreach ($comunes as $ancId => $_) {
                $sum = $ancA[$ancId] + $ancB[$ancId];
                if ($sum < $minSum) {
                    $minSum = $sum;
                    $mejor = $ancId;
                }
            }
            $dA = $ancA[$mejor];
            $dB = $ancB[$mejor];
            $motivo = 'Parentesco prohibido: ' . $this->labelCommonAncestor($dA, $dB);
            return ['compatible' => false, 'motivo' => $motivo, 'a' => $a, 'b' => $b];
        }

        // Sin ancestros comunes hasta N generaciones
        return ['compatible' => true, 'motivo' => null, 'a' => $a, 'b' => $b];
    }

    private function getAncestorsMap(string $animalId, int $maxGenerations): array
    {
        $ancestors = [];
        $frontera = [$animalId];
        $dist = 0;

        while ($dist < $maxGenerations && !empty($frontera)) {
            $placeholders = implode(',', array_fill(0, count($frontera), '?'));
            $types = str_repeat('s', count($frontera));
            $sql = "SELECT animal_id, madre_id, padre_id
                    FROM {$this->table}
                    WHERE animal_id IN ($placeholders) AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new \mysqli_sql_exception("Error preparando ancestros: " . $this->db->error);
            $stmt->bind_param($types, ...$frontera);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $siguientes = [];
            $nivel = $dist + 1;

            foreach ($rows as $row) {
                foreach (['madre_id', 'padre_id'] as $col) {
                    $p = $row[$col] ?? null;
                    if ($p && !isset($ancestors[$p])) {
                        $ancestors[$p] = $nivel;
                        $siguientes[] = $p;
                    }
                }
            }

            $frontera = array_values(array_unique($siguientes));
            $dist++;
        }

        return $ancestors;
    }

    private function labelAncestor($d)
    {
        switch ($d) {
            case 1:
                return 'padre/madre';
            case 2:
                return 'abuelo/abuela';
            case 3:
                return 'bisabuelo/bisabuela';
            case 4:
                return 'tatarabuelo/tatarabuela';
            default:
                return $d . ' generaciones arriba';
        }
    }

    private function labelCommonAncestor(int $dA, int $dB): string
    {
        if (($dA === 1 && $dB >= 2) || ($dB === 1 && $dA >= 2)) {
            if ($dA + $dB === 3)
                return 'tío/tía con sobrino/a';
            return 'parientes en línea colateral (grado cercano)';
        }

        if ($dA === $dB) {
            if ($dA === 2)
                return 'primos hermanos (comparten abuelo/a)';
            if ($dA === 3)
                return 'primos segundos (comparten bisabuelo/a)';
            if ($dA === 4)
                return 'primos terceros (comparten tatarabuelo/a)';
            $k = $dA - 1;
            return "primos de grado {$k}";
        }

        $k = min($dA, $dB) - 1;
        $r = abs($dA - $dB);
        if ($k <= 0) {
            if (($dA === 2 && $dB === 3) || ($dA === 3 && $dB === 2)) {
                return 'tío abuelo/tía abuela con sobrino-nieto/a';
            }
            return 'parientes en línea colateral (distinto grado)';
        }
        $grado = ($k === 1) ? 'primos hermanos' : "primos de grado {$k}";
        $remov = ($r === 1) ? 'una vez removidos' : "{$r} veces removidos";
        return "{$grado} ({$remov})";
    }

 public function listar(
        int $limit = 10000,
        int $offset = 0,
        bool $incluirEliminados = false,
        ?string $q = null,
        ?string $sexo = null,
        ?string $especie = null,
        ?string $estado = null,
        ?string $etapa = null,
        ?string $categoria = null,
        ?string $nacDesde = null,
        ?string $nacHasta = null,
        ?string $fincaId = null,
        ?string $apriscoId = null,
        ?string $areaId = null,
        ?string $camadaId = null
    ): array {
        $w = [];
        $p = [];
        $t = '';

        $w[] = $incluirEliminados ? 'a.deleted_at IS NOT NULL OR a.deleted_at IS NULL' : 'a.deleted_at IS NULL';

        if ($q) {
            $w[] = 'a.identificador LIKE ?';
            $p[] = '%' . $q . '%';
            $t .= 's';
        }
        if ($sexo) {
            $w[] = 'a.sexo = ?';
            $p[] = $this->validarEnum($sexo, ['MACHO', 'HEMBRA'], 'sexo');
            $t .= 's';
        }
        if ($especie) {
            $w[] = 'a.especie = ?';
            $p[] = $this->validarEnum($especie, ['BOVINO', 'OVINO', 'CAPRINO', 'PORCINO', 'OTRO'], 'especie');
            $t .= 's';
        }
        if ($estado) {
            $w[] = 'a.estado = ?';
            $p[] = $this->validarEnum($estado, ['ACTIVO', 'INACTIVO', 'MUERTO', 'VENDIDO'], 'estado');
            $t .= 's';
        }
        if ($etapa) {
            $w[] = 'a.etapa_productiva = ?';
            $p[] = $this->validarEnum($etapa, ['TERNERO', 'LEVANTE', 'CEBA', 'REPRODUCTOR', 'LACTANTE', 'SECA', 'GESTANTE', 'OTRO'], 'etapa_productiva');
            $t .= 's';
        }
        if ($categoria) {
            $w[] = 'a.categoria = ?';
            $p[] = $this->validarEnum($categoria, ['CRIA', 'MADRE', 'PADRE', 'ENGORDE', 'REEMPLAZO', 'OTRO'], 'categoria');
            $t .= 's';
        }

        if ($nacDesde) {
            $this->validarFecha($nacDesde, 'nac_desde');
            $w[] = 'a.fecha_nacimiento >= ?';
            $p[] = $nacDesde;
            $t .= 's';
        }
        if ($nacHasta) {
            $this->validarFecha($nacHasta, 'nac_hasta');
            $w[] = 'a.fecha_nacimiento <= ?';
            $p[] = $nacHasta;
            $t .= 's';
        }
        if ($camadaId) {
            $w[] = 'a.camada_id = ?';
            $p[] = $camadaId;
            $t .= 's';
        }

        if ($fincaId || $apriscoId || $areaId) {
            if ($fincaId) {
                $w[] = 'ua.finca_id = ?';
                $p[] = $fincaId;
                $t .= 's';
            }
            if ($apriscoId) {
                $w[] = 'ua.aprisco_id = ?';
                $p[] = $apriscoId;
                $t .= 's';
            }
            if ($areaId) {
                $w[] = 'ua.area_id = ?';
                $p[] = $areaId;
                $t .= 's';
            }
        }

        $where = implode(' AND ', $w);

        $sql = "
        SELECT
            a.animal_id,
            a.identificador,
            a.sexo,
            a.especie,
            a.raza_id,
            rz.nombre AS raza_nombre,
            rz.codigo AS raza_codigo,
            a.color,
            a.fecha_nacimiento,
            a.estado,
            a.etapa_productiva,
            a.categoria,
            a.origen,
            a.madre_id,
            a.padre_id,
            a.fotografia_url,
            a.created_at, a.created_by, a.updated_at, a.updated_by,

            pw.fecha_peso AS ultima_fecha_peso,
            pw.peso_kg    AS ultimo_peso_kg,

            ua.finca_id,   f.nombre AS nombre_finca,
            ua.aprisco_id, ap.nombre AS nombre_aprisco,
            ua.area_id,    ar.nombre_personalizado AS nombre_area, ar.numeracion AS area_numeracion,
            ua.recinto_id,
            r.codigo_recinto AS codigo_recinto

        FROM {$this->table} a
        LEFT JOIN razas rz ON rz.raza_id = a.raza_id

        LEFT JOIN (
            SELECT u1.*
            FROM animal_ubicaciones u1
            JOIN (
                SELECT animal_id, MAX(fecha_desde) AS md
                FROM animal_ubicaciones
                WHERE deleted_at IS NULL
                GROUP BY animal_id
            ) u2 ON u2.animal_id = u1.animal_id AND u2.md = u1.fecha_desde
            WHERE u1.deleted_at IS NULL
        ) ua ON ua.animal_id = a.animal_id
        LEFT JOIN fincas   f  ON f.finca_id    = ua.finca_id
        LEFT JOIN apriscos ap ON ap.aprisco_id = ua.aprisco_id
        LEFT JOIN areas    ar ON ar.area_id    = ua.area_id
        LEFT JOIN recintos r  ON r.recinto_id  = ua.recinto_id

        LEFT JOIN (
            SELECT p1.*
            FROM animal_pesos p1
            JOIN (
                SELECT animal_id, MAX(fecha_peso) AS mf
                FROM animal_pesos
                WHERE deleted_at IS NULL
                GROUP BY animal_id
            ) p2 ON p2.animal_id = p1.animal_id AND p2.mf = p1.fecha_peso
            WHERE p1.deleted_at IS NULL
        ) pw ON pw.animal_id = a.animal_id

        WHERE {$where}
        ORDER BY a.created_at DESC, a.identificador ASC
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
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function obtenerPorId(string $animalId): ?array
    {
        $sql = "
        SELECT
            a.animal_id,
            a.identificador,
            a.sexo,
            a.especie,
            a.raza_id,
            rz.nombre AS raza_nombre,
            rz.codigo AS raza_codigo,
            a.color,
            a.fecha_nacimiento,
            a.estado,
            a.etapa_productiva,
            a.categoria,
            a.origen,
            a.madre_id,
            a.padre_id,
            a.fotografia_url,
            a.created_at, a.created_by, a.updated_at, a.updated_by, a.deleted_at, a.deleted_by,

            pw.fecha_peso AS ultima_fecha_peso,
            pw.peso_kg    AS ultimo_peso_kg,

            ua.finca_id,   f.nombre AS nombre_finca,
            ua.aprisco_id, ap.nombre AS nombre_aprisco,
            ua.area_id,    ar.nombre_personalizado AS nombre_area, ar.numeracion AS area_numeracion,
            ua.recinto_id,
            r.codigo_recinto AS codigo_recinto

        FROM {$this->table} a
        LEFT JOIN razas rz ON rz.raza_id = a.raza_id

        LEFT JOIN (
            SELECT u1.*
            FROM animal_ubicaciones u1
            JOIN (
                SELECT animal_id, MAX(fecha_desde) AS md
                FROM animal_ubicaciones
                WHERE deleted_at IS NULL
                GROUP BY animal_id
            ) u2 ON u2.animal_id = u1.animal_id AND u2.md = u1.fecha_desde
            WHERE u1.deleted_at IS NULL
        ) ua ON ua.animal_id = a.animal_id
        LEFT JOIN fincas   f  ON f.finca_id    = ua.finca_id
        LEFT JOIN apriscos ap ON ap.aprisco_id = ua.aprisco_id
        LEFT JOIN areas    ar ON ar.area_id    = ua.area_id
        LEFT JOIN recintos r  ON r.recinto_id  = ua.recinto_id

        LEFT JOIN (
            SELECT p1.*
            FROM animal_pesos p1
            JOIN (
                SELECT animal_id, MAX(fecha_peso) AS mf
                FROM animal_pesos
                WHERE deleted_at IS NULL
                GROUP BY animal_id
            ) p2 ON p2.animal_id = p1.animal_id AND p2.mf = p1.fecha_peso
            WHERE p1.deleted_at IS NULL
        ) pw ON pw.animal_id = a.animal_id
        WHERE a.animal_id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar consulta: " . $this->db->error);
        $stmt->bind_param('s', $animalId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getOptions(?string $q = null): array
    {
        $sql = "SELECT animal_id, identificador AS label
                FROM {$this->table}
                WHERE deleted_at IS NULL";
        $params = [];
        $types = '';
        if ($q) {
            $sql .= " AND identificador LIKE ?";
            $params[] = '%' . $q . '%';
            $types .= 's';
        }
        $sql .= " ORDER BY identificador ASC LIMIT 200";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar options: " . $this->db->error);
        if ($params)
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /* ============ Escrituras ============ */

    /**
     * Crear animal (sin manejar archivos aquí).
     * Luego el controlador, si recibe archivo, actualizará fotografia_url.
     */
    public function crear(array $in): string
    {
        foreach (['identificador', 'sexo', 'especie'] as $k) {
            if (!isset($in[$k]) || $in[$k] === '') {
                throw new InvalidArgumentException("Falta campo requerido: {$k}.");
            }
        }

        $identificador = trim((string) $in['identificador']);
        if (!$this->identificadorDisponible($identificador)) {
            throw new RuntimeException('El identificador ya está en uso.');
        }

        $sexo = $this->validarEnum((string) $in['sexo'], ['MACHO', 'HEMBRA'], 'sexo');
        $especie = $this->validarEnum((string) $in['especie'], ['BOVINO', 'OVINO', 'CAPRINO', 'PORCINO', 'OTRO'], 'especie');

        // raza_id (nullable) con validación de existencia si viene
        $razaId = isset($in['raza_id']) ? trim((string) $in['raza_id']) : null;
        if ($razaId === '' || strtolower($razaId) === 'null' || strtolower($razaId) === 'undefined') {
            $razaId = null;
        }
        if ($razaId && !$this->razaExistePorId($razaId)) {
            $razaId = null; // o lanzar excepción si quieres forzar coherencia estricta
        }

        $color = isset($in['color']) ? trim((string) $in['color']) : null;

        $fechaNacimiento = isset($in['fecha_nacimiento']) ? (string) $in['fecha_nacimiento'] : null;
        $this->validarFecha($fechaNacimiento, 'fecha_nacimiento');

        $estado = isset($in['estado'])
            ? $this->validarEnum((string) $in['estado'], ['ACTIVO', 'INACTIVO', 'MUERTO', 'VENDIDO'], 'estado')
            : 'ACTIVO';

        $etapa = isset($in['etapa_productiva'])
            ? $this->validarEnum((string) $in['etapa_productiva'], ['TERNERO', 'LEVANTE', 'CEBA', 'REPRODUCTOR', 'LACTANTE', 'SECA', 'GESTANTE', 'OTRO'], 'etapa_productiva')
            : null;

        $categ = isset($in['categoria'])
            ? $this->validarEnum((string) $in['categoria'], ['CRIA', 'MADRE', 'PADRE', 'ENGORDE', 'REEMPLAZO', 'OTRO'], 'categoria')
            : null;

        $origen = isset($in['origen'])
            ? $this->validarEnum((string) $in['origen'], ['NACIMIENTO', 'COMPRA', 'TRASLADO', 'OTRO'], 'origen')
            : 'OTRO';

        // Normalización estricta a NULL
        $madreId = isset($in['madre_id']) ? trim((string) $in['madre_id']) : null;
        if ($madreId === '' || strtolower($madreId) === 'null' || strtolower($madreId) === 'undefined')
            $madreId = null;

        $padreId = isset($in['padre_id']) ? trim((string) $in['padre_id']) : null;
        if ($padreId === '' || strtolower($padreId) === 'null' || strtolower($padreId) === 'undefined')
            $padreId = null;

        if ($madreId && !$this->animalExistePorId($madreId))
            $madreId = null;
        if ($padreId && !$this->animalExistePorId($padreId))
            $padreId = null;

        // ============ INICIO DE MODIFICACIÓN ============
        $camadaId = isset($in['camada_id']) ? trim((string) $in['camada_id']) : null;
        if ($camadaId === '' || strtolower($camadaId) === 'null' || strtolower($camadaId) === 'undefined') {
            $camadaId = null;
        }

        if ($camadaId && !$this->camadaExistePorId($camadaId)) {
            // Opcional: podrías lanzar un error si la camada_id es inválida o no está activa
            // throw new RuntimeException('La camada_id proporcionada no es válida o está cerrada.');
            $camadaId = null; // O simplemente ignorarla
        }

        $madreIdDeCamada = null;

        // Si viene una camada, el origen DEBE ser 'NACIMIENTO'
        if ($camadaId) {
            if (!$this->camadaExistePorId($camadaId)) {
                throw new RuntimeException('La camada_id proporcionada no es válida o está cerrada.');
            }

            // Si viene camada, forzamos origen y buscamos la madre
            $origen = 'NACIMIENTO';

            // Buscamos la madre real de la camada para heredar su ubicación
            $stmtCamada = $this->db->prepare("SELECT madre_id FROM camadas WHERE camada_id = ? AND deleted_at IS NULL");
            if ($stmtCamada) {
                $stmtCamada->bind_param('s', $camadaId);
                $stmtCamada->execute();
                $camadaRes = $stmtCamada->get_result();
                $camadaRow = $camadaRes->fetch_assoc();
                $stmtCamada->close();
                if ($camadaRow && $camadaRow['madre_id']) {
                    $madreIdDeCamada = $camadaRow['madre_id'];
                    // Sobreescribimos el madre_id (si venía nulo) con el de la camada
                    if ($madreId === null) {
                        $madreId = $madreIdDeCamada;
                    }
                }
            }
        }
        // Validación de consanguinidad si vienen ambos
        if ($madreId !== null && $padreId !== null) {
            $permitirConsanguinidad = (int) $this->configModel->obtenerValor('permitir_registro_consanguineo', '0');
            if ($permitirConsanguinidad === 0) {
                try {
                    $check = $this->puedenCruzar($madreId, $padreId);
                    if (($check['compatible'] ?? null) === false) {
                        throw new RuntimeException(
                            'Registro denegado: Los padres seleccionados (Madre y Padre) tienen un parentesco sanguíneo no permitido. ' .
                            'Motivo: ' . ($check['motivo'] ?? 'Parentesco detectado.')
                        );
                    }
                } catch (\Throwable $e) {
                    throw new RuntimeException('Error al validar parentesco de los padres: ' . $e->getMessage());
                }
            }
        }

        $fotoUrl = isset($in['fotografia_url']) ? (string) $in['fotografia_url'] : null;

        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();
            $uuid = UuidHelper::generateUUIDv4();
            $actorId = $_SESSION['user_id'] ?? $uuid;

            $sql = "INSERT INTO {$this->table}
                (animal_id, identificador, sexo, especie, raza_id, color, fecha_nacimiento,
                 estado, etapa_productiva, categoria, origen, madre_id, padre_id, camada_id,
                 fotografia_url,
                 created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new mysqli_sql_exception("Error al preparar inserción: " . $this->db->error);
            }

            $stmt->bind_param(
                'sssssssssssssssssss',
                $uuid,            // 1
                $identificador,   // 2
                $sexo,            // 3
                $especie,         // 4
                $razaId,          // 5 (nullable)
                $color,           // 6 (nullable)
                $fechaNacimiento, // 7 (nullable)
                $estado,          // 8
                $etapa,           // 9 (nullable)
                $categ,           //10 (nullable)
                $origen,          //11
                $madreId,         //12 (nullable)
                $padreId,         //13 (nullable)
                $camadaId,        //14 (nullable)
                $fotoUrl,         //15 (nullable)
                $now,             //16 created_at
                $actorId,         //17 created_by
                $now,             //18 updated_at
                $actorId          //19 updated_by
            );

            if (!$stmt->execute()) {
                $err = strtolower($stmt->error ?? '');
                $stmt->close();
                $this->db->rollback();

                if (strpos($err, 'duplicate') !== false) {
                    throw new RuntimeException('Identificador duplicado.');
                }
                if (strpos($err, 'foreign key') !== false) {
                    throw new RuntimeException('FK inválida (madre/padre/raza).');
                }
                throw new mysqli_sql_exception("Error al ejecutar inserción: " . $err);
            }

            if ($camadaId && $madreIdDeCamada) {

                // 2a. Obtener ubicación actual de la madre
                $madreUbicacion = $this->ubicacionModel->getActual($madreIdDeCamada);

                if ($madreUbicacion) {
                    // 2b. Crear la ubicación para el lechón ($uuid)
                    // Usamos 'INGRESO' como motivo, ya que 'NACIMIENTO' no es
                    // un ENUM válido en tu AnimalUbicacionModel.
                    $this->ubicacionModel->crear([
                        'animal_id' => $uuid, // El ID del lechón recién creado
                        'fecha_desde' => $fechaNacimiento, // La ubicación inicia en su fecha de nac.
                        'motivo' => 'INGRESO', // O 'NACIMIENTO' si lo añades al ENUM
                        'finca_id' => $madreUbicacion['finca_id'],
                        'aprisco_id' => $madreUbicacion['aprisco_id'],
                        'area_id' => $madreUbicacion['area_id'],
                        'recinto_id' => $madreUbicacion['recinto_id'],
                        'observaciones' => 'Ubicación asignada por nacimiento (heredada de la madre).'
                    ]);
                }
                // Si la madre no tiene ubicación activa, el lechón
                // nacerá "sin ubicación", lo cual es correcto.
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
     * Obtiene el árbol genealógico (formato por niveles).
     * @param string      $animalId
     * @param string|null $direccion 'ARRIBA'|'ASC'|'ABAJO'|'DESC'|null
     * @param int         $maxGeneraciones
     */
    public function getArbolGenealogico(string $animalId, ?string $direccion = null, int $maxGeneraciones = 6): array
    {
        $animalId = trim($animalId);
        if ($animalId === '') {
            throw new InvalidArgumentException('animal_id requerido.');
        }
        $dir = $direccion ? strtoupper(trim($direccion)) : null;
        if ($dir !== null && !in_array($dir, ['ARRIBA', 'ASC', 'ABAJO', 'DESC'], true)) {
            throw new InvalidArgumentException("Parámetro 'direccion' inválido. Use ARRIBA|ASC|ABAJO|DESC o null.");
        }
        if ($maxGeneraciones < 1)
            $maxGeneraciones = 1;

        $cache = [];

        $fetchAnimal = function (string $id) use (&$cache) {
            if (isset($cache[$id]))
                return $cache[$id];

            $sql = "SELECT a.animal_id, a.identificador, a.sexo, a.especie,
                           a.raza_id, rz.nombre AS raza_nombre, rz.codigo AS raza_codigo,
                           a.color, a.fecha_nacimiento,
                           a.madre_id, a.padre_id, a.fotografia_url
                    FROM {$this->table} a
                    LEFT JOIN razas rz ON rz.raza_id = a.raza_id
                    WHERE a.animal_id = ? AND a.deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new \mysqli_sql_exception("Error preparando fetchAnimal: " . $this->db->error);
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc() ?: null;
            $stmt->close();

            if ($row) {
                $row['fecha_nacimiento'] = $row['fecha_nacimiento'] ?: null;
                $cache[$id] = $row;
            }
            return $row;
        };

        $labelAsc = function (int $gen, ?string $sexo): string {
            $sx = strtoupper((string) $sexo);
            if ($gen === 1)
                return ($sx === 'HEMBRA') ? 'MADRE' : 'PADRE';
            if ($gen === 2)
                return ($sx === 'HEMBRA') ? 'ABUELA' : 'ABUELO';
            if ($gen === 3)
                return ($sx === 'HEMBRA') ? 'BISABUELA' : 'BISABUELO';
            if ($gen === 4)
                return ($sx === 'HEMBRA') ? 'TATARABUELA' : 'TATARABUELO';
            return strtoupper($gen . ' GENERACIONES ARRIBA');
        };

        $labelDesc = function (int $gen, ?string $sexo): string {
            $sx = strtoupper((string) $sexo);
            if ($gen === 1)
                return ($sx === 'HEMBRA') ? 'HIJA' : 'HIJO';
            if ($gen === 2)
                return ($sx === 'HEMBRA') ? 'NIETA' : 'NIETO';
            if ($gen === 3)
                return ($sx === 'HEMBRA') ? 'BISNIETA' : 'BISNIETO';
            if ($gen === 4)
                return ($sx === 'HEMBRA') ? 'TATARANIETA' : 'TATARANIETO';
            return strtoupper($gen . ' GENERACIONES ABAJO');
        };

        $root = $fetchAnimal($animalId);
        if (!$root)
            throw new RuntimeException('El animal no existe o está eliminado.');

        $asc = null;
        if ($dir === null || in_array($dir, ['ARRIBA', 'ASC'], true)) {
            $niveles = [];
            $actual = [];
            if (!empty($root['madre_id']))
                $actual[] = $root['madre_id'];
            if (!empty($root['padre_id']))
                $actual[] = $root['padre_id'];

            $nivel = 1;
            while (!empty($actual) && $nivel <= $maxGeneraciones) {
                $siguiente = [];
                $listaNivel = [];

                foreach ($actual as $aid) {
                    $a = $fetchAnimal($aid);
                    if (!$a)
                        continue;

                    $listaNivel[] = [
                        'animal_id' => $a['animal_id'],
                        'identificador' => $a['identificador'],
                        'parentesco' => $labelAsc($nivel, $a['sexo']),
                        'sexo' => $a['sexo'],
                        'especie' => $a['especie'],
                        'raza_id' => $a['raza_id'],
                        'raza_nombre' => $a['raza_nombre'] ?? null,
                        'raza_codigo' => $a['raza_codigo'] ?? null,
                        'color' => $a['color'],
                        'fecha_nacimiento' => $a['fecha_nacimiento'],
                        'madre_id' => $a['madre_id'] ?: null,
                        'padre_id' => $a['padre_id'] ?: null,
                        'fotografia_url' => $a['fotografia_url'] ?: null,
                    ];

                    if ($nivel < $maxGeneraciones) {
                        if (!empty($a['madre_id']))
                            $siguiente[] = $a['madre_id'];
                        if (!empty($a['padre_id']))
                            $siguiente[] = $a['padre_id'];
                    }
                }

                if (!empty($listaNivel)) {
                    $niveles[(string) $nivel] = $listaNivel;
                }

                $actual = $siguiente;
                $nivel++;
            }

            $asc = $niveles;
        }

        $desc = null;
        if ($dir === null || in_array($dir, ['ABAJO', 'DESC'], true)) {
            $nivelesD = [];
            $actual = [];

            $sqlH = "SELECT animal_id FROM {$this->table}
                     WHERE deleted_at IS NULL AND (madre_id = ? OR padre_id = ?)
                     ORDER BY fecha_nacimiento ASC, identificador ASC";
            $stmt = $this->db->prepare($sqlH);
            if (!$stmt)
                throw new \mysqli_sql_exception("Error preparando hijos raíz: " . $this->db->error);
            $stmt->bind_param('ss', $animalId, $animalId);
            $stmt->execute();
            $rs = $stmt->get_result();
            while ($r = $rs->fetch_assoc())
                $actual[] = $r['animal_id'];
            $stmt->close();

            $nivel = 1;
            while (!empty($actual) && $nivel <= $maxGeneraciones) {
                $siguiente = [];
                $listaNivel = [];

                foreach ($actual as $cid) {
                    $c = $fetchAnimal($cid);
                    if (!$c)
                        continue;

                    $listaNivel[] = [
                        'animal_id' => $c['animal_id'],
                        'identificador' => $c['identificador'],
                        'parentesco' => $labelDesc($nivel, $c['sexo']),
                        'sexo' => $c['sexo'],
                        'especie' => $c['especie'],
                        'raza_id' => $c['raza_id'],
                        'raza_nombre' => $c['raza_nombre'] ?? null,
                        'raza_codigo' => $c['raza_codigo'] ?? null,
                        'color' => $c['color'],
                        'fecha_nacimiento' => $c['fecha_nacimiento'],
                        'madre_id' => $c['madre_id'] ?: null,
                        'padre_id' => $c['padre_id'] ?: null,
                        'fotografia_url' => $c['fotografia_url'] ?: null,
                    ];

                    if ($nivel < $maxGeneraciones) {
                        $stmt = $this->db->prepare($sqlH);
                        if (!$stmt)
                            throw new \mysqli_sql_exception("Error preparando descendientes: " . $this->db->error);
                        $stmt->bind_param('ss', $cid, $cid);
                        $stmt->execute();
                        $rs2 = $stmt->get_result();
                        while ($r2 = $rs2->fetch_assoc())
                            $siguiente[] = $r2['animal_id'];
                        $stmt->close();
                    }
                }

                if (!empty($listaNivel)) {
                    $nivelesD[(string) $nivel] = $listaNivel;
                }

                $actual = $siguiente;
                $nivel++;
            }

            $desc = $nivelesD;
        }

        return [
            'animal' => [
                'animal_id' => $root['animal_id'],
                'identificador' => $root['identificador'],
                'sexo' => $root['sexo'],
                'especie' => $root['especie'],
                'raza_id' => $root['raza_id'],
                'raza_nombre' => $root['raza_nombre'] ?? null,
                'raza_codigo' => $root['raza_codigo'] ?? null,
                'color' => $root['color'],
                'fecha_nacimiento' => $root['fecha_nacimiento'],
                'madre_id' => $root['madre_id'] ?: null,
                'padre_id' => $root['padre_id'] ?: null,
                'fotografia_url' => $root['fotografia_url'] ?: null,
            ],
            'ascendencia' => $asc,
            'descendencia' => $desc,
        ];
    }

    /**
     * Árbol genealógico en formato jerárquico para D3 (ascendencia).
     */
    public function getArbolGenealogicoD3Asc(string $animalId, int $maxGeneraciones = 6): array
    {
        $animalId = trim($animalId);
        if ($animalId === '') {
            throw new InvalidArgumentException('animal_id requerido.');
        }
        if ($maxGeneraciones < 1)
            $maxGeneraciones = 1;

        $ALLOW_DUPLICATE_OCCURRENCES = true;

        $cache = [];

        $fetchAnimal = function (string $id) use (&$cache) {
            if (isset($cache[$id]))
                return $cache[$id];

            $sql = "SELECT a.animal_id, a.identificador, a.sexo, a.especie,
                           a.raza_id, rz.nombre AS raza_nombre, rz.codigo AS raza_codigo,
                           a.color, a.fecha_nacimiento,
                           a.madre_id, a.padre_id, a.fotografia_url, a.origen, a.categoria, a.estado
                    FROM {$this->table} a
                    LEFT JOIN razas rz ON rz.raza_id = a.raza_id
                    WHERE a.animal_id = ? AND a.deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new \mysqli_sql_exception("Error preparando fetchAnimal: " . $this->db->error);
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc() ?: null;
            $stmt->close();

            if ($row) {
                $row['fecha_nacimiento'] = $row['fecha_nacimiento'] ?: null;
                $cache[$id] = $row;
            }
            return $row;
        };

        $labelAsc = function (int $gen, ?string $sexo): string {
            $sx = strtoupper((string) $sexo);
            $mujer = ($sx === 'HEMBRA');
            if ($gen === 1)
                return $mujer ? 'MADRE' : 'PADRE';
            if ($gen === 2)
                return $mujer ? 'ABUELA' : 'ABUELO';
            if ($gen === 3)
                return $mujer ? 'BISABUELA' : 'BISABUELO';
            $base = $mujer ? 'TATARABUELA' : 'TATARABUELO';
            $n = $gen - 3; // 4->1, 5->2...
            return $n === 1 ? $base : $base . ' N' . $n;
        };

        $makeInfo = function (array $a, string $rol): string {
            $partes = [$rol];
            if (!empty($a['especie']))
                $partes[] = $a['especie'];
            if (!empty($a['raza_nombre']))
                $partes[] = $a['raza_nombre'];
            if (!empty($a['color']))
                $partes[] = $a['color'];
            if (!empty($a['fecha_nacimiento']))
                $partes[] = 'Nac. ' . $a['fecha_nacimiento'];
            if (!empty($a['origen']))
                $partes[] = 'Origen: ' . $a['origen'];
            return implode(' · ', $partes);
        };

        $stackIds = [];

        $buildAscBranch = function (?string $id, int $gen) use (&$buildAscBranch, &$stackIds, $fetchAnimal, $labelAsc, $makeInfo, $maxGeneraciones, $ALLOW_DUPLICATE_OCCURRENCES) {
            if (!$id)
                return null;
            if (in_array($id, $stackIds, true))
                return null;

            $a = $fetchAnimal($id);
            if (!$a)
                return null;

            $rolReal = $labelAsc($gen, $a['sexo'] ?? null);

            $node = [
                'name' => ($a['identificador'] ?: $rolReal),
                'info' => $makeInfo($a, $rolReal),
                'rel' => $rolReal,
                'meta' => array_merge(['_known' => true, '_generation' => $gen, '_relationship' => $rolReal], $a),
                'children' => []
            ];

            if ($gen < $maxGeneraciones) {
                $stackIds[] = $id;
                $c1 = $buildAscBranch($a['padre_id'] ?? null, $gen + 1);
                $c2 = $buildAscBranch($a['madre_id'] ?? null, $gen + 1);
                if ($c1 !== null)
                    $node['children'][] = $c1;
                if ($c2 !== null)
                    $node['children'][] = $c2;
                array_pop($stackIds);
            }

            return $node;
        };

        $root = $fetchAnimal($animalId);
        if (!$root)
            throw new RuntimeException('El animal no existe o está eliminado.');

        $rootNode = [
            'name' => $root['identificador'] ?: 'ANIMAL',
            'info' => $makeInfo($root, 'ANIMAL'),
            'rel' => 'ANIMAL',
            'meta' => array_merge(['_known' => true, '_generation' => 0, '_relationship' => 'ANIMAL'], $root),
            'children' => []
        ];

        $p = $buildAscBranch($root['padre_id'] ?? null, 1);
        $m = $buildAscBranch($root['madre_id'] ?? null, 1);
        if ($p !== null)
            $rootNode['children'][] = $p;
        if ($m !== null)
            $rootNode['children'][] = $m;

        return $rootNode;
    }

    /**
     * Devuelve TODOS los árboles genealógicos (bosque), desde los más viejos a los más recientes (descendencia).
     */
    public function getTodosLosArbolesGenealogicos(int $maxGeneraciones = 6): array
    {
        if ($maxGeneraciones < 1)
            $maxGeneraciones = 1;

        $sqlRoots = "SELECT animal_id
                     FROM {$this->table}
                     WHERE deleted_at IS NULL
                       AND madre_id IS NULL
                       AND padre_id IS NULL
                     ORDER BY (fecha_nacimiento IS NULL), fecha_nacimiento ASC, identificador ASC";
        $rs = $this->db->query($sqlRoots);
        if (!$rs)
            throw new \mysqli_sql_exception("Error listando raíces: " . $this->db->error);

        $rootIds = [];
        while ($r = $rs->fetch_assoc())
            $rootIds[] = $r['animal_id'];

        $cache = [];

        $fetchAnimal = function (string $id) use (&$cache) {
            if (isset($cache[$id]))
                return $cache[$id];
            $sql = "SELECT a.animal_id, a.identificador, a.sexo, a.especie,
                           a.raza_id, rz.nombre AS raza_nombre, rz.codigo AS raza_codigo,
                           a.color, a.fecha_nacimiento,
                           a.madre_id, a.padre_id, a.fotografia_url
                    FROM {$this->table} a
                    LEFT JOIN razas rz ON rz.raza_id = a.raza_id
                    WHERE a.animal_id = ? AND a.deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new \mysqli_sql_exception("Error preparando fetchAnimal: " . $this->db->error);
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc() ?: null;
            $stmt->close();
            if ($row) {
                $row['fecha_nacimiento'] = $row['fecha_nacimiento'] ?: null;
                $cache[$id] = $row;
            }
            return $row;
        };

        $buildDesc = function (string $id, int $nivel, int $max) use (&$buildDesc, $fetchAnimal) {
            $self = $fetchAnimal($id);
            if (!$self)
                return null;

            $sql = "SELECT animal_id
                    FROM {$this->table}
                    WHERE deleted_at IS NULL AND (madre_id = ? OR padre_id = ?)
                    ORDER BY fecha_nacimiento ASC, identificador ASC";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new \mysqli_sql_exception("Error preparando hijos: " . $this->db->error);
            $stmt->bind_param('ss', $id, $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $childIds = [];
            while ($r = $res->fetch_assoc())
                $childIds[] = $r['animal_id'];
            $stmt->close();

            $nodosHijos = [];
            if ($nivel < $max) {
                foreach ($childIds as $cid) {
                    $sub = $buildDesc($cid, $nivel + 1, $max);
                    if ($sub)
                        $nodosHijos[] = $sub;
                }
            }

            return [
                'animal' => $self,
                'hijos' => $nodosHijos,
                'nivel' => $nivel,
            ];
        };

        $bosque = [];
        foreach ($rootIds as $rid) {
            $arbol = $buildDesc($rid, 0, $maxGeneraciones);
            if ($arbol) {
                $bosque[] = [
                    'animal' => $arbol['animal'],
                    'descendencia' => $arbol['hijos'],
                ];
            }
        }

        return $bosque;
    }

    /**
     * Actualiza campos explícitos (incluye fotografia_url).
     */
    public function actualizar(string $animalId, array $in): bool
    {
        if (!$this->animalExistePorId($animalId)) {
            throw new RuntimeException('Animal no existe o está eliminado.');
        }

        $toNulls = ['', 'null', 'undefined', '0', '-',];
        $normalizeId = function ($v) use ($toNulls) {
            if (!isset($v))
                return null;
            $v = trim((string) $v);
            return (in_array(strtolower($v), $toNulls, true)) ? null : $v;
        };

        $campos = [];
        $params = [];
        $types = '';

        if (isset($in['identificador'])) {
            $ident = trim((string) $in['identificador']);
            if (!$this->identificadorDisponible($ident, $animalId)) {
                throw new RuntimeException('El identificador ya está en uso.');
            }
            $campos[] = 'identificador = ?';
            $params[] = $ident;
            $types .= 's';
        }

        if (isset($in['sexo'])) {
            $campos[] = 'sexo = ?';
            $params[] = $this->validarEnum((string) $in['sexo'], ['MACHO', 'HEMBRA'], 'sexo');
            $types .= 's';
        }

        if (isset($in['especie'])) {
            $campos[] = 'especie = ?';
            $params[] = $this->validarEnum((string) $in['especie'], ['BOVINO', 'OVINO', 'CAPRINO', 'PORCINO', 'OTRO'], 'especie');
            $types .= 's';
        }

        // raza_id (nuevo bloque)
        if (array_key_exists('raza_id', $in)) {
            $rid = $in['raza_id'];
            $rid = isset($rid) ? trim((string) $rid) : null;
            if ($rid === '' || in_array(strtolower($rid), ['null', 'undefined', '-', '0'], true)) {
                $rid = null;
            } else {
                if (!$this->razaExistePorId($rid)) {
                    throw new RuntimeException('raza_id no existe en la tabla razas.');
                }
            }
            $campos[] = 'raza_id = ?';
            $params[] = $rid;
            $types .= 's';
        }

        if (array_key_exists('color', $in)) {
            $campos[] = 'color = ?';
            $params[] = $in['color'] !== null ? trim((string) $in['color']) : null;
            $types .= 's';
        }

        if (array_key_exists('fecha_nacimiento', $in)) {
            $fn = $in['fecha_nacimiento'];
            $fn = isset($fn) ? trim((string) $fn) : null;
            if ($fn === '' || in_array(strtolower($fn), ['null', 'undefined', '-', '0'], true)) {
                $fn = null;
            } else {
                $this->validarFecha($fn, 'fecha_nacimiento');
            }
            $campos[] = 'fecha_nacimiento = ?';
            $params[] = $fn;
            $types .= 's';
        }

        if (isset($in['estado'])) {
            $campos[] = 'estado = ?';
            $params[] = $this->validarEnum((string) $in['estado'], ['ACTIVO', 'INACTIVO', 'MUERTO', 'VENDIDO'], 'estado');
            $types .= 's';
        }

        if (isset($in['etapa_productiva'])) {
            $campos[] = 'etapa_productiva = ?';
            $params[] = $this->validarEnum((string) $in['etapa_productiva'], ['TERNERO', 'LEVANTE', 'CEBA', 'REPRODUCTOR', 'LACTANTE', 'SECA', 'GESTANTE', 'OTRO'], 'etapa_productiva');
            $types .= 's';
        }

        if (isset($in['categoria'])) {
            $campos[] = 'categoria = ?';
            $params[] = $this->validarEnum((string) $in['categoria'], ['CRIA', 'MADRE', 'PADRE', 'ENGORDE', 'REEMPLAZO', 'OTRO'], 'categoria');
            $types .= 's';
        }

        if (isset($in['origen'])) {
            $campos[] = 'origen = ?';
            $params[] = $this->validarEnum((string) $in['origen'], ['NACIMIENTO', 'COMPRA', 'TRASLADO', 'OTRO'], 'origen');
            $types .= 's';
        }

        if (array_key_exists('camada_id', $in)) {
            $cid = $normalizeId($in['camada_id']);
            if ($cid && !$this->camadaExistePorId($cid)) {
                // Opcional: lanzar error
                $cid = null;
            }
            $campos[] = 'camada_id = ?';
            $params[] = $cid;
            $types .= 's';
        }

        // madre/padre
        $madreIdEntrada = array_key_exists('madre_id', $in) ? $normalizeId($in['madre_id']) : null;
        $padreIdEntrada = array_key_exists('padre_id', $in) ? $normalizeId($in['padre_id']) : null;

        if (array_key_exists('madre_id', $in)) {
            $madreId = $madreIdEntrada;
            if ($madreId === $animalId)
                throw new RuntimeException('La madre no puede ser el mismo animal.');
            if ($madreId && !$this->animalExistePorId($madreId))
                $madreId = null;
            $campos[] = 'madre_id = ?';
            $params[] = $madreId;
            $types .= 's';
        }

        if (array_key_exists('padre_id', $in)) {
            $padreId = $padreIdEntrada;
            if ($padreId === $animalId)
                throw new RuntimeException('El padre no puede ser el mismo animal.');
            if ($padreId && !$this->animalExistePorId($padreId))
                $padreId = null;
            $campos[] = 'padre_id = ?';
            $params[] = $padreId;
            $types .= 's';
        }

        // Consanguinidad (si ambos definidos)
        $madreIdCheck = $madreIdEntrada ?? null;
        $padreIdCheck = $padreIdEntrada ?? null;
        if ($madreIdCheck && !$this->animalExistePorId($madreIdCheck))
            $madreIdCheck = null;
        if ($padreIdCheck && !$this->animalExistePorId($padreIdCheck))
            $padreIdCheck = null;

        if ($madreIdCheck !== null && $padreIdCheck !== null) {
            $permitirConsanguinidad = (int) $this->configModel->obtenerValor('permitir_registro_consanguineo', '0');
            if ($permitirConsanguinidad === 0) {
                try {
                    $check = $this->puedenCruzar($madreIdCheck, $padreIdCheck);
                    if (($check['compatible'] ?? null) === false) {
                        throw new RuntimeException(
                            'Registro denegado: Los padres seleccionados (Madre y Padre) tienen un parentesco sanguíneo no permitido. ' .
                            'Motivo: ' . ($check['motivo'] ?? 'Parentesco detectado.')
                        );
                    }
                } catch (\Throwable $e) {
                    throw new RuntimeException('Error al validar parentesco de los padres: ' . $e->getMessage());
                }
            }
        }

        // fotografia_url
        if (array_key_exists('fotografia_url', $in)) {
            $foto = $in['fotografia_url'];
            $foto = isset($foto) ? trim((string) $foto) : null;
            if ($foto === '' || in_array(strtolower($foto), ['null', 'undefined', '-', '0'], true)) {
                $foto = null;
            }
            $campos[] = 'fotografia_url = ?';
            $params[] = $foto;
            $types .= 's';
        }

        if (empty($campos)) {
            throw new InvalidArgumentException('No hay campos para actualizar.');
        }

        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $animalId;
        $campos[] = 'updated_at = ?';
        $params[] = $now;
        $types .= 's';
        $campos[] = 'updated_by = ?';
        $params[] = $actorId;
        $types .= 's';

        $sql = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE animal_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar actualización: " . $this->db->error);
        $types .= 's';
        $params[] = $animalId;

        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $err = strtolower($stmt->error ?? '');
        $stmt->close();

        if (!$ok) {
            if (strpos($err, 'duplicate') !== false) {
                throw new RuntimeException('Identificador duplicado.');
            }
            if (strpos($err, 'foreign key') !== false) {
                throw new RuntimeException('FK inválida (madre/padre/raza).');
            }
            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }
        return true;
    }

    /**
     * Soft delete
     */
    public function eliminar(string $animalId): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $animalId;

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE animal_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar eliminación: " . $this->db->error);
        $stmt->bind_param('sss', $now, $actorId, $animalId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
