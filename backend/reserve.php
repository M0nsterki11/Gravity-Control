<?php

use App\Controller\ReservationController;

$pdo = require __DIR__ . '/../src/bootstrap.php';
ReservationController::reserve($pdo);
