<?php
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/rate_limit.php';
require __DIR__ . '/security.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require_http_method('POST');

// Ogranicava broj registracija po IP adresi.
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateKey = 'register:' . $ip;
if (!check_rate_limit($pdo, $rateKey, 5, 600)) {
    rate_limit_exceeded_response(
        'Previse pokusaja registracije. Pokusaj ponovo za 10 minuta.'
    );
}

// Cita i priprema JSON ulazne podatke.
$input = json_decode(file_get_contents('php://input'), true);
$fullName = trim($input['fullName'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirm = $input['confirmPassword'] ?? '';

// Validira obavezna polja i osnovna pravila lozinke/emaila.
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

// Provjerava postoji li vec korisnik s istim emailom.
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'Korisnik s tim emailom vec postoji.']);
    exit;
}

// Stvara korisnika i hvata duplicate-key race condition.
$hash = password_hash($password, PASSWORD_DEFAULT);
try {
    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$fullName, $email, $hash]);
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23000') {
        echo json_encode(['success' => false, 'message' => 'Korisnik s tim emailom vec postoji.']);
        exit;
    }
    throw $e;
}

// Nakon uspjeha kreira session i vraca user payload s CSRF tokenom.
$userId = $pdo->lastInsertId();
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$userId;
$_SESSION['is_admin'] = 0;
$_SESSION['full_name'] = $fullName;
$_SESSION['email'] = $email;
$csrfToken = issue_csrf_token();

echo json_encode([
    'success' => true,
    'message' => 'Registracija uspjesna.',
    'user' => [
        'id' => (int)$userId,
        'full_name' => $fullName,
        'email' => $email,
        'is_admin' => 0,
        'csrf_token' => $csrfToken,
    ],
]);
