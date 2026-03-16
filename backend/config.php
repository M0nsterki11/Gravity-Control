<?php
// Zabranjuje cache za API odgovore i admin stranice.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Osnovna konfiguracija konekcije prema MySQL bazi.
$DB_HOST = 'localhost';
$DB_NAME = 'gravity_control';
$DB_USER = 'root';      
$DB_PASS = '';         

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Pokusava otvoriti PDO konekciju i vraca JSON gresku ako baza nije dostupna.
try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}
