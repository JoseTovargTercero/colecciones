<?php
require_once __DIR__ . '/../models/PartoModel.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class PartoController
{
    private $model;

    public function __construct()
    {
        $this->model = new PartoModel();
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
        // 1. Validar estructura básica y errores de subida
        if (!$file || !isset($file['tmp_name']) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null; // No hay archivo o hubo error en la subida (ej. tamaño excedido en php.ini)
        }

        // 2. Validar que es un archivo subido legítimo por HTTP POST
        if (!is_uploaded_file($file['tmp_name'])) {
            return null; // El archivo temporal no existe o no es válido
        }

        // 3. Validaciones de tamaño (20MB)
        $maxBytes = 20 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            throw new InvalidArgumentException('La fotografía excede el tamaño máximo (20MB).');
        }

        // 4. Determinar mimetype de forma segura
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        // Usamos @ para evitar que el Warning rompa el JSON si XAMPP falla al leer el tmp
        $mime = @$finfo->file($file['tmp_name']);

        // Si finfo falla (false), usamos mime_content_type o el tipo reportado por el navegador como fallback
        if ($mime === false) {
            $mime = mime_content_type($file['tmp_name']) ?: $file['type'];
        }

        $ext = null;
        switch ($mime) {
            case 'image/jpeg':
            case 'image/pjpeg': // Variación IE
                $ext = 'jpg';
                break;
            case 'image/png':
            case 'image/x-png': // Variación antigua
                $ext = 'png';
                break;
            case 'image/webp':
                $ext = 'webp';
                break;
            default:
                // Incluimos el mime detectado en el error para depuración
                throw new InvalidArgumentException("Formato de imagen no permitido ($mime). Use JPG, PNG o WEBP.");
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

        // 5. Mover el archivo
        if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
            throw new RuntimeException('No se pudo guardar la fotografía en el servidor.');
        }

        // Ruta relativa que guardamos en BD
        $rel = '/uploads/' . $uuid . '.' . $ext;
        return $rel;
    }

    // GET /partos/gestantes
    public function gestantes(): void
    {
        try {
            $data = $this->model->listarGestantes();
            $this->jsonResponse(true, 'Listado de hembras gestantes obtenido.', $data);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar gestantes: ' . $e->getMessage(), null, 500);
        }
    }

    // GET /partos?limit=&offset=&incluirEliminados=0|1&periodo_id=&estado_parto=&desde=&hasta=
    public function listar(): void
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10000;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $incluir = isset($_GET['incluirEliminados']) ? ((int) $_GET['incluirEliminados'] === 1) : false;

        $periodoId = $_GET['periodo_id'] ?? null;
        $estado = $_GET['estado_parto'] ?? null;   // NORMAL|COMPLICADO|ABORTO
        $desde = $_GET['desde'] ?? null;          // YYYY-mm-dd
        $hasta = $_GET['hasta'] ?? null;          // YYYY-mm-dd

        try {
            $data = $this->model->listar($limit, $offset, $incluir, $periodoId, $estado, $desde, $hasta);
            $this->jsonResponse(true, 'Listado de partos obtenido correctamente.', $data);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar partos: ' . $e->getMessage(), null, 500);
        }
    }

    // GET /partos/{parto_id}
    public function mostrar(array $params): void
    {
        $partoId = $params['parto_id'] ?? '';
        if ($partoId === '') {
            $this->jsonResponse(false, 'Parámetro parto_id es obligatorio.', null, 400);
        }
        try {
            $row = $this->model->obtenerPorId($partoId);
            if (!$row)
                $this->jsonResponse(false, 'Parto no encontrado.', null, 404);
            $this->jsonResponse(true, 'Parto encontrado.', $row);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al obtener parto: ' . $e->getMessage(), null, 500);
        }
    }

    // POST /partos

    public function crear(): void
    {
        try {
            // 1. Capturar todos los datos del formulario (incluye peso, origen, destino, etc.)
            $data = $this->isMultipart() ? $_POST : $this->getJsonInput();

            // 2. Crear el registro inicial (esto devuelve el UUID)
            // Nota: En este punto, fotografia_url será NULL en la BD
            $uuid = $this->model->crear($data);

            $fotoRel = null;

            // 3. Procesar la foto SOLO si estamos en modo Multipart
            if ($this->isMultipart()) {
                // Intentamos guardar la foto
                $fotoRel = $this->saveFotoIfAny($uuid, $_FILES['fotografia'] ?? null);

                // Si se guardó correctamente (tenemos ruta), actualizamos el registro
                if ($fotoRel) {
                    // IMPORTANTE: Llamamos a actualizar explícitamente con la URL
                    $this->model->actualizar($uuid, ['fotografia_url' => $fotoRel]);
                }
            }

            $this->jsonResponse(true, 'Parto creado correctamente.', [
                'parto_id' => $uuid,
                'fotografia_url' => $fotoRel // Devuelve la URL para confirmar al front
            ]);

        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al crear parto: ' . $e->getMessage(), null, 500);
        }
    }

    // POST /partos/{parto_id}
    // JSON o multipart:
    //   { periodo_id?, fecha_parto?, crias_machos?, crias_hembras?, peso_promedio_kg?, estado_parto?, observaciones?, fotografia_url? }
    // Archivo: fotografia (opcional, solo en multipart)
    public function actualizar(array $params): void
    {
        $partoId = $params['parto_id'] ?? '';
        if ($partoId === '') {
            $this->jsonResponse(false, 'Parámetro parto_id es obligatorio.', null, 400);
        }

        try {
            if ($this->isMultipart()) {
                // 1. Tomamos todos los datos enviados por el formulario
                $data = $_POST;

                // 2. Procesar foto nueva si viene
                $fotoRel = $this->saveFotoIfAny($partoId, $_FILES['fotografia'] ?? null);
                if ($fotoRel) {
                    $data['fotografia_url'] = $fotoRel;
                }

                if (empty($data)) {
                    $this->jsonResponse(false, 'No hay campos para actualizar.', null, 400);
                }

                $ok = $this->model->actualizar($partoId, $data);

                $this->jsonResponse(true, 'Parto actualizado correctamente.', [
                    'updated' => $ok,
                    'fotografia_url' => $fotoRel
                ]);
            } else {
                $in = $this->getJsonInput();
                $ok = $this->model->actualizar($partoId, $in);
                $this->jsonResponse(true, 'Parto actualizado correctamente.', ['updated' => $ok]);
            }
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al actualizar parto: ' . $e->getMessage(), null, 500);
        }
    }

    // POST /partos/{parto_id}/estado
    // JSON: { estado_parto: 'NORMAL'|'COMPLICADO'|'ABORTO' }
    public function actualizarEstado(array $params): void
    {
        $partoId = $params['parto_id'] ?? '';
        if ($partoId === '') {
            $this->jsonResponse(false, 'Parámetro parto_id es obligatorio.', null, 400);
        }

        $in = $this->getJsonInput();
        if (!isset($in['estado_parto'])) {
            $this->jsonResponse(false, 'El campo estado_parto es obligatorio.', null, 400);
        }

        $estado = (string) $in['estado_parto'];

        try {
            $ok = $this->model->actualizarEstado($partoId, $estado);
            $this->jsonResponse(true, 'Estado del parto actualizado correctamente.', ['updated' => $ok]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al actualizar estado: ' . $e->getMessage(), null, 500);
        }
    }

    // DELETE /partos/{parto_id}
    public function eliminar(array $params): void
    {
        $partoId = $params['parto_id'] ?? '';
        if ($partoId === '') {
            $this->jsonResponse(false, 'Parámetro parto_id es obligatorio.', null, 400);
        }
        try {
            $ok = $this->model->eliminar($partoId);
            if (!$ok)
                $this->jsonResponse(false, 'No se pudo eliminar (o ya estaba eliminado).', null, 400);
            $this->jsonResponse(true, 'Parto eliminado correctamente.', ['deleted' => true]);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al eliminar parto: ' . $e->getMessage(), null, 500);
        }
    }
}
