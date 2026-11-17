<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
define('APP_NAME', envv('APP_NAME','School Tuition and Payment Monitoring System'));
define('APP_DEBUG', filter_var(envv('APP_DEBUG','false'), FILTER_VALIDATE_BOOLEAN));
define('APP_URL', rtrim(envv('APP_URL','http://localhost/school_tuition'), '/'));
