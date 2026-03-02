<?php
session_start();
require __DIR__ . '/security.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_http_method('POST');

if (!empty($_SESSION['user_id'])) {
    require_valid_csrf_token();
}

// Očisti session podatke
$_SESSION = [];

// Obriši session cookie (PHPSESSID deafult od phpa) 
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Odjava uspješna.',
]);
