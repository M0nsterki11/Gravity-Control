<?php
// backend/admin_reservations.php

session_start();
require __DIR__ . '/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ako korisnik nije logiran ili nije admin → van
$isAdmin = isset($_SESSION['is_admin']) ? (int)$_SESSION['is_admin'] : 0;

if (empty($_SESSION['user_id']) || $isAdmin !== 1) {

    header('Location: ../index.html');
    exit;
}

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
    // Ako postoji povezani session iz tablice sessions
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

    // Fallback – tekst iz session_info
    return $row['session_info'] ?: 'N/A';
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Gravity Admin – Rezervacije</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --bg-dark: #050608;
            --accent: #ff7a1a;
            --accent-soft: rgba(255, 122, 26, 0.35);
            --text-main: #f5f5f5;
            --text-muted: #b3b3b3;
            --border-subtle: rgba(255, 255, 255, 0.12);
            --row-alt: rgba(255, 255, 255, 0.02);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, rgba(255,122,26,0.14), #050608 55%);
            color: var(--text-main);
            padding: 1.5rem;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .title-block h1 {
            font-size: 1.6rem;
            margin: 0;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .title-block p {
            margin: 0.3rem 0 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            border: 1px solid var(--accent-soft);
            background: rgba(0,0,0,0.4);
            font-size: 0.8rem;
            gap: 0.4rem;
        }

        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--accent);
        }

        .card {
            background: rgba(0,0,0,0.6);
            border-radius: 18px;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 18px 35px rgba(0,0,0,0.65);
            padding: 1.2rem 1.4rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        thead {
            background: rgba(255,255,255,0.03);
        }

        th, td {
            padding: 0.55rem 0.6rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        th {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-muted);
        }

        tbody tr:nth-child(even) {
            background: var(--row-alt);
        }

        tbody tr:hover {
            background: rgba(255,122,26,0.06);
        }

        .col-id {
            width: 3rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .col-user {
            min-width: 180px;
        }

        .col-email {
            min-width: 200px;
        }

        .col-session {
            min-width: 260px;
        }

        .col-date {
            min-width: 120px;
            white-space: nowrap;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.1rem 0.5rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            font-size: 0.75rem;
        }

        .pill-label {
            color: var(--text-muted);
        }

        .pill-value {
            color: var(--accent);
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .card {
                padding: 1rem;
            }

            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead {
                display: none;
            }

            tbody tr {
                margin-bottom: 0.9rem;
                border-radius: 14px;
                overflow: hidden;
                border: 1px solid rgba(255,255,255,0.06);
            }

            td {
                border-bottom: 1px solid rgba(255,255,255,0.06);
                display: flex;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.45rem 0.7rem;
                font-size: 0.85rem;
            }

            td::before {
                content: attr(data-label);
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                font-size: 0.72rem;
                color: var(--text-muted);
                flex-shrink: 0;
            }

            .col-id {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <header class="page-header">
        <div class="title-block">
            <h1>Gravity Admin</h1>
            <p>Pregled svih rezervacija iz baze (users + reservations + sessions).</p>
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
