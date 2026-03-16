<?php

use App\Controller\AuthController;

// Legacy entrypoint koji samo delegira logout na novi controller sloj.
$pdo = require __DIR__ . '/../src/bootstrap.php';
AuthController::logout($pdo);
