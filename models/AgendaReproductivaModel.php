<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';
require_once __DIR__ . '/../helpers/FechasHelper.php';
require_once __DIR__ . '/../helpers/UuidHelper.php';
require_once __DIR__ . '/PeriodoServicioModel.php';


class AgendaReproductivaModel
{
    private $infoPeriodo;
    private $db;
    private $table = 'revisiones_servicio';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->infoPeriodo = new PeriodoServicioModel();
    }

    /* ============ Utilidades ============ */

    /**
     * Lista servicios arevisar (excluye eliminados por defecto).
     * Filtros: periodo_id, numero_monta, fecha_monta (desde/hasta).
     */
    public function listar(): array
    {

        $sql = "SELECT * FROM {$this->table} AS RS
        LEFT JOIN periodos_servicio AS PS ON RS.periodo_id = PS.periodo_id
        WHERE RS.fecha_realizada IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error al preparar listado: " . $this->db->error);
        }

        $stmt->execute();

        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $status = $row['fecha_realizada'] == null ?  'Pendiente' : 'Completado';
            array_push($data, [
                "title" => 'Revisión Servicio',
                "id" => $row['revision_id'],
                "start" => $row['fecha_programada'],
                "estado" => $status,
                "detalles" => $this->infoPeriodo->obtenerPorId($row['periodo_id']),
                "className" => $status == 'Pendiente' ? "bg-info" : 'bg-default' // ✅ Clase desde backend
            ]);
        }

        $stmt->close();
        return $data;
    }
}
