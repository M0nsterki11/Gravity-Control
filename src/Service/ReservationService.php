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

    public function reserve(int $sessionUserId, int $requestedUserId, int $sessionId): void
    {
        if ($requestedUserId > 0 && $requestedUserId !== $sessionUserId) {
            throw new RuntimeException('Neispravan korisnik za rezervaciju.');
        }

        if ($sessionUserId <= 0 || $sessionId <= 0) {
            throw new RuntimeException('Nedostaju podaci za rezervaciju.');
        }

        if (!$this->users->existsById($sessionUserId)) {
            throw new RuntimeException('Korisnik ne postoji.');
        }

        $sessionRow = $this->sessions->findActiveById($sessionId);
        if (!$sessionRow) {
            throw new RuntimeException('Termin ne postoji ili nije aktivan.');
        }

        if ($this->reservations->existsForUserSession($sessionUserId, $sessionId)) {
            throw new RuntimeException('Vec imas rezervaciju za taj termin.');
        }

        $timeFrom = substr((string)$sessionRow['time_from'], 0, 5);
        $timeTo = substr((string)$sessionRow['time_to'], 0, 5);
        $sessionInfo = sprintf(
            '%s %s - %s (%s, %s)',
            (string)$sessionRow['day'],
            $timeFrom,
            $timeTo,
            (string)$sessionRow['type'],
            (string)$sessionRow['coach']
        );

        try {
            $this->reservations->create($sessionUserId, $sessionId, $sessionInfo);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                throw new RuntimeException('Vec imas rezervaciju za taj termin.');
            }
            throw $e;
        }
    }
}
