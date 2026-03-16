<?php

// Standardizirani JSON error response i prekid izvrsavanja.
function json_error_response(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
    exit;
}

// Blokira zahtjev ako HTTP metoda nije ona koju endpoint ocekuje.
function require_http_method(string $method): void
{
    $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
    $expected = strtoupper($method);

    if ($requestMethod !== $expected) {
        header('Allow: ' . $expected);
        json_error_response(405, 'Method Not Allowed.');
    }
}

// Kreira i vraca CSRF token iz sessiona.
function issue_csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Provjerava CSRF token iz zaglavlja protiv tokena u sessionu.
function require_valid_csrf_token(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($sessionToken) || $sessionToken === '') {
        json_error_response(403, 'CSRF token nije postavljen u sessionu.');
    }

    if (!is_string($headerToken) || $headerToken === '' || !hash_equals($sessionToken, $headerToken)) {
        json_error_response(403, 'Neispravan CSRF token.');
    }
}
