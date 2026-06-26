<?php
// Asumiendo que este archivo estará en la carpeta 'controllers'
// /controllers/ConfiguracionesController.php

require_once __DIR__ . '/../models/ConfiguracionModel.php';

class ConfiguracionesController
{
    private $model;

    public function __construct()
    {
        $this->model = new ConfiguracionModel();
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
        echo json_encode([
            'value' => $value,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Lista todas las configuraciones.
     * GET /configuraciones
     */
    public function listar(): void
    {
        try {
            $data = $this->model->listar();
            $this->jsonResponse(true, 'Listado de configuraciones obtenido.', $data);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al listar configuraciones: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Muestra una configuración por su clave.
     * GET /configuraciones/{config_key}
     */
    public function mostrar(array $params): void
    {
        $key = $params['config_key'] ?? '';
        if ($key === '') {
            $this->jsonResponse(false, 'Parámetro config_key es obligatorio.', null, 400);
        }

        try {
            $row = $this->model->obtenerPorClave($key);
            if (!$row)
                $this->jsonResponse(false, 'Configuración no encontrada.', null, 404);
            $this->jsonResponse(true, 'Configuración encontrada.', $row);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al obtener configuración: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Actualiza una configuración.
     * POST /configuraciones/{config_key}
     * JSON: { "config_value": "1" }
     */
    public function actualizar(array $params): void
    {
        $key = $params['config_key'] ?? '';
        if ($key === '') {
            $this->jsonResponse(false, 'Parámetro config_key es obligatorio.', null, 400);
        }

        $in = $this->getJsonInput();

        if (!array_key_exists('config_value', $in)) {
            $this->jsonResponse(false, 'Falta el campo "config_value" en el JSON.', null, 400);
        }

        $value = (string) $in['config_value']; // Guardamos todo como string

        try {
            $ok = $this->model->actualizar($key, $value);
            $this->jsonResponse(true, 'Configuración actualizada correctamente.', ['updated' => $ok]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(false, $e->getMessage(), null, 400);
        } catch (RuntimeException $e) {
            // Error de "no encontrado" o "duplicado"
            $this->jsonResponse(false, $e->getMessage(), null, 404);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Error al actualizar configuración: ' . $e->getMessage(), null, 500);
        }
    }
}