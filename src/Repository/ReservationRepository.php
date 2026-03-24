<?php

namespace App\Repository;

use PDO;

final class ReservationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    // Vraca sve rezervacije sa spojenim korisnikom i terminom za admin panel.
    public function listAllWithDetails(): array
    {
        $sql = "
            SELECT
                r.id,
                r.user_id,
                r.session_id,
                r.session_info,
                r.created_at,
                u.full_name,
                u.email,
                s.day,
                s.time_from,
                s.time_to,
                s.type AS session_type,
                s.coach,
                s.active AS session_active
            FROM reservations r
            INNER JOIN users u ON r.user_id = u.id
            LEFT JOIN sessions s ON r.session_id = s.id
            ORDER BY r.created_at DESC, u.full_name ASC
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Dohvaca jednu rezervaciju po ID-u.
    public function findById(int $reservationId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, session_id, session_info, created_at FROM reservations WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$reservationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    // Provjera ima li korisnik vec rezervaciju za termin.
    public function existsForUserSession(int $userId, int $sessionId, ?int $excludeReservationId = null): bool
    {
        $sql = 'SELECT id FROM reservations WHERE user_id = ? AND session_id = ?';
        $params = [$userId, $sessionId];

        if ($excludeReservationId !== null && $excludeReservationId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeReservationId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetch();
    }

    // Sprema novu rezervaciju u bazu i vraca njezin ID.
    public function create(int $userId, int $sessionId, string $sessionInfo): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reservations (user_id, session_id, session_info) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $sessionId, $sessionInfo]);

        return (int)$this->pdo->lastInsertId();
    }

    // Azurira korisnika, termin i snapshot teksta rezervacije.
    public function update(int $reservationId, int $userId, int $sessionId, string $sessionInfo): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE reservations SET user_id = ?, session_id = ?, session_info = ? WHERE id = ?'
        );
        $stmt->execute([$userId, $sessionId, $sessionInfo, $reservationId]);
    }

    // Brise rezervaciju po ID-u.
    public function delete(int $reservationId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM reservations WHERE id = ?');
        $stmt->execute([$reservationId]);
    }
}

