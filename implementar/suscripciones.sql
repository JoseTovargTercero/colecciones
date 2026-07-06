-- ============================================================
-- Tablas de suscripciones / pagos
-- Ejecutar una sola vez en la base de datos del proyecto.
-- ============================================================

CREATE TABLE IF NOT EXISTS configuracion_planes (
    id          INT PRIMARY KEY,
    nombre      VARCHAR(50)    NOT NULL,
    precio_mensual DECIMAL(10,2) NOT NULL,
    precio_anual   DECIMAL(10,2) NOT NULL,
    moneda      VARCHAR(3)     NOT NULL DEFAULT 'USD'
);

-- Plan único: mensual $25, anual $260 (ahorro vs. $300)
INSERT INTO configuracion_planes (id, nombre, precio_mensual, precio_anual, moneda)
VALUES (1, 'Plan Estándar', 25.00, 260.00, 'USD')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

CREATE TABLE IF NOT EXISTS suscripciones (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id   CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    plan_id      INT          NOT NULL DEFAULT 1,
    tipo_pago    ENUM('trial','mensual','anual') NOT NULL DEFAULT 'trial',
    fecha_inicio DATE         NOT NULL,
    fecha_fin    DATE         NOT NULL,
    estatus      ENUM('activa','vencida','cancelada','pendiente') NOT NULL DEFAULT 'activa',
    FOREIGN KEY (usuario_id) REFERENCES system_users(user_id),
    FOREIGN KEY (plan_id)    REFERENCES configuracion_planes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS historial_pagos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    suscripcion_id   INT           NOT NULL,
    monto_pagado     DECIMAL(10,2) NOT NULL,
    fecha_pago       DATE          NOT NULL,
    referencia_pago  VARCHAR(100)  NOT NULL,
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (suscripcion_id) REFERENCES suscripciones(id)
);
