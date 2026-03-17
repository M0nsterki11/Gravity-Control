<?php

use App\Controller\AuthController;
use App\Controller\ReservationController;
use App\Controller\SessionController;
use App\Http\JsonResponse;

// Ucitava aplikacijski bootstrap i PDO konekciju.
$pdo = require __DIR__ . '/../src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($scriptBasePath !== '' && str_starts_with($path, $scriptBasePath)) {
    $path = substr($path, strlen($scriptBasePath));
}

// Jednostavan router koji mapira URL na odgovarajuci controller endpoint.
switch ($path) {
    case '/api/login':
        AuthController::login($pdo);
        break;
    case '/api/register':
        AuthController::register($pdo);
        break;
    case '/api/logout':
        AuthController::logout($pdo);
        break;
    case '/api/sessions':
        SessionController::list($pdo);
        break;
    case '/api/reserve':
        ReservationController::reserve($pdo);
        break;
    default:
        JsonResponse::error('Endpoint nije pronaden.', 404);
}
