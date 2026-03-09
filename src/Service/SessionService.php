<?php

namespace App\Service;

use App\Repository\SessionRepository;

final class SessionService
{
    public function __construct(private SessionRepository $sessions)
    {
    }

    public function listActive(): array
    {
        return $this->sessions->listActive();
    }
}
