<?php

namespace App\Middleware;

final class MethodMiddleware
{
    // Provjerava je li HTTP metoda uskladena s pravilom endpointa.
    public static function require(string $method): void
    {
        require_http_method($method);
    }
}
