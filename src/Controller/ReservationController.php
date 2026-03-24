<?php

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Middleware\AdminMiddleware;
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
    // Endpoint za rezervaciju: auth, CSRF, rate limit i upis rezervacije.
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

    // Admin CRUD endpoint za pregled i uredjivanje rezervacija.
    public static function adminCrud(PDO $pdo): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        AdminMiddleware::requireAdminUserId();

        $users = new UserRepository($pdo);
        $sessions = new SessionRepository($pdo);
        $reservations = new ReservationRepository($pdo);
        $service = new ReservationService($users, $sessions, $reservations);
        $method = Request::method();

        try {
            switch ($method) {
                case 'GET':
                    JsonResponse::success([
                        'reservations' => $service->listForAdmin(),
                        'users' => $users->listAllBasic(),
                        'sessions' => $sessions->listAll(),
                    ]);
                    break;
                case 'POST':
                    CsrfMiddleware::requireValidToken();
                    $payload = Request::json();
                    $service->createForAdmin(
                        (int)($payload['userId'] ?? 0),
                        (int)($payload['sessionId'] ?? 0)
                    );
                    JsonResponse::success([
                        'message' => 'Rezervacija je uspjesno kreirana.',
                    ]);
                    break;
                case 'PUT':
                    CsrfMiddleware::requireValidToken();
                    $payload = Request::json();
                    $service->updateForAdmin(
                        (int)($payload['id'] ?? 0),
                        (int)($payload['userId'] ?? 0),
                        (int)($payload['sessionId'] ?? 0)
                    );
                    JsonResponse::success([
                        'message' => 'Rezervacija je uspjesno azurirana.',
                    ]);
                    break;
                case 'DELETE':
                    CsrfMiddleware::requireValidToken();
                    $payload = Request::json();
                    $service->deleteForAdmin((int)($payload['id'] ?? 0));
                    JsonResponse::success([
                        'message' => 'Rezervacija je uspjesno obrisana.',
                    ]);
                    break;
                default:
                    header('Allow: GET, POST, PUT, DELETE');
                    JsonResponse::error('Method Not Allowed.', 405);
            }
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage());
        } catch (Throwable $e) {
            JsonResponse::error('Neocekivana greska u admin rezervacijama.', 500);
        }
    }
}

