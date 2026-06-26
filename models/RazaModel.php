<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class RazaModel
{
    private $db;
    private string $table = 'razas';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ================= Utilidades ================= */

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

    private function validarEnum(string $val, array $permitidos, string $campo): string
    {
        $val = strtoupper(trim($val));
        if (!in_array($val, $permitidos, true)) {
            $opts = implode('|', $permitidos);
            throw new InvalidArgumentException("$campo inválido. Use: $opts.");
        }
        return $val;
    }

    private function normalizarCodigo(?string $c): ?string
    {
        if ($c === null) return null;
        $c = strtoupper(trim($c));
        if ($c === '') return null;
        if (!preg_match('/^[A-Z0-9_\-]{2,32}$/', $c)) {
            throw new InvalidArgumentException("codigo inválido. Use 2-32 chars A-Z, 0-9, _ o -.");
        }
        return $c;
    }

    private function normalizarNombre(string $n): string
    {
        $n = trim($n);
        if ($n === '') {
            throw new InvalidArgumentException('nombre es requerido.');
        }
        if (mb_strlen($n) > 120) {
            throw new InvalidArgumentException('nombre demasiado largo (máx 120).');
        }
        return $n;
    }

    private function normalizarDescripcion(?string $d): ?string
    {
        if ($d === null) return null;
        $d = trim($d);
        return $d === '' ? null : $d;
    }

    private function existeCodigo(string $codigo, ?string $excluirId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table}
                 WHERE codigo = ?
                   AND (deleted_at IS NULL)"; // consideramos únicos entre activos
        $types = 's';
        $params = [$codigo];

        if ($excluirId) {
            $sql .= " AND raza_id <> ?";
            $types .= 's';
            $params[] = $excluirId;
        }
        $sql .= " LIMIT 1";

        $st = $this->db->prepare($sql);
        if (!$st) throw new mysqli_sql_exception("Error verificación de código: ".$this->db->error);
        $st->bind_param($types, ...$params);
        $st->execute();
        $st->store_result();
        $ok = $st->num_rows > 0;
        $st->close();
        return $ok;
    }

    /* ================= Lecturas ================= */

    /**
     * Listado con filtros:
     *  - especie: BOVINO|OVINO|CAPRINO|PORCINO|OTRO
     *  - estado:  ACTIVA|INACTIVA
     *  - q: busca en codigo/nombre
     *  - incluirEliminados: incluye registros soft-deleted
     */
    public function listar(
        int $limit = 10000,
        int $offset = 0,
        ?string $especie = null,
        ?string $estado = null,
        ?string $q = null,
        bool $incluirEliminados = false
    ): array {
        $where  = [];
        $types  = '';
        $params = [];

        if ($incluirEliminados) {
            // no filtramos por deleted_at
        } else {
            $where[] = 'r.deleted_at IS NULL';
        }

        if ($especie) {
            $especie = $this->validarEnum($especie, ['BOVINO','OVINO','CAPRINO','PORCINO','OTRO'], 'especie');
            $where[] = 'r.especie = ?';
            $types  .= 's';
            $params[] = $especie;
        }

        if ($estado) {
            $estado = $this->validarEnum($estado, ['ACTIVA','INACTIVA'], 'estado');
            $where[] = 'r.estado = ?';
            $types  .= 's';
            $params[] = $estado;
        }

        if ($q !== null && $q !== '') {
            $where[] = '(r.codigo LIKE ? OR r.nombre LIKE ?)';
            $types  .= 'ss';
            $like = '%'.trim($q).'%';
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
        $sql = "SELECT
                    r.raza_id,
                    r.especie,
                    r.codigo,
                    r.nombre,
                    r.descripcion,
                    r.estado,
                    r.created_at,
                    r.created_by,
                    r.updated_at,
                    r.updated_by,
                    r.deleted_at,
                    r.deleted_by
                FROM {$this->table} r
                $whereSql
                ORDER BY r.especie, r.nombre ASC
                LIMIT ? OFFSET ?";

        $st = $this->db->prepare($sql);
        if (!$st) throw new mysqli_sql_exception("Error al preparar listado: ".$this->db->error);

        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $st->bind_param($types, ...$params);
        $st->execute();
        $res = $st->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $st->close();
        return $rows;
    }

    public function obtenerPorId(string $id): ?array
    {
        $sql = "SELECT
                    r.raza_id,
                    r.especie,
                    r.codigo,
                    r.nombre,
                    r.descripcion,
                    r.estado,
                    r.created_at,
                    r.created_by,
                    r.updated_at,
                    r.updated_by,
                    r.deleted_at,
                    r.deleted_by
                FROM {$this->table} r
               WHERE r.raza_id = ?
               LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) throw new mysqli_sql_exception("Error al preparar consulta: ".$this->db->error);
        $st->bind_param('s', $id);
        $st->execute();
        $res = $st->get_result();
        $row = $res->fetch_assoc();
        $st->close();
        return $row ?: null;
    }

    /* ================= Escrituras ================= */

    /**
     * Crear raza.
     * Requeridos: especie, nombre, estado
     * Opcionales: codigo, descripcion
     */
    public function crear(array $in): string
    {
        foreach (['especie','nombre','estado'] as $k) {
            if (!isset($in[$k]) || $in[$k] === '') {
                throw new InvalidArgumentException("Falta campo requerido: {$k}.");
            }
        }

        $especie = $this->validarEnum((string)$in['especie'], ['BOVINO','OVINO','CAPRINO','PORCINO','OTRO'], 'especie');
        $estado  = $this->validarEnum((string)$in['estado'],  ['ACTIVA','INACTIVA'], 'estado');
        $nombre  = $this->normalizarNombre((string)$in['nombre']);
        $codigo  = isset($in['codigo']) ? $this->normalizarCodigo($in['codigo']) : null;
        $desc    = isset($in['descripcion']) ? $this->normalizarDescripcion($in['descripcion']) : null;

        if ($codigo && $this->existeCodigo($codigo, null)) {
            throw new RuntimeException('Ya existe una raza activa con ese código.');
        }

        [$now] = $this->nowWithAudit();
        $uuid    = UuidHelper::generateUUIDv4();
        $actorId = $_SESSION['user_id'] ?? $uuid;

        $sql = "INSERT INTO {$this->table}
                (raza_id, especie, codigo, nombre, descripcion, estado,
                 created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL)";
        $st = $this->db->prepare($sql);
        if (!$st) throw new mysqli_sql_exception("Error al preparar inserción: ".$this->db->error);

        $st->bind_param(
            'ssssssss',
            $uuid, $especie, $codigo, $nombre, $desc, $estado, $now, $actorId
        );

        if (!$st->execute()) {
            $err = strtolower($st->error);
            $st->close();
            if (str_contains($err, 'duplicate')) {
                throw new RuntimeException('Conflicto de unicidad (posible código duplicado).');
            }
            throw new mysqli_sql_exception("Error al ejecutar inserción: ".$err);
        }
        $st->close();
        return $uuid;
    }

    /**
     * Actualizar raza (cualquier campo).
     * Revalida código único entre activos.
     */
    public function actualizar(string $id, array $in): bool
    {
        $set = [];
        $types = '';
        $params = [];

        if (array_key_exists('especie', $in)) {
            $val = $this->validarEnum((string)$in['especie'], ['BOVINO','OVINO','CAPRINO','PORCINO','OTRO'], 'especie');
            $set[] = 'especie = ?'; $types .= 's'; $params[] = $val;
        }

        if (array_key_exists('estado', $in)) {
            $val = $this->validarEnum((string)$in['estado'], ['ACTIVA','INACTIVA'], 'estado');
            $set[] = 'estado = ?'; $types .= 's'; $params[] = $val;
        }

        if (array_key_exists('nombre', $in)) {
            $val = $this->normalizarNombre((string)$in['nombre']);
            $set[] = 'nombre = ?'; $types .= 's'; $params[] = $val;
        }

        if (array_key_exists('codigo', $in)) {
            $val = $this->normalizarCodigo($in['codigo']);
            if ($val && $this->existeCodigo($val, $id)) {
                throw new RuntimeException('Ya existe una raza activa con ese código.');
            }
            $set[] = 'codigo = ?'; $types .= 's'; $params[] = $val;
        }

        if (array_key_exists('descripcion', $in)) {
            $val = $this->normalizarDescripcion($in['descripcion']);
            $set[] = 'descripcion = ?'; $types .= 's'; $params[] = $val;
        }

        if (empty($set)) {
            throw new InvalidArgumentException('No hay campos para actualizar.');
        }

        [$now]  = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $id;

        $set[] = 'updated_at = ?'; $types .= 's'; $params[] = $now;
        $set[] = 'updated_by = ?'; $types .= 's'; $params[] = $actorId;

        $sql = "UPDATE {$this->table} SET ".implode(', ', $set)." WHERE raza_id = ? AND (deleted_at IS NULL)";
        $types .= 's';
        $params[] = $id;

        $st = $this->db->prepare($sql);
        if (!$st) throw new mysqli_sql_exception("Error al preparar actualización: ".$this->db->error);
        $st->bind_param($types, ...$params);

        $ok  = $st->execute();
        $err = strtolower($st->error);
        $st->close();

        if (!$ok) {
            if (str_contains($err, 'duplicate')) {
                throw new RuntimeException('Conflicto de unicidad (posible código duplicado).');
            }
            throw new mysqli_sql_exception("Error al actualizar: ".$err);
        }
        return true;
    }

    /** Borrado lógico */
    public function eliminar(string $id): bool
    {
        [$now]  = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $id;

        $sql = "UPDATE {$this->table}
                   SET deleted_at = ?, deleted_by = ?
                 WHERE raza_id = ? AND deleted_at IS NULL";
        $st = $this->db->prepare($sql);
        if (!$st) throw new mysqli_sql_exception("Error al preparar eliminación: ".$this->db->error);
        $st->bind_param('sss', $now, $actorId, $id);
        $ok = $st->execute();
        $st->close();
        return $ok;
    }

    /** Restaurar borrado lógico */
    public function restaurar(string $id): bool
    {
        $sql = "UPDATE {$this->table}
                   SET deleted_at = NULL, deleted_by = NULL
                 WHERE raza_id = ? AND deleted_at IS NOT NULL";
        $st = $this->db->prepare($sql);
        if (!$st) throw new mysqli_sql_exception("Error al preparar restauración: ".$this->db->error);
        $st->bind_param('s', $id);
        $ok = $st->execute();
        $st->close();
        return $ok;
    }
}
