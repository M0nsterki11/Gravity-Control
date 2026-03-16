<?php

namespace App\Middleware;

use PDO;

final class RateLimitMiddleware
{
    // Primjenjuje rate limit pravilo i vraca 429 kad je limit probijen.
    public static function enforce(PDO $pdo, string $key, int $maxAttempts, int $windowSeconds, string $message): void
    {
        if (!check_rate_limit($pdo, $key, $maxAttempts, $windowSeconds)) {
            rate_limit_exceeded_response($message);
        }
    }
}
