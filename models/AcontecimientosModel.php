<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/FechasHelper.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';
require_once __DIR__ . '/../models/AnimalModel.php';

// === INICIO DE ADICIONES ===
// Clases requeridas del modelo de ejemplo
require_once __DIR__ . '/../helpers/NotificationTemplateHelper.php';
require_once __DIR__ . '/AlertaModel.php';
require_once __DIR__ . '/NotificationModel.php';
// === FIN DE ADICIONES ===


class AcontecimientosModel
{
    private $db;
    private $insert;

    // === INICIO DE ADICIONES ===
    private $notificationModel;
    private $alertaModel;
    // === FIN DE ADICIONES ===

    public function __construct()
    {
        $this->db = Database::getInstance();

        // === INICIO DE ADICIONES ===
        // Inicializar los nuevos modelos
        $this->alertaModel = new AlertaModel();
        $this->notificationModel = new NotificationModel();
        // === FIN DE ADICIONES ===

        $this->insert = [

            'vacunacion' => [
                'sql' => "INSERT INTO animal_salud (
                    animal_salud_id, animal_id, fecha_evento, tipo_evento, 
                    medicamento, dosis, observaciones, created_by
                ) VALUES (?, ?, ?, 'VACUNACION', ?, ?, ?, ?)",

                'params' => function ($animalId, $values, $userId, $reporte_id) {
                    return [
                        UuidHelper::generateUUIDv4(),
                        $animalId,
                        $values['vacuna_fecha'] ?? date('Y-m-d'),
                        $values['vacuna_nombre'] ?? 'desconocida',
                        $values['vacuna_dosis'] ?? '1',
                        $values['observacion'] ?? '',
                        $userId
                    ];
                },

                'types' => 'sssssss',
            ],
            'decesos' => [
                'sql' => "INSERT INTO animal_decesos (
                    deceso_id, animal_id, causa_probable, fecha, observacion, created_by
                ) VALUES (?, ?, ?, ?, ?, ?)",

                'types' => 'ssssss',

                'params' => function ($animalId, $values, $userId, $reporte_id) {
                    return [
                        UuidHelper::generateUUIDv4(),
                        $animalId,
                        $values['deceso_causa'] ?? null,
                        $values['deceso_fecha'] ?? null,
                        $values['observacion'] ?? null,
                        $userId
                    ];
                },
                'extra_sql' => [
                    [
                        'sql' => "UPDATE animales 
                        SET estado = 'INACTIVO', estado_causa = 'deceso' 
                        WHERE animal_id = ?",
                        'types' => 's',
                        'params' => function ($animalId) {
                            return [$animalId];
                        }
                    ]
                ]
            ],
            
            'beneficios' => [
                'sql' => "INSERT INTO beneficios (
                    beneficio_id, animal_id, fecha, kilogramos_tanda, ingreso_tanda, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?)",

                'types' => 'sssssss',

                'params' => function ($animalId, $values, $userId, $reporte_id) {
                    return [
                        UuidHelper::generateUUIDv4(),
                        $animalId,
                        date('Y-m-d'),
                        $values['kilogramos'] ?? null,
                        $values['ingreso'] ?? null,
                        $userId,
                        date('Y-m-d H:i:s')
                    ];
                },
                'extra_sql' => [
                    [
                        'sql' => "UPDATE animales 
                        SET estado = 'INACTIVO', estado_causa = 'beneficio' 
                        WHERE animal_id = ?",
                        'types' => 's',
                        'params' => function ($animalId) {
                            return [$animalId];
                        }
                    ]
                ]
            ],


            'revision' => [
                'sql' => "INSERT INTO animal_salud (
                    animal_salud_id, animal_id, fecha_evento, tipo_evento,
                    veterinario, observaciones, created_by
                ) VALUES (?, ?, ?, 'REVISION', ?, ?, ?)",

                'params' => function ($animalId, $values, $userId, $reporte_id) {
                    return [
                        UuidHelper::generateUUIDv4(),
                        $animalId,
                        $values['revision_fecha'] ?? date('Y-m-d'),
                        $values['revision_veterinario'] ?? 'desconocido',
                        $values['observacion'] ?? '',
                        $userId
                    ];
                },

                'types' => 'ssssss',
            ],

            'cuarentena' => [
                'sql' => "INSERT INTO animal_salud (
                    animal_salud_id, animal_id, fecha_evento, tipo_evento, proxima_revision, diagnostico, observaciones, created_by
                ) VALUES (?, ?, ?, 'CUARENTENA', ?, ?, ?, ?)",

                'params' => function ($animalId, $values, $userId, $reporte_id) {
                    return [
                        UuidHelper::generateUUIDv4(),
                        $animalId,
                        $values['cuarentena_inicio'] ?? date('Y-m-d'),
                        $values['cuarentena_fin'] ?? date('Y-m-d'),
                        $values['cuarentena_motivo'] ?? 'no especificado',
                        $values['observacion'] ?? '',
                        $userId
                    ];
                },

                'types' => 'sssssss'
            ],

            'tratamiento' => [
                'sql' => "INSERT INTO animal_salud (
                    animal_salud_id, animal_id, fecha_evento, tipo_evento,
                    medicamento, dosis, observaciones, created_by
                ) VALUES (?, ?, ?, 'TRATAMIENTO', ?, ?, ?, ?)",

                'params' => function ($animalId, $values, $userId, $reporte_id) {
                    return [
                        UuidHelper::generateUUIDv4(),
                        $animalId,
                        date('Y-m-d'),
                        $values['tratamiento_medicamento'] ?? 'desconocido',
                        $values['tratamiento_dosis'] ?? '1',
                        $values['observacion'] ?? '',
                        $userId
                    ];
                },

                'types' => 'ssssssss',
            ],

            'brote' => [
                'sql' => "INSERT INTO animal_salud (
                    animal_salud_id, animal_id, tipo_evento, diagnostico, severidad, fecha_evento, acontecimiento_id, observaciones, created_by
                ) VALUES (?, ?, 'ENFERMEDAD', ?, ?, ?, ?, ?, ?)",

                'params' => function ($animalId, $values, $userId, $reporte_id) {
                    return [
                        UuidHelper::generateUUIDv4(),
                        $animalId,
                        $values['brote_tipo'] ?? 'desconocido',
                        $values['brote_severidad'] ?? 0,
                        date('Y-m-d'),
                        $reporte_id ?? null,
                        $values['observacion'] ?? '',
                        $userId
                    ];
                },

                'types' => 'ssssssss',
            ],
        ];
    }

    // === INICIO DE ADICIONES ===
    // Helper de auditoría y helper de notificación copiados de IncidenciaModel

    /**
     * Configura el contexto de auditoría y obtiene la fecha/hora actual.
     * Devuelve [datetime, env, actorId, role]
     */
    private function nowWithAudit(): array
    {
        $env = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');
        // userId=0 si aún no hay sesión; lo importante es setear contexto y tz
        $uuid = UuidHelper::generateUUIDv4();
        $actorId = $_SESSION['user_id'] ?? $uuid;
        $role = (string) ($_SESSION['user_type'] ?? 'user'); // <-- Añadido para devolver rol

        $env->applyAuditContext($this->db, $actorId);
        $tzManager = new TimezoneManager($this->db);
        $tzManager->applyTimezone();

        // Devolvemos todo lo necesario
        return [$env->getCurrentDatetime(), $env, (string)$actorId, $role];
    }

    /**
     * Helper para despachar notificaciones usando NotificationModel
     */
    private function notificar(
        string $templateKey,
        array $params,
        ?string $route,
        ?string $legacyUnused = null, // ← se mantiene por retrocompatibilidad (no se usa)
        ?string $userId = null,       // ← 5to parámetro: user_id real (actor)
        ?string $role = null          // ← 6to parámetro: rol
    ): void {
        // 1) Módulo sugerido por la plantilla (fallback a 'acontecimientos')
        $meta = NotificationTemplateHelper::getMeta($templateKey);
        $module = $meta ? ($meta['module'] ?? 'acontecimientos') : 'acontecimientos';

        // 2) Resolver user_id y rol con fallback a sesión
        $finalUserId = $userId ?: (string) ($_SESSION['user_id'] ?? '0');
        $finalRole = $role ?: (string) ($_SESSION['user_type'] ?? 'user');

        // 3) Armar payload para NotificationModel->crear()
        $data_para_crear = [
            'template_key' => $templateKey,
            'template_params' => $params,
            'route' => $route,
            'module' => $module,
            'rol' => $finalRole,       // ← rol desde parámetro o sesión
            'user_id' => $finalUserId, // ← user_id desde parámetro o sesión
            // 'created_by' lo maneja internamente el modelo con el actor de sesión
        ];

        // 4) Guardar y despachar
        try {
            $this->notificationModel->crear($data_para_crear);
            // El modelo se encarga de: persistir, renderizar y disparar push si aplica
        } catch (Exception $e) {
            error_log("Error al crear notificación desde AcontecimientosModel::notificar(): " . $e->getMessage());
        }
    }
    // === FIN DE ADICIONES ===


    public function obtenerAnimalesUbicacion($tipo_ubicacion, $ubicacion_id): array
    {

        $AnimalModel = new AnimalModel();

        // Definir parámetros según el tipo de ubicación
        $params = [
            'fincaId'       => null,
            'apriscoId'     => null,
            'areaId'        => null,
        ];

        // Asignar el parámetro correcto según tipo_ubicacion
        switch ($tipo_ubicacion) {
            case 'fincas':
                $params['fincaId'] = $ubicacion_id;
                break;

            case 'apriscos':
                $params['apriscoId'] = $ubicacion_id;
                break;

            case 'areas':
                $params['areaId'] = $ubicacion_id;
                break;
        }



        // Llamada al método listar()
        $animales = $AnimalModel->listar(
            100,
            0,
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $params['fincaId'],
            $params['apriscoId'],
            $params['areaId'],
            null
        );

        $animal_ids = array_map(function ($animal) {
            return $animal['animal_id'];
        }, $animales);

        return $animal_ids;
    }


    public function registroAcontecimientos(array $values): ?string
    {
        $tipo = $values['tipo'] ?? null;
        if (!$tipo) {
            throw new InvalidArgumentException("Tipo requerido.");
        }

        if (!isset($this->insert[$tipo])) {
            throw new InvalidArgumentException("Tipo no soportado: " . $tipo);
        }

        $inserts = $this->insert[$tipo];
        $animales = $values['animales'] ?? [];
        if (empty($animales)) {
            throw new InvalidArgumentException("Debe seleccionar animales.");
        }

        $num_animales = count($animales);


        // === MODIFICACIÓN ===
        // Usar el helper de auditoría para obtener el user ID y setear el contexto
        [$now, $env, $userId, $role] = $this->nowWithAudit();
        // === FIN MODIFICACIÓN ===


        // Iniciar la transacción completa
        $this->db->begin_transaction();

        try {
            $reporte_id = $this->registrarReporte($values, $num_animales);

            foreach ($animales as $animalId) {

                //  INSERT PRINCIPAL DEL TIPO
                $stmt = $this->db->prepare($inserts['sql']);
                if (!$stmt) {
                    throw new RuntimeException("Error preparando insert principal: " . $this->db->error);
                }

                $params = $inserts['params']($animalId, $values, $userId, $reporte_id);

                $stmt->bind_param($inserts['types'], ...$params);

                if (!$stmt->execute()) {
                    throw new RuntimeException("Error ejecutando insert principal: " . $stmt->error);
                }

                $stmt->close();



                //  INSERTS EXTRA (si existen)
                if (!empty($inserts['extra_sql'])) {

                    foreach ($inserts['extra_sql'] as $extra) {

                        $stmtExtra = $this->db->prepare($extra['sql']);
                        if (!$stmtExtra) {
                            throw new RuntimeException("Error preparando extra insert: " . $this->db->error);
                        }

                        // Pasamos $userId por si algún extra_sql lo necesita
                        $extraParams = $extra['params']($animalId);

                        $stmtExtra->bind_param($extra['types'], ...$extraParams);

                        if (!$stmtExtra->execute()) {
                            throw new RuntimeException("Error ejecutando extra insert: " . $stmtExtra->error);
                        }

                        $stmtExtra->close();
                    }
                }
            }

            $this->db->commit();
            return $reporte_id; // Retornar el ID en lugar de true
        } catch (Throwable $e) {

            $this->db->rollback();
            throw $e;
        }
    }


    public function registrarReporte(array $values, $cantidades): string
    {
        // Datos base
        $fecha        = date('Y-m-d');
        $observacion  = $values['observacion'] ?? null;
        $tipo         = $values['tipo'] ?? null;
        $foto         = $values['foto_name'] ?? null;

        if (!$tipo) {
            throw new InvalidArgumentException("El tipo de acontecimiento es obligatorio.");
        }

        // Generar ID
        $uuid = UuidHelper::generateUUIDv4();
        [$now, $env, $userId, $role] = $this->nowWithAudit();

        // Campo dinámico según el tipo
        $campoCantidades = ($tipo === 'limpieza')
            ? 'areas_intervenidas'
            : 'animales_involucrados';


        $sql = "INSERT INTO acontecimiento_reportes (
                acontecimiento_id, tipo, fecha, observacion, {$campoCantidades}, foto, created_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException(
                "Error preparando inserción de reporte: {$this->db->error}"
            );
        }

        // Bind
        $stmt->bind_param(
            'sssssss',
            $uuid,
            $tipo,
            $fecha,
            $observacion,
            $cantidades,
            $foto,
            $userId
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException("Error al insertar reporte: {$error}");
        }

        $stmt->close();
        return $uuid;
    }



    public function registroAcontecimientoLimpieza(array $values): ?string
    {
        $limpieza_area   = $values['limpieza_area'] ?? [];
        $limpieza_fecha  = $values['limpieza_fecha'] ?? date('Y-m-d');

        // === MODIFICACIÓN ===
        [$now, $env, $userId, $role] = $this->nowWithAudit();
        // === FIN MODIFICACIÓN ===

        $foto_name       = $values['foto_name'] ?? null;
        $observacion     = $values['observacion'] ?? null;

        // obten el numero de valores de $limpieza_area
        $num_areas = count($limpieza_area);
        $reporte_id = $this->registrarReporte($values, $num_areas);

        // Verificar que haya areas seleccionadas
        if (empty($limpieza_area)) {
            throw new InvalidArgumentException("Debe seleccionar al menos una area intervenida.");
        }

        $this->db->begin_transaction();

        try {

            foreach ($limpieza_area as $area_id) {
                // Insertar el registro de limpiez
                $sql = "INSERT INTO saneamiento_areas (
                            saneamiento_areas_id, area_id, acontecimiento_id, fecha, observacion, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?)";

                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    throw new RuntimeException("Error preparando inserción de limpiez: " . $this->db->error);
                }

                $uuid = UuidHelper::generateUUIDv4();
                $stmt->bind_param('ssssss', $uuid, $area_id, $reporte_id, $limpieza_fecha, $observacion, $userId);

                if (!$stmt->execute()) {
                    throw new RuntimeException("Error al insertar limpiez: " . $stmt->error);
                }
                $stmt->close();
            }

            $this->db->commit();
            return $reporte_id; // Retornar el ID en lugar de true
        } catch (Throwable $e) {
            $this->db->rollback();
            // Corregido el nombre del método en el log de error
            error_log("[AcontecimientosModel::registroAcontecimientoLimpieza] Error: " . $e->getMessage());
            throw $e;
        }
    }



    public function crear(array $in): bool
    {

        // === Validación mínima ===
        if (empty($in['tipo'])) {
            throw new InvalidArgumentException("El campo 'tipo' es obligatorio.");
        }

        // === Normalizar campos ===
        $tipo            = trim($in['tipo']);
        $animales        = [];
        $cantidad_fotos = 0;
        if (isset($_FILES['photo']['name']) && is_array($_FILES['photo']['name'])) {
            $cantidad_fotos = count(array_filter($_FILES['photo']['name'], fn($n) => !empty($n)));
        }
        $foto_id         = 'F-' . $cantidad_fotos . '-' . UuidHelper::generateUUIDv4();
        $in['foto_name'] = $foto_id;


        if ($tipo !== 'limpieza' && !empty($in['animales_seleccion'])) {
            if ($in['animales_seleccion'] === 'manual') {
                // Selección manual: tomar los animales directamente
                $animales = $in['animales'] ?? [];
            } else {
                // Selección por ubicación: obtener animales según la ubicación correspondiente
                $campo = $in['animales_seleccion'];
                $valor = $in[$campo][0] ?? null;


                if ($valor) {
                    $animales = $this->obtenerAnimalesUbicacion($campo, $valor);
                }
            }
        }
        // Asignar los animales procesados de nuevo a $in para que los métodos de registro los vean
        $in['animales'] = $animales;

        // NOTA: 'limpieza_area' se asume que ya viene en $in['limpieza_area'] si tipo es 'limpieza'

        // === MODIFICACIÓN: Capturar el acontecimiento_id ===
        $acontecimiento_id = null;
        
        if ($in['tipo'] === 'limpieza') {
            $acontecimiento_id = $this->registroAcontecimientoLimpieza($in);
            $result = !empty($acontecimiento_id);
        } else {
            $acontecimiento_id = $this->registroAcontecimientos($in);
            $result = !empty($acontecimiento_id);
        }


        // mover fotos
        if ($result) {
            $fotosGuardadas = [];
            if (isset($_FILES['photo']['name']) && is_array($_FILES['photo']['name']) && !empty($_FILES['photo']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads/acontecimientos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $contador = 1;
                foreach ($_FILES['photo']['tmp_name'] as $i => $tmpName) {
                    if (is_uploaded_file($tmpName)) {
                        // $fileName = uniqid('foto_') . '_' . basename($_FILES['photo']['name'][$i]); // Original
                        $fileName = "{$foto_id}-{$contador}.jpg"; // Nueva versión
                        $destino = $uploadDir . $fileName;

                        if (move_uploaded_file($tmpName, $destino)) {
                            $fotosGuardadas[] = $fileName;
                        }
                        $contador++;
                    }
                }
            }
        }

        // === INICIO DE LÓGICA DE NOTIFICACIÓN ===
        if ($result && !empty($acontecimiento_id)) {
            // Obtener info del actor
            [$now, $env, $actorId, $role] = $this->nowWithAudit();

            $templateKey = '';
            $params = [];
            $route = "/acontecimientos?acontecimiento_id={$acontecimiento_id}"; // Ruta con ID específico

            // 1. Preparar conteo de animales/áreas
            $detalle_animales = '';
            $detalle_areas = '';

            if ($tipo === 'limpieza') {
                $areas = $in['limpieza_area'] ?? [];
                $count = count($areas);
                $detalle_areas = ($count === 1) ? '1 área' : "$count áreas";
            } else {
                // $animales ya fue calculado arriba
                $count = count($animales);
                $detalle_animales = ($count === 1) ? '1 animal' : "$count animales";
            }

            // 2. Seleccionar plantilla y parámetros según el tipo
            switch ($tipo) {
                case 'vacunacion':
                    $templateKey = 'acon_vacunacion_registrada';
                    $params = [
                        'vacuna_nombre' => $in['vacuna_nombre'] ?? 'N/A',
                        'vacuna_fecha' => $in['vacuna_fecha'] ?? date('Y-m-d'),
                        'vacuna_dosis' => $in['vacuna_dosis'] ?? 'N/A',
                        'detalle_animales' => $detalle_animales
                    ];
                    break;

                case 'decesos':
                    $templateKey = 'acon_deceso_registrado';
                    $params = [
                        'deceso_fecha' => $in['deceso_fecha'] ?? date('Y-m-d'),
                        'deceso_causa' => $in['deceso_causa'] ?? 'N/A',
                        'detalle_animales' => $detalle_animales
                    ];
                    break;

                case 'revision':
                    $templateKey = 'acon_revision_registrada';
                    $params = [
                        'revision_fecha' => $in['revision_fecha'] ?? date('Y-m-d'),
                        'revision_veterinario' => $in['revision_veterinario'] ?? 'N/A',
                        'detalle_animales' => $detalle_animales
                    ];
                    break;

                case 'cuarentena':
                    $templateKey = 'acon_cuarentena_inicio';
                    $params = [
                        'cuarentena_inicio' => $in['cuarentena_inicio'] ?? date('Y-m-d'),
                        'cuarentena_fin' => $in['cuarentena_fin'] ?? 'N/A',
                        'cuarentena_motivo' => $in['cuarentena_motivo'] ?? 'N/A',
                        'detalle_animales' => $detalle_animales
                    ];
                    break;

                case 'tratamiento':
                    $templateKey = 'acon_tratamiento_registrado';
                    $params = [
                        'tratamiento_medicamento' => $in['tratamiento_medicamento'] ?? 'N/A',
                        'tratamiento_dosis' => $in['tratamiento_dosis'] ?? 'N/A',
                        'detalle_animales' => $detalle_animales
                    ];
                    break;

                case 'brote':
                    $templateKey = 'acon_brote_registrado';
                    $params = [
                        'brote_tipo' => $in['brote_tipo'] ?? 'N/A',
                        'brote_severidad' => $in['brote_severidad'] ?? 'N/A',
                        'detalle_animales' => $detalle_animales
                    ];
                    break;

                case 'limpieza':
                    $templateKey = 'acon_limpieza_registrada';
                    $params = [
                        'limpieza_fecha' => $in['limpieza_fecha'] ?? date('Y-m-d'),
                        'detalle_areas' => $detalle_areas
                    ];
                    break;
            }

            // 3. Despachar la notificación
            if (!empty($templateKey)) {
                $this->notificar($templateKey, $params, $route, null, $actorId, $role);
            }

            // Aquí también podrías agregar lógica para AlertaModel si es necesario
            // ej: if ($tipo === 'brote' && $in['brote_severidad'] > 5) { ... }

        }
        // === FIN DE LÓGICA DE NOTIFICACIÓN ===

        return $result;
    }

    public function listar(): array
    {
        $sql = "SELECT 
                    ar.*,
                    u.nombre as created_by_name
                FROM acontecimiento_reportes ar
                LEFT JOIN system_users u ON ar.created_by = u.user_id
                ORDER BY ar.fecha DESC, ar.created_at DESC";

        $result = $this->db->query($sql);
        $data = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Procesar fotos
                $fotos = [];
                if (!empty($row['foto'])) {
                    // El formato es F-{cantidad}-{uuid}
                    // Ejemplo: F-2-uuid
                    $parts = explode('-', $row['foto']);
                    if (count($parts) >= 3 && $parts[0] === 'F') {
                        $cantidad = (int)$parts[1];
                        $uuid_part = substr($row['foto'], strpos($row['foto'], $parts[2])); // El resto es el uuid? No, el formato es F-N-UUID.
                        // Mejor usamos el nombre base para construir los nombres de los archivos
                        // Según el controller: $fileName = "{$foto_id}-{$contador}.jpg";
                        
                        for ($i = 1; $i <= $cantidad; $i++) {
                            $fotos[] = "{$row['foto']}-{$i}.jpg";
                        }
                    }
                }
                $row['fotos_urls'] = $fotos;
                
                // Estado por defecto si es nulo (aunque la DB debería tener default)
                if (empty($row['estado'])) {
                    $row['estado'] = 'ABIERTO';
                }

                $data[] = $row;
            }
            $result->free();
        }

        return $data;
    }

    /**
     * Actualiza el estado de un acontecimiento
     * @param string $acontecimiento_id ID del acontecimiento
     * @param string $nuevo_estado Nuevo estado (ABIERTO o CERRADO)
     * @return bool True si se actualizó correctamente
     * @throws InvalidArgumentException Si el estado no es válido
     */
    public function actualizarEstado(string $acontecimiento_id, string $nuevo_estado): bool
    {
        // Validar estado
        if (!in_array($nuevo_estado, ['ABIERTO', 'CERRADO'])) {
            throw new InvalidArgumentException("Estado inválido. Debe ser ABIERTO o CERRADO.");
        }
        
        $sql = "UPDATE acontecimiento_reportes 
                SET estado = ? 
                WHERE acontecimiento_id = ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Error al preparar la consulta: " . $this->db->error);
        }
        
        $stmt->bind_param('ss', $nuevo_estado, $acontecimiento_id);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new RuntimeException("Error al actualizar el estado: " . $stmt->error);
        }
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $affected > 0;
    }
}
