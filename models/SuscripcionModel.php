<?php
require_once __DIR__ . '/../config/Database.php';

class SuscripcionModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Devuelve la suscripción activa del usuario o null
    public function activa(string $userId): ?array
    {
        $sql = "SELECT s.*, p.precio_mensual, p.precio_anual
                FROM suscripciones s
                JOIN configuracion_planes p ON p.id = s.plan_id
                WHERE s.usuario_id = ? AND s.estatus = 'activa'
                ORDER BY s.fecha_fin DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // Devuelve la suscripción pendiente del usuario o null
    public function pendiente(string $userId): ?array
    {
        $sql = "SELECT s.*, p.precio_mensual, p.precio_anual
                FROM suscripciones s
                JOIN configuracion_planes p ON p.id = s.plan_id
                WHERE s.usuario_id = ? AND s.estatus = 'pendiente'
                ORDER BY s.id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // Crea trial de 7 días al registrar un usuario
    public function crearTrial(string $userId): bool
    {
        $sql = "INSERT INTO suscripciones
                    (usuario_id, plan_id, tipo_pago, fecha_inicio, fecha_fin, estatus)
                VALUES (?, 1, 'trial', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'activa')";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Marca vencidas las suscripciones expiradas (llamado por cron o middleware)
    public function marcarVencidas(): int
    {
        $sql = "UPDATE suscripciones
                SET estatus = 'vencida'
                WHERE estatus = 'activa' AND fecha_fin < CURDATE()";
        $this->db->query($sql);
        return $this->db->affected_rows;
    }

    // Registra un pago y crea/renueva la suscripción
    public function procesarPago(string $userId, string $tipoPago, float $monto, string $fechaPago, string $referencia): bool
    {
        $dias = $tipoPago === 'anual' ? 365 : 30;

        $this->db->begin_transaction();
        try {
            // Cancelar cualquier pago pendiente anterior para evitar duplicados
            $upd = $this->db->prepare(
                "UPDATE suscripciones SET estatus = 'cancelada'
                 WHERE usuario_id = ? AND estatus = 'pendiente'"
            );
            $upd->bind_param('s', $userId);
            $upd->execute();
            $upd->close();

            // Nueva suscripción en estado 'pendiente' (se activará cuando el admin valide el pago)
            $ins = $this->db->prepare(
                "INSERT INTO suscripciones
                    (usuario_id, plan_id, tipo_pago, fecha_inicio, fecha_fin, estatus)
                 VALUES (?, 1, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL $dias DAY), 'pendiente')"
            );
            $ins->bind_param('ss', $userId, $tipoPago);
            $ins->execute();
            $suscId = $this->db->insert_id;
            $ins->close();

            // Historial
            $pay = $this->db->prepare(
                "INSERT INTO historial_pagos
                    (suscripcion_id, monto_pagado, fecha_pago, referencia_pago)
                 VALUES (?, ?, ?, ?)"
            );
            $pay->bind_param('idss', $suscId, $monto, $fechaPago, $referencia);
            $pay->execute();
            $pay->close();

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function planes(): array
    {
        $res = $this->db->query("SELECT * FROM configuracion_planes LIMIT 10");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
}
