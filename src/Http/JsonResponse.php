<?php

namespace App\Http;

final class JsonResponse
{
    // Salje JSON payload, status kod i prekida daljnju obradu.
    public static function send(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($payload);
        exit;
    }

    // Pomocni wrapper za uspjesan JSON odgovor.
    public static function success(array $payload = [], int $statusCode = 200): void
    {
        self::send(array_merge(['success' => true], $payload), $statusCode);
    }

    // Pomocni wrapper za greske sa standardnim formatom odgovora.
    public static function error(string $message, int $statusCode = 400, array $payload = []): void
    {
        self::send(array_merge(['success' => false, 'message' => $message], $payload), $statusCode);
    }
}
