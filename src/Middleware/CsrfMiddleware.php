<?php

namespace App\Middleware;

final class CsrfMiddleware
{
    // Blokira zahtjev bez valjanog CSRF tokena.
    public static function requireValidToken(): void
    {
        require_valid_csrf_token();
    }
}
