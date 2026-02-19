<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require __DIR__ . '/config.php';

try {
    $sql = "
        SELECT id, day, time_from, time_to, type, coach
        FROM sessions
        WHERE active = 1
        ORDER BY 
          FIELD(day, 'Ponedjeljak','Utorak','Srijeda','Četvrtak','Petak','Subota','Nedjelja'),
          time_from
    ";

    $stmt = $pdo->query($sql);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'sessions' => $sessions
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška kod dohvaćanja termina.'
    ]);
}
