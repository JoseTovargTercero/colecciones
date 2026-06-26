<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';
require_once __DIR__ . '/../helpers/NotificationTemplateHelper.php';
// (Opcional) si tienes autoload puedes omitirlo; si no, ajusta la ruta:
if (is_file(__DIR__ . '/NotificationModel.php')) {
    require_once __DIR__ . '/NotificationModel.php';
}

class AnimalMovimientoModel
{
    private $db;
    private $table = 'animal_movimientos';
    /** @var NotificationModel|null */
    private $notificationModel = null;

    public function __construct()
    {
        $this->db = Database::getInstance();

        // Instancia opcional del NotificationModel si existe
        if (class_exists('NotificationModel')) {
            try {
                $this->notificationModel = new NotificationModel();
            } catch (\Throwable $e) {
                // Si falla no bloquea el flujo; usaremos INSERT directo
                $this->notificationModel = null;
            }
        }
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

    private function validarFecha(string $ymd, string $campo = 'fecha'): void
    {
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

    private function fincaExiste(?string $id): bool
    {
        if ($id === null)
            return true;
        $sql = "SELECT 1 FROM fincas WHERE finca_id = ? AND deleted_at IS NULL LIMIT 1";
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
    private function apriscoExiste(?string $id): bool
    {
        if ($id === null)
            return true;
        $sql = "SELECT finca_id FROM apriscos WHERE aprisco_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception($this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return !!$row;
    }
    private function areaExiste(?string $id): bool
    {
        if ($id === null)
            return true;
        $sql = "SELECT aprisco_id FROM areas WHERE area_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception($this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return !!$row;
    }
    private function recintoExiste(?string $id): bool
    {
        if ($id === null)
            return true;
        $sql = "SELECT area_id FROM recintos WHERE recinto_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception($this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return !!$row;
    }

    private function validarJerarquia(?string $fincaId, ?string $apriscoId, ?string $areaId, ?string $recintoId): void
    {
        // Validar cadena desde recinto -> área -> aprisco -> finca
        if ($recintoId !== null) {
            $sql = "SELECT r.area_id, a.aprisco_id, ap.finca_id
                    FROM recintos r
                    JOIN areas a     ON a.area_id = r.area_id
                    JOIN apriscos ap ON ap.aprisco_id = a.aprisco_id
                    WHERE r.recinto_id = ? AND r.deleted_at IS NULL AND a.deleted_at IS NULL AND ap.deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception($this->db->error);
            $stmt->bind_param('s', $recintoId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if (!$row)
                throw new InvalidArgumentException("El recinto no existe o está eliminado.");
            if ($areaId !== null && $row['area_id'] !== $areaId)
                throw new InvalidArgumentException("El recinto no pertenece al área indicada.");
            if ($apriscoId !== null && $row['aprisco_id'] !== $apriscoId)
                throw new InvalidArgumentException("El recinto no pertenece al aprisco indicado.");
            if ($fincaId !== null && $row['finca_id'] !== $fincaId)
                throw new InvalidArgumentException("El recinto no pertenece a la finca indicada.");
        }

        if ($areaId !== null) {
            $sql = "SELECT ap.aprisco_id, ap.finca_id
                    FROM areas a JOIN apriscos ap ON ap.aprisco_id = a.aprisco_id
                    WHERE a.area_id = ? AND a.deleted_at IS NOT NULL IS FALSE";
            // (mantengo el original; si tu motor no acepta IS NOT NULL IS FALSE, reemplaza por 'AND a.deleted_at IS NULL')
            $sql = "SELECT ap.aprisco_id, ap.finca_id
                    FROM areas a JOIN apriscos ap ON ap.aprisco_id = a.aprisco_id
                    WHERE a.area_id = ? AND a.deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception($this->db->error);
            $stmt->bind_param('s', $areaId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if (!$row)
                throw new InvalidArgumentException("El área no existe o está eliminada.");
            if ($apriscoId !== null && $row['aprisco_id'] !== $apriscoId) {
                throw new InvalidArgumentException("El área no pertenece al aprisco indicado.");
            }
            if ($fincaId !== null && $row['finca_id'] !== $fincaId) {
                throw new InvalidArgumentException("El área no pertenece a la finca indicada.");
            }
        }

        if ($apriscoId !== null) {
            $sql = "SELECT finca_id FROM apriscos WHERE aprisco_id = ? AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception($this->db->error);
            $stmt->bind_param('s', $apriscoId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if (!$row)
                throw new InvalidArgumentException("El aprisco no existe o está eliminado.");
            if ($fincaId !== null && $row['finca_id'] !== $fincaId) {
                throw new InvalidArgumentException("El aprisco no pertenece a la finca indicada.");
            }
        }
    }

    /**
     * Método interno para generar una notificación a partir de un template.
     * Usa NotificationTemplateHelper y, si está disponible, NotificationModel.
     * En caso contrario, hace INSERT directo en `notifications`.
     */
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


    /**
     * Invoca validación de compatibilidad para TRASLADO, genera alerta y notificación si aplica.
     */
    private function validarCompatibilidadTraslado(
        string $animalId,
        ?string $fDes,
        ?string $aDes,
        ?string $arDes,
        ?string $rDes,
        string $fechaMov,
        string $movId,
        string $actorId,
        ?string $role = null // ← opcional, retrocompatible
    ): void {
        $mysqli = $this->db; // mysqli
        $windowDays = 30;
        $warnCount = 0;
        $detalles = [];

        // ===== Helpers locales para nombres legibles =====
        $resolveScalar = function (?mysqli_stmt $stmt): ?string {
            if (!$stmt)
                return null;
            if (!$stmt->execute()) {
                $e = $stmt->error;
                $stmt->close();
                throw new mysqli_sql_exception($e);
            }
            $res = $stmt->get_result();
            $val = $res && ($row = $res->fetch_row()) ? (string) $row[0] : null;
            if ($res)
                $res->free();
            $stmt->close();
            return $val;
        };

        $getAnimalIdent = function (string $id) use ($mysqli, $resolveScalar): string {
            // Toma el primer identificador disponible según tu esquema
            $sql = <<<'SQL'
SELECT COALESCE(identificador, animal_id)
FROM animales
WHERE animal_id = ?
LIMIT 1
SQL;
            $stmt = $mysqli->prepare($sql);
            if ($stmt)
                $stmt->bind_param('s', $id);
            return $resolveScalar($stmt) ?: $id;
        };

        $getNombre = function (?string $id, string $tabla, string $col, string $idCol) use ($mysqli, $resolveScalar): ?string {
            if (empty($id))
                return null;
            // Usamos placeholders para tabla/columna en el SQL dinámico solo a nivel de string;
            // asumiendo que $tabla y $col vienen de un set controlado arriba.
            $sql = 'SELECT ' . $col . ' FROM ' . $tabla . ' WHERE ' . $idCol . ' = ? LIMIT 1';
            $stmt = $mysqli->prepare($sql);
            if ($stmt)
                $stmt->bind_param('s', $id);
            return $resolveScalar($stmt);
        };

        $animalIdent = $getAnimalIdent($animalId);
        // Línea 318 (Ajustada)
        $fincaNom = $getNombre($fDes, 'fincas', 'nombre', 'finca_id');
        // Línea 319 (Ajustada)
        $apriscoNom = $getNombre($aDes, 'apriscos', 'nombre', 'aprisco_id');
        // Línea 320 (Ajustada)
        $areaNom = $getNombre($arDes, 'areas', 'nombre_personalizado', 'area_id');
        // Línea 321 (Ajustada)
        $recintoNom = $getNombre($rDes, 'recintos', 'codigo_recinto', 'recinto_id');

        $destinoNombre = trim(implode(' · ', array_filter([
            $fincaNom ?: $fDes,
            $apriscoNom ?: $aDes,
            $areaNom ?: $arDes,
            $recintoNom ?: $rDes
        ], fn($v) => !empty($v))));

        // Calcular fechaDesde = fechaMov - 30 días (en SQL para mantener TZ/DB)
        $stmt = $mysqli->prepare('SELECT DATE_SUB(?, INTERVAL ? DAY)');
        if (!$stmt)
            throw new mysqli_sql_exception('Error preparar cálculo fechaDesde: ' . $mysqli->error);
        $stmt->bind_param('si', $fechaMov, $windowDays);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new mysqli_sql_exception('Error calcular fechaDesde: ' . $err);
        }
        $res = $stmt->get_result();
        $row = $res->fetch_row();
        $fechaDesde = $row ? (string) $row[0] : null;
        if ($res)
            $res->free();
        $stmt->close();
        if ($fechaDesde === null)
            throw new mysqli_sql_exception('No se pudo calcular fechaDesde.');

        // ===== Transacción =====
        $mysqli->begin_transaction();
        try {
            // 1) Agresividad propia
            $sql = <<<'SQL'
SELECT EXISTS(
    SELECT 1
    FROM incidencias i
    WHERE i.deleted_at IS NULL
      AND i.animal_id = ?
      AND i.tipo IN ('RINA','MORDIDA')
      AND i.fecha_evento BETWEEN ? AND ?
)
SQL;
            $stmt = $mysqli->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception('Error preparar agresividad propia: ' . $mysqli->error);
            $stmt->bind_param('sss', $animalId, $fechaDesde, $fechaMov);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new mysqli_sql_exception($err);
            }
            $res = $stmt->get_result();
            $flag = (int) ($res->fetch_row()[0] ?? 0);
            if ($res)
                $res->free();
            $stmt->close();

            if ($flag === 1) {
                $warnCount++;
                $detalles[] = "• El animal a transferir presenta incidentes de agresión en los últimos {$windowDays} días.";
            }

            // 1.b) Agresividad residentes destino
            $mysqli->query('DROP TEMPORARY TABLE IF EXISTS tmp_residentes');
            if (!$mysqli->query('CREATE TEMPORARY TABLE tmp_residentes (animal_id CHAR(36) PRIMARY KEY) ENGINE=Memory')) {
                throw new mysqli_sql_exception('Error crear tmp_residentes: ' . $mysqli->error);
            }

            $where = ['am.deleted_at IS NULL', 'am.fecha_mov <= ?'];
            $params = [$fechaMov];
            $types = 's';

            if (!empty($fDes)) {
                $where[] = 'am.finca_destino_id = ?';
                $params[] = $fDes;
                $types .= 's';
            }
            if (!empty($aDes)) {
                $where[] = 'am.aprisco_destino_id = ?';
                $params[] = $aDes;
                $types .= 's';
            }
            if (!empty($arDes)) {
                $where[] = 'am.area_destino_id = ?';
                $params[] = $arDes;
                $types .= 's';
            }
            if (!empty($rDes)) {
                $where[] = 'am.recinto_id_destino = ?';
                $params[] = $rDes;
                $types .= 's';
            }

            $whereSql = implode(' AND ', $where);
            $sqlInsertTmp = <<<SQL
INSERT INTO tmp_residentes (animal_id)
SELECT DISTINCT am.animal_id
FROM animal_movimientos am
WHERE {$whereSql}
  AND NOT EXISTS (
      SELECT 1
      FROM animal_movimientos am2
      WHERE am2.deleted_at IS NULL
        AND am2.animal_id = am.animal_id
        AND am2.fecha_mov > am.fecha_mov
        AND am2.fecha_mov <= ?
  )
SQL;
            $params2 = $params;
            $types2 = $types . 's';
            $params2[] = $fechaMov;

            $stmt = $mysqli->prepare($sqlInsertTmp);
            if (!$stmt)
                throw new mysqli_sql_exception('Error preparar tmp_residentes: ' . $mysqli->error);
            $stmt->bind_param($types2, ...$params2);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new mysqli_sql_exception($err);
            }
            $stmt->close();

            // eliminar al propio animal
            $stmt = $mysqli->prepare('DELETE FROM tmp_residentes WHERE animal_id = ?');
            if (!$stmt)
                throw new mysqli_sql_exception('Error limpiar tmp_residentes: ' . $mysqli->error);
            $stmt->bind_param('s', $animalId);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new mysqli_sql_exception($err);
            }
            $stmt->close();

            // ¿Residentes con agresividad?
            $sql = <<<'SQL'
SELECT EXISTS(
    SELECT 1
    FROM incidencias i
    JOIN tmp_residentes r ON r.animal_id = i.animal_id
    WHERE i.deleted_at IS NULL
      AND i.tipo IN ('RINA','MORDIDA')
      AND i.fecha_evento BETWEEN ? AND ?
)
SQL;
            $stmt = $mysqli->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception('Error preparar agresividad residentes: ' . $mysqli->error);
            $stmt->bind_param('ss', $fechaDesde, $fechaMov);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new mysqli_sql_exception($err);
            }
            $res = $stmt->get_result();
            $flag = (int) ($res->fetch_row()[0] ?? 0);
            if ($res)
                $res->free();
            $stmt->close();

            if ($flag === 1) {
                $warnCount++;
                $detalles[] = "• En el destino hay residentes con incidentes de agresión en los últimos {$windowDays} días.";
            }

            // 2) Alerta de peso activa
            $sql = <<<'SQL'
SELECT EXISTS(
    SELECT 1
    FROM alertas a
    WHERE a.deleted_at IS NULL
      AND a.estado_alerta = 'PENDIENTE'
      AND a.tipo_alerta   = 'PESO_FUERA_RANGO'
      AND a.animal_id = ?
)
SQL;
            $stmt = $mysqli->prepare($sql);
            if (!$stmt)
                throw new mysqli_sql_exception('Error preparar check peso: ' . $mysqli->error);
            $stmt->bind_param('s', $animalId);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new mysqli_sql_exception($err);
            }
            $res = $stmt->get_result();
            $flag = (int) ($res->fetch_row()[0] ?? 0);
            if ($res)
                $res->free();
            $stmt->close();

            if ($flag === 1) {
                $warnCount++;
                $detalles[] = '• El animal presenta alerta activa de PESO_FUERA_RANGO.';
            }

            // 3) Generación de alerta + notificación si hay hallazgos
            if ($warnCount > 0) {
                // idempotencia: borrar alerta previa del mismo día
                $sql = <<<'SQL'
DELETE FROM alertas
WHERE deleted_at IS NULL
  AND tipo_alerta = 'COMPATIBILIDAD_ANIMAL'
  AND animal_id = ?
  AND DATE(fecha_objetivo) = DATE(?)
SQL;
                $stmt = $mysqli->prepare($sql);
                if (!$stmt)
                    throw new mysqli_sql_exception('Error preparar delete alerta previa: ' . $mysqli->error);
                $stmt->bind_param('ss', $animalId, $fechaMov);
                if (!$stmt->execute()) {
                    $err = $stmt->error;
                    $stmt->close();
                    throw new mysqli_sql_exception($err);
                }
                $stmt->close();

                // detalle para la alerta
                $encabezado = [];
                $encabezado[] = '• Movimiento: ' . $movId . ' — Fecha: ' . date('Y-m-d', strtotime($fechaMov));
                $encabezado[] = '• Destino -> Finca: ' . ($fincaNom ?? $fDes ?? '-')
                    . ', Aprisco: ' . ($apriscoNom ?? $aDes ?? '-')
                    . ', Área: ' . ($areaNom ?? $arDes ?? '-')
                    . ', Recinto: ' . ($recintoNom ?? $rDes ?? '-');
                $detalleTexto = implode("\n", $encabezado);
                if (!empty($detalles))
                    $detalleTexto .= "\n" . implode("\n", $detalles);

                // insert alerta
                $alertaId = \UuidHelper::generateUUIDv4();
                $now = date('Y-m-d H:i:s');
                $sql = <<<'SQL'
INSERT INTO alertas
    (alerta_id, tipo_alerta, fecha_objetivo, periodo_id, animal_id, detalle, estado_alerta,
     created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
VALUES
    (?, 'COMPATIBILIDAD_ANIMAL', ?, NULL, ?, ?, 'PENDIENTE',
     ?, ?, NULL, NULL, NULL, NULL)
SQL;
                $stmt = $mysqli->prepare($sql);
                if (!$stmt)
                    throw new mysqli_sql_exception('Error preparar insert alerta: ' . $mysqli->error);
                $stmt->bind_param('ssssss', $alertaId, $fechaMov, $animalId, $detalleTexto, $now, $actorId);
                if (!$stmt->execute()) {
                    $err = $stmt->error;
                    $stmt->close();
                    throw new mysqli_sql_exception($err);
                }
                $stmt->close();

                // --- Notificación (plantilla transfer_compatibilidad_riesgo) ---
                // motivo_riesgo = bullets en una sola línea
                $motivo = trim(preg_replace('/\s+/', ' ', implode(' ', array_map(
                    fn($l) => ltrim($l, "• \t"),
                    $detalles
                ))));
                $params = [
                    'animal_identificador' => $animalIdent,
                    'destino_nombre' => $destinoNombre !== '' ? $destinoNombre : '-',
                    'motivo_riesgo' => $motivo !== '' ? $motivo : 'Hallazgos de riesgo en validación',
                ];
                $route = '/transferencias?mov_id=' . urlencode($movId);

                // Llamada compatible
                $this->notificar(
                    'transfer_compatibilidad_riesgo',
                    $params,
                    $route,
                    null,
                    $actorId,
                    $role
                );
            }

            // Limpieza y commit
            $mysqli->query('DROP TEMPORARY TABLE IF EXISTS tmp_residentes');
            $mysqli->commit();
        } catch (\Throwable $e) {
            $mysqli->query('DROP TEMPORARY TABLE IF EXISTS tmp_residentes');
            $mysqli->rollback();
            throw $e;
        }
    }

    /* ========= Lecturas ========= */

    /**
     * Filtros: animal_id, tipo_movimiento, motivo, estado, fecha_desde..fecha_hasta,
     *          origen/destino (finca/aprisco/area/recinto), incluirEliminados
     */
    public function listar(
        int $limit = 10000,
        int $offset = 0,
        bool $incluirEliminados = false,
        ?string $animalId = null,
        ?string $tipo = null,
        ?string $motivo = null,
        ?string $estado = null,
        ?string $desde = null,
        ?string $hasta = null,
        ?string $fincaOri = null,
        ?string $apriscoOri = null,
        ?string $areaOri = null,
        ?string $recintoOri = null,
        ?string $fincaDes = null,
        ?string $apriscoDes = null,
        ?string $areaDes = null,
        ?string $recintoDes = null
    ): array {
        $w = [];
        $p = [];
        $t = '';

        $w[] = $incluirEliminados ? 'm.deleted_at IS NOT NULL OR m.deleted_at IS NULL' : 'm.deleted_at IS NULL';
        if ($animalId) {
            $w[] = 'm.animal_id = ?';
            $p[] = $animalId;
            $t .= 's';
        }
        if ($tipo) {
            $w[] = 'm.tipo_movimiento = ?';
            $p[] = $this->validarEnum($tipo, ['INGRESO', 'EGRESO', 'TRASLADO', 'VENTA', 'COMPRA', 'NACIMIENTO', 'MUERTE', 'OTRO'], 'tipo_movimiento');
            $t .= 's';
        }
        if ($motivo) {
            $w[] = 'm.motivo = ?';
            $p[] = $this->validarEnum($motivo, ['TRASLADO', 'INGRESO', 'EGRESO', 'AISLAMIENTO', 'VENTA', 'OTRO'], 'motivo');
            $t .= 's';
        }
        if ($estado) {
            $w[] = 'm.estado = ?';
            $p[] = $this->validarEnum($estado, ['REGISTRADO', 'ANULADO'], 'estado');
            $t .= 's';
        }
        if ($desde) {
            $this->validarFecha($desde, 'desde');
            $w[] = 'm.fecha_mov >= ?';
            $p[] = $desde;
            $t .= 's';
        }
        if ($hasta) {
            $this->validarFecha($hasta, 'hasta');
            $w[] = 'm.fecha_mov <= ?';
            $p[] = $hasta;
            $t .= 's';
        }

        if ($fincaOri) {
            $w[] = 'm.finca_origen_id      = ?';
            $p[] = $fincaOri;
            $t .= 's';
        }
        if ($apriscoOri) {
            $w[] = 'm.aprisco_origen_id    = ?';
            $p[] = $apriscoOri;
            $t .= 's';
        }
        if ($areaOri) {
            $w[] = 'm.area_origen_id       = ?';
            $p[] = $areaOri;
            $t .= 's';
        }
        if ($recintoOri) {
            $w[] = 'm.recinto_id_origen    = ?';
            $p[] = $recintoOri;
            $t .= 's';
        }

        if ($fincaDes) {
            $w[] = 'm.finca_destino_id     = ?';
            $p[] = $fincaDes;
            $t .= 's';
        }
        if ($apriscoDes) {
            $w[] = 'm.aprisco_destino_id   = ?';
            $p[] = $apriscoDes;
            $t .= 's';
        }
        if ($areaDes) {
            $w[] = 'm.area_destino_id      = ?';
            $p[] = $areaDes;
            $t .= 's';
        }
        if ($recintoDes) {
            $w[] = 'm.recinto_id_destino   = ?';
            $p[] = $recintoDes;
            $t .= 's';
        }

        $where = implode(' AND ', $w);

        $sql = "SELECT
                    m.animal_movimiento_id, m.animal_id, a.identificador AS animal_identificador,
                    m.fecha_mov, m.tipo_movimiento, m.motivo, m.estado, m.costo, m.documento_ref,
                    m.finca_origen_id, fo.nombre AS finca_origen,
                    m.aprisco_origen_id, ao.nombre AS aprisco_origen,
                    m.area_origen_id, aro.nombre_personalizado AS area_origen_nombre, aro.numeracion AS area_origen_nro,
                    m.recinto_id_origen,
                    ro.codigo_recinto AS codigo_recinto_origen,
                    m.finca_destino_id, fd.nombre AS finca_destino,
                    m.aprisco_destino_id, ad.nombre AS aprisco_destino,
                    m.area_destino_id, ard.nombre_personalizado AS area_destino_nombre, ard.numeracion AS area_destino_nro,
                    m.recinto_id_destino,
                    rd.codigo_recinto AS codigo_recinto_destino,
                    m.observaciones,
                    m.created_at, m.created_by, m.updated_at, m.updated_by
                FROM {$this->table} m
                LEFT JOIN animales a ON a.animal_id = m.animal_id
                LEFT JOIN fincas   fo ON fo.finca_id      = m.finca_origen_id
                LEFT JOIN apriscos ao ON ao.aprisco_id    = m.aprisco_origen_id
                LEFT JOIN areas    aro ON aro.area_id     = m.area_origen_id
                LEFT JOIN recintos ro ON ro.recinto_id    = m.recinto_id_origen
                LEFT JOIN fincas   fd ON fd.finca_id      = m.finca_destino_id
                LEFT JOIN apriscos ad ON ad.aprisco_id    = m.aprisco_destino_id
                LEFT JOIN areas    ard ON ard.area_id     = m.area_destino_id
                LEFT JOIN recintos rd ON rd.recinto_id    = m.recinto_id_destino
                WHERE {$where}
                ORDER BY m.fecha_mov DESC, m.created_at DESC
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
        $sql = "SELECT
                    m.*,
                    a.identificador AS animal_identificador,
                    ro.codigo_recinto AS codigo_recinto_origen,
                    rd.codigo_recinto AS codigo_recinto_destino
                FROM {$this->table} m
                LEFT JOIN animales a ON a.animal_id = m.animal_id
                LEFT JOIN recintos ro ON ro.recinto_id = m.recinto_id_origen
                LEFT JOIN recintos rd ON rd.recinto_id = m.recinto_id_destino
                WHERE m.animal_movimiento_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error preparar consulta: " . $this->db->error);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /* ========= Escrituras ========= */

    /**
     * Reglas por tipo_movimiento:
     * - INGRESO/COMPRA/NACIMIENTO: requiere destino (al menos finca/aprisco/area/recinto) — origen opcional
     * - EGRESO/VENTA/MUERTE:       requiere origen  (al menos finca/aprisco/area/recinto) — destino opcional
     * - TRASLADO:                  requiere origen y destino (al menos finca/aprisco/area/recinto)
     */
    private function validarReglasTipo(
        string $tipo,
        ?string $fOri,
        ?string $aOri,
        ?string $arOri,
        ?string $rOri,
        ?string $fDes,
        ?string $aDes,
        ?string $arDes,
        ?string $rDes
    ): void {
        $t = $tipo;
        $hayOri = $fOri || $aOri || $arOri || $rOri;
        $hayDes = $fDes || $aDes || $arDes || $rDes;

        if (in_array($t, ['INGRESO', 'COMPRA', 'NACIMIENTO'], true)) {
            if (!$hayDes)
                throw new InvalidArgumentException("Para $t es obligatorio indicar destino (finca/aprisco/area/recinto).");
        } elseif (in_array($t, ['EGRESO', 'VENTA', 'MUERTE'], true)) {
            if (!$hayOri)
                throw new InvalidArgumentException("Para $t es obligatorio indicar origen (finca/aprisco/area/recinto).");
        } elseif ($t === 'TRASLADO') {
            if (!$hayOri || !$hayDes)
                throw new InvalidArgumentException("TRASLADO requiere origen y destino.");
        }
    }

    public function crear(array $data): string
    {
        foreach (['animal_id', 'fecha_mov', 'tipo_movimiento'] as $k) {
            if (!isset($data[$k]) || $data[$k] === '') {
                throw new InvalidArgumentException("Falta campo requerido: {$k}.");
            }
        }

        $animalId = (string) trim($data['animal_id']);
        if (!$this->animalExiste($animalId)) {
            throw new RuntimeException('El animal especificado no existe o está eliminado.');
        }

        $fechaMov = (string) trim($data['fecha_mov']);
        $this->validarFecha($fechaMov, 'fecha_mov');

        $tipo = $this->validarEnum((string) $data['tipo_movimiento'], ['INGRESO', 'EGRESO', 'TRASLADO', 'VENTA', 'COMPRA', 'NACIMIENTO', 'MUERTE', 'OTRO'], 'tipo_movimiento');
        $motivo = isset($data['motivo']) ? $this->validarEnum((string) $data['motivo'], ['TRASLADO', 'INGRESO', 'EGRESO', 'AISLAMIENTO', 'VENTA', 'OTRO'], 'motivo') : 'OTRO';
        $estado = isset($data['estado']) ? $this->validarEnum((string) $data['estado'], ['REGISTRADO', 'ANULADO'], 'estado') : 'REGISTRADO';

        // Origen/destino (permitir null)
        $fOri = $data['finca_origen_id'] ?? null;
        $aOri = $data['aprisco_origen_id'] ?? null;
        $arOri = $data['area_origen_id'] ?? null;
        $rOri = $data['recinto_id_origen'] ?? null;

        $fDes = $data['finca_destino_id'] ?? null;
        $aDes = $data['aprisco_destino_id'] ?? null;
        $arDes = $data['area_destino_id'] ?? null;
        $rDes = $data['recinto_id_destino'] ?? null;

        if (
            !$this->fincaExiste($fOri) || !$this->apriscoExiste($aOri) || !$this->areaExiste($arOri) || !$this->recintoExiste($rOri) ||
            !$this->fincaExiste($fDes) || !$this->apriscoExiste($aDes) || !$this->areaExiste($arDes) || !$this->recintoExiste($rDes)
        ) {
            throw new RuntimeException('Finca/aprisco/área/recinto (origen/destino) no existen o están eliminados.');
        }

        // Consistencia jerárquica
        $this->validarJerarquia($fOri, $aOri, $arOri, $rOri);
        $this->validarJerarquia($fDes, $aDes, $arDes, $rDes);

        // Reglas por tipo
        $this->validarReglasTipo($tipo, $fOri, $aOri, $arOri, $rOri, $fDes, $aDes, $arDes, $rDes);

        // costo nullable
        $costo = (isset($data['costo']) && $data['costo'] !== '' && $data['costo'] !== null) ? (string) (float) $data['costo'] : null;

        $documento = isset($data['documento_ref']) ? trim((string) $data['documento_ref']) : null;
        $obs = isset($data['observaciones']) ? trim((string) $data['observaciones']) : null;

        $this->db->begin_transaction();
        try {
            [$now, $env] = $this->nowWithAudit();
            $uuid = UuidHelper::generateUUIDv4();
            $actorId = $_SESSION['user_id'] ?? $uuid;

            $sql = "INSERT INTO {$this->table}
                (animal_movimiento_id, animal_id, fecha_mov, tipo_movimiento, motivo, estado,
                 finca_origen_id, aprisco_origen_id, area_origen_id, recinto_id_origen,
                 finca_destino_id, aprisco_destino_id, area_destino_id, recinto_id_destino,
                 costo, documento_ref, observaciones,
                 created_at, created_by, updated_at, updated_by, deleted_at, deleted_by)
                VALUES (?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, NULL, NULL, NULL, NULL)";

            // Contar placeholders (deben ser 19)
            $placeholders = substr_count($sql, '?'); // 19 esperados
            if ($placeholders !== 19) {
                throw new RuntimeException("SQL mal formado: se esperaban 19 placeholders, hay {$placeholders}.");
            }

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new mysqli_sql_exception("Error preparar inserción: " . $this->db->error);
            }

            // Usar todos 's' para evitar problemas con NULL en 'd'
            $types = str_repeat('s', 19);

            $stmt->bind_param(
                $types,
                $uuid,
                $animalId,
                $fechaMov,
                $tipo,
                $motivo,
                $estado,
                $fOri,
                $aOri,
                $arOri,
                $rOri,
                $fDes,
                $aDes,
                $arDes,
                $rDes,
                $costo,
                $documento,
                $obs,
                $now,
                $actorId
            );

            if (!$stmt->execute()) {
                $err = strtolower($stmt->error);
                $stmt->close();
                $this->db->rollback();

                if (str_contains($err, 'foreign key')) {
                    throw new RuntimeException('Violación de clave foránea (animal/origen/destino).');
                }
                throw new mysqli_sql_exception("Error al ejecutar inserción: " . $err);
            }
            $stmt->close();

            // === Validación de compatibilidad (SOLO para TRASLADO con destino informado) ===
            if ($tipo === 'TRASLADO' && ($fDes || $aDes || $arDes || $rDes)) {
                $this->validarCompatibilidadTraslado($animalId, $fDes, $aDes, $arDes, $rDes, $fechaMov, $uuid, $actorId);
            }

            $this->db->commit();
            return $uuid;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function actualizar(string $id, array $data): bool
    {
        $row = $this->obtenerPorId($id);
        if (!$row || $row['deleted_at'] !== null) {
            throw new RuntimeException("Movimiento no existe o está eliminado.");
        }

        $campos = [];
        $params = [];
        $types = '';

        if (isset($data['fecha_mov'])) {
            $this->validarFecha((string) $data['fecha_mov'], 'fecha_mov');
            $campos[] = 'fecha_mov = ?';
            $params[] = (string) $data['fecha_mov'];
            $types .= 's';
        }
        if (isset($data['tipo_movimiento'])) {
            $tipo = $this->validarEnum((string) $data['tipo_movimiento'], ['INGRESO', 'EGRESO', 'TRASLADO', 'VENTA', 'COMPRA', 'NACIMIENTO', 'MUERTE', 'OTRO'], 'tipo_movimiento');
            $campos[] = 'tipo_movimiento = ?';
            $params[] = $tipo;
            $types .= 's';
        } else {
            $tipo = $row['tipo_movimiento'];
        }

        if (isset($data['motivo'])) {
            $motivo = $this->validarEnum((string) $data['motivo'], ['TRASLADO', 'INGRESO', 'EGRESO', 'AISLAMIENTO', 'VENTA', 'OTRO'], 'motivo');
            $campos[] = 'motivo = ?';
            $params[] = $motivo;
            $types .= 's';
        }
        if (isset($data['estado'])) {
            $estado = $this->validarEnum((string) $data['estado'], ['REGISTRADO', 'ANULADO'], 'estado');
            $campos[] = 'estado = ?';
            $params[] = $estado;
            $types .= 's';
        }

        // Posibles cambios de origen/destino
        $fOri = array_key_exists('finca_origen_id', $data) ? ($data['finca_origen_id'] ?? null) : $row['finca_origen_id'];
        $aOri = array_key_exists('aprisco_origen_id', $data) ? ($data['aprisco_origen_id'] ?? null) : $row['aprisco_origen_id'];
        $arOri = array_key_exists('area_origen_id', $data) ? ($data['area_origen_id'] ?? null) : $row['area_origen_id'];
        $rOri = array_key_exists('recinto_id_origen', $data) ? ($data['recinto_id_origen'] ?? null) : $row['recinto_id_origen'];

        $fDes = array_key_exists('finca_destino_id', $data) ? ($data['finca_destino_id'] ?? null) : $row['finca_destino_id'];
        $aDes = array_key_exists('aprisco_destino_id', $data) ? ($data['aprisco_destino_id'] ?? null) : $row['aprisco_destino_id'];
        $arDes = array_key_exists('area_destino_id', $data) ? ($data['area_destino_id'] ?? null) : $row['area_destino_id'];
        $rDes = array_key_exists('recinto_id_destino', $data) ? ($data['recinto_id_destino'] ?? null) : $row['recinto_id_destino'];

        // Si se cambió algo de FKs, validar:
        if (
            array_key_exists('finca_origen_id', $data) || array_key_exists('aprisco_origen_id', $data) ||
            array_key_exists('area_origen_id', $data) || array_key_exists('recinto_id_origen', $data) ||
            array_key_exists('finca_destino_id', $data) || array_key_exists('aprisco_destino_id', $data) ||
            array_key_exists('area_destino_id', $data) || array_key_exists('recinto_id_destino', $data)
        ) {
            if (
                !$this->fincaExiste($fOri) || !$this->apriscoExiste($aOri) || !$this->areaExiste($arOri) || !$this->recintoExiste($rOri) ||
                !$this->fincaExiste($fDes) || !$this->apriscoExiste($aDes) || !$this->areaExiste($arDes) || !$this->recintoExiste($rDes)
            ) {
                throw new RuntimeException('Finca/aprisco/área/recinto (origen/destino) no existen o están eliminados.');
            }
            $this->validarJerarquia($fOri, $aOri, $arOri, $rOri);
            $this->validarJerarquia($fDes, $aDes, $arDes, $rDes);
            $this->validarReglasTipo($tipo, $fOri, $aOri, $arOri, $rOri, $fDes, $aDes, $arDes, $rDes);
        }

        if (array_key_exists('finca_origen_id', $data)) {
            $campos[] = 'finca_origen_id = ?';
            $params[] = $fOri;
            $types .= 's';
        }
        if (array_key_exists('aprisco_origen_id', $data)) {
            $campos[] = 'aprisco_origen_id = ?';
            $params[] = $aOri;
            $types .= 's';
        }
        if (array_key_exists('area_origen_id', $data)) {
            $campos[] = 'area_origen_id = ?';
            $params[] = $arOri;
            $types .= 's';
        }
        if (array_key_exists('recinto_id_origen', $data)) {
            $campos[] = 'recinto_id_origen = ?';
            $params[] = $rOri;
            $types .= 's';
        }

        if (array_key_exists('finca_destino_id', $data)) {
            $campos[] = 'finca_destino_id = ?';
            $params[] = $fDes;
            $types .= 's';
        }
        if (array_key_exists('aprisco_destino_id', $data)) {
            $campos[] = 'aprisco_destino_id = ?';
            $params[] = $aDes;
            $types .= 's';
        }
        if (array_key_exists('area_destino_id', $data)) {
            $campos[] = 'area_destino_id = ?';
            $params[] = $arDes;
            $types .= 's';
        }
        if (array_key_exists('recinto_id_destino', $data)) {
            $campos[] = 'recinto_id_destino = ?';
            $params[] = $rDes;
            $types .= 's';
        }

        if (array_key_exists('costo', $data)) {
            $costo = $data['costo'] !== null ? (float) $data['costo'] : null;
            if ($costo !== null && ($costo < 0 || $costo > 999999.99)) {
                throw new InvalidArgumentException("costo fuera de rango.");
            }
            // usar 's' para permitir NULL sin warnings en bind
            $campos[] = 'costo = ?';
            $params[] = $costo !== null ? (string) $costo : null;
            $types .= 's';
        }
        if (array_key_exists('documento_ref', $data)) {
            $campos[] = 'documento_ref = ?';
            $params[] = $data['documento_ref'] !== null ? trim((string) $data['documento_ref']) : null;
            $types .= 's';
        }
        if (array_key_exists('observaciones', $data)) {
            $campos[] = 'observaciones = ?';
            $params[] = $data['observaciones'] !== null ? trim((string) $data['observaciones']) : null;
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

        $sql = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE animal_movimiento_id = ? AND deleted_at IS NULL";
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
            if (str_contains($err, 'foreign key'))
                throw new RuntimeException('Violación de clave foránea (origen/destino).');
            throw new mysqli_sql_exception("Error al actualizar: " . $err);
        }

        // === Revalidar compatibilidad si ahora es TRASLADO o si cambió alguno de los destinos ===
        $destinoCambio = array_key_exists('finca_destino_id', $data) || array_key_exists('aprisco_destino_id', $data)
            || array_key_exists('area_destino_id', $data) || array_key_exists('recinto_id_destino', $data)
            || array_key_exists('tipo_movimiento', $data) || array_key_exists('fecha_mov', $data);

        if ($destinoCambio) {
            $tMov = isset($data['tipo_movimiento']) ? $tipo : $row['tipo_movimiento'];
            $fDes2 = $fDes;
            $aDes2 = $aDes;
            $arDes2 = $arDes;
            $rDes2 = $rDes;
            $fecha = isset($data['fecha_mov']) ? (string) $data['fecha_mov'] : (string) $row['fecha_mov'];

            if ($tMov === 'TRASLADO' && ($fDes2 || $aDes2 || $arDes2 || $rDes2)) {
                $this->validarCompatibilidadTraslado($row['animal_id'], $fDes2, $aDes2, $arDes2, $rDes2, $fecha, $id, $actorId);
            }
        }

        return true;
    }

    public function eliminar(string $id): bool
    {
        [$now, $env] = $this->nowWithAudit();
        $actorId = $_SESSION['user_id'] ?? $id;

        $sql = "UPDATE {$this->table} SET deleted_at = ?, deleted_by = ? WHERE animal_movimiento_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            throw new mysqli_sql_exception("Error preparar eliminación: " . $this->db->error);
        $stmt->bind_param('sss', $now, $actorId, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
