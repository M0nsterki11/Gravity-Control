<?php

use App\Controller\AuthController;

$pdo = require __DIR__ . '/../src/bootstrap.php';
AuthController::login($pdo);
