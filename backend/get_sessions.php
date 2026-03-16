<?php

use App\Controller\SessionController;

// Legacy entrypoint koji delegira dohvacanje termina na controller.
$pdo = require __DIR__ . '/../src/bootstrap.php';
SessionController::list($pdo);
