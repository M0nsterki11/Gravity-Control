<?php
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/rate_limit.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// rate limit: max 10 pokušaja / 1 min po IP-u
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateKey = 'login:' . $ip;

if (!check_rate_limit($pdo, $rateKey, 10, 60)) {
    rate_limit_exceeded_response(
        'Previše pokušaja prijave s tvoje IP adrese. Pokušaj ponovo za minutu.'
    );
}

$input = json_decode(file_get_contents('php://input'), true);

$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Email i lozinka su obavezni.']);
    exit;
}

// Nađi korisnika
$stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, is_admin FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Pogrešan email ili lozinka.']);
    exit;
}

$_SESSION['user_id']   = (int)$user['id'];
$_SESSION['is_admin']  = (int)($user['is_admin'] ?? 0);
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email']     = $user['email'];

// (Za ozbiljnu aplikaciju ovdje bi išli sessioni ili JWT tokeni)
echo json_encode([
    'success' => true,
    'message' => 'Login uspješan.',
    'user'    => [
        'id'        => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'is_admin'  => (int)($user['is_admin'] ?? 0),
    ],
]);
