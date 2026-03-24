<?php

namespace App\Repository;

use PDO;

final class SessionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    // Dohvaca jedan termin po ID-u bez obzira na active status.
    public function findById(int $sessionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, day, time_from, time_to, type, coach, active FROM sessions WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    // Dohvaca jedan aktivan termin po ID-u.
    public function findActiveById(int $sessionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, day, time_from, time_to, type, coach FROM sessions WHERE id = ? AND active = 1'
        );
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    // Vraca sve termine za admin forme.
    public function listAll(): array
    {
        $sql = "
            SELECT id, day, time_from, time_to, type, coach, active
            FROM sessions
            ORDER BY active DESC,
              CASE
                WHEN day = 'Ponedjeljak' THEN 1
                WHEN day = 'Utorak' THEN 2
                WHEN day = 'Srijeda' THEN 3
                WHEN day = 'Cetvrtak' THEN 4
                WHEN day = 'Petak' THEN 5
                WHEN day = 'Subota' THEN 6
                WHEN day = 'Nedjelja' THEN 7
                ELSE 8
              END,
              time_from
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Vraca sve aktivne termine sortirane po danu i vremenu.
    public function listActive(): array
    {
        $sql = "
            SELECT id, day, time_from, time_to, type, coach
            FROM sessions
            WHERE active = 1
            ORDER BY
              CASE
                WHEN day = 'Ponedjeljak' THEN 1
                WHEN day = 'Utorak' THEN 2
                WHEN day = 'Srijeda' THEN 3
                WHEN day = 'Cetvrtak' THEN 4
                WHEN day = 'Petak' THEN 5
                WHEN day = 'Subota' THEN 6
                WHEN day = 'Nedjelja' THEN 7
                ELSE 8
              END,
              time_from
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

