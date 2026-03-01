<?php

function check_rate_limit(PDO $pdo, string $key, int $maxAttempts, int $windowSeconds): bool
{
    // Obriši stare zapise izvan prozora
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

    // Prebroji koliko zapisa još ima za taj ključ (u prozoru)
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

    // 3) Upis novog zahtjeva
    $insertSql = "
        INSERT INTO rate_limits (key_name, created_at)
        VALUES (:key, NOW())
    ";
    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([':key' => $key]);

    return true;
}

function rate_limit_exceeded_response(string $message = 'Previše zahtjeva, pokušaj kasnije.')
{
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
    exit;
}
