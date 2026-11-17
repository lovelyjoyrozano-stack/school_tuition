<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$envFile = $root . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if (strlen($v) >= 2 && (($v[0] === '"' && str_ends_with($v, '"')) || ($v[0] === "'" && str_ends_with($v, "'")))) $v = substr($v, 1, -1);
        if ($k !== '' && getenv($k) === false) { putenv($k . '=' . $v); $_ENV[$k] = $v; }
    }
}
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Manila');
$logDir = $root . '/storage/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0770, true);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/app.log');
function envv(string $key, ?string $default = null): ?string { $v = getenv($key); return $v === false ? $default : $v; }
function app_root(): string { return dirname(__DIR__); }
