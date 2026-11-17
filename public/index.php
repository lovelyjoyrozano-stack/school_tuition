<?php
declare(strict_types=1);
// Allow PHP built-in server to serve static assets directly.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $assetPath = realpath(__DIR__ . $requestPath);
    $publicRoot = realpath(__DIR__);

    if (
        preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?)$/i', $requestPath)
        && $assetPath !== false
        && str_starts_with($assetPath, $publicRoot . DIRECTORY_SEPARATOR)
        && is_file($assetPath)
    ) {
        return false;
    }
}
require_once dirname(__DIR__).'/config/app.php';
require_once dirname(__DIR__).'/config/database.php';
require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/app/routes.php';


if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    ini_set('session.use_strict_mode','1');
    session_start();
}
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
try { route_app(); }
catch (Throwable $e) { error_log('[Application] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine()); http_response_code(500); echo 'Something went wrong. Please contact the system administrator.'; }
