<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';
require_once __DIR__ . '/CamadaModel.php';
require_once __DIR__ . '/IncidenciaModel.php'; // <--- AÑADIR ESTO

class CamadaBajaModel
{
    private $db;
    private $table = 'camada_bajas';
    private $incidenciaModel; // <--- AÑADIR ESTO
    private $camadaModel; // <--- AÑADIR ESTO (Para buscar madre_id)

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->incidenciaModel = new IncidenciaModel(); // <--- AÑADIR ESTO
        $this->camadaModel = new CamadaModel(); // <--- AÑADIR ESTO

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


    private function getPendientesCamada(string $camadaId): int
    {
        $camadaModel = new CamadaModel();
        $camada = $camadaModel->obtenerPorId($camadaId);
        if (!$camada) {
            throw new RuntimeException('La camada no existe o está inactiva.');
        }
        // Este cálculo ya respeta los deleted_at porque CamadaModel->obtenerPorId lo hace
        return (int) $camada['pendientes_count'];
    }

    public function listarPorCamada(string $camadaId): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE camada_id = ?
                AND deleted_at IS NULL"; // <-- MODIFICADO

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al listar bajas: " . $this->db->error);

        $stmt->bind_param('s', $camadaId);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }
    private function getDatosCamada(string $camadaId): ?array
    {
        // Usamos el CamadaModel para obtener el cálculo consolidado
        $camada = $this->camadaModel->obtenerPorId($camadaId);
        if (!$camada) {
            throw new RuntimeException('La camada no existe o está inactiva.');
        }
        // Devolvemos los datos clave que necesitamos
        return [
            'pendientes_count' => (int) $camada['pendientes_count'],
            'madre_id' => $camada['madre_id'] ?? null
        ];
    }

    public function crear(array $in): string
    {
        // ... (Validaciones de camadaId, fechaBaja, cantidad)
        $camadaId = $in['camada_id'] ?? null;
        $fechaBaja = $in['fecha_baja'] ?? null;
        $cantidad = isset($in['cantidad']) ? (int) $in['cantidad'] : 1;

        if (!$camadaId || !$fechaBaja) {
            throw new InvalidArgumentException('Campos requeridos: camada_id, fecha_baja.');
        }
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }

        $causa = $in['causa_deceso'] ?? 'Desconocida';
        $actaUrl = $in['documento_acta_url'] ?? null; // Acepta el acta si ya viene
        $obs = $in['observaciones'] ?? null;

        $pendientes = $this->getPendientesCamada($camadaId);
        if ($cantidad > $pendientes) {
            throw new RuntimeException("No se puede reportar la baja. Solo quedan {$pendientes} lechones pendientes.");
        }

        // 1. Validar pendientes y obtener madre_id
        $datosCamada = $this->getDatosCamada($camadaId);
        $pendientes = $datosCamada['pendientes_count'];
        $madreId = $datosCamada['madre_id'];

        if ($cantidad > $pendientes) {
            throw new RuntimeException("No se puede reportar la baja. Solo quedan {$pendientes} lechones pendientes.");
        }
        if (!$madreId) {
            // Esto no debería pasar si la camada está bien creada
            throw new RuntimeException("No se pudo encontrar la madre asociada a esta camada.");
        }

        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();
            $uuid = UuidHelper::generateUUIDv4();
            $actorId = $_SESSION['user_id'] ?? $uuid;

            // <--- INICIO: MODIFICADO SQL INSERT --->
            $sql = "INSERT INTO {$this->table}
                    (baja_id, camada_id, fecha_baja, cantidad, causa_deceso, 
                     documento_acta_url, observaciones, created_at, created_by,
                     updated_at, updated_by, deleted_at, deleted_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception("Error al preparar inserción de baja: " . $this->db->error);

            $stmt->bind_param(
                'sssisssssss', // 11 params
                $uuid,
                $camadaId,
                $fechaBaja,
                $cantidad,
                $causa,
                $actaUrl,
                $obs,
                $now,
                $actorId,
                $now,
                $actorId
            );
            // <--- FIN: MODIFICADO SQL INSERT --->

            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                $this->db->rollback();
                throw new mysqli_sql_exception("Error al ejecutar inserción de baja: " . $err);
            }

            $stmt->close();

            // 3. (NUEVO) Si la causa es Aplastamiento, registrar incidencia en la MADRE
            $causaNormalizada = strtoupper(str_replace(' ', '', $causa));
            if ($causaNormalizada === 'APLASTAMIENTO') {

                $descripcionIncidencia = "Aplastamiento de {$cantidad} lechón(es). Baja registrada.";

                // Creamos el array de datos para IncidenciaModel
                $incidenciaData = [
                    'animal_id' => $madreId, // La incidencia es de la MADRE
                    'tipo' => 'APLASTAMIENTO',
                    'fecha_evento' => $fechaBaja . ' 00:00:00', // Asumimos la fecha de la baja
                    'descripcion' => $descripcionIncidencia,
                    'responsable' => 'sistema' // O $actorId si prefieres
                ];

                // Esta llamada se une a la transacción
                // IncidenciaModel->crear() se encargará de las notificaciones y alertas
                $this->incidenciaModel->crear($incidenciaData);
            }

            // 4. Commit
            $this->db->commit();
            return $uuid;

        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // <--- INICIO: MÉTODO AÑADIDO (PARA EL ACTA) --->
    public function actualizar(string $bajaId, array $data): bool
    {
        $set = [];
        $p = [];
        $t = '';

        // Solo permitimos actualizar el acta y observaciones por ahora
        if (array_key_exists('documento_acta_url', $data)) {
            $set[] = 'documento_acta_url = ?';
            $p[] = $data['documento_acta_url'];
            $t .= 's';
        }
        if (array_key_exists('observaciones', $data)) {
            $set[] = 'observaciones = ?';
            $p[] = $data['observaciones'];
            $t .= 's';
        }

        if (empty($set)) {
            throw new InvalidArgumentException('No hay campos para actualizar.');
        }

        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $bajaId;

        $set[] = 'updated_at = ?';
        $p[] = $now;
        $t .= 's';
        $set[] = 'updated_by = ?';
        $p[] = $actorId;
        $t .= 's';

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE baja_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar actualización de baja: " . $this->db->error);

        $t .= 's';
        $p[] = $bajaId;
        $stmt->bind_param($t, ...$p);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
    // <--- FIN: MÉTODO AÑADIDO --->

    // <--- INICIO: MÉTODO MODIFICADO (SOFT DELETE) --->
    public function eliminar(string $bajaId): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $bajaId;

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE baja_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error al preparar eliminación de baja: " . $this->db->error);

        $stmt->bind_param('sss', $now, $actorId, $bajaId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    // <--- FIN: MÉTODO MODIFICADO --->
}