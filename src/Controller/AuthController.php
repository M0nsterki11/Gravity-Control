<?php

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Middleware\MethodMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Repository\UserRepository;
use App\Service\AuthService;
use PDO;
use RuntimeException;
use Throwable;

final class AuthController
{
    public static function login(PDO $pdo): void
    {
        self::jsonHeaders(true);
        MethodMiddleware::require('POST');

        $ip = Request::ip();
        RateLimitMiddleware::enforce(
            $pdo,
            'login:' . $ip,
            10,
            60,
            'Previse pokusaja prijave s tvoje IP adrese. Pokusaj ponovo za minutu.'
        );

        $payload = Request::json();
        $email = trim((string)($payload['email'] ?? ''));
        $password = (string)($payload['password'] ?? '');

        $service = new AuthService(new UserRepository($pdo));

        try {
            $user = $service->login($email, $password);
            JsonResponse::success([
                'message' => 'Login uspjesan.',
                'user' => $user,
            ]);
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage());
        } catch (Throwable $e) {
            JsonResponse::error('Neocekivana greska kod prijave.', 500);
        }
    }

    public static function register(PDO $pdo): void
    {
        self::jsonHeaders(true);
        MethodMiddleware::require('POST');

        $ip = Request::ip();
        RateLimitMiddleware::enforce(
            $pdo,
            'register:' . $ip,
            5,
            600,
            'Previse pokusaja registracije. Pokusaj ponovo za 10 minuta.'
        );

        $payload = Request::json();
        $fullName = trim((string)($payload['fullName'] ?? ''));
        $email = trim((string)($payload['email'] ?? ''));
        $password = (string)($payload['password'] ?? '');
        $confirmPassword = (string)($payload['confirmPassword'] ?? '');

        $service = new AuthService(new UserRepository($pdo));

        try {
            $user = $service->register($fullName, $email, $password, $confirmPassword);
            JsonResponse::success([
                'message' => 'Registracija uspjesna.',
                'user' => $user,
            ]);
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage());
        } catch (Throwable $e) {
            JsonResponse::error('Neocekivana greska kod registracije.', 500);
        }
    }

    public static function logout(PDO $pdo): void
    {
        unset($pdo);

        self::jsonHeaders(false);
        MethodMiddleware::require('POST');

        $service = new AuthService();
        try {
            $service->logout(true);
            JsonResponse::success([
                'message' => 'Odjava uspjesna.',
            ]);
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 403);
        } catch (Throwable $e) {
            JsonResponse::error('Neocekivana greska kod odjave.', 500);
        }
    }

    private static function jsonHeaders(bool $allowCors): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($allowCors) {
            header('Access-Control-Allow-Origin: *');
        }
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
}
