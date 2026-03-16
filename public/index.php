<?php

use App\Controller\AuthController;
use App\Controller\ReservationController;
use App\Controller\SessionController;
use App\Http\JsonResponse;

// Ucitava aplikacijski bootstrap i PDO konekciju.
$pdo = require __DIR__ . '/../src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = '/gravity-control/public';
if (str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
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
