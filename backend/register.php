<?php
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/rate_limit.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// max 5 registracija u 10 minuta po IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateKey = 'register:' . $ip;

if (!check_rate_limit($pdo, $rateKey, 5, 600)) {
    rate_limit_exceeded_response(
        'Previše pokušaja registracije. Pokušaj ponovo za 10 minuta.'
    );
}

// Pročitaj JSON body
$input = json_decode(file_get_contents('php://input'), true);

$fullName = trim($input['fullName'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirm  = $input['confirmPassword'] ?? '';

if ($fullName === '' || $email === '' || $password === '' || $confirm === '') {
    echo json_encode(['success' => false, 'message' => 'Sva polja su obavezna.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email nije ispravan.']);
    exit;
}

if ($password !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Lozinke se ne podudaraju.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Lozinka mora imati barem 6 znakova.']);
    exit;
}

// Provjeri je li email već zauzet
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'Korisnik s tim emailom već postoji.']);
    exit;
}

// Spremi usera
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
$stmt->execute([$fullName, $email, $hash]);

$userId = $pdo->lastInsertId();

echo json_encode([
    'success' => true,
    'message' => 'Registracija uspješna.',
    'user'    => [
        'id'        => (int)$userId,
        'full_name' => $fullName,
        'email'     => $email,
    ],
]);
