<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php'; // <--- AÑADIDO
require_once __DIR__ . '/../config/TimezoneManager.php'; // <--- AÑADIDO
require_once __DIR__ . '/../helpers/UuidHelper.php';

class CamadaModel
{
    private $db;
    private $table = 'camadas';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // <--- INICIO: AÑADIDO HELPER DE AUDITORÍA --->
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
    // <--- FIN: AÑADIDO HELPER DE AUDITORÍA --->


    public function crearDesdeParto(string $partoId, string $madreId, int $cantidadInicial, array $ctx): string
    {
        $sql = "INSERT INTO {$this->table}
                (camada_id, parto_id, madre_id, cantidad_inicial, estado_camada, 
                 created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                VALUES (?, ?, ?, ?, 'ACTIVA', ?, ?, ?, ?, NULL, NULL)"; // <-- MODIFICADO

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar inserción de camada: " . $this->db->error);

        $uuid = UuidHelper::generateUUIDv4();
        $stmt->bind_param(
            'sssissss', // <-- SIN CAMBIOS (9 params)
            $uuid,
            $partoId,
            $madreId,
            $cantidadInicial,
            $ctx['now'],
            $ctx['actorId'],
            $ctx['now'],
            $ctx['actorId']
        );

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new mysqli_sql_exception("Error al ejecutar inserción de camada: " . $err);
        }

        $stmt->close();
        return $uuid;
    }

    public function listar(int $limit = 10000, int $offset = 0, ?string $madreId = null): array
    {
        // <-- MODIFICADO: Añadido c.deleted_at IS NULL -->
        $w = ["c.estado_camada = 'ACTIVA'", "c.deleted_at IS NULL"];
        $p = [];
        $t = '';

        if ($madreId) {
            $w[] = 'c.madre_id = ?';
            $p[] = $madreId;
            $t .= 's';
        }

        $whereSql = implode(' AND ', $w);

        $sql = "SELECT
                    c.camada_id,
                    c.parto_id,
                    c.madre_id,
                    a.identificador AS madre_identificador,
                    p.fecha_parto,
                    c.cantidad_inicial,
                    c.estado_camada,
                    -- <-- MODIFICADO: Añadido deleted_at IS NULL en subqueries -->
                    (SELECT COUNT(*) FROM animales an WHERE an.camada_id = c.camada_id AND an.deleted_at IS NULL) AS registrados_count,
                    (SELECT IFNULL(SUM(cb.cantidad), 0) FROM camada_bajas cb WHERE cb.camada_id = c.camada_id AND cb.deleted_at IS NULL) AS bajas_count
                FROM {$this->table} c
                JOIN partos p ON p.parto_id = c.parto_id
                JOIN animales a ON a.animal_id = c.madre_id
                WHERE {$whereSql}
                ORDER BY p.fecha_parto DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar listado de camadas: " . $this->db->error);

        $t .= 'ii';
        $p[] = $limit;
        $p[] = $offset;
        $stmt->bind_param($t, ...$p);

        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($data as &$row) {
            $row['pendientes_count'] = $row['cantidad_inicial'] - $row['registrados_count'] - $row['bajas_count'];
        }

        return $data;
    }

    public function obtenerPorId(string $camadaId): ?array
    {
        $sql = "SELECT
                    c.camada_id,
                    c.parto_id,
                    c.madre_id,
                    a_madre.identificador AS madre_identificador,
                    a_madre.raza_id AS madre_raza_id,
                    ps.verraco_id AS padre_id,
                    a_padre.identificador AS padre_identificador,
                    p.fecha_parto,
                    p.fotografia_url,
                    c.cantidad_inicial,
                    c.estado_camada,
                    c.created_at,
                    -- <-- MODIFICADO: Añadido deleted_at IS NULL en subqueries -->
                    (SELECT COUNT(*) FROM animales an WHERE an.camada_id = c.camada_id AND an.deleted_at IS NULL) AS registrados_count,
                    (SELECT IFNULL(SUM(cb.cantidad), 0) FROM camada_bajas cb WHERE cb.camada_id = c.camada_id AND cb.deleted_at IS NULL) AS bajas_count
                FROM {$this->table} c
                JOIN partos p ON p.parto_id = c.parto_id
                JOIN periodos_servicio ps ON ps.periodo_id = p.periodo_id
                JOIN animales a_madre ON a_madre.animal_id = c.madre_id
                LEFT JOIN animales a_padre ON a_padre.animal_id = ps.verraco_id
                WHERE c.camada_id = ?
                AND c.deleted_at IS NULL"; // <-- MODIFICADO

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar consulta de camada: " . $this->db->error);

        $stmt->bind_param('s', $camadaId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row) {
            $row['pendientes_count'] = $row['cantidad_inicial'] - $row['registrados_count'] - $row['bajas_count'];
        }

        return $row ?: null;
    }

    // <--- INICIO: MÉTODO AÑADIDO (EL QUE FALTABA) --->
    public function actualizarEstado(string $camadaId, string $estado): bool
    {
        $estado = strtoupper(trim($estado));
        if (!in_array($estado, ['ACTIVA', 'CERRADA'], true)) {
            throw new InvalidArgumentException('Estado de camada inválido.');
        }

        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $camadaId;

        $sql = "UPDATE {$this->table}
                SET estado_camada = ?, updated_at = ?, updated_by = ?
                WHERE camada_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar actualización de estado: " . $this->db->error);

        $stmt->bind_param('ssss', $estado, $now, $actorId, $camadaId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    // <--- FIN: MÉTODO AÑADIDO --->

    // <--- INICIO: MÉTODO AÑADIDO (SOFT DELETE) --->
    public function eliminar(string $camadaId): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $camadaId;

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE camada_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar eliminación: " . $this->db->error);

        $stmt->bind_param('sss', $now, $actorId, $camadaId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    // <--- FIN: MÉTODO AÑADIDO --->
}