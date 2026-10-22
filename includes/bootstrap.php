<?php
/**
 * Bootstrap — loaded at the top of every page.
 */
require_once __DIR__ . '/../config/config.php';

date_default_timezone_set(APP_TIMEZONE);
define('APP_ROOT', dirname(__DIR__));

// Base URL, e.g. "/cut-schedule" (works from any sub-folder of the app).
if (BASE_URL_OVERRIDE !== null) {
    define('BASE_URL', rtrim(BASE_URL_OVERRIDE, '/'));
} else {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#/(owner|staff|client|api)$#', $dir)) {
        $dir = dirname($dir);
    }
    define('BASE_URL', rtrim(str_replace('\\', '/', $dir), '/'));
}

spl_autoload_register(function (string $class) {
    $map = ['StandardRate' => 'Fee', 'SeniorDiscount' => 'Fee', 'PwdDiscount' => 'Fee'];
    $file = APP_ROOT . '/classes/' . ($map[$class] ?? $class) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/icons.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('cutschedule_sid');
    session_set_cookie_params(['path' => BASE_URL . '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Make sure the database is reachable before any page logic runs.
if (!defined('SKIP_DB_CHECK')) {
    try {
        Database::conn();
    } catch (PDOException $e) {
        if ((int) $e->getCode() === 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
            header('Location: ' . BASE_URL . '/setup.php');
            exit;
        }
        http_response_code(500);
        $msg = e($e->getMessage());
        echo "<!doctype html><meta charset='utf-8'><title>Database error</title>
              <div style='font-family:system-ui;max-width:560px;margin:80px auto;padding:28px;border:1px solid #e5d9bd;border-radius:12px;background:#fffdf8'>
              <h2 style='margin-top:0;color:#1b2a41'>Cannot connect to MySQL</h2>
              <p>Please start <b>MySQL</b> in the XAMPP Control Panel, then refresh this page.</p>
              <p style='color:#8a8f99;font-size:13px'>Details: {$msg}</p></div>";
        exit;
    }
}
