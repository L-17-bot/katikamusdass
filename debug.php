<?php
// debug.php - lightweight diagnostics for local environment
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$out = [
    'time' => date('c'),
    'php_version' => phpversion(),
    'using_sqlite_fallback' => defined('USE_SQLITE_FALLBACK') && USE_SQLITE_FALLBACK === true,
    'db' => [],
    'uploads_writable' => is_writable(__DIR__ . DIRECTORY_SEPARATOR . 'uploads'),
    'data_writable' => is_writable(__DIR__ . DIRECTORY_SEPARATOR . 'data'),
];

// Test PDO connection and simple query
try {
    $out['db'] = [ 'connected' => true ];
    // detect driver
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $out['db']['driver'] = $driver;
    // run a simple query depending on driver
    if ($driver === 'sqlite') {
        $res = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name LIMIT 10");
        $out['db']['tables'] = $res ? $res->fetchAll(PDO::FETCH_COLUMN) : [];
    } else {
        // Try listing tables for MySQL
        try {
            $res = $pdo->query("SHOW TABLES LIMIT 10");
            $out['db']['tables'] = $res ? $res->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (Exception $e) {
            $out['db']['tables_error'] = $e->getMessage();
        }
    }
} catch (Exception $e) {
    $out['db'] = [ 'connected' => false, 'error' => $e->getMessage() ];
}

echo json_encode($out, JSON_PRETTY_PRINT);
