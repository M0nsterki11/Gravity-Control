<?php

namespace App\Service;

use App\Repository\SessionRepository;

final class SessionService
{
    public function __construct(private SessionRepository $sessions)
    {
    }
    // Vraca sve aktivne termine iz repozitorija.
    public function listActive(): array
    {
        return $this->sessions->listActive();
    }
}
