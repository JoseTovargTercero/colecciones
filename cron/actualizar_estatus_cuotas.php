<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/**
 * Ejecuta la actualización de estatus de cuotas.
 * @param bool $echoMode Si true, imprime salida legible; si false, devuelve array con resumen.
 * @return array{success:bool, message:string, actualizados:int, errores:int}|void
 */
function ejecutarActualizacionEstatusCuotas(bool $echoMode = true)
{
    $log = function(string $msg) use ($echoMode) {
        $line = "[" . date('Y-m-d H:i:s') . "] " . trim($msg) . "\n";
        if ($echoMode) {
            echo $line;
        }
        file_put_contents(__DIR__ . '/cron_cuotas.log', $line, FILE_APPEND);
    };

    $log("Iniciando actualización de estatus de cuotas...");

    try {
        $db = Database::getInstance();

        // Cargar días de retraso permitidos por empresa
        $empresas = [];
        $res = $db->query("SELECT id, dias_retraso_permitido FROM empresas");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $empresas[(int)$row['id']] = (int)($row['dias_retraso_permitido'] ?? 3);
            }
            $res->free();
        }
        $log("Empresas cargadas: " . count($empresas));

        if (empty($empresas)) {
            $msg = "No hay empresas registradas.";
            $log($msg);
            if (!$echoMode) {
                return ['success' => true, 'message' => $msg, 'actualizados' => 0, 'errores' => 0];
            }
            return;
        }

        // 🐴 ponytail: direct array iteration for both table types instead of code duplication or abstractions
        $queries = [
            [
                'select' => "SELECT c.id, c.estatus_pago, c.fecha_pago, cc.empresa_id
                             FROM cuotas_coleccion c
                             INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
                             INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
                             WHERE c.estatus_pago IN ('pendiente', 'dentro_de_margen')",
                'update_margen' => "UPDATE cuotas_coleccion SET estatus_pago = 'dentro_de_margen' WHERE id = ?",
                'update_vencido' => "UPDATE cuotas_coleccion SET estatus_pago = 'vencido' WHERE id = ?",
                'nombre' => 'cuotas_coleccion'
            ],
            [
                'select' => "SELECT c.id, c.estatus_pago, c.fecha_pago, aa.empresa_id
                             FROM cuotas_articulo c
                             INNER JOIN asignaciones_articulos aa ON c.asignacion_id = aa.id
                             WHERE c.estatus_pago IN ('pendiente', 'dentro_de_margen')",
                'update_margen' => "UPDATE cuotas_articulo SET estatus_pago = 'dentro_de_margen' WHERE id = ?",
                'update_vencido' => "UPDATE cuotas_articulo SET estatus_pago = 'vencido' WHERE id = ?",
                'nombre' => 'cuotas_articulo'
            ]
        ];

        $hoy = new DateTime('today');
        $actualizados = 0;
        $errores = 0;

        foreach ($queries as $q) {
            $res = $db->query($q['select']);
            if (!$res) throw new RuntimeException("Error en consulta {$q['nombre']}: " . $db->error);

            $stmtMargen = $db->prepare($q['update_margen']);
            $stmtVencido = $db->prepare($q['update_vencido']);
            if (!$stmtMargen || !$stmtVencido) throw new RuntimeException("Error prepare en {$q['nombre']}: " . $db->error);

            while ($row = $res->fetch_assoc()) {
                $cuotaId = (int)$row['id'];
                $estatus = $row['estatus_pago'];
                $empresaId = (int)$row['empresa_id'];
                $diasRetraso = $empresas[$empresaId] ?? 3;

                try {
                    $fechaVenc = new DateTime($row['fecha_pago']);
                    $fechaLimite = clone $fechaVenc;
                    $fechaLimite->modify("+{$diasRetraso} days");

                    if ($estatus === 'pendiente') {
                        if ($hoy >= $fechaVenc && $hoy <= $fechaLimite) {
                            $stmtMargen->bind_param('i', $cuotaId);
                            $stmtMargen->execute();
                            $actualizados++;
                            $log("  {$q['nombre']} #{$cuotaId}: pendiente -> dentro_de_margen (venc: {$row['fecha_pago']}, tope: {$fechaLimite->format('Y-m-d')})");
                        } elseif ($hoy > $fechaLimite) {
                            $stmtVencido->bind_param('i', $cuotaId);
                            $stmtVencido->execute();
                            $actualizados++;
                            $log("  {$q['nombre']} #{$cuotaId}: pendiente -> vencido (venc: {$row['fecha_pago']}, tope: {$fechaLimite->format('Y-m-d')})");
                        }
                    } elseif ($estatus === 'dentro_de_margen') {
                        if ($hoy > $fechaLimite) {
                            $stmtVencido->bind_param('i', $cuotaId);
                            $stmtVencido->execute();
                            $actualizados++;
                            $log("  {$q['nombre']} #{$cuotaId}: dentro_de_margen -> vencido (venc: {$row['fecha_pago']}, tope: {$fechaLimite->format('Y-m-d')})");
                        }
                    }
                } catch (Exception $e) {
                    $errores++;
                    $log("  Error en {$q['nombre']} #{$cuotaId}: " . $e->getMessage());
                }
            }

            $res->free();
            $stmtMargen->close();
            $stmtVencido->close();
        }

        $log("Actualizados: {$actualizados}, Errores: {$errores}");
        $log("Finalizado.");

        if (!$echoMode) {
            return ['success' => true, 'message' => "OK", 'actualizados' => $actualizados, 'errores' => $errores];
        }
    } catch (Throwable $e) {
        $log("ERROR FATAL: " . $e->getMessage());
        if (!$echoMode) {
            return ['success' => false, 'message' => $e->getMessage(), 'actualizados' => 0, 'errores' => 0];
        }
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    ejecutarActualizacionEstatusCuotas(true);
}
