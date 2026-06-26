<?php
// Asumiendo que este archivo estará en la carpeta 'models'
// /models/ConfiguracionModel.php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/ClientEnvironmentInfo.php';
require_once __DIR__ . '/../config/TimezoneManager.php';

class ConfiguracionModel
{
    private $db;
    private $table = 'configuraciones';
    private static $cache = []; // Caché estático simple por solicitud

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function nowWithAudit(): array
    {
        $env = new ClientEnvironmentInfo(APP_ROOT . '/app/config/geolite.mmdb');
        // userId=0 si aún no hay sesión; lo importante es setear contexto y tz
          $uuid    = UuidHelper::generateUUIDv4();
            $actorId = $_SESSION['user_id'] ?? $uuid;
        $env->applyAuditContext($this->db, $actorId);
        $tzManager = new TimezoneManager($this->db);
        $tzManager->applyTimezone();
        return [$env->getCurrentDatetime(), $env];
    }

    /**
     * Obtiene el valor de una clave de configuración.
     * Esta es la función principal que usarán otros modelos.
     *
     * @param string $key La clave a buscar (ej: 'permitir_registro_consanguineo')
     * @param mixed $defaultValue Valor a retornar si la clave no se encuentra
     * @return string|null
     */
    public function obtenerValor(string $key, $defaultValue = null)
    {
        // Usar caché para evitar múltiples consultas a la DB por la misma clave
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $sql = "SELECT config_value FROM {$this->table} WHERE config_key = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Error al preparar consulta de config: " . $this->db->error);
                return $defaultValue;
            }
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();

            $value = $row ? $row['config_value'] : $defaultValue;
            self::$cache[$key] = $value; // Guardar en caché
            return $value;

        } catch (\Exception $e) {
            error_log("Excepción al leer configuración '{$key}': " . $e->getMessage());
            return $defaultValue;
        }
    }

    /**
     * Lista todas las configuraciones (para el panel de admin).
     */
    public function listar(): array
    {
        $sql = "SELECT config_key, config_value, descripcion, updated_at 
                FROM {$this->table} 
                ORDER BY config_key ASC";
        $res = $this->db->query($sql);
        if (!$res) {
            throw new mysqli_sql_exception("Error al listar configuraciones: " . $this->db->error);
        }
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtiene una configuración por su clave (para el panel de admin).
     */
    public function obtenerPorClave(string $key): ?array
    {
        $sql = "SELECT config_key, config_value, descripcion, updated_at 
                FROM {$this->table} 
                WHERE config_key = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error al obtener config: " . $this->db->error);
        }
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Actualiza el valor de una configuración.
     *
     * @param string $key La clave a actualizar
     * @param string $value El nuevo valor
     * @return bool
     */
    public function actualizar(string $key, string $value): bool
    {
        [$now, $env] = $this->nowWithAudit();

        $sql = "UPDATE {$this->table} 
                SET config_value = ?, updated_at = ? 
                WHERE config_key = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error al preparar actualización de config: " . $this->db->error);
        }

        $stmt->bind_param('sss', $value, $now, $key);
        $ok = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($ok && $affected > 0) {
            self::$cache[$key] = $value; // Actualizar caché
            return true;
        }

        if ($affected === 0) {
            throw new RuntimeException("La clave de configuración '{$key}' no existe.");
        }

        return $ok;
    }
}