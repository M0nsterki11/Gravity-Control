<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/rate_limit.php';
require_once __DIR__ . '/../backend/security.php';

return $pdo;
