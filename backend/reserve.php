<?php

use App\Controller\ReservationController;

// Legacy entrypoint koji delegira rezervaciju na controller.
$pdo = require __DIR__ . '/../src/bootstrap.php';
ReservationController::reserve($pdo);
