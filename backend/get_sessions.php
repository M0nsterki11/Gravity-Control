<?php

use App\Controller\SessionController;

$pdo = require __DIR__ . '/../src/bootstrap.php';
SessionController::list($pdo);
