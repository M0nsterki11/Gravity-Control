<?php

namespace App\Service;

use App\Repository\ReservationRepository;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use PDOException;
use RuntimeException;

final class ReservationService
{
    public function __construct(
        private UserRepository $users,
        private SessionRepository $sessions,
        private ReservationRepository $reservations
    ) {
    }

    // Validira korisnika i termin te sprema novu rezervaciju.
    public function reserve(int $sessionUserId, int $requestedUserId, int $sessionId): void
    {
        if ($requestedUserId > 0 && $requestedUserId !== $sessionUserId) {
            throw new RuntimeException('Neispravan korisnik za rezervaciju.');
        }

        $reservationPayload = $this->buildReservationPayload(
            $sessionUserId,
            $sessionId,
            true,
            null,
            'Vec imas rezervaciju za taj termin.'
        );
        $sessionInfo = $reservationPayload['session_info'];

        try {
            $this->reservations->create($sessionUserId, $sessionId, $sessionInfo);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                throw new RuntimeException('Vec imas rezervaciju za taj termin.');
            }

            throw $e;
        }
    }

    // Vraca rezervacije u admin-friendly formatu.
    public function listForAdmin(): array
    {
        $rows = $this->reservations->listAllWithDetails();

        return array_map(function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'user_id' => (int)$row['user_id'],
                'session_id' => (int)$row['session_id'],
                'session_info' => (string)($row['session_info'] ?? ''),
                'session_label' => $this->formatSessionLabel($row),
                'created_at' => (string)($row['created_at'] ?? ''),
                'full_name' => (string)($row['full_name'] ?? ''),
                'email' => (string)($row['email'] ?? ''),
                'day' => isset($row['day']) ? (string)$row['day'] : null,
                'time_from' => isset($row['time_from']) ? (string)$row['time_from'] : null,
                'time_to' => isset($row['time_to']) ? (string)$row['time_to'] : null,
                'session_type' => isset($row['session_type']) ? (string)$row['session_type'] : null,
                'coach' => isset($row['coach']) ? (string)$row['coach'] : null,
                'session_active' => isset($row['session_active']) ? (int)$row['session_active'] : null,
            ];
        }, $rows);
    }

    // Kreira rezervaciju iz admin panela.
    public function createForAdmin(int $userId, int $sessionId): void
    {
        $reservationPayload = $this->buildReservationPayload($userId, $sessionId);
        $sessionInfo = $reservationPayload['session_info'];

        try {
            $this->reservations->create($userId, $sessionId, $sessionInfo);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                throw new RuntimeException('Taj korisnik vec ima rezervaciju za odabrani termin.');
            }

            throw $e;
        }
    }

    // Azurira postojecu rezervaciju iz admin panela.
    public function updateForAdmin(int $reservationId, int $userId, int $sessionId): void
    {
        if ($reservationId <= 0) {
            throw new RuntimeException('Rezervacija nije ispravna.');
        }

        if (!$this->reservations->findById($reservationId)) {
            throw new RuntimeException('Rezervacija ne postoji.');
        }

        $reservationPayload = $this->buildReservationPayload($userId, $sessionId, false, $reservationId);
        $sessionInfo = $reservationPayload['session_info'];

        try {
            $this->reservations->update($reservationId, $userId, $sessionId, $sessionInfo);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                throw new RuntimeException('Taj korisnik vec ima rezervaciju za odabrani termin.');
            }

            throw $e;
        }
    }

    // Brise rezervaciju iz admin panela.
    public function deleteForAdmin(int $reservationId): void
    {
        if ($reservationId <= 0) {
            throw new RuntimeException('Rezervacija nije ispravna.');
        }

        if (!$this->reservations->findById($reservationId)) {
            throw new RuntimeException('Rezervacija ne postoji.');
        }

        $this->reservations->delete($reservationId);
    }

    private function buildReservationPayload(
        int $userId,
        int $sessionId,
        bool $requireActiveSession = false,
        ?int $excludeReservationId = null,
        string $duplicateMessage = 'Taj korisnik vec ima rezervaciju za odabrani termin.'
    ): array {
        if ($userId <= 0 || $sessionId <= 0) {
            throw new RuntimeException('Nedostaju podaci za rezervaciju.');
        }

        if (!$this->users->existsById($userId)) {
            throw new RuntimeException('Korisnik ne postoji.');
        }

        $sessionRow = $requireActiveSession
            ? $this->sessions->findActiveById($sessionId)
            : $this->sessions->findById($sessionId);

        if (!$sessionRow) {
            throw new RuntimeException(
                $requireActiveSession
                    ? 'Termin ne postoji ili nije aktivan.'
                    : 'Termin ne postoji.'
            );
        }

        if ($this->reservations->existsForUserSession($userId, $sessionId, $excludeReservationId)) {
            throw new RuntimeException($duplicateMessage);
        }

        return [
            'session_info' => $this->buildSessionInfo($sessionRow),
        ];
    }

    private function buildSessionInfo(array $sessionRow): string
    {
        $timeFrom = substr((string)$sessionRow['time_from'], 0, 5);
        $timeTo = substr((string)$sessionRow['time_to'], 0, 5);

        return sprintf(
            '%s %s - %s (%s, %s)',
            (string)$sessionRow['day'],
            $timeFrom,
            $timeTo,
            (string)$sessionRow['type'],
            (string)$sessionRow['coach']
        );
    }

    private function formatSessionLabel(array $row): string
    {
        if (
            !empty($row['day'])
            && !empty($row['time_from'])
            && !empty($row['time_to'])
        ) {
            $label = sprintf(
                '%s %s - %s',
                (string)$row['day'],
                substr((string)$row['time_from'], 0, 5),
                substr((string)$row['time_to'], 0, 5)
            );

            $extra = array_filter([
                (string)($row['session_type'] ?? ''),
                (string)($row['coach'] ?? ''),
            ]);

            if ($extra !== []) {
                $label .= ' (' . implode(', ', $extra) . ')';
            }

            return $label;
        }

        return (string)($row['session_info'] ?? 'N/A');
    }
}

