<?php

/**
 * Aplica reemplazos de texto para usuarios tipo 'vendedor'
 * según configuración en config/vendedor_replacements.json
 *
 * Reemplaza en:
 * - Texto visible entre tags HTML
 * - Atributos placeholder, title, aria-label
 *
 * Ignora contenido dentro de <script>.
 *
 * @param string $output   HTML ya renderizado
 * @param string $viewName Nombre del archivo de vista (ej: 'main.php')
 * @return string          HTML con reemplazos aplicados (sin cambios si no es vendedor)
 */
function aplicarReemplazosVendedor(string $output, string $viewName): string
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return $output;

    static $userTipo = null;
    if ($userTipo === null) {
        require_once APP_ROOT . 'models/SystemUserModel.php';
        $userModel = new SystemUserModel();
        $user = $userModel->obtenerPorId($userId);
        $userTipo = $user['tipo'] ?? '';
    }

    if ($userTipo !== 'vendedor') return $output;

    static $replacements = null;
    if ($replacements === null) {
        $path = APP_ROOT . 'config/vendedor_replacements.json';
        if (!file_exists($path)) return $output;
        $replacements = json_decode(file_get_contents($path), true) ?: [];
    }

    $rules = $replacements[$viewName] ?? [];
    if (!$rules) return $output;

    // 1. Remove <script>...</script> blocks temporarily
    $scripts = [];
    $output = preg_replace_callback('/<script\b[^>]*>.*?<\/script>/si', function ($m) use (&$scripts) {
        $key = '%%%SCRIPT_' . count($scripts) . '%%%';
        $scripts[$key] = $m[0];
        return $key;
    }, $output);

    // 2. Replace in placeholder, title, aria-label attributes
    $attrPattern = '/\b(placeholder|title|aria-label)="([^"]*)"/i';
    $output = preg_replace_callback($attrPattern, function ($m) use ($rules) {
        return $m[1] . '="' . str_replace(array_keys($rules), array_values($rules), $m[2]) . '"';
    }, $output);

    // 3. Replace in visible text (between > and <)
    $output = preg_replace_callback('/>([^<]+)</s', function ($m) use ($rules) {
        $text = $m[1];
        $replaced = str_replace(array_keys($rules), array_values($rules), $text);
        return '>' . $replaced . '<';
    }, $output);

    // 4. Restore scripts
    $output = str_replace(array_keys($scripts), array_values($scripts), $output);

    return $output;
}