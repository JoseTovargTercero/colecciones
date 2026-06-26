<?php
require_once __DIR__ . '/../models/ServicioModel.php';

class ServiciosController
{
    private $model;

    public function __construct()
    {
        $this->model = new ServicioModel();
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


    public function ultimo_servicio($params): void
    {
        $periodoId = $params['periodo'] ?? null; // $args viene del router


        try {
            $data = $this->model->obtenerUltimoServicio($periodoId);
            $this->jsonResponse(true, 'Último servicio obtenido correctamente.', $data);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al obtener último servicio: ' . $e->getMessage(), null, 500);
        }
    } 

    // JSON: { fecha_servicio, observacion_servicio, periodo_id }
    public function crear(): void
    {
        $periodo_id = $_POST['periodo_id'] ?? '';
        if ($periodo_id === '') {
            $this->jsonResponse(false, 'Parámetro monta_id es obligatorio.', null, 400);
        }

        $in = [
            'fecha_servicio'        => $_POST['fecha']    ?? null,
            'observacion_servicio'  => $_POST['observacion_servicio']     ?? null,
            'numero_servicio'       => $_POST['numero_servicio']     ?? null,
            'periodo_id'            => $_POST['periodo_id']  ?? null
        ];

        try {
            $uuid = $this->model->crear($in);
            $this->jsonResponse(true, 'Monta creada correctamente.', ['monta_id' => $uuid]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al crear monta: ' . $e->getMessage(), null, 500);
        }
    } 
    public function actualizar(): void
    {
        $data = $this->getJsonInput();
        $monta_id = $data['montaId'] ?? '';
        if ($monta_id === '') {
            $this->jsonResponse(false, 'Parámetro monta_id es obligatorio.', null, 400);
        }

        try {
            $uuid = $this->model->actualizar($monta_id);
            $this->jsonResponse(true, 'Actualizado con exito.', ['monta_id' => $uuid]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 409);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al actualizar monta: ' . $e->getMessage(), null, 500);
        }
    } 

}
