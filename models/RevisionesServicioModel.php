<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class RevisionesServicioModel
{
    private $db;
    private $table = 'revisiones_servicio';

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

    private function getActorIdFallback(string $fallback): string
    {
        return $_SESSION['user_id'] ?? $fallback;
    }

    private function validarResultado(?string $resultado): void
    {
        if ($resultado === null || $resultado === '') return;
        // Agregado SIN_SEÑALES
        $validos = ['ENTRO_EN_CELO','SOSPECHA_PREÑEZ','CONFIRMADA_PREÑEZ','SIN_SEÑALES'];
        if (!in_array($resultado, $validos, true)) {
            throw new InvalidArgumentException(
                "resultado inválido. Use uno de: " . implode(', ', $validos)
            );
        }
    }

    private function validarCiclo(int $ciclo): void
    {
        if ($ciclo < 1 || $ciclo > 3) {
            throw new InvalidArgumentException('ciclo_control debe estar entre 1 y 3.');
        }
    }

    private function periodoExiste(string $periodoId, bool $requerirAbierto = false): array
    {
        $sql = "SELECT periodo_id, hembra_id, fecha_inicio, estado_periodo
                FROM periodos_servicio
                WHERE periodo_id = ? AND deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error preparando verificación de período: " . $this->db->error);
        $stmt->bind_param('s', $periodoId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new RuntimeException('El período de servicio no existe o está eliminado.');
        }
        if ($requerirAbierto && $row['estado_periodo'] !== 'ABIERTO') {
            throw new RuntimeException('El período de servicio no está ABIERTO.');
        }
        return $row;
    }

    private function getMaxCiclo(string $periodoId): int
    {
        $sql = "SELECT COALESCE(MAX(ciclo_control), 0) AS max_ciclo
                FROM {$this->table}
                WHERE periodo_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error preparando MAX ciclo: " . $this->db->error);
        $stmt->bind_param('s', $periodoId);
        $stmt->execute();
        $max = (int)$stmt->get_result()->fetch_assoc()['max_ciclo'];
        $stmt->close();
        return $max;
    }

/** Devuelve la fecha de la primera monta (equivale a fecha_inicio del período) */
private function getFechaPrimeraMonta(string $periodoId): ?string
{
    $sql = "SELECT DATE(fecha_inicio) AS primera
            FROM periodos_servicio
            WHERE periodo_id = ? AND deleted_at IS NULL
            LIMIT 1";
    $stmt = $this->db->prepare($sql);
    if (!$stmt) throw new mysqli_sql_exception("Error preparando consulta de primera monta: " . $this->db->error);

    $stmt->bind_param('s', $periodoId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !$row['primera']) return null;
    return $row['primera']; // 'YYYY-MM-DD'
}


    /** Inserta una alerta (REVISION_20_21 o PROX_PARTO_117) */
    private function crearAlerta(string $tipo, string $periodoId, ?string $animalId, string $fechaObjetivo, ?string $detalle = null): void
    {
        $sql = "INSERT INTO alertas (alerta_id, tipo_alerta, periodo_id, animal_id, fecha_objetivo, estado_alerta, detalle)
                VALUES (?, ?, ?, ?, ?, 'PENDIENTE', ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error preparando inserción de alerta: " . $this->db->error);

        $alertaId = UuidHelper::generateUUIDv4();
        $stmt->bind_param('ssssss', $alertaId, $tipo, $periodoId, $animalId, $fechaObjetivo, $detalle);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new mysqli_sql_exception("No se pudo crear la alerta: " . $err);
        }
        $stmt->close();
    }

    private function dateAddDays(string $yyyy_mm_dd, int $days): string
    {
        $d = new DateTime($yyyy_mm_dd);
        $d->modify(($days >= 0 ? '+' : '') . $days . ' days');
        return $d->format('Y-m-d');
    }

    /* ============ Lecturas ============ */

    public function listar(
        int $limit = 10000,
        int $offset = 0,
        ?string $periodoId = null,
        ?string $resultado = null,
        bool $incluirEliminados = false
    ): array {
        $w = []; $p = []; $t = '';

        // soft delete
        $w[] = $incluirEliminados ? '(r.deleted_at IS NOT NULL OR r.deleted_at IS NULL)' : 'r.deleted_at IS NULL';

        if ($periodoId) { $w[] = 'r.periodo_id = ?'; $p[] = $periodoId; $t .= 's'; }
        if ($resultado) { $this->validarResultado($resultado); $w[] = 'r.resultado = ?'; $p[] = $resultado; $t .= 's'; }

        $where = implode(' AND ', $w);

        $sql = "SELECT 
                    r.revision_id, r.periodo_id, r.ciclo_control, r.fecha_programada,
                    r.fecha_realizada, r.resultado, r.observaciones,
                    r.created_at, r.created_by, r.updated_at, r.updated_by,
                    r.deleted_at, r.deleted_by
                FROM {$this->table} r
                WHERE {$where}
                ORDER BY r.fecha_programada ASC, r.ciclo_control ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error preparando listado: " . $this->db->error);

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

    public function obtenerPorId(string $revisionId): ?array
    {
        $sql = "SELECT 
                    revision_id, periodo_id, ciclo_control, fecha_programada,
                    fecha_realizada, resultado, observaciones,
                    created_at, created_by, updated_at, updated_by,
                    deleted_at, deleted_by
                FROM {$this->table}
                WHERE revision_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error preparando consulta: " . $this->db->error);
        $stmt->bind_param('s', $revisionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /* ============ Escrituras ============ */
    /**
     * Crear revisión (flujo existente para pre-creación por scheduler).
     * Requeridos: periodo_id, fecha_programada (YYYY-MM-DD)
     * Opcionales: ciclo_control (si no viene, se autocalcula max+1), fecha_realizada, resultado, observaciones.
     * - Si resultado = CONFIRMADA_PREÑEZ -> cierra período y crea alerta PROX_PARTO_117 (si hay fecha de 1era monta).
     * - Si resultado = SOSPECHA_PREÑEZ y ciclo_control<3 -> crea alerta REVISION_20_21 para siguiente ciclo (+21 días).
     * - Si resultado = ENTRO_EN_CELO -> cierra período.
     */
    public function crear(array $data): string
    {
        $periodoId       = trim((string)($data['periodo_id'] ?? ''));
        $fechaProgramada = trim((string)($data['fecha_programada'] ?? ''));
        $fechaRealizada  = isset($data['fecha_realizada']) && $data['fecha_realizada'] !== '' ? trim((string)$data['fecha_realizada']) : null;
        $resultado       = isset($data['resultado']) && $data['resultado'] !== '' ? trim((string)$data['resultado']) : null;
        $obs             = isset($data['observaciones']) ? trim((string)$data['observaciones']) : null;

        if ($periodoId === '' || $fechaProgramada === '') {
            throw new InvalidArgumentException('Faltan campos requeridos: periodo_id, fecha_programada.');
        }
        $this->validarResultado($resultado);
        $periodo = $this->periodoExiste($periodoId, true); // exigir ABIERTO

        $ciclo = isset($data['ciclo_control']) && $data['ciclo_control'] !== ''
            ? (int)$data['ciclo_control']
            : ($this->getMaxCiclo($periodoId) + 1);

        $this->validarCiclo($ciclo);
        if ($fechaRealizada && $fechaRealizada < $fechaProgramada) {
            throw new InvalidArgumentException('fecha_realizada no puede ser anterior a fecha_programada.');
        }

        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();
            $uuid    = UuidHelper::generateUUIDv4();
            $actorId = $this->getActorIdFallback($uuid);

            // Insert principal con auditoría
            $sql = "INSERT INTO {$this->table}
                    (revision_id, periodo_id, ciclo_control, fecha_programada, fecha_realizada, resultado, observaciones,
                     created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) throw new mysqli_sql_exception("Error preparando inserción: " . $this->db->error);

            $stmt->bind_param(
                'ssissssss',
                $uuid, $periodoId, $ciclo, $fechaProgramada, $fechaRealizada, $resultado, $obs,
                $now, $actorId
            );
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                $this->db->rollback();
                if (str_contains(strtolower($err), 'duplicate')) {
                    throw new RuntimeException('Ya existe una revisión para ese ciclo en este período.');
                }
                throw new mysqli_sql_exception("Error al ejecutar inserción: " . $err);
            }
            $stmt->close();

            // Efectos colaterales según resultado (flujo creación)
            if ($resultado === 'CONFIRMADA_PREÑEZ') {
                // 1) Cerrar período
                $stmt = $this->db->prepare("UPDATE periodos_servicio SET estado_periodo='CERRADO', updated_at=?, updated_by=? WHERE periodo_id=? AND deleted_at IS NULL");
                if (!$stmt) throw new mysqli_sql_exception("Error preparando cierre de período: " . $this->db->error);
                $stmt->bind_param('sss', $now, $actorId, $periodoId);
                $stmt->execute();
                $stmt->close();

                // 2) Crear alerta de parto a +117 días desde la primera monta (si existe)
                $primeraMonta = $this->getFechaPrimeraMonta($periodoId);
                if ($primeraMonta) {
                    $fechaParto = $this->dateAddDays($primeraMonta, 117);
                    $this->crearAlerta('PROX_PARTO_117', $periodoId, $periodo['hembra_id'], $fechaParto, 'Parto estimado a +117 días de la primera monta');
                }
            } elseif ($resultado === 'ENTRO_EN_CELO') {
                // Cerrar período
                $stmt = $this->db->prepare("UPDATE periodos_servicio SET estado_periodo='CERRADO', updated_at=?, updated_by=? WHERE periodo_id=? AND deleted_at IS NULL");
                if (!$stmt) throw new mysqli_sql_exception("Error preparando cierre de período (celo): " . $this->db->error);
                $stmt->bind_param('sss', $now, $actorId, $periodoId);
                $stmt->execute();
                $stmt->close();
            } elseif (in_array($resultado, ['SOSPECHA_PREÑEZ','SIN_SEÑALES'], true) && $ciclo < 3) {
                // Programar próxima revisión (alerta) — en creación no tocamos la fila recién insertada
                $proxima = $this->dateAddDays($fechaProgramada, 21);
                $this->crearAlerta('REVISION_20_21', $periodoId, $periodo['hembra_id'], $proxima, "Ciclo ".($ciclo+1));
            }

            $this->db->commit();
            return $uuid;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Actualiza solo a partir de:
     * - revision_id (parámetro del método)
     * - resultado (obligatorio)
     * - observaciones (opcional)
     *
     * Reglas:
     * - CONFIRMADA_PREÑEZ: actualiza registro (resultado, observaciones, fecha_realizada=now), cierra período y crea PROX_PARTO_117 (+117 desde 1ra monta, si existe).
     * - ENTRO_EN_CELO: actualiza registro (resultado, observaciones, fecha_realizada=now) y cierra período.
     * - SOSPECHA_PREÑEZ / SIN_SEÑALES:
     *      * si ciclo_control < 3: reagenda misma revisión -> fecha_programada = +21 días, ciclo_control = ciclo+1,
     *        limpia fecha_realizada, actualiza resultado/observaciones; crea alerta REVISION_20_21 con detalle del nuevo ciclo.
     *      * si ciclo_control = 3: solo actualiza resultado/observaciones; no se reagenda ni se crea alerta.
     */
    public function actualizar(string $revisionId, array $data): bool
    {
        // Tomamos estrictamente los 3 valores del requerimiento
        $resultado     = isset($data['resultado']) ? trim((string)$data['resultado']) : null;
        $observaciones = array_key_exists('observaciones', $data)
            ? ($data['observaciones'] !== null ? trim((string)$data['observaciones']) : null)
            : null;

        if (!$resultado) {
            throw new InvalidArgumentException('El campo "resultado" es obligatorio.');
        }
        $this->validarResultado($resultado);

        $row = $this->obtenerPorId($revisionId);
        if (!$row || $row['deleted_at'] !== null) {
            throw new mysqli_sql_exception('Revisión no encontrada o eliminada.');
        }

        $periodo = $this->periodoExiste($row['periodo_id'], false);

        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();
            $actorId = $this->getActorIdFallback($revisionId);

            // Branch por resultado
            if ($resultado === 'CONFIRMADA_PREÑEZ') {
                // 1) Actualizar registro (resultado + obs + fecha_realizada)
                $sql = "UPDATE {$this->table}
                           SET resultado = ?, observaciones = ?, fecha_realizada = ?, updated_at = ?, updated_by = ?
                         WHERE revision_id = ? AND deleted_at IS NULL";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) throw new mysqli_sql_exception("Error preparando actualización (confirmada): " . $this->db->error);
                $stmt->bind_param('ssssss', $resultado, $observaciones, $now, $now, $actorId, $revisionId);
                if (!$stmt->execute()) {
                    $err = $stmt->error; $stmt->close();
                    throw new mysqli_sql_exception("Error al actualizar revisión (confirmada): " . $err);
                }
                $stmt->close();

                // 2) Cerrar período
                $stmt = $this->db->prepare("UPDATE periodos_servicio SET estado_periodo='CERRADO', updated_at=?, updated_by=? WHERE periodo_id=? AND deleted_at IS NULL");
                if (!$stmt) throw new mysqli_sql_exception("Error preparando cierre de período: " . $this->db->error);
                $stmt->bind_param('sss', $now, $actorId, $row['periodo_id']);
                $stmt->execute();
                $stmt->close();

                // 3) Alerta parto +117 desde primera monta (si existe)
                $primeraMonta = $this->getFechaPrimeraMonta($row['periodo_id']);
                if ($primeraMonta) {
                    $fechaParto = $this->dateAddDays($primeraMonta, 117);
                    $this->crearAlerta('PROX_PARTO_117', $row['periodo_id'], $periodo['hembra_id'], $fechaParto, 'Parto estimado a +117 días de la primera monta');
                }
            }
            elseif ($resultado === 'ENTRO_EN_CELO') {
                // Actualizar registro (resultado + obs + fecha_realizada)
                $sql = "UPDATE {$this->table}
                           SET resultado = ?, observaciones = ?, fecha_realizada = ?, updated_at = ?, updated_by = ?
                         WHERE revision_id = ? AND deleted_at IS NULL";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) throw new mysqli_sql_exception("Error preparando actualización (celo): " . $this->db->error);
                $stmt->bind_param('ssssss', $resultado, $observaciones, $now, $now, $actorId, $revisionId);
                if (!$stmt->execute()) {
                    $err = $stmt->error; $stmt->close();
                    throw new mysqli_sql_exception("Error al actualizar revisión (celo): " . $err);
                }
                $stmt->close();

                // Cerrar período
                $stmt = $this->db->prepare("UPDATE periodos_servicio SET estado_periodo='CERRADO', updated_at=?, updated_by=? WHERE periodo_id=? AND deleted_at IS NULL");
                if (!$stmt) throw new mysqli_sql_exception("Error preparando cierre de período (celo): " . $this->db->error);
                $stmt->bind_param('sss', $now, $actorId, $row['periodo_id']);
                $stmt->execute();
                $stmt->close();
            }
            elseif (in_array($resultado, ['SOSPECHA_PREÑEZ', 'SIN_SEÑALES'], true)) {
                $cicloActual = (int)$row['ciclo_control'];

                if ($cicloActual < 3) {
                    $nuevoCiclo = $cicloActual + 1;
                    $nuevaFecha = $this->dateAddDays($row['fecha_programada'], 21);

                    // Reagendar MISMA revisión: set fecha_programada = +21, ciclo_control = +1
                    $sql = "UPDATE {$this->table}
                               SET resultado = ?, observaciones = ?, fecha_programada = ?, ciclo_control = ?, 
                                   fecha_realizada = NULL, updated_at = ?, updated_by = ?
                             WHERE revision_id = ? AND deleted_at IS NULL";
                    $stmt = $this->db->prepare($sql);
                    if (!$stmt) throw new mysqli_sql_exception("Error preparando reagendado (sospecha/sin_señales): " . $this->db->error);
                    $stmt->bind_param('sssisss', $resultado, $observaciones, $nuevaFecha, $nuevoCiclo, $now, $actorId, $revisionId);
                    if (!$stmt->execute()) {
                        $err = $stmt->error; $stmt->close();
                        throw new mysqli_sql_exception("Error al reagendar revisión: " . $err);
                    }
                    $stmt->close();

                    // Crear alerta para la nueva revisión
                    $this->crearAlerta('REVISION_20_21', $row['periodo_id'], $periodo['hembra_id'], $nuevaFecha, "Ciclo ".$nuevoCiclo);
                } else {
                    // En ciclo 3: solo actualizar resultado/obs; no se reagenda ni alerta
                    $sql = "UPDATE {$this->table}
                               SET resultado = ?, observaciones = ?, updated_at = ?, updated_by = ?
                             WHERE revision_id = ? AND deleted_at IS NULL";
                    $stmt = $this->db->prepare($sql);
                    if (!$stmt) throw new mysqli_sql_exception("Error preparando actualización en ciclo 3: " . $this->db->error);
                    $stmt->bind_param('sssss', $resultado, $observaciones, $now, $actorId, $revisionId);
                    if (!$stmt->execute()) {
                        $err = $stmt->error; $stmt->close();
                        throw new mysqli_sql_exception("Error al actualizar revisión (ciclo 3): " . $err);
                    }
                    $stmt->close();
                }
            }
            else {
                // Si en el futuro llegan otros estados válidos, simplemente se persisten.
                $sql = "UPDATE {$this->table}
                           SET resultado = ?, observaciones = ?, updated_at = ?, updated_by = ?
                         WHERE revision_id = ? AND deleted_at IS NULL";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) throw new mysqli_sql_exception("Error preparando actualización genérica: " . $this->db->error);
                $stmt->bind_param('sssss', $resultado, $observaciones, $now, $actorId, $revisionId);
                if (!$stmt->execute()) {
                    $err = $stmt->error; $stmt->close();
                    throw new mysqli_sql_exception("Error al actualizar revisión: " . $err);
                }
                $stmt->close();
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /** Soft delete (usa deleted_at/deleted_by en la nueva estructura). */
    public function eliminar(string $revisionId): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId     = $this->getActorIdFallback($revisionId);

        $sql = "UPDATE {$this->table}
                SET deleted_at = ?, deleted_by = ?
                WHERE revision_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new mysqli_sql_exception("Error preparando eliminación: " . $this->db->error);

        $stmt->bind_param('sss', $now, $actorId, $revisionId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
