<?php
require_once __DIR__ . '/../models/CamadaBajaModel.php';

class CamadaBajaController
{
    private $model;

    public function __construct()
    {
        $this->model = new CamadaBajaModel();
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
    private function saveDocumentoIfAny(string $uuid, ?array $file): ?string
    {
        if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null; // no hay archivo
        }
        $maxBytes = 20 * 1024 * 1024; // 20MB
        if ($file['size'] > $maxBytes) {
            throw new InvalidArgumentException('El acta excede el tamaño máximo (20MB).');
        }
        $allowedMimes = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!isset($allowedMimes[$mime])) {
            throw new InvalidArgumentException('Formato de acta no permitido. Use PDF, DOC, DOCX, JPG o PNG.');
        }
        $ext = $allowedMimes[$mime];
        $uploadsDir = rtrim(APP_ROOT, '/\\') . '/uploads/actas_baja';
        if (!is_dir($uploadsDir)) {
            if (!@mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
                throw new RuntimeException('No se pudo crear el directorio de uploads/actas_baja.');
            }
        }
        $destAbs = $uploadsDir . '/' . $uuid . '.' . $ext;
        if (!@move_uploaded_file($file['tmp_name'], $destAbs)) {
            throw new RuntimeException('No se pudo guardar el acta en el servidor.');
        }
        $rel = '/uploads/actas_baja/' . $uuid . '.' . $ext;
        return $rel;
    }


    // GET /camadas/{camada_id}/bajas
    public function listar(array $params): void
    {
        // ... (sin cambios)
        $camadaId = $params['camada_id'] ?? '';
        if ($camadaId === '') {
            $this->jsonResponse(false, 'Parámetro camada_id es obligatorio.', null, 400);
        }
        try {
            $data = $this->model->listarPorCamada($camadaId);
            $this->jsonResponse(true, 'Listado de bajas de camada obtenido.', $data);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar bajas: ' . $e->getMessage(), null, 500);
        }
    }

    // <--- INICIO: MÉTODO 'CREAR' CORREGIDO --->
    // POST /camadas/{camada_id}/bajas
    public function crear(array $params): void
    {
        $camadaId = $params['camada_id'] ?? '';
        if ($camadaId === '') {
            $this->jsonResponse(false, 'Parámetro camada_id es obligatorio.', null, 400);
        }

        try {
            $in = [];
            $file = null;
            if ($this->isMultipart()) {
                $in['fecha_baja'] = $_POST['fecha_baja'] ?? null;
                $in['cantidad'] = $_POST['cantidad'] ?? 1;
                $in['causa_deceso'] = $_POST['causa_deceso'] ?? null;
                $in['observaciones'] = $_POST['observaciones'] ?? null;
                $file = $_FILES['documento_acta'] ?? null;
            } else {
                $in = $this->getJsonInput();
            }

            $in['camada_id'] = $camadaId; // Forzar la camada_id desde la URL

            // 1. Crear el registro de baja (sin el acta)
            // El modelo 'crear' ya no acepta 'documento_acta_url' directamente
            unset($in['documento_acta_url']);
            $uuid_baja = $this->model->crear($in);

            // 2. Guardar el archivo (si existe) usando el UUID del registro
            $actaRel = $this->saveDocumentoIfAny($uuid_baja, $file);

            // 3. Actualizar el registro con la ruta del acta (si se guardó)
            if ($actaRel) {
                // Ahora usamos el nuevo método 'actualizar'
                $this->model->actualizar($uuid_baja, ['documento_acta_url' => $actaRel]);
            }

            $this->jsonResponse(true, 'Baja de camada registrada.', [
                'baja_id' => $uuid_baja,
                'documento_acta_url' => $actaRel
            ]);

        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al registrar baja: ' . $e->getMessage(), null, 500);
        }
    }
    // <--- FIN: MÉTODO 'CREAR' CORREGIDO --->

    // <--- INICIO: MÉTODO 'ELIMINAR' CORREGIDO (SOFT DELETE) --->
    // DELETE /camada_bajas/{baja_id}
    public function eliminar(array $params): void
    {
        $bajaId = $params['baja_id'] ?? '';
        if ($bajaId === '') {
            $this->jsonResponse(false, 'Parámetro baja_id es obligatorio.', null, 400);
        }
        try {
            // (Opcional: aquí deberías borrar el archivo físico del acta si existe)

            $ok = $this->model->eliminar($bajaId); // Esto ahora es soft delete
            if (!$ok)
                $this->jsonResponse(false, 'No se pudo eliminar la baja.', null, 400);
            $this->jsonResponse(true, 'Baja eliminada correctamente.', ['deleted' => true]);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al eliminar baja: ' . $e->getMessage(), null, 500);
        }
    }
    // <--- FIN: MÉTODO 'ELIMINAR' CORREGIDO --->
}