<?php
require_once __DIR__ . '/../config/Database.php';

class DashboardModel
{
    /**
     * @var mysqli
     */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Devuelve el nombre del mes en español a partir de su número (1-12).
     */
    private function getMonthNameEs(int $mes): string
    {
        $nombres = [
            1  => 'Enero',
            2  => 'Febrero',
            3  => 'Marzo',
            4  => 'Abril',
            5  => 'Mayo',
            6  => 'Junio',
            7  => 'Julio',
            8  => 'Agosto',
            9  => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
        return $nombres[$mes] ?? (string) $mes;
    }

    /* =========================================================
     * 1. ANIMALES
     * ========================================================= */

    private function getAnimalesResumen(): array
    {
        // 1.1 Total de animales (no eliminados)
        $sqlTotal = "SELECT COUNT(*) AS total 
                     FROM animales 
                     WHERE deleted_at IS NULL";

        $stmt = $this->db->prepare($sqlTotal);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error al preparar total_animales: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $totalAnimales = (int)($res['total'] ?? 0);

        // 1.2 Distribución por etapa productiva
        $sqlEtapas = "SELECT 
                          etapa_productiva AS etapa,
                          COUNT(*) AS cantidad
                      FROM animales
                      WHERE deleted_at IS NULL
                        AND etapa_productiva IS NOT NULL
                      GROUP BY etapa_productiva";

        $stmt = $this->db->prepare($sqlEtapas);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error al preparar distribucion_etapas: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $etapas = [];
        while ($row = $res->fetch_assoc()) {
            $etapas[] = [
                'etapa'    => $row['etapa'],
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        // 1.3 Pirámide de edades (histograma por rangos de meses)
        $sqlEdades = "
            SELECT 
                CASE
                    WHEN edad_meses BETWEEN 0 AND 2 THEN '0-2'
                    WHEN edad_meses BETWEEN 3 AND 5 THEN '3-5'
                    WHEN edad_meses BETWEEN 6 AND 8 THEN '6-8'
                    WHEN edad_meses BETWEEN 9 AND 11 THEN '9-11'
                    WHEN edad_meses BETWEEN 12 AND 14 THEN '12-14'
                    WHEN edad_meses BETWEEN 15 AND 17 THEN '15-17'
                    ELSE '18+'
                END AS edad_rango,
                COUNT(*) AS cantidad
            FROM (
                SELECT 
                    TIMESTAMPDIFF(
                        MONTH, 
                        fecha_nacimiento, 
                        CURDATE()
                    ) AS edad_meses
                FROM animales
                WHERE deleted_at IS NULL
                  AND fecha_nacimiento IS NOT NULL
            ) AS t
            GROUP BY edad_rango
            ORDER BY 
                CASE edad_rango
                    WHEN '0-2' THEN 1
                    WHEN '3-5' THEN 2
                    WHEN '6-8' THEN 3
                    WHEN '9-11' THEN 4
                    WHEN '12-14' THEN 5
                    WHEN '15-17' THEN 6
                    ELSE 7
                END
        ";

        $stmt = $this->db->prepare($sqlEdades);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error al preparar piramide_edades: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $piramide = [];
        while ($row = $res->fetch_assoc()) {
            $piramide[] = [
                'edad_meses' => $row['edad_rango'],
                'cantidad'   => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        return [
            'animales' => [
                'total_animales' => $totalAnimales,
                'por_etapa'      => $etapas,
                'piramide_edades'=> $piramide,
            ],
        ];
    }

    /* =========================================================
     * 2. SALUD ANIMAL
     * ========================================================= */

    private function getRecuperadosTrimestrales(int $anio): array
    {
        $sql = "
            SELECT
                SUM(CASE WHEN MONTH(fecha_evento) BETWEEN 1 AND 3 THEN 1 ELSE 0 END) AS q1,
                SUM(CASE WHEN MONTH(fecha_evento) BETWEEN 4 AND 6 THEN 1 ELSE 0 END) AS q2,
                SUM(CASE WHEN MONTH(fecha_evento) BETWEEN 7 AND 9 THEN 1 ELSE 0 END) AS q3,
                SUM(CASE WHEN MONTH(fecha_evento) BETWEEN 10 AND 12 THEN 1 ELSE 0 END) AS q4
            FROM animal_salud
            WHERE deleted_at IS NULL
              AND estado = 'CERRADO'
              AND YEAR(fecha_evento) = ?
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar recuperadosTrimestrales: " . $this->db->error);
        }
        $stmt->bind_param('i', $anio);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0];
        $stmt->close();

        return [
            (int)($row['q1'] ?? 0),
            (int)($row['q2'] ?? 0),
            (int)($row['q3'] ?? 0),
            (int)($row['q4'] ?? 0),
        ];
    }

    private function getDecesosTrimestrales(int $anio): array
    {
        $sql = "
            SELECT
                SUM(CASE WHEN MONTH(fecha) BETWEEN 1 AND 3 THEN 1 ELSE 0 END) AS q1,
                SUM(CASE WHEN MONTH(fecha) BETWEEN 4 AND 6 THEN 1 ELSE 0 END) AS q2,
                SUM(CASE WHEN MONTH(fecha) BETWEEN 7 AND 9 THEN 1 ELSE 0 END) AS q3,
                SUM(CASE WHEN MONTH(fecha) BETWEEN 10 AND 12 THEN 1 ELSE 0 END) AS q4
            FROM animal_decesos
            WHERE fecha IS NOT NULL
              AND YEAR(fecha) = ?
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar decesosTrimestrales: " . $this->db->error);
        }
        $stmt->bind_param('i', $anio);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0];
        $stmt->close();

        return [
            (int)($row['q1'] ?? 0),
            (int)($row['q2'] ?? 0),
            (int)($row['q3'] ?? 0),
            (int)($row['q4'] ?? 0),
        ];
    }

    private function getSaludResumen(): array
    {
        $anioActual = (int) date('Y');
        $mesActual  = (int) date('n');

        $mesAnterior      = ($mesActual === 1) ? 12 : $mesActual - 1;
        $anioMesAnterior  = ($mesActual === 1) ? $anioActual - 1 : $anioActual;

        // 2.1 Nº tratamientos mes actual vs anterior
        $sqlTrat = "
            SELECT
                SUM(CASE 
                        WHEN YEAR(fecha_evento) = ? AND MONTH(fecha_evento) = ? 
                        THEN 1 ELSE 0 END) AS actual,
                SUM(CASE 
                        WHEN YEAR(fecha_evento) = ? AND MONTH(fecha_evento) = ? 
                        THEN 1 ELSE 0 END) AS anterior
            FROM animal_salud
            WHERE deleted_at IS NULL
              AND tipo_evento = 'TRATAMIENTO'
        ";
        $stmt = $this->db->prepare($sqlTrat);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar tratamientos_mes: " . $this->db->error);
        }
        $stmt->bind_param('iiii', $anioActual, $mesActual, $anioMesAnterior, $mesAnterior);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: ['actual'=>0,'anterior'=>0];
        $stmt->close();

        $tratamientosMes = [
            'actual'   => (int) ($row['actual'] ?? 0),
            'anterior' => (int) ($row['anterior'] ?? 0),
        ];

        // 2.2 Nº casos de enfermedad por tipo (diagnóstico)
        $sqlEnf = "
            SELECT 
                COALESCE(diagnostico, 'SIN_DIAGNOSTICO') AS tipo,
                COUNT(*) AS cantidad
            FROM animal_salud
            WHERE deleted_at IS NULL
              AND tipo_evento = 'ENFERMEDAD'
            GROUP BY COALESCE(diagnostico, 'SIN_DIAGNOSTICO')
        ";
        $stmt = $this->db->prepare($sqlEnf);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar enfermedades_por_tipo: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $enfermedadesPorTipo = [];
        while ($r = $res->fetch_assoc()) {
            $enfermedadesPorTipo[] = [
                'tipo'     => $r['tipo'],
                'cantidad' => (int) $r['cantidad'],
            ];
        }
        $stmt->close();

        // 2.3 Desenlaces trimestrales: Recuperados vs decesos (año actual y anterior)
        $recAct  = $this->getRecuperadosTrimestrales($anioActual);
        $decAct  = $this->getDecesosTrimestrales($anioActual);
        $recPrev = $this->getRecuperadosTrimestrales($anioActual - 1);
        $decPrev = $this->getDecesosTrimestrales($anioActual - 1);

        $trimestres = [];
        for ($i = 1; $i <= 4; $i++) {
            $idx = $i - 1;
            $trimestres[] = [
                'trimestre'                  => 'T' . $i,
                'recuperados'                => $recAct[$idx],
                'decesos'                    => $decAct[$idx],
                'recuperados_anio_anterior'  => $recPrev[$idx],
                'decesos_anio_anterior'      => $decPrev[$idx],
            ];
        }

        return [
            'salud' => [
                'tratamientos_mes'      => $tratamientosMes,
                'enfermedades_por_tipo' => $enfermedadesPorTipo,
                'eventos_trimestrales'  => $trimestres,
            ],
        ];
    }

    /* =========================================================
     * 3. PESOS DE LOS ANIMALES
     * ========================================================= */

    private function getPesosResumen(): array
    {
        // 3.1 Scatter plot: pesos + edad en meses
        $sqlLista = "
            SELECT 
                ap.animal_id,
                ap.peso_kg AS peso,
                TIMESTAMPDIFF(
                    MONTH, 
                    a.fecha_nacimiento, 
                    ap.fecha_peso
                ) AS edad_meses
            FROM animal_pesos ap
            INNER JOIN animales a ON a.animal_id = ap.animal_id
            WHERE ap.deleted_at IS NULL
              AND a.deleted_at IS NULL
              AND a.fecha_nacimiento IS NOT NULL
        ";
        $stmt = $this->db->prepare($sqlLista);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar lista_pesos: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $listaPesos = [];
        while ($row = $res->fetch_assoc()) {
            $listaPesos[] = [
                'animal_id'  => $row['animal_id'],
                'peso'       => (float) $row['peso'],
                'edad_meses' => (int) $row['edad_meses'],
            ];
        }
        $stmt->close();

        // 3.2 Curva de peso ideal desde tabuladores_peso
        $sqlIdeal = "
            SELECT 
                FLOOR(((edad_min_dias + edad_max_dias) / 2) / 30) AS edad_meses,
                AVG(peso_ideal) AS peso
            FROM tabuladores_peso
            GROUP BY FLOOR(((edad_min_dias + edad_max_dias) / 2) / 30)
            ORDER BY edad_meses ASC
        ";
        $stmt = $this->db->prepare($sqlIdeal);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar peso_ideal: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $pesoIdeal = [];
        while ($row = $res->fetch_assoc()) {
            $pesoIdeal[] = [
                'edad_meses' => (int) $row['edad_meses'],
                'peso'       => isset($row['peso']) ? (float) $row['peso'] : 0.0,
            ];
        }
        $stmt->close();

        // 3.3 Promedio de peso por etapa productiva (Levante / Ceba)
        $sqlProm = "
            SELECT 
                a.etapa_productiva AS etapa,
                AVG(ap.peso_kg) AS promedio_peso
            FROM animal_pesos ap
            INNER JOIN animales a ON a.animal_id = ap.animal_id
            WHERE ap.deleted_at IS NULL
              AND a.deleted_at IS NULL
              AND a.etapa_productiva IN ('LEVANTE','CEBA')
            GROUP BY a.etapa_productiva
        ";
        $stmt = $this->db->prepare($sqlProm);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar promedios_por_etapa: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $promedios = [];
        while ($row = $res->fetch_assoc()) {
            $promedios[] = [
                'etapa'         => $row['etapa'],
                'promedio_peso' => (float) $row['promedio_peso'],
            ];
        }
        $stmt->close();

        return [
            'pesos' => [
                'lista_pesos'         => $listaPesos,
                'peso_ideal'          => $pesoIdeal,
                'promedios_por_etapa' => $promedios,
            ],
        ];
    }

    /* =========================================================
     * 4. CAMADAS BAJAS (Muertes lactantes)
     * ========================================================= */

    private function getCamadasBajasResumen(): array
    {
        // 4.1 Muertes lactantes por mes (año actual)
        $sqlMes = "
            SELECT 
                MONTH(cb.fecha_baja) AS mes_num,
                COUNT(*) AS cantidad
            FROM camada_bajas cb
            WHERE cb.deleted_at IS NULL
              AND YEAR(cb.fecha_baja) = YEAR(CURDATE())
            GROUP BY YEAR(cb.fecha_baja), MONTH(cb.fecha_baja)
            ORDER BY YEAR(cb.fecha_baja), MONTH(cb.fecha_baja)
        ";
        $stmt = $this->db->prepare($sqlMes);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar muertes_por_mes: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $muertesMes = [];
        while ($row = $res->fetch_assoc()) {
            $muertesMes[] = [
                'mes'      => $this->getMonthNameEs((int)$row['mes_num']),
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        // 4.2 Causas de muerte (sumando cantidad)
        $sqlCausas = "
            SELECT 
                COALESCE(cb.causa_deceso, 'SIN_CAUSA') AS causa,
                SUM(cb.cantidad) AS cantidad
            FROM camada_bajas cb
            WHERE cb.deleted_at IS NULL
            GROUP BY COALESCE(cb.causa_deceso, 'SIN_CAUSA')
        ";
        $stmt = $this->db->prepare($sqlCausas);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar causas_muerte: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $causas = [];
        while ($row = $res->fetch_assoc()) {
            $causas[] = [
                'causa'    => $row['causa'],
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        // 4.3 Promedio de edad a la muerte (en días)
        $sqlPromEdad = "
            SELECT 
                AVG(DATEDIFF(cb.fecha_baja, p.fecha_parto)) AS promedio_dias
            FROM camada_bajas cb
            INNER JOIN camadas c ON c.camada_id = cb.camada_id
            INNER JOIN partos  p ON p.parto_id  = c.parto_id
            WHERE cb.deleted_at IS NULL
              AND c.deleted_at IS NULL
              AND p.deleted_at IS NULL
        ";
        $stmt = $this->db->prepare($sqlPromEdad);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar promedio_edad_muerte_dias: " . $this->db->error);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $promedioDias = isset($row['promedio_dias']) ? (float) $row['promedio_dias'] : 0.0;

        return [
            'camadas_bajas' => [
                'muertes_por_mes'            => $muertesMes,
                'causas_muerte'              => $causas,
                'promedio_edad_muerte_dias'  => $promedioDias,
            ],
        ];
    }

    /* =========================================================
     * 5. DAÑOS EN INFRAESTRUCTURA
     * ========================================================= */

    private function getInfraestructuraResumen(): array
    {
        // 5.1 Nº de daños por mes (año actual)
        $sqlMes = "
            SELECT 
                MONTH(r.fecha_reporte) AS mes_num,
                COUNT(*) AS cantidad
            FROM reportes_dano r
            WHERE r.deleted_at IS NULL
              AND YEAR(r.fecha_reporte) = YEAR(CURDATE())
            GROUP BY YEAR(r.fecha_reporte), MONTH(r.fecha_reporte)
            ORDER BY YEAR(r.fecha_reporte), MONTH(r.fecha_reporte)
        ";
        $stmt = $this->db->prepare($sqlMes);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar danos_mensuales: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $danosMensuales = [];
        while ($row = $res->fetch_assoc()) {
            $danosMensuales[] = [
                'mes'      => $this->getMonthNameEs((int)$row['mes_num']),
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        // 5.2 Daños por tipo
        $sqlTipo = "
            SELECT 
                COALESCE(
                    a.tipo_area,
                    'DESCONOCIDO'
                ) AS tipo,
                COUNT(*) AS cantidad
            FROM reportes_dano r
            LEFT JOIN areas a ON a.area_id = r.area_id
            WHERE r.deleted_at IS NULL
            GROUP BY COALESCE(a.tipo_area, 'DESCONOCIDO')
        ";
        $stmt = $this->db->prepare($sqlTipo);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar danos_por_tipo: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $danosPorTipo = [];
        while ($row = $res->fetch_assoc()) {
            $danosPorTipo[] = [
                'tipo'     => $row['tipo'],
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        return [
            'infraestructura' => [
                'danos_mensuales' => $danosMensuales,
                'danos_por_tipo'  => $danosPorTipo,
            ],
        ];
    }

    /* =========================================================
     * 6. DECESOS GENERALES
     * ========================================================= */

    private function getDecesosResumen(): array
    {
        // 6.1 Muertes mensuales
        $sqlMes = "
            SELECT
                MONTH(d.fecha) AS mes_num,
                COUNT(*) AS cantidad
            FROM animal_decesos d
            WHERE d.fecha IS NOT NULL
            GROUP BY YEAR(d.fecha), MONTH(d.fecha)
            ORDER BY YEAR(d.fecha), MONTH(d.fecha)
        ";
        $stmt = $this->db->prepare($sqlMes);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar muertes_mensuales: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $muertesMensuales = [];
        while ($row = $res->fetch_assoc()) {
            $muertesMensuales[] = [
                'mes'      => $this->getMonthNameEs((int)$row['mes_num']),
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        // 6.2 Top causas probables
        $sqlCausas = "
            SELECT 
                COALESCE(d.causa_probable, 'SIN_CAUSA') AS causa,
                COUNT(*) AS cantidad
            FROM animal_decesos d
            GROUP BY COALESCE(d.causa_probable, 'SIN_CAUSA')
            ORDER BY cantidad DESC
            LIMIT 10
        ";
        $stmt = $this->db->prepare($sqlCausas);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar causas_probables: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $causas = [];
        while ($row = $res->fetch_assoc()) {
            $causas[] = [
                'causa'    => $row['causa'],
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        return [
            'decesos' => [
                'muertes_mensuales' => $muertesMensuales,
                'causas_probables'  => $causas,
            ],
        ];
    }

    /* =========================================================
     * 7. CAMADAS (Producción)
     * ========================================================= */

    private function getCamadasResumen(): array
    {
        // 7.1 Lactantes nacidos vs muertos por mes (año actual)
        $sqlNacMuertos = "
            SELECT 
                MONTH(p.fecha_parto) AS mes_num,
                SUM(p.crias_machos + p.crias_hembras) AS nacidos,
                COALESCE(SUM(cb.cantidad), 0) AS muertos
            FROM partos p
            LEFT JOIN camadas c 
              ON c.parto_id = p.parto_id 
             AND c.deleted_at IS NULL
            LEFT JOIN camada_bajas cb 
              ON cb.camada_id = c.camada_id
             AND cb.deleted_at IS NULL
            WHERE p.deleted_at IS NULL
              AND YEAR(p.fecha_parto) = YEAR(CURDATE())
            GROUP BY YEAR(p.fecha_parto), MONTH(p.fecha_parto)
            ORDER BY YEAR(p.fecha_parto), MONTH(p.fecha_parto)
        ";
        $stmt = $this->db->prepare($sqlNacMuertos);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar nacidos_vs_muertos: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $nacidosVsMuertos = [];
        while ($row = $res->fetch_assoc()) {
            $nacidosVsMuertos[] = [
                'mes'     => $this->getMonthNameEs((int)$row['mes_num']),
                'nacidos' => (int) $row['nacidos'],
                'muertos' => (int) $row['muertos'],
            ];
        }
        $stmt->close();

        // 7.2 Promedio de peso por camada por mes (usando peso_promedio_kg del parto)
        $sqlPesoMes = "
            SELECT 
                MONTH(p.fecha_parto) AS mes_num,
                AVG(p.peso_promedio_kg) AS promedio_peso
            FROM partos p
            WHERE p.deleted_at IS NULL
              AND p.peso_promedio_kg IS NOT NULL
              AND YEAR(p.fecha_parto) = YEAR(CURDATE())
            GROUP BY YEAR(p.fecha_parto), MONTH(p.fecha_parto)
            ORDER BY YEAR(p.fecha_parto), MONTH(p.fecha_parto)
        ";
        $stmt = $this->db->prepare($sqlPesoMes);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar peso_promedio_mes: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $pesoPromedioMes = [];
        while ($row = $res->fetch_assoc()) {
            $pesoPromedioMes[] = [
                'mes'           => $this->getMonthNameEs((int)$row['mes_num']),
                'promedio_peso' => isset($row['promedio_peso']) ? (float) $row['promedio_peso'] : 0.0,
            ];
        }
        $stmt->close();

        // 7.3 Promedio de crías por parto por mes
        $sqlCriasMes = "
            SELECT
                MONTH(p.fecha_parto) AS mes_num,
                AVG(p.crias_machos + p.crias_hembras) AS promedio
            FROM partos p
            WHERE p.deleted_at IS NULL
              AND YEAR(p.fecha_parto) = YEAR(CURDATE())
            GROUP BY YEAR(p.fecha_parto), MONTH(p.fecha_parto)
            ORDER BY YEAR(p.fecha_parto), MONTH(p.fecha_parto)
        ";
        $stmt = $this->db->prepare($sqlCriasMes);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar promedio_crias_mes: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $promedioCriasMes = [];
        while ($row = $res->fetch_assoc()) {
            $promedioCriasMes[] = [
                'mes'      => $this->getMonthNameEs((int)$row['mes_num']),
                'promedio' => isset($row['promedio']) ? (float) $row['promedio'] : 0.0,
            ];
        }
        $stmt->close();

        return [
            'camadas' => [
                'nacidos_vs_muertos' => $nacidosVsMuertos,
                'peso_promedio_mes'  => $pesoPromedioMes,
                'promedio_crias_mes' => $promedioCriasMes,
            ],
        ];
    }

    /* =========================================================
     * 8. INCIDENCIAS
     * ========================================================= */

    private function getIncidenciasResumen(): array
    {
        // 8.1 Incidencias por tipo
        $sqlTipo = "
            SELECT 
                i.tipo,
                COUNT(*) AS cantidad
            FROM incidencias i
            WHERE i.deleted_at IS NULL
            GROUP BY i.tipo
        ";
        $stmt = $this->db->prepare($sqlTipo);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar incidencias_por_tipo: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $porTipo = [];
        while ($row = $res->fetch_assoc()) {
            $porTipo[] = [
                'tipo'     => $row['tipo'],
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        // 8.2 Incidencias mensuales (año actual)
        $sqlMensuales = "
            SELECT 
                MONTH(i.fecha_evento) AS mes_num,
                COUNT(*) AS cantidad
            FROM incidencias i
            WHERE i.deleted_at IS NULL
              AND YEAR(i.fecha_evento) = YEAR(CURDATE())
            GROUP BY YEAR(i.fecha_evento), MONTH(i.fecha_evento)
            ORDER BY YEAR(i.fecha_evento), MONTH(i.fecha_evento)
        ";
        $stmt = $this->db->prepare($sqlMensuales);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar incidencias_mensuales: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $mensuales = [];
        while ($row = $res->fetch_assoc()) {
            $mensuales[] = [
                'mes'      => $this->getMonthNameEs((int)$row['mes_num']),
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        // 8.3 Incidencias por área/corral
        $sqlArea = "
            SELECT 
                COALESCE(
                    a.nombre_personalizado,
                    CONCAT(a.tipo_area, ' ', COALESCE(a.numeracion, '')),
                    'SIN_AREA'
                ) AS area,
                COUNT(*) AS cantidad
            FROM incidencias i
            LEFT JOIN areas a ON a.area_id = i.area_id
            WHERE i.deleted_at IS NULL
            GROUP BY COALESCE(
                a.nombre_personalizado,
                CONCAT(a.tipo_area, ' ', COALESCE(a.numeracion, '')),
                'SIN_AREA'
            )
            ORDER BY cantidad DESC
        ";
        $stmt = $this->db->prepare($sqlArea);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparar incidencias_por_area: " . $this->db->error);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $porArea = [];
        while ($row = $res->fetch_assoc()) {
            $porArea[] = [
                'area'     => $row['area'],
                'cantidad' => (int) $row['cantidad'],
            ];
        }
        $stmt->close();

        return [
            'incidencias' => [
                'por_tipo' => $porTipo,
                'mensuales'=> $mensuales,
                'por_area' => $porArea,
            ],
        ];
    }

    /* =========================================================
     * MÉTODO PÚBLICO PRINCIPAL
     * ========================================================= */

    /**
     * Devuelve el JSON completo del dashboard tal como lo define DASH.pdf.
     */
    public function obtenerResumen(): array
    {
        // Combinar todas las secciones en un solo array
        $animales        = $this->getAnimalesResumen();
        $salud           = $this->getSaludResumen();
        $pesos           = $this->getPesosResumen();
        $camadasBajas    = $this->getCamadasBajasResumen();
        $infraestructura = $this->getInfraestructuraResumen();
        $decesos         = $this->getDecesosResumen();
        $camadas         = $this->getCamadasResumen();
        $incidencias     = $this->getIncidenciasResumen();

        // Estructura final
        return array_merge(
            $animales,
            $salud,
            $pesos,
            $camadasBajas,
            $infraestructura,
            $decesos,
            $camadas,
            $incidencias
        );
    }
}
