<?php
require_once __DIR__ . '/../config/Database.php';

class TutorialController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function res($v, $m, $d = null, $c = 200)
    {
        http_response_code($c);
        header('Content-Type: application/json');
        echo json_encode(['value' => $v, 'message' => $m, 'data' => $d]);
        exit;
    }

    public function state()
    {
        $u = $_SESSION['user_id'] ?? '';
        if (!$u) {
            $this->res(false, 'No autenticado.', null, 401);
        }

        $tipo = $_SESSION['tipo'] ?? '';
        $esVendedor = $tipo === 'vendedor';

        if ($esVendedor) {
            $tables = ['empresas', 'temporadas', 'articulos', 'asignaciones_articulos'];
            $steps = [
                ['key' => 'empresas', 'label' => 'Crea una empresa', 'url' => 'empresas', 'desc' => 'Registra tu primera empresa para comenzar.', 'details' => 'Haz click en el boton <b class="text-primary">Nueva Empresa</b>. Completa los datos de la empresa, luego configura las cuotas y los días de retraso permitidos.'],
                ['key' => 'temporadas', 'label' => 'Crea una campaña', 'url' => 'temporadas', 'desc' => 'Define una campaña con fechas de inicio y fin.', 'details' => 'Haz click en el boton <b class="text-primary">Nueva Campaña</b>. Selecciona la empresa que creaste y define el rango de fechas.'],
                ['key' => 'articulos', 'label' => 'Crea un artículo', 'url' => 'articulos', 'desc' => 'Registra un artículo para tu catálogo.', 'details' => 'Haz click en el boton <b class="text-primary">Nuevo Artículo</b>. Define los datos del artículo y su precio unitario.'],
                ['key' => 'asignaciones_articulos', 'label' => 'Registra una venta', 'url' => 'vendedore_articulo', 'desc' => 'Asigna un artículo a un cliente.', 'details' => 'Haz click en el boton <b class="text-primary">Nueva venta</b>. Selecciona vendedor, artículo y temporada, y define las cuotas.'],
            ];
        } else {
            $tables = ['empresas', 'temporadas', 'colecciones_combos', 'vendedores', 'asignaciones_colecciones'];
            $steps = [
                ['key' => 'empresas', 'label' => 'Crea una empresa', 'url' => 'empresas', 'desc' => 'Registra tu primera empresa para comenzar a gestionar colecciones.', 'details' => 'Haz click en el boton <b class="text-primary"> Nueva Empresa</b>. Completa los datos de la empresa, luego configura las cuotas y los días de retraso permitidos.'],
                ['key' => 'temporadas', 'label' => 'Crea una campaña', 'url' => 'temporadas', 'desc' => 'Define una campaña con fechas de inicio y fin para organizar tus ciclos.', 'details' => 'Haz click en el boton <b class="text-primary"> Nueva Campaña</b>. Selecciona la empresa que creaste, asigna un nombre a la campaña y define el rango de fechas en que estará activa.'],
                ['key' => 'colecciones_combos', 'label' => 'Crea una colección', 'url' => 'colecciones', 'desc' => 'Continúa registrando tu primera colección.', 'details' => 'Haz click en el boton <b class="text-primary">Nueva Colección</b>. Elige la empresa correspondientes, define el nombre, precio de la empresa, precio al entregar al vendedor y su ganacia.'],
                ['key' => 'vendedores', 'label' => 'Registra un vendedor', 'url' => 'vendedores', 'desc' => 'Ingresa los datos del vendedor para empezar a asignarle colecciones.', 'details' => 'Haz click en el boton <b class="text-primary">Nueva Vendedor</b>. Completa el nombre, cédula y teléfono del vendedor. Asígnale un nivel para definir su progreso.'],
                ['key' => 'asignaciones_colecciones', 'label' => 'Asigna una colección', 'url' => 'asignaciones', 'desc' => 'Asigna una colección a un vendedor y define el plan de cuotas.', 'details' => 'Haz click en el boton <b class="text-primary">Nueva Asignación</b>. Selecciona el vendedor, la colección y la temporada. Define la fecha de asignación y configura las cuotas.'],
            ];
        }

        try {
            $counts = [];
            foreach ($tables as $name) {
                $s = $this->db->prepare("SELECT COUNT(*) AS c FROM `$name` WHERE usuario_id = ?");
                $s->bind_param('s', $u);
                $s->execute();
                $r = $s->get_result();
                $counts[$name] = (int)$r->fetch_assoc()['c'];
                $s->close();
            }

            // Find first step with 0 records
            $currentStep = count($steps);
            $currentKey = null;
            foreach ($steps as $i => $s) {
                if (($counts[$s['key']] ?? 0) === 0) {
                    $currentStep = $i;
                    $currentKey = $s['key'];
                    break;
                }
            }

            $this->res(true, 'OK', [
                'counts' => $counts,
                'steps' => $steps,
                'currentStep' => $currentStep,
                'currentKey' => $currentKey,
                'totalSteps' => count($steps),
                'completed' => $currentStep >= count($steps),
            ]);
        } catch (Throwable $e) {
            $this->res(false, $e->getMessage(), null, 500);
        }
    }
}
