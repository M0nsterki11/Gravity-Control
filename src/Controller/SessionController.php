<?php

namespace App\Controller;

use App\Http\JsonResponse;
use App\Middleware\MethodMiddleware;
use App\Repository\SessionRepository;
use App\Service\SessionService;
use PDO;
use Throwable;

final class SessionController
{
    public static function list(PDO $pdo): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        MethodMiddleware::require('GET');

        try {
            $service = new SessionService(new SessionRepository($pdo));
            $sessions = $service->listActive();

            JsonResponse::success([
                'sessions' => $sessions,
            ]);
        } catch (Throwable $e) {
            JsonResponse::error('Greska kod dohvacanja termina.', 500);
        }
    }
}
