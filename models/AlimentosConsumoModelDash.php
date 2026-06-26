<?php
require_once __DIR__ . '/../config/Database.php';

class AlimentosConsumoModelDash
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtiene el consumo diario de todos los planes alimenticios, sumando consumo_kg y considerando animales_presentes
     * Devuelve JSON listo para graficar en ApexCharts
     */
    public function obtenerConsumoPlanes(): array
    {
        $sql = "
            SELECT
                p.id AS plan_id,
                p.nombre AS plan_nombre,
                p.ubicacion_id,
                c.fecha,
                SUM(c.consumo_kg) AS consumo_total,
                SUM(c.animales_presentes) AS animales_total
            FROM alimentos_planes_alimenticios p
            LEFT JOIN alimentos_consumo_diario_ubicacion c
                ON p.id = c.plan_id
            WHERE p.activo = 1
            GROUP BY p.id, c.fecha
            ORDER BY p.id, c.fecha ASC
        ";

        $res = $this->db->query($sql);

        if (!$res) {
            throw new Exception("Error al obtener consumo de planes: " . $this->db->error);
        }

        $planes = [];
        while ($row = $res->fetch_assoc()) {
            $planId = $row['plan_id'];
            if (!isset($planes[$planId])) {
                $planes[$planId] = [
                    'plan_id' => $planId,
                    'nombre' => $row['plan_nombre'],
                    'ubicacion_id' => $row['ubicacion_id'],
                    'fechas' => [],
                    'consumo_total' => [],
                    'animales_total' => [],
                    'kg_por_animal' => [],
                ];
            }

            $planes[$planId]['fechas'][] = $row['fecha'];
            $planes[$planId]['consumo_total'][] = (float)$row['consumo_total'];
            $planes[$planId]['animales_total'][] = (float)$row['animales_total'];
            $planes[$planId]['kg_por_animal'][] = $row['animales_total'] > 0
                ? round($row['consumo_total'] / $row['animales_total'], 2)
                : 0;
        }

        return array_values($planes);
    }
}
