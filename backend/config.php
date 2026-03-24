<?php
// Zabranjuje cache za API odgovore i admin stranice.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Osnovna konfiguracija konekcije prema MySQL bazi.
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'gravity_control';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$envPort = getenv('DB_PORT');
$portsToTry = $envPort ? [(string)$envPort] : ['3306', '3309'];

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Pokusava otvoriti PDO konekciju i vraca JSON gresku ako baza nije dostupna.
$lastException = null;
foreach (array_unique($portsToTry) as $port) {
    $dsn = "mysql:host=$DB_HOST;port=$port;dbname=$DB_NAME;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
        break;
    } catch (PDOException $e) {
        $lastException = $e;
    }
}

if (!isset($pdo) || !$pdo instanceof PDO) {
    error_log('DB connection error: ' . ($lastException?->getMessage() ?? 'Unknown error'));
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}
