<?php

namespace App\Repository;

use PDO;

final class ReservationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function existsForUserSession(int $userId, int $sessionId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM reservations WHERE user_id = ? AND session_id = ? LIMIT 1');
        $stmt->execute([$userId, $sessionId]);

        return (bool)$stmt->fetch();
    }

    public function create(int $userId, int $sessionId, string $sessionInfo): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reservations (user_id, session_id, session_info) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $sessionId, $sessionInfo]);
    }
}
