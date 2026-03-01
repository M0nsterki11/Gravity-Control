<?php
// user_id i is_admin.
session_start();
require __DIR__ . '/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Provjeri admina
$isAdmin = isset($_SESSION['is_admin']) ? (int)$_SESSION['is_admin'] : 0;

if (empty($_SESSION['user_id']) || $isAdmin !== 1) {

    header('Location: ../index.html');
    exit;
}

// SQL za admin
$sql = "
    SELECT
        r.id,
        r.session_info,
        r.created_at,
        u.full_name,
        u.email,
        s.day,
        s.time_from,
        s.time_to,
        s.type AS session_type,
        s.coach
    FROM reservations r
    INNER JOIN users u ON r.user_id = u.id
    LEFT JOIN sessions s ON r.session_id = s.id
    ORDER BY r.created_at DESC, u.full_name ASC
";

$stmt = $pdo->query($sql);
$reservations = $stmt->fetchAll();

function formatSessionRow(array $row): string
{
    if (!empty($row['day']) && !empty($row['time_from']) && !empty($row['time_to'])) {
        $timeFrom = substr($row['time_from'], 0, 5);
        $timeTo   = substr($row['time_to'], 0, 5);

        $label = sprintf(
            '%s %s - %s',
            $row['day'],
            $timeFrom,
            $timeTo
        );

        if (!empty($row['session_type']) || !empty($row['coach'])) {
            $extra = [];
            if (!empty($row['session_type'])) {
                $extra[] = $row['session_type'];
            }
            if (!empty($row['coach'])) {
                $extra[] = $row['coach'];
            }
            $label .= ' (' . implode(', ', $extra) . ')';
        }

        return $label;
    }
    return $row['session_info'] ?: 'N/A';
}

?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Gravity Admin – Rezervacije</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/gravity-control/css/admin_res.css?v=1.2">
</head>
<body>
<div class="container">
    <header class="page-header">
        <div class="title-block">
            <h1>Gravity Admin</h1>
        </div>
        <div class="badge">
            <span class="badge-dot"></span>
            <span>Rezervacija: <?php echo count($reservations); ?></span>
        </div>
    </header>

    <section class="card">
        <?php if (empty($reservations)): ?>
            <div class="empty-state">
                Trenutno nema niti jedne rezervacije.
            </div>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th class="col-id">#</th>
                    <th class="col-user">Korisnik</th>
                    <th class="col-email">Email</th>
                    <th class="col-session">Termin</th>
                    <th class="col-date">Kreirano</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $row): ?>
                    <tr>
                        <td class="col-id"><?php echo (int)$row['id']; ?></td>
                        <td class="col-user"><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td class="col-email"><?php echo htmlspecialchars($row['email']); ?></td>
                        <td class="col-session">
                            <?php echo htmlspecialchars(formatSessionRow($row)); ?>
                        </td>
                        <td class="col-date">
                            <?php echo htmlspecialchars($row['created_at'] ?? ''); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
