<?php
require_once __DIR__ . '/../models/SuscripcionModel.php';

class SuscripcionController
{
    private SuscripcionModel $model;

    public function __construct()
    {
        $this->model = new SuscripcionModel();
    }

    // GET /suscripcion/plan — vista selección de plan (trial activo)
    public function planView(array $params = []): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) { header('Location: ' . BASE_URL . 'login'); exit(); }

        $suscripcion = $this->model->activa($userId);
        $planes      = $this->model->planes();

        // ponytail: render directo sin ViewRenderer para no añadir dependencia
        require __DIR__ . '/../views/suscripcion/seleccion_plan.php';
    }

    // GET /suscripcion/vencida — vista suscripción vencida
    public function vencidaView(array $params = []): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $planes = $this->model->planes();
        require __DIR__ . '/../views/suscripcion/suscripcion_vencida.php';
    }

    // GET /suscripcion/pendiente — vista de pago en proceso de validación
    public function pendienteView(array $params = []): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $planes = $this->model->planes();
        require __DIR__ . '/../views/suscripcion/suscripcion_pendiente.php';
    }

    // POST /suscripcion/pagar — procesar el pago manual
    public function pagar(array $params = []): void
    {
        header('Content-Type: application/json');

        $userId    = $_SESSION['user_id'] ?? null;
        $tipoPago  = $_POST['tipo_pago']  ?? '';
        $monto     = (float)($_POST['monto'] ?? 0);
        $fechaPago = $_POST['fecha_pago'] ?? '';
        $ref       = trim($_POST['referencia_pago'] ?? '');

        if (!$userId || !in_array($tipoPago, ['mensual', 'anual'], true) || $monto <= 0 || !$fechaPago || !$ref) {
            http_response_code(422);
            echo json_encode(['value' => false, 'message' => 'Datos incompletos o inválidos.']);
            return;
        }

        // Validar monto contra el plan
       /* $plan = $this->model->planes()[0] ?? null;
        $montoEsperado = $tipoPago === 'anual' ? (float)$plan['precio_anual'] : (float)$plan['precio_mensual'];
        if ($plan && abs($monto - $montoEsperado) > 0.01) {
            http_response_code(422);
            echo json_encode(['value' => false, 'message' => "El monto no coincide con el plan seleccionado (\${$montoEsperado})."] );
            return;
        }*/

        try {
            $this->model->procesarPago($userId, $tipoPago, $monto, $fechaPago, $ref);
            echo json_encode(['value' => true, 'message' => 'Pago pendiente por aprobacion']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['value' => false, 'message' => $e->getMessage()]);
        }
    }
}
