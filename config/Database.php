<?php
class Database
{
    private static $instance = null;
    private $mysqli;

    private function __construct()
    {
        $dir = '/home/gitcomco/.env_colecciones';
        $this->loadEnv($dir);

        $host = 'localhost';
        // Usamos null coalescing seguro
        $dbname = $_ENV['DB_NAME'] ?? 'colecciones';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        // Definiciones seguras
        if (!defined('APP_URL')) {
            $appUrl = isset($_ENV['APP_URL']) ? $_ENV['APP_URL'] . '/colecciones/' : 'https://iseller-tiendas.com/colecciones/';
            define('APP_URL', $appUrl);
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->mysqli = new mysqli($host, $username, $password, $dbname);
            $this->mysqli->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            $this->errorResponse(500, "Database connection error: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance; // Retorna el objeto Database, no mysqli
    }

    // Proxy para usar los métodos de mysqli
    public function getConnection() { return $this->mysqli; }

    private function loadEnv($filePath)
    {
        if (!file_exists($filePath)) return;

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;

            list($key, $value) = explode('=', $line, 2);
            // Limpiamos espacios Y comillas de ambos lados
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }

    // Mantén tus métodos startTransaction, commit, rollback...
    public function startTransaction() { $this->mysqli->begin_transaction(); }
    public function commit() { $this->mysqli->commit(); }
    public function rollback() { $this->mysqli->rollback(); }

    private function errorResponse($http_code, $message)
    {
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "[ERROR $http_code] $message" . PHP_EOL);
            exit(1);
        }
        http_response_code($http_code);
        header('Content-Type: application/json'); // Importante para APIs
        echo json_encode(['value' => false, 'message' => $message]);
        exit;
    }
}