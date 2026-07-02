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
