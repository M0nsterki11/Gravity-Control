<?php

namespace App\Middleware;

final class CsrfMiddleware
{
    public static function requireValidToken(): void
    {
        require_valid_csrf_token();
    }
}
