<?php
require_once __DIR__ . '/../models/VendedorModel.php';

class VendedorController
{
    private $m;
    public function __construct()
    {
        $this->m = new VendedorModel();
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
            $this->res(true, 'OK', $this->m->listar());
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }
    public function crear()
    {
        $d = $_POST;
        if (empty($d)) $d = json_decode(file_get_contents('php://input'), true) ?: [];
        $this->res(true, 'OK', ['id' => $this->m->crear($d)], 201);
    }
    public function actualizar($p)
    {
        $id = $p['id'] ?? '';
        $d = $_POST;
        if (empty($d)) $d = json_decode(file_get_contents('php://input'), true) ?: [];
        $this->res(true, 'OK', ['ok' => $this->m->actualizar($id, $d)]);
    }
    public function eliminar($p)
    {
        if (!($id = $p['id'] ?? '')) $this->res(false, 'ID req', null, 400);
        try {
            $this->res(true, 'OK', ['ok' => $this->m->eliminar($id)]);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }

    public function buscarPorCedula()
    {
        $q = trim($_GET['q'] ?? '');
        if (!$q) {
            $this->res(false, 'Indique cédula o nombre.', null, 400);
        }
        try {
            $data = $this->m->buscarPorCedula($q);
            if (!$data) {
                $this->res(false, 'Vendedor no encontrado.', null, 404);
            }
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }

    public function detalles($p)
    {
        $id = (int)($p['id'] ?? 0);
        if (!$id) $this->res(false, 'ID requerido', null, 400);
        try {
            $data = $this->m->obtenerDetalles($id);
            if (!$data) $this->res(false, 'Vendedor no encontrado', null, 404);
            $this->res(true, 'OK', $data);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }

    public function solicitarPago()
    {
        try {
            $d = json_decode(file_get_contents('php://input'), true) ?: [];
            $telefono = trim($d['telefono'] ?? '');
            $vendedorNombre = trim($d['vendedor_nombre'] ?? '');
            $monto = $d['monto'] ?? 0;

            if (!$telefono) $this->res(false, 'Teléfono requerido', null, 400);

            $userId = $this->m->findSystemUserByPhone($telefono);
            if (!$userId) $this->res(false, 'Usuario no encontrado en la plataforma', null, 404);

            $emisorNombre = $_SESSION['user_nombre'] ?? $_SESSION['nombre'] ?? 'Un administrador';

            require_once __DIR__ . '/../models/NotificationModel.php';
            $notif = new NotificationModel();
            $notif->crear([
                'template_key' => 'solicitud_pago',
                'template_params' => [
                    'emisor_nombre' => $emisorNombre,
                    'monto' => number_format((float)$monto, 2),
                ],
                'route' => '/deudas',
                'module' => 'deudas',
                'rol' => null,
                'user_id' => $userId,
            ]);

            $this->res(true, 'Notificación enviada');
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }
}
