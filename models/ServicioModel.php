<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class ServicioModel
{
    private $db;
    private $table = 'servicios';

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

    private function periodoExiste(string $id): bool
    {
        $sql = "SELECT 1 FROM periodos_servicio WHERE periodo_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception($this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }


    public function obtenerUltimoServicio($periodoId = null): array
    {
        if (!$periodoId) {
            throw new InvalidArgumentException('El parámetro periodo es obligatorio.');
        }

        $sql = "SELECT s.numero_monta, s.fecha_monta, m.frecuencia_servicios
        FROM servicios AS s
        LEFT JOIN periodos_servicio m ON s.periodo_id = m.periodo_id
        WHERE s.periodo_id = ?
        ORDER BY s.numero_monta DESC LIMIT 1 OFFSET 0";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar: " . $this->db->error);

        $t = 'i';
        $p[] = $periodoId;
        $stmt->bind_param($t, ...$p);
        $stmt->execute();
        $res  = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $ultimo = $data[0];
        $stmt->close();

        $frecuencia = $ultimo['frecuencia_servicios'];
        $fechaUltimoServicio = new DateTime($ultimo['fecha_monta']);
        $ultimo['siguiente_monta'] = (int)$ultimo['numero_monta'] + 1;

        $frecuencias = [
            'diaria'        => 1,
            'cada_2_dias'   => 2,
            'cada_3_dias'   => 3,
            'cada_4_dias'   => 4,
            'cada_5_dias'   => 5,
        ];

        $diasFrecuencia = $frecuencias[$frecuencia] ?? 1;


        $fechaUltimoServicio->modify("+{$diasFrecuencia} days");
        $ultimo['fecha_siguiente_monta'] = $fechaUltimoServicio->format('Y-m-d');

        return $ultimo;
    }




    /* ============ Escrituras ============ */


    /**
     * Crea una monta.
     * Requeridos: periodo_id, numero_monta (>=1), fecha_monta (Y-m-d)
     */
    public function crear(array $data): string
    {
        $fecha_servicio   = trim((string)($data['fecha_servicio'] ?? ''));
        $periodo_id  = trim((string)($data['periodo_id'] ?? ''));
        $numeroMonta  = trim((string)($data['numero_servicio'] ?? ''));


        if ($fecha_servicio === '' || $periodo_id === 0 || $numeroMonta === '') {
            throw new InvalidArgumentException('Faltan campos requeridos.' . $fecha_servicio);
        }

        if (!$this->periodoExiste($periodo_id)) {
            throw new RuntimeException('El periodo de servicio no existe o está eliminado.');
        }


        $this->db->begin_transaction();
        try {
            $uuid    = UuidHelper::generateUUIDv4();
            $now = (new DateTime())->format('Y-m-d H:i:s');


            $actorId = $_SESSION['user_id'] ?? $uuid;

            $sql = "INSERT INTO {$this->table}
                    (monta_id, periodo_id, numero_monta, fecha_monta,
                     created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                    VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) throw new mysqli_sql_exception("Error al preparar inserción: " . $this->db->error);

            $stmt->bind_param(
                'ssisss',
                $uuid,
                $periodo_id,
                $numeroMonta,
                $fecha_servicio,
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
            $this->db->commit();
            return $uuid;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    } // TODO: REVISADO


    /**
     * Actualiza campos explícitos de la monta.
     * Campos: periodo_id?, numero_monta?, fecha_monta?
     */
    /* public function actualizar(string $montaId, array $data): bool
    {
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
        if (array_key_exists('numero_monta', $data)) {
            $nm = $data['numero_monta'];
            if ($nm === null || $nm === '') {
                $set[] = 'numero_monta = ?';
                $p[] = null;
                $t .= 's';
            } else {
                $nm = (int)$nm;
                if ($nm < 1) throw new InvalidArgumentException('numero_monta debe ser >= 1.');
                $set[] = 'numero_monta = ?';
                $p[] = $nm;
                $t .= 'i';
            }
        }
        if (isset($data['fecha_monta'])) {
            $set[] = 'fecha_monta = ?';
            $p[] = (string)$data['fecha_monta'];
            $t .= 's';
        }

        if (empty($set)) throw new InvalidArgumentException('No hay campos para actualizar.');

        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $montaId;

        $set[] = 'updated_at = ?';
        $p[] = $now;
        $t .= 's';
        $set[] = 'updated_by = ?';
        $p[] = $actorId;
        $t .= 's';

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE monta_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar actualización: " . $this->db->error);

        $t .= 's';
        $p[] = $montaId;
        $ok = $stmt->bind_param($t, ...$p);
        if (!$ok) throw new mysqli_sql_exception("Error al bind_param en actualización.");

        $ok  = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();

        if (!$ok) {
            $errLow = strtolower($err);
            if (str_contains($errLow, 'foreign key')) {
                throw new RuntimeException('Referencia inválida a periodo de servicio.');
            }
            if (str_contains($errLow, 'duplicate') || str_contains($errLow, 'unique')) {
                throw new RuntimeException('Ya existe una monta con ese número en este periodo.');
            }
            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }
        return true;
    }*/

    public function actualizar(string $montaId): bool
    {
        // Validar entrada
        if (empty($montaId)) {
            throw new InvalidArgumentException("El ID de la monta no puede estar vacío.");
        }

        // Preparar la consulta
        $sql = "UPDATE {$this->table} 
                SET estatus = 'REALIZADO' 
                WHERE monta_id = ? 
                  AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error al preparar la actualización: " . $this->db->error);
        }

        // Enlazar parámetro (asegura tipo correcto)
        $stmt->bind_param("s", $montaId);

        // Ejecutar
        $ok = $stmt->execute();
        $err = $stmt->error;

        $stmt->close();

        // Manejo de errores
        if (!$ok) {
            $errLow = strtolower($err);

            if (str_contains($errLow, 'foreign key')) {
                throw new RuntimeException('Referencia inválida a periodo de servicio.');
            }

            if (str_contains($errLow, 'duplicate') || str_contains($errLow, 'unique')) {
                throw new RuntimeException('Ya existe una monta con ese número en este periodo.');
            }

            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }

        return true;
    } // TODO: REALIZADO


    /**
     * Eliminación lógica (soft delete).
     */
    public function eliminar(string $periodo_id): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId     = $_SESSION['user_id'] ?? $periodo_id;

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE periodo_id  = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error al preparar eliminación: " . $this->db->error);

        $stmt->bind_param('sss', $now, $actorId, $periodo_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
