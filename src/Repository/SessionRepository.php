<?php

namespace App\Repository;

use PDO;

final class SessionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findActiveById(int $sessionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, day, time_from, time_to, type, coach FROM sessions WHERE id = ? AND active = 1'
        );
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function listActive(): array
    {
        $sql = "
            SELECT id, day, time_from, time_to, type, coach
            FROM sessions
            WHERE active = 1
            ORDER BY 
              FIELD(day, 'Ponedjeljak','Utorak','Srijeda','Četvrtak','Petak','Subota','Nedjelja'),
              time_from
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
