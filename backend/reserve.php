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

$input = json_decode(file_get_contents('php://input'), true);
$requestedUserId = (int)($input['userId'] ?? 0);
$sessionId = isset($input['sessionId']) ? (int)$input['sessionId'] : 0;

// korisnik mora biti prijavljen; user id uzimamo iz sessiona, ne iz requesta
$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
if ($sessionUserId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Nisi prijavljen.',
    ]);
    exit;
}

require_valid_csrf_token();

// Ako frontend pošalje userId, mora odgovarati aktivnom session korisniku
if ($requestedUserId > 0 && $requestedUserId !== $sessionUserId) {
    echo json_encode([
        'success' => false,
        'message' => 'Neispravan korisnik za rezervaciju.',
    ]);
    exit;
}

// max 10 rezervacija / 5 minuta po useru
$rateKey = 'reserve:user:' . $sessionUserId;

if (!check_rate_limit($pdo, $rateKey, 10, 300)) {
    rate_limit_exceeded_response(
        'Previše pokušaja rezervacije. Pokušaj ponovo za par minuta.'
    );
}

// Spremanje rezervacija
$userId = $sessionUserId;
if ($userId <= 0 || $sessionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Nedostaju podaci za rezervaciju.']);
    exit;
}

// Provjeri postoji li korisnik
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Korisnik ne postoji.']);
    exit;
}

// Dohvati standardizirani opis termina
$stmt = $pdo->prepare('SELECT day, time_from, time_to, type, coach FROM sessions WHERE id = ? AND active = 1');
$stmt->execute([$sessionId]);
$sessionRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sessionRow) {
    echo json_encode(['success' => false, 'message' => 'Termin ne postoji ili nije aktivan.']);
    exit;
}

$timeFrom = substr($sessionRow['time_from'], 0, 5); // "18:00"
$timeTo   = substr($sessionRow['time_to'], 0, 5);   // "19:30"
$sessionInfo = sprintf(
    '%s %s - %s (%s, %s)',
    $sessionRow['day'],
    $timeFrom,
    $timeTo,
    $sessionRow['type'],
    $sessionRow['coach']
);

// Nemoj dozvoliti istom useru duplu rezervaciju istog termina
$stmt = $pdo->prepare('SELECT id FROM reservations WHERE user_id = ? AND session_id = ? LIMIT 1');
$stmt->execute([$userId, $sessionId]);
$existingReservation = $stmt->fetch();
if ($existingReservation) {
    echo json_encode([
        'success' => false,
        'message' => 'Vec imas rezervaciju za taj termin.',
    ]);
    exit;
}

// Spremi rezervaciju 
$stmt = $pdo->prepare('
    INSERT INTO reservations (user_id, session_id, session_info) 
    VALUES (?, ?, ?)
');
try {
    $stmt->execute([$userId, $sessionId, $sessionInfo]);
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23000') {
        echo json_encode([
            'success' => false,
            'message' => 'Vec imas rezervaciju za taj termin.',
        ]);
        exit;
    }
    throw $e;
}

echo json_encode([
    'success' => true,
    'message' => 'Rezervacija spremljena.'
]);
