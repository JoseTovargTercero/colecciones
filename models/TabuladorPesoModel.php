<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class TabuladorPesoModel
{
    private $db;
    private $table = 'tabuladores_peso';

    public function __construct()
    {
        $this->db = Database::getInstance();
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
    private function razaExiste(string $razaId): bool
    {
        $sql = "SELECT 1
                  FROM razas
                 WHERE raza_id = ?
                   AND (deleted_at IS NULL OR deleted_at IS NULL)"; // por si no usas borrado en razas
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar verificación de raza: " . $this->db->error);
        $stmt->bind_param('s', $razaId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }

    private function validarEnteroNoNegativo($v, string $nombre): int
    {
        if (!is_numeric($v) || (int) $v != $v || (int) $v < 0) {
            throw new InvalidArgumentException("$nombre debe ser entero >= 0.");
        }
        return (int) $v;
    }

    private function validarDecimalPositivo($v, string $nombre, float $max = 9999.99): float
    {
        if (!is_numeric($v)) {
            throw new InvalidArgumentException("$nombre debe ser numérico.");
        }
        $f = (float) $v;
        if ($f <= 0 || $f > $max) {
            throw new InvalidArgumentException("$nombre fuera de rango (0 - $max].");
        }
        return $f;
    }

    private function validarRangoEdades(int $min, int $max): void
    {
        if ($min > $max) {
            throw new InvalidArgumentException("edad_min_dias no puede ser mayor que edad_max_dias.");
        }
    }

    /** Verifica si existe solapamiento de [min,max] para la raza dada. */
    private function existeSolapamiento(string $razaId, int $min, int $max, ?string $excluirId = null): bool
    {
        $sql = "SELECT 1
                  FROM {$this->table}
                 WHERE raza_id = ?
                   AND NOT (edad_max_dias < ? OR edad_min_dias > ?)";
        $types = 'sii';
        $params = [$razaId, $min, $max];

        if ($excluirId) {
            $sql .= " AND tab_peso_id <> ?";
            $types .= 's';
            $params[] = $excluirId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar verificación de solapamiento: " . $this->db->error);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /* ============ Lecturas ============ */

    /**
     * Lista tabuladores.
     * Filtros: raza_id?, edad_dias? (devuelve el rango que cubra esa edad), limit/offset.
     */
    public function listar(
        int $limit = 10000,
        int $offset = 0,
        ?string $razaId = null,
        ?int $edadDias = null
    ): array {
        $where = [];
        $types = '';
        $params = [];

        if ($razaId) {
            $where[] = 't.raza_id = ?';
            $types .= 's';
            $params[] = $razaId;
        }
        if ($edadDias !== null) {
            $where[] = ' ? BETWEEN t.edad_min_dias AND t.edad_max_dias';
            $types .= 'i';
            $params[] = (int) $edadDias;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT
                    t.tab_peso_id,
                    t.raza_id,
                    r.nombre AS raza_nombre,
                    t.edad_min_dias,
                    t.edad_max_dias,
                    t.peso_ideal,
                    t.margen_min,
                    t.margen_max,
                    t.created_at,
                    t.created_by
                FROM {$this->table} t
                LEFT JOIN razas r ON r.raza_id = t.raza_id
                $whereSql
                ORDER BY t.raza_id, t.edad_min_dias ASC, t.edad_max_dias ASC
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
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function obtenerPorId(string $id): ?array
    {
        $sql = "SELECT
                    t.tab_peso_id,
                    t.raza_id,
                    r.nombre AS raza_nombre,
                    t.edad_min_dias,
                    t.edad_max_dias,
                    t.peso_ideal,
                    t.margen_min,
                    t.margen_max,
                    t.created_at,
                    t.created_by
                FROM {$this->table} t
                LEFT JOIN razas r ON r.raza_id = t.raza_id
               WHERE t.tab_peso_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar consulta: " . $this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /* ============ Escrituras ============ */

    /**
     * Crear tabulador.
     * Requeridos: raza_id, edad_min_dias, edad_max_dias, peso_ideal, margen_min, margen_max.
     * Valida que no haya solape por raza.
     */
    public function crear(array $in): string
    {
        foreach (['raza_id', 'edad_min_dias', 'edad_max_dias', 'peso_ideal', 'margen_min', 'margen_max'] as $k) {
            if (!isset($in[$k]) || $in[$k] === '') {
                throw new InvalidArgumentException("Falta campo requerido: {$k}.");
            }
        }

        $razaId = (string) trim((string) $in['raza_id']);
        if (!$this->razaExiste($razaId)) {
            throw new RuntimeException('La raza especificada no existe o está inactiva.');
        }

        $min = $this->validarEnteroNoNegativo($in['edad_min_dias'], 'edad_min_dias');
        $max = $this->validarEnteroNoNegativo($in['edad_max_dias'], 'edad_max_dias');
        $this->validarRangoEdades($min, $max);

        $pesoIdeal = $this->validarDecimalPositivo($in['peso_ideal'], 'peso_ideal', 999.99);
        $mMin = $this->validarDecimalPositivo($in['margen_min'], 'margen_min', 200.00);
        $mMax = $this->validarDecimalPositivo($in['margen_max'], 'margen_max', 200.00);

        if ($this->existeSolapamiento($razaId, $min, $max, null)) {
            throw new RuntimeException('Solapamiento de rangos de edad para la raza especificada.');
        }

        [$now, $env] = $this->nowWithAudit();
        $uuid = UuidHelper::generateUUIDv4();
        $actorId = $_SESSION['user_id'] ?? $uuid;

        $sql = "INSERT INTO {$this->table}
                (tab_peso_id, raza_id, edad_min_dias, edad_max_dias, peso_ideal, margen_min, margen_max, created_at, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar inserción: " . $this->db->error);


        // bind correcto:
        $types = 'ssii ddd ss';
        $types = 'ssii' . 'ddd' . 'ss'; // = ssii ddd ss
        $stmt->bind_param(
            $types,
            $uuid,
            $razaId,
            $min,
            $max,
            $pesoIdeal,
            $mMin,
            $mMax,
            $now,
            $actorId
        );

        if (!$stmt->execute()) {
            $err = strtolower($stmt->error);
            $stmt->close();

            if (strpos($err, 'foreign key') !== false) {
                throw new RuntimeException('La raza no existe (violación de clave foránea).');
            }
            throw new mysqli_sql_exception("Error al ejecutar inserción: " . $err);
        }
        $stmt->close();
        return $uuid;
    }

    /**
     * Actualiza cualquier campo. Revalida solapamiento si cambian raza/edades.
     */
    public function actualizar(string $id, array $in): bool
    {
        $set = [];
        $types = '';
        $params = [];

        $razaId = null;
        $min = null;
        $max = null;
        $tocaRango = false;

        if (array_key_exists('raza_id', $in)) {
            $razaId = (string) trim((string) $in['raza_id']);
            if (!$this->razaExiste($razaId)) {
                throw new RuntimeException('La raza especificada no existe o está inactiva.');
            }
            $set[] = 'raza_id = ?';
            $types .= 's';
            $params[] = $razaId;
            $tocaRango = true;
        }

        if (array_key_exists('edad_min_dias', $in)) {
            $min = $this->validarEnteroNoNegativo($in['edad_min_dias'], 'edad_min_dias');
            $set[] = 'edad_min_dias = ?';
            $types .= 'i';
            $params[] = $min;
            $tocaRango = true;
        }
        if (array_key_exists('edad_max_dias', $in)) {
            $max = $this->validarEnteroNoNegativo($in['edad_max_dias'], 'edad_max_dias');
            $set[] = 'edad_max_dias = ?';
            $types .= 'i';
            $params[] = $max;
            $tocaRango = true;
        }
        if ($min !== null && $max !== null) {
            $this->validarRangoEdades($min, $max);
        }

        if (array_key_exists('peso_ideal', $in)) {
            $pi = $this->validarDecimalPositivo($in['peso_ideal'], 'peso_ideal', 999.99);
            $set[] = 'peso_ideal = ?';
            $types .= 'd';
            $params[] = $pi;
        }
        if (array_key_exists('margen_min', $in)) {
            $mm = $this->validarDecimalPositivo($in['margen_min'], 'margen_min', 200.00);
            $set[] = 'margen_min = ?';
            $types .= 'd';
            $params[] = $mm;
        }
        if (array_key_exists('margen_max', $in)) {
            $mx = $this->validarDecimalPositivo($in['margen_max'], 'margen_max', 200.00);
            $set[] = 'margen_max = ?';
            $types .= 'd';
            $params[] = $mx;
        }

        if (empty($set)) {
            throw new InvalidArgumentException('No hay campos para actualizar.');
        }

        // Si cambia raza y/o edades, necesitamos valores actuales faltantes
        if ($tocaRango) {
            $sqlGet = "SELECT raza_id, edad_min_dias, edad_max_dias FROM {$this->table} WHERE tab_peso_id = ? LIMIT 1";
            $st = $this->db->prepare($sqlGet);
            if (!$st)
                throw new mysqli_sql_exception("Error al preparar lectura previa: " . $this->db->error);
            $st->bind_param('s', $id);
            $st->execute();
            $res = $st->get_result();
            $row = $res->fetch_assoc();
            $st->close();

            if (!$row)
                throw new RuntimeException('Tabulador no encontrado.');

            $razaId = $razaId ?? $row['raza_id'];
            $min = $min ?? (int) $row['edad_min_dias'];
            $max = $max ?? (int) $row['edad_max_dias'];
            $this->validarRangoEdades($min, $max);

            if ($this->existeSolapamiento($razaId, $min, $max, $id)) {
                throw new RuntimeException('Solapamiento de rangos de edad para la raza especificada.');
            }
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE tab_peso_id = ?";
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
            // adaptar a PHP 7.4:
            if (strpos($err, 'foreign key') !== false) {
                throw new RuntimeException('La raza no existe (violación de clave foránea).');
            }
            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }

        return true;
    }

    /** Borrado físico (la tabla no define deleted_at). */
    public function eliminar(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE tab_peso_id = ?");
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar eliminación: " . $this->db->error);
        $stmt->bind_param('s', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
