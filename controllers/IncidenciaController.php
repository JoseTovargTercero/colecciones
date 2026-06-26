<?php
require_once __DIR__ . '/../models/IncidenciaModel.php';

class IncidenciaController
{
    private $model;

    public function __construct()
    {
        $this->model = new IncidenciaModel();
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    private function jsonResponse($value, string $message = '', $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['value' => $value, 'message' => $message, 'data' => $data]);
        exit;
    }

    /** Detecta si la petición viene como multipart/form-data */
    private function isMultipart(): bool
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        return stripos($ct, 'multipart/form-data') !== false;
    }

    /** Valida y guarda la imagen (si existe) con nombre {uuid}.{ext} en APP_ROOT/uploads */
    private function saveFotoIfAny(string $uuid, ?array $file): ?string
    {
        if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null; // no hay archivo
        }

        $maxBytes = 20 * 1024 * 1024; // 20MB
        if ($file['size'] > $maxBytes) {
            throw new InvalidArgumentException('La fotografía excede el tamaño máximo (20MB).');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        $ext = null;
        switch ($mime) {
            case 'image/jpeg':
                $ext = 'jpg';
                break;
            case 'image/png':
                $ext = 'png';
                break;
            case 'image/webp':
                $ext = 'webp';
                break;
            default:
                throw new InvalidArgumentException('Formato de imagen no permitido. Use JPG, PNG o WEBP.');
        }

        $uploadsDir = rtrim(APP_ROOT, '/\\') . '/uploads';
        if (!is_dir($uploadsDir)) {
            if (!@mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
                throw new RuntimeException('No se pudo crear el directorio de uploads.');
            }
        }
        if (!is_writable($uploadsDir)) {
            throw new RuntimeException('El directorio de uploads no es escribible.');
        }

        $destAbs = $uploadsDir . '/' . $uuid . '.' . $ext;
        if (!@move_uploaded_file($file['tmp_name'], $destAbs)) {
            throw new RuntimeException('No se pudo guardar la fotografía en el servidor.');
        }

        $rel = '/uploads/' . $uuid . '.' . $ext;
        return $rel;
    }

    // GET /incidencias?animal_id=&tipo=&desde=&hasta=&area_id=&responsable=&incluirEliminados=0|1&limit=&offset=
    public function listar(): void
    {
        $limit = isset($_GET['limit']) ? max(1, min((int) $_GET['limit'], 500)) : 100;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $incluir = isset($_GET['incluirEliminados']) ? ((int) $_GET['incluirEliminados'] === 1) : false;

        $animalId = $_GET['animal_id'] ?? null;
        $tipo = $_GET['tipo'] ?? null;
        $desde = $_GET['desde'] ?? null;
        $hasta = $_GET['hasta'] ?? null;
        $areaId = $_GET['area_id'] ?? null;
        $resp = $_GET['responsable'] ?? null;

        try {
            $rows = $this->model->listar($limit, $offset, $incluir, $animalId, $tipo, $desde, $hasta, $areaId, $resp);
            $this->jsonResponse(true, 'Listado de incidencias obtenido correctamente.', $rows);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar incidencias: ' . $e->getMessage(), null, 500);
        }
    }

    // GET /incidencias/{incidencia_id}
    public function mostrar(array $params): void
    {
        $id = $params['incidencia_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro incidencia_id es obligatorio.', null, 400);
        }
        try {
            $row = $this->model->obtenerPorId($id);
            if (!$row) {
                $this->jsonResponse(false, 'Incidencia no encontrada.', null, 404);
            }
            $this->jsonResponse(true, 'Incidencia encontrada.', $row);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al obtener incidencia: ' . $e->getMessage(), null, 500);
        }
    }

    // POST /incidencias
    // JSON o multipart:
    //   {
    //     animal_id, tipo (RINA/APLASTAMIENTO/HERIDA/MORDIDA/FUGA/OTRA),
    //     fecha_evento(YYYY-MM-DD[ HH:MM[:SS]]),
    //     descripcion?, responsable?, area_id?, fotografia_url?
    //   }
    // Archivo: fotografia (opcional, solo en multipart)
    public function crear(): void
    {
        try {
            $fotoRel = null;

            if ($this->isMultipart()) {
                $in = [
                    'animal_id' => $_POST['animal_id'] ?? null,
                    'tipo' => $_POST['tipo'] ?? null,
                    'fecha_evento' => $_POST['fecha_evento'] ?? null,
                    'descripcion' => $_POST['descripcion'] ?? null,
                    'responsable' => $_POST['responsable'] ?? null,
                    'area_id' => $_POST['area_id'] ?? null,
                    'consecuencias_salud' => $_POST['consecuencias_salud'] ?? null,
                    'incidencia_id' => $_POST['incidencia_id'] ?? null,
                ];

                if ($in['consecuencias_salud']) {
                    $in['consecuencias_salud'] = json_decode($in['consecuencias_salud'], true);
                }

                $uuid = $this->model->crear($in);

                $fotoRel = $this->saveFotoIfAny($uuid, $_FILES['fotografia'] ?? null);
                if ($fotoRel) {
                    $this->model->actualizar($uuid, ['fotografia_url' => $fotoRel]);
                }
            } else {
                $in = $this->getJsonInput();
                $uuid = $this->model->crear($in);
                $fotoRel = $in['fotografia_url'] ?? null;
            }

            // Traer el correlativo recién creado para devolverlo
            $row = $this->model->obtenerPorId($uuid);
            $payload = ['incidencia_id' => $uuid];

            if ($row && isset($row['correlativo'])) {
                $payload['correlativo'] = $row['correlativo'];
            }

            if ($fotoRel) {
                $payload['fotografia_url'] = $fotoRel;
            } elseif ($row && isset($row['fotografia_url'])) {
                $payload['fotografia_url'] = $row['fotografia_url'];
            }

            $this->jsonResponse(true, 'Incidencia creada correctamente.', $payload);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al crear incidencia: ' . $e->getMessage(), null, 500);
        }
    }

    // POST /incidencias/{incidencia_id}
    // JSON o multipart. Si envías archivo 'fotografia' en multipart, actualiza la foto.
    public function actualizar(array $params): void
    {
        $id = $params['incidencia_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro incidencia_id es obligatorio.', null, 400);
        }

        try {
            if ($this->isMultipart()) {
                $in = [];
                // 1. Capturamos los campos de texto estándar
                foreach ([
                    'animal_id',
                    'tipo',
                    'fecha_evento',
                    'descripcion',
                    'responsable',
                    'area_id',
                    'fecha_evento',
                    'incidencia_id',
                    'consecuencias_salud'
                ] as $k) {
                    if (array_key_exists($k, $_POST)) {
                        $in[$k] = $_POST[$k];
                    }
                }

                if ($in['consecuencias_salud']) {
                    $in['consecuencias_salud'] = json_decode($in['consecuencias_salud'], true);
                }


                // 2. Intentamos guardar nueva foto si viene en $_FILES
                $fotoRel = $this->saveFotoIfAny($id, $_FILES['fotografia'] ?? null);

                if ($fotoRel) {
                    // Caso A: Se subió una nueva foto -> Actualizamos con la nueva ruta
                    $in['fotografia_url'] = $fotoRel;
                } elseif (array_key_exists('fotografia_url', $_POST)) {
                    // Caso B: No hay archivo nuevo, pero el JS envió 'fotografia_url' (ej. vacío para borrar)
                    // Pasamos el valor tal cual; el Modelo se encarga de convertir string vacío a NULL.
                    $in['fotografia_url'] = $_POST['fotografia_url'];
                }

                if (empty($in)) {
                    $this->jsonResponse(false, 'No hay campos para actualizar.', null, 400);
                }

                $ok = $this->model->actualizar($id, $in);
                $this->jsonResponse(true, 'Incidencia actualizada correctamente.', [
                    'updated' => $ok,
                    'fotografia_url' => $in['fotografia_url'] ?? null
                ]);
            } else {
                $in = $this->getJsonInput();
                $ok = $this->model->actualizar($id, $in);
                $this->jsonResponse(true, 'Incidencia actualizado correctamente.', [
                    'updated' => $ok,
                    'fotografia_url' => $in['fotografia_url'] ?? null
                ]);
            }
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al actualizar incidencia: ' . $e->getMessage(), null, 500);
        }
    }

    // DELETE /incidencias/{incidencia_id}
    public function eliminar(array $params): void
    {
        $id = $params['incidencia_id'] ?? '';
        if ($id === '') {
            $this->jsonResponse(false, 'Parámetro incidencia_id es obligatorio.', null, 400);
        }
        try {
            $ok = $this->model->eliminar($id);
            if (!$ok) {
                $this->jsonResponse(false, 'No se pudo eliminar (o ya estaba eliminada).', null, 400);
            }
            $this->jsonResponse(true, 'Incidencia eliminada correctamente.', ['deleted' => true]);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al eliminar incidencia: ' . $e->getMessage(), null, 500);
        }
    }
}
