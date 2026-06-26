<?php
require_once __DIR__ . '/../models/AcontecimientosModel.php';

class AcontecimientosController
{
    private $model;

    public function __construct()
    {
        $this->model = new AcontecimientosModel();
    }

    private function jsonResponse($value, string $message = '', $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['value' => $value, 'message' => $message, 'data' => $data]);
        exit;
    }


    public function crear(): void
    {
        try {
            // Tipo de acontecimiento (obligatorio)
            $tipo = $_POST['tipo'] ?? null;
            if (!$tipo) {
                throw new InvalidArgumentException("El tipo de acontecimiento es obligatorio.");
            }

            // Campos comunes
            $in = [
                'tipo'         => $tipo,
                'observacion'  => $_POST['observacion'] ?? null,
            ];

            // Campos específicos por tipo
            switch ($tipo) {
                case 'vacunacion':
                    $in['vacuna_nombre'] = $_POST['vacuna_nombre'] ?? null;
                    $in['vacuna_fecha']  = $_POST['vacuna_fecha']  ?? null;
                    $in['vacuna_dosis']  = $_POST['vacuna_dosis']  ?? null;
                    break;

                case 'decesos':
                    $in['deceso_cantidad'] = $_POST['deceso_cantidad'] ?? null;
                    $in['deceso_causa']    = $_POST['deceso_causa']    ?? null;
                    $in['deceso_fecha']    = $_POST['deceso_fecha']    ?? null;
                    break;

                case 'revision':
                    $in['revision_veterinario'] = $_POST['revision_veterinario'] ?? null;
                    $in['revision_fecha']       = $_POST['revision_fecha']       ?? null;
                    break;

                case 'cuarentena':
                    $in['cuarentena_inicio'] = $_POST['cuarentena_inicio'] ?? null;
                    $in['cuarentena_fin']    = $_POST['cuarentena_fin']    ?? null;
                    $in['cuarentena_motivo'] = $_POST['cuarentena_motivo'] ?? null;
                    break;

                case 'tratamiento':
                    $in['tratamiento_medicamento'] = $_POST['tratamiento_medicamento'] ?? null;
                    $in['tratamiento_dosis']       = $_POST['tratamiento_dosis']       ?? null;
                    break;

                case 'brote':
                    $in['brote_tipo']      = $_POST['brote_tipo']      ?? null;
                    $in['brote_afectados'] = $_POST['brote_afectados'] ?? null;
                    $in['brote_severidad'] = $_POST['brote_severidad'] ?? null;
                    break;

                case 'limpieza':

                    $in['limpieza_area'] = $_POST['limpieza_area'] ?? [];
                    if (!is_array($in['limpieza_area'])) {
                        $in['limpieza_area'] = [$in['limpieza_area']];
                    }

                    $in['limpieza_fecha'] = $_POST['limpieza_fecha'] ?? null;
                    break;


                case 'beneficios':
                    $in['ingreso'] = $_POST['ingreso'] ?? null;
                    $in['kilogramos'] = $_POST['kilogramos'] ?? null;
                    break;
            }

            // Animales involucrados (si no es deceso_fecha)
            if ($tipo !== 'limpieza') {
                $in['animales_seleccion'] = $_POST['animales_seleccion'] ?? null;

                switch ($in['animales_seleccion']) {
                    case 'fincas':
                        $in['fincas'] = $_POST['fincas'] ?? null;
                        break;
                    case 'apriscos':
                        $in['apriscos'] = $_POST['apriscos'] ?? null;
                        break;
                    case 'areas':
                        $in['areas'] = $_POST['areas'] ?? null;
                        break;
                    case 'manual':
                        // Puede venir como array si seleccionó múltiples animales
                        $in['animales'] = $_POST['animales'] ?? [];
                        if (!is_array($in['animales'])) {
                            $in['animales'] = [$in['animales']];
                        }
                        break;
                }
            }

            // Guardar en BD
            $result = $this->model->crear($in);
            $this->jsonResponse(true, 'Acontecimiento creado correctamente.');
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al crear acontecimiento: ' . $e->getMessage(), null, 500);
        }
    }

    public function listar(): void
    {
        try {
            $data = $this->model->listar();
            $this->jsonResponse(true, 'Listado de acontecimientos', $data);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar acontecimientos: ' . $e->getMessage(), null, 500);
        }
    }

    public function actualizarEstado(): void
    {
        try {
            $acontecimiento_id = $_POST['acontecimiento_id'] ?? null;
            $nuevo_estado = $_POST['estado'] ?? null;
            
            if (!$acontecimiento_id || !$nuevo_estado) {
                throw new InvalidArgumentException("Faltan parámetros requeridos: acontecimiento_id y estado");
            }
            
            $result = $this->model->actualizarEstado($acontecimiento_id, $nuevo_estado);
            
            if ($result) {
                $this->jsonResponse(true, 'Estado actualizado correctamente');
            } else {
                $this->jsonResponse(false, 'No se encontró el acontecimiento o no se pudo actualizar', null, 404);
            }
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al actualizar estado: ' . $e->getMessage(), null, 500);
        }
    }
}
