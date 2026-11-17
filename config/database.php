<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
function db(): PDO {
    static $pdo = null; if ($pdo instanceof PDO) return $pdo;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', envv('DB_HOST','127.0.0.1'), envv('DB_PORT','3306'), envv('DB_NAME','school_tuition_db'));
    try { $pdo = new PDO($dsn, envv('DB_USER','root'), envv('DB_PASS',''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
        $pdo->exec("SET time_zone = '+08:00'"); }
    catch (PDOException $e) { error_log('[DB] '.$e->getMessage()); throw new RuntimeException('Database connection failed. Check the local .env settings.'); }
    return $pdo;
}
