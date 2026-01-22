<?php
// backend/rate_limit.php

/**
 * Jednostavan rate-limit:
 *  - $key           = npr. "login:IP" ili "reserve:userId"
 *  - $maxAttempts   = max broj zahtjeva u prozoru
 *  - $windowSeconds = trajanje prozora u sekundama (npr. 60 = 1 min)
 *
 * Vraća true ako je dozvoljeno, false ako je limit premašen.
 */
function check_rate_limit(PDO $pdo, string $key, int $maxAttempts, int $windowSeconds): bool
{
    // 1) Obriši stare zapise izvan prozora
    $windowStartTs = time() - $windowSeconds;

    $deleteSql = "
        DELETE FROM rate_limits
        WHERE key_name = :key
          AND created_at < FROM_UNIXTIME(:window_start)
    ";
    $stmt = $pdo->prepare($deleteSql);
    $stmt->execute([
        ':key'          => $key,
        ':window_start' => $windowStartTs,
    ]);

    // 2) Prebroji koliko zapisa još ima za taj ključ (u prozoru)
    $countSql = "
        SELECT COUNT(*) AS cnt
        FROM rate_limits
        WHERE key_name = :key
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute([':key' => $key]);
    $count = (int) $stmt->fetchColumn();

    if ($count >= $maxAttempts) {
        // limit premašen
        return false;
    }

    // 3) Upis novog zahtjeva
    $insertSql = "
        INSERT INTO rate_limits (key_name, created_at)
        VALUES (:key, NOW())
    ";
    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([':key' => $key]);

    return true;
}

/**
 * Helper da lako pošalješ JSON 429 odgovor.
 */
function rate_limit_exceeded_response(string $message = 'Previše zahtjeva, pokušaj kasnije.')
{
    http_response_code(429); // Too Many Requests
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
    exit;
}
