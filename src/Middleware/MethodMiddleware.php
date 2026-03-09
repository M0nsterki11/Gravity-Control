<?php

namespace App\Middleware;

final class MethodMiddleware
{
    public static function require(string $method): void
    {
        require_http_method($method);
    }
}
