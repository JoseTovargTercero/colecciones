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
    if ($echoMode) echo "[" . date('Y-m-d H:i:s') . "] Iniciando actualización de estatus de cuotas...\n";

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
        if ($echoMode) echo "Empresas cargadas: " . count($empresas) . "\n";

        if (empty($empresas)) {
            $msg = "No hay empresas registradas.";
            if ($echoMode) {
                echo $msg . "\n";
                return;
            }
            return ['success' => true, 'message' => $msg, 'actualizados' => 0, 'errores' => 0];
        }

        // Consultar cuotas pendientes o en margen con su empresa
        $sql = "SELECT c.id, c.estatus_pago, c.fecha_pago, cc.empresa_id
                FROM cuotas_coleccion c
                INNER JOIN asignaciones_colecciones ac ON c.asignacion_id = ac.id
                INNER JOIN colecciones_combos cc ON ac.coleccion_combo_id = cc.id
                WHERE c.estatus_pago IN ('pendiente', 'dentro_de_margen')";

        $res = $db->query($sql);
        if (!$res) {
            throw new RuntimeException("Error en consulta de cuotas: " . $db->error);
        }

        $hoy = new DateTime('today');
        $actualizados = 0;
        $errores = 0;

        $stmtMargen = $db->prepare(
            "UPDATE cuotas_coleccion SET estatus_pago = 'dentro_de_margen' WHERE id = ?"
        );
        if (!$stmtMargen) throw new RuntimeException("Error prepare margen: " . $db->error);

        $stmtVencido = $db->prepare(
            "UPDATE cuotas_coleccion SET estatus_pago = 'vencido' WHERE id = ?"
        );
        if (!$stmtVencido) throw new RuntimeException("Error prepare vencido: " . $db->error);

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
                        if ($echoMode) echo "  Cuota #{$cuotaId}: pendiente -> dentro_de_margen (venc: {$row['fecha_pago']}, tope: {$fechaLimite->format('Y-m-d')})\n";
                    } elseif ($hoy > $fechaLimite) {
                        $stmtVencido->bind_param('i', $cuotaId);
                        $stmtVencido->execute();
                        $actualizados++;
                        if ($echoMode) echo "  Cuota #{$cuotaId}: pendiente -> vencido (venc: {$row['fecha_pago']}, tope: {$fechaLimite->format('Y-m-d')})\n";
                    }
                } elseif ($estatus === 'dentro_de_margen') {
                    if ($hoy > $fechaLimite) {
                        $stmtVencido->bind_param('i', $cuotaId);
                        $stmtVencido->execute();
                        $actualizados++;
                        if ($echoMode) echo "  Cuota #{$cuotaId}: dentro_de_margen -> vencido (venc: {$row['fecha_pago']}, tope: {$fechaLimite->format('Y-m-d')})\n";
                    }
                }
            } catch (Exception $e) {
                $errores++;
                if ($echoMode) echo "  Error cuota #{$cuotaId}: " . $e->getMessage() . "\n";
            }
        }

        $res->free();
        $stmtMargen->close();
        $stmtVencido->close();

        if ($echoMode) {
            echo "Actualizados: {$actualizados}, Errores: {$errores}\n";
            echo "[" . date('Y-m-d H:i:s') . "] Finalizado.\n";
        } else {
            return ['success' => true, 'message' => "OK", 'actualizados' => $actualizados, 'errores' => $errores];
        }
    } catch (Throwable $e) {
        if ($echoMode) {
            echo "ERROR FATAL: " . $e->getMessage() . "\n";
        } else {
            return ['success' => false, 'message' => $e->getMessage(), 'actualizados' => 0, 'errores' => 0];
        }
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    ejecutarActualizacionEstatusCuotas(true);
}
