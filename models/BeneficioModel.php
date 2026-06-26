<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/FechasHelper.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';

class BeneficioModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function nowWithAudit(): array
    {
        $env = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');
        $uuid    = UuidHelper::generateUUIDv4();
        $actorId = $_SESSION['user_id'] ?? $uuid;
        $env->applyAuditContext($this->db, $actorId);
        $tz = new TimezoneManager($this->db);
        $tz->applyTimezone();
        return [$env->getCurrentDatetime(), $env];
    }

    /**
     * Lista matanzas agrupadas
     */
    public function listarMatanzas(): array
    {
        $sql = "SELECT 
                created_at,
                fecha,
                COUNT(*) AS total_animales,
                kilogramos_tanda AS kg_total,
                ingreso_tanda AS ingreso_total
            FROM beneficios
            GROUP BY created_at, fecha
            ORDER BY created_at DESC";

        $res = $this->db->query($sql);
        if (!$res) {
            throw new RuntimeException("Error al listar beneficios");
        }

        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Detalle de animales beneficiados
     */
    public function detalleMatanza(string $createdAt): array
    {
        $createdAt = str_replace('%20', ' ', $createdAt);

        $sql = "
            SELECT 
                b.beneficio_id,
                b.fecha,
                b.kilogramos_tanda,
                b.ingreso_tanda,
                a.identificador,
                a.especie,
                a.sexo,
                (
                    SELECT ap.peso_kg
                    FROM animal_pesos ap
                    WHERE ap.animal_id = a.animal_id
                    ORDER BY ap.fecha_peso DESC
                    LIMIT 1
                ) AS ultimo_peso
            FROM beneficios b
            INNER JOIN animales a ON a.animal_id = b.animal_id
            WHERE b.created_at = ?
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Error al preparar detalle");
        }

        $stmt->bind_param('s', $createdAt);
        $stmt->execute();
        $res = $stmt->get_result();

        $data = [];
        while ($row = $res->fetch_assoc()) {
            if ($row['ultimo_peso'] === null) {
                $row['ultimo_peso'] = 'No disponible';
            }
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Gráfico mensual
     */
    public function graficoMensual(): array
    {
        $sql = "
            SELECT 
                DATE_FORMAT(fecha, '%Y-%m') AS mes,
                SUM(kilogramos_tanda) AS kg,
                SUM(ingreso_tanda) AS ingreso
            FROM beneficios
            GROUP BY mes
            ORDER BY mes
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            throw new RuntimeException("Error gráfico beneficios");
        }

        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
