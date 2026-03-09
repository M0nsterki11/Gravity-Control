<?php

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\MethodMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Repository\ReservationRepository;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use App\Service\ReservationService;
use PDO;
use RuntimeException;
use Throwable;

final class ReservationController
{
    public static function reserve(PDO $pdo): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        MethodMiddleware::require('POST');

        $payload = Request::json();
        $requestedUserId = (int)($payload['userId'] ?? 0);
        $sessionId = isset($payload['sessionId']) ? (int)$payload['sessionId'] : 0;

        $sessionUserId = AuthMiddleware::requireUserId();
        CsrfMiddleware::requireValidToken();

        RateLimitMiddleware::enforce(
            $pdo,
            'reserve:user:' . $sessionUserId,
            10,
            300,
            'Previse pokusaja rezervacije. Pokusaj ponovo za par minuta.'
        );

        $service = new ReservationService(
            new UserRepository($pdo),
            new SessionRepository($pdo),
            new ReservationRepository($pdo)
        );

        try {
            $service->reserve($sessionUserId, $requestedUserId, $sessionId);
            JsonResponse::success([
                'message' => 'Rezervacija spremljena.',
            ]);
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage());
        } catch (Throwable $e) {
            JsonResponse::error('Neocekivana greska kod rezervacije.', 500);
        }
    }
}
