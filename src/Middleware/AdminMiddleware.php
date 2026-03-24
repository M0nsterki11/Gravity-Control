<?php

namespace App\Middleware;

use App\Http\JsonResponse;

final class AdminMiddleware
{
    // Osigurava da je prijavljeni korisnik administrator.
    public static function requireAdminUserId(): int
    {
        $userId = AuthMiddleware::requireUserId();
        $isAdmin = (int)($_SESSION['is_admin'] ?? 0);

        if ($isAdmin !== 1) {
            JsonResponse::error('Nemate administratorski pristup.', 403);
        }

        return $userId;
    }
}

