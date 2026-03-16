<?php

// Sliding-window limiter: cisti stare hitove, broji pokusaje i biljezi novi zahtjev.
function check_rate_limit(PDO $pdo, string $key, int $maxAttempts, int $windowSeconds): bool
{
    // Brise zapise izvan aktivnog vremenskog prozora.
    $windowStartTs = time() - $windowSeconds;

    $deleteSql = "
        DELETE FROM rate_limits
        WHERE key_name = :key
          AND created_at < FROM_UNIXTIME(:window_start)
    ";
    $stmt = $pdo->prepare($deleteSql);
    $stmt->execute([
        ':key' => $key,
        ':window_start' => $windowStartTs,
    ]);

    // Broji koliko pokusaja je ostalo u prozoru.
    $countSql = "
        SELECT COUNT(*) AS cnt
        FROM rate_limits
        WHERE key_name = :key
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute([':key' => $key]);
    $count = (int) $stmt->fetchColumn();

    if ($count >= $maxAttempts) {
        return false;
    }

    // Biljezi trenutni zahtjev kao novi pokusaj.
    $insertSql = "
        INSERT INTO rate_limits (key_name, created_at)
        VALUES (:key, NOW())
    ";
    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([':key' => $key]);

    return true;
}

// Standardni 429 JSON odgovor za blokirane zahtjeve.
function rate_limit_exceeded_response(string $message = 'Previse zahtjeva, pokusaj kasnije.'): void
{
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
    exit;
}
