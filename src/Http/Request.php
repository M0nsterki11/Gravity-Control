<?php

namespace App\Http;

final class Request
{
    public static function method(): string
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
    }

    public static function ip(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    public static function header(string $headerName): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        return (string)($_SERVER[$key] ?? '');
    }

    public static function json(): array
    {
        static $payload = null;

        if (is_array($payload)) {
            return $payload;
        }

        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        $payload = is_array($decoded) ? $decoded : [];

        return $payload;
    }
}
