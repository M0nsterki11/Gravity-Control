<?php

namespace App\Http;

final class Request
{
    // Vraca HTTP metodu zahtjeva u uppercase formatu.
    public static function method(): string
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
    }

    // Vraca IP adresu klijenta (ili fallback vrijednost).
    public static function ip(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    // Cita vrijednost pojedinog HTTP zaglavlja iz server varijabli.
    public static function header(string $headerName): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        return (string)($_SERVER[$key] ?? '');
    }

    // Parsira JSON body jednom po requestu i cachea rezultat.
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
