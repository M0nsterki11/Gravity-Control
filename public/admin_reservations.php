<?php
session_start();
require __DIR__ . '/../backend/security.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$isAdmin = isset($_SESSION['is_admin']) ? (int)$_SESSION['is_admin'] : 0;

if (empty($_SESSION['user_id']) || $isAdmin !== 1) {
    header('Location: index.html');
    exit;
}

$csrfToken = issue_csrf_token();
$adminName = (string)($_SESSION['full_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title>Gravity Admin - Rezervacije</title>
    <link rel="stylesheet" href="css/admin_res.css?v=2.0">
</head>
<body>
<div class="page-shell">
    <div class="background-glow background-glow-left"></div>
    <div class="background-glow background-glow-right"></div>

    <main class="container">
        <header class="page-header">
            <div class="title-block">
                <p class="eyebrow">Admin panel</p>
                <h1>CRUD rezervacija</h1>
                <p class="subtitle">
                    Upravljanje rezervacijama za prijavljenog administratora:
                    <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></strong>
                </p>
            </div>

            <div class="header-stats">
                <div class="badge">
                    <span class="badge-dot"></span>
                    <span>Ukupno rezervacija: <strong id="reservation-count">0</strong></span>
                </div>
            </div>
        </header>

        <section id="feedback" class="feedback" hidden></section>

        <section class="layout-grid">
            <article class="card form-card">
                <div class="card-heading">
                    <div>
                        <p class="section-label">Create / Update</p>
                        <h2 id="form-title">Nova rezervacija</h2>
                    </div>
                    <p id="form-hint" class="section-copy">Odaberi korisnika i termin za kreiranje rezervacije.</p>
                </div>

                <form id="reservation-form" class="reservation-form">
                    <input type="hidden" id="reservation-id" name="reservationId" value="">

                    <label class="field">
                        <span>Korisnik</span>
                        <select id="user-id" name="userId" required>
                            <option value="">Ucitavanje korisnika...</option>
                        </select>
                    </label>

                    <label class="field">
                        <span>Termin</span>
                        <select id="session-id" name="sessionId" required>
                            <option value="">Ucitavanje termina...</option>
                        </select>
                    </label>

                    <div class="form-actions">
                        <button type="submit" id="submit-button" class="btn btn-primary">Kreiraj rezervaciju</button>
                        <button type="button" id="cancel-button" class="btn btn-secondary" hidden>Odustani</button>
                    </div>
                </form>
            </article>

            <article class="card table-card">
                <div class="card-heading table-heading">
                    <div>
                        <p class="section-label">Read / Delete</p>
                        <h2>Popis rezervacija</h2>
                    </div>
                    <p id="loading-state" class="loading-state" hidden>Ucitavam podatke...</p>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th class="col-id">#</th>
                            <th class="col-user">Korisnik</th>
                            <th class="col-email">Email</th>
                            <th class="col-session">Termin</th>
                            <th class="col-date">Kreirano</th>
                            <th class="col-actions">Akcije</th>
                        </tr>
                        </thead>
                        <tbody id="reservations-body"></tbody>
                    </table>
                </div>

                <div id="empty-state" class="empty-state" hidden>
                    Trenutno nema rezervacija.
                </div>
            </article>
        </section>
    </main>
</div>

<noscript>
    <div class="noscript-banner">Za admin CRUD je potreban ukljucen JavaScript.</div>
</noscript>

<script type="module" src="js/admin_reservations.js"></script>
</body>
</html>

