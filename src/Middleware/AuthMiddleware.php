<?php

namespace App\Middleware;

use App\Http\JsonResponse;

final class AuthMiddleware
{
    // Osigurava da je korisnik prijavljen i vraca njegov user_id.
    public static function requireUserId(): int
    {
        $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
        if ($sessionUserId <= 0) {
            JsonResponse::error('Nisi prijavljen.', 401);
        }

        return $sessionUserId;
    }
}
