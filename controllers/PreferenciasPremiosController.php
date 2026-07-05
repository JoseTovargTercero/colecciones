<?php
require_once __DIR__ . '/../models/PreferenciasPremiosModel.php';

class PreferenciasPremiosController
{
    private $m;

    public function __construct()
    {
        $this->m = new PreferenciasPremiosModel();
    }

    private function res($v, $m, $d = null, $c = 200)
    {
        http_response_code($c);
        header('Content-Type: application/json');
        echo json_encode(['value' => $v, 'message' => $m, 'data' => $d]);
        exit;
    }

    public function listar()
    {
        try {
            $data = $this->m->listar();
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }

    public function pagosTiempo()
    {
        $empresa_id   = (int)($_GET['empresa_id'] ?? 0);
        $temporada_id = trim($_GET['temporada_id'] ?? '');

        if (!$empresa_id || !$temporada_id) {
            $this->res(false, 'Faltan parámetros empresa_id y temporada_id.', null, 400);
        }

        try {
            $data = $this->m->pagosTiempo($empresa_id, $temporada_id);
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }

    public function premiosDisponibles()
    {
        $empresa_id = (int)($_GET['empresa_id'] ?? 0);
        if (!$empresa_id) $this->res(false, 'Falta empresa_id.', null, 400);
        try {
            $data = $this->m->obtenerPremiosPorEmpresa($empresa_id);
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }

    public function asignarPremios()
    {
        $empresa_id   = (int)($_POST['empresa_id'] ?? 0);
        $temporada_id = trim($_POST['temporada_id'] ?? '');
        $vendedor_id  = (int)($_POST['vendedor_id'] ?? 0);
        $premio_ids   = $_POST['premio_ids'] ?? [];

        if (!$empresa_id || !$temporada_id || !$vendedor_id || empty($premio_ids)) {
            $this->res(false, 'Faltan datos obligatorios.', null, 400);
        }

        if (!is_array($premio_ids)) $premio_ids = [$premio_ids];
        $premio_ids = array_map('intval', $premio_ids);

        try {
            $this->m->asignarPremiosPagosTiempo($empresa_id, $temporada_id, $vendedor_id, $premio_ids);
            $this->res(true, 'Premio(s) asignado(s) correctamente.', null, 201);
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }

    public function entregar()
    {
        // Se espera el ID en la URL, manejado por el router o en $_GET si no es param de URL
        // El router mapea /api/preferencias-premios/{id}/entregar
        // Veamos cómo el router maneja params de URL. Normalmente vienen en $_GET['id'] si el router los inyecta.
        // Asumiremos $_GET['id'] (ya que otros endpoints los obtienen de ahí o usando el router, me guío de otros controladores).
        // Si el router no inyecta $_GET, extraemos del path.
        
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));
        $id = null;
        foreach ($parts as $i => $p) {
            if ($p === 'preferencias-premios' && isset($parts[$i+1]) && is_numeric($parts[$i+1])) {
                $id = (int)$parts[$i+1];
                break;
            }
        }
        
        if (!$id) {
            $id = (int)($_GET['id'] ?? 0);
        }

        if (!$id) {
            $this->res(false, 'ID de solicitud no proporcionado.', null, 400);
        }

        try {
            $success = $this->m->entregar($id);
            if ($success) {
                $this->res(true, 'Premio marcado como entregado.', null, 200);
            } else {
                $this->res(false, 'No se pudo entregar o ya estaba entregado.', null, 404);
            }
        } catch (Throwable $e) {
            $this->res(false, 'Error: ' . $e->getMessage(), null, 500);
        }
    }
}
