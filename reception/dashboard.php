<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['receptionist', 'admin']);
require_once __DIR__ . '/../config/db.php';

$u = current_user();

$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$waiting = $pdo->query("SELECT COUNT(*) FROM queue_tickets WHERE status='waiting'")->fetchColumn();
$serving = $pdo->query("SELECT COUNT(*) FROM queue_tickets WHERE status='serving'")->fetchColumn();
$completed = $pdo->query("SELECT COUNT(*) FROM queue_tickets WHERE status='completed'")->fetchColumn();

$queue = $pdo->query("
    SELECT qt.queue_no, p.name, qt.source, qt.status, qt.arrival_at
    FROM queue_tickets qt
    JOIN patients p ON p.patient_id = qt.patient_id
    WHERE qt.status IN ('waiting','serving')
    ORDER BY qt.arrival_at ASC
    LIMIT 20
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>MediQueue - Receptionist Dashboard</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%230d1b8f'/><text x='50%' y='50%' font-size='45' font-family='Arial' font-weight='bold' fill='white' dominant-baseline='central' text-anchor='middle'>MQ</text></svg>">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial; margin: 0; background: #f4f6f9; }
        .header { background: #0d1b8f; color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .header a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; }
        .layout { display: flex; min-height: calc(100vh - 55px); }
        .sidebar { width: 220px; background: #37474f; padding: 20px 0; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 14px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #263238; }
        .main { flex: 1; padding: 25px; }
        .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        .card h3 { margin: 0 0 8px; color: #666; font-size: 13px; text-transform: uppercase; }
        .card .num { font-size: 28px; font-weight: bold; color: #0d1b8f; }
        .actions { margin-bottom: 25px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { background: #0d1b8f; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; }
        .btn:hover { background: #0a1466; }
        .btn.green { background: #2e7d32; }
        .btn.green:hover { background: #1b5e20; }
        .panel { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        .panel h2 { margin-top: 0; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; font-size: 12px; text-transform: uppercase; color: #666; }
        .status { padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .status.waiting { background: #fff3e0; color: #e65100; }
        .status.serving { background: #e8f5e9; color: #2e7d32; }
        .status.completed { background: #e3f2fd; color: #1565c0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MediQueue | Receptionist | <?= htmlspecialchars($u['name']) ?></h1>
        <a href="/Mediqueue/auth/logout.php">Logout</a>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="/Mediqueue/reception/dashboard.php" class="active">Dashboard</a>
            <a href="/Mediqueue/reception/register_patient.php">Register Patient</a>
            <a href="/Mediqueue/reception/patients.php">All Patients</a>
            <a href="/Mediqueue/reception/appointments.php">Appointments</a>
            <a href="/Mediqueue/reception/walkin.php">Walk-in Queue</a>
            <a href="/Mediqueue/reception/reports.php">Reports</a>
        </div>
        <div class="main">
            <div class="cards">
                <div class="card"><h3>Total Patients</h3><div class="num"><?= $totalPatients ?></div></div>
                <div class="card"><h3>Waiting</h3><div class="num"><?= $waiting ?></div></div>
                <div class="card"><h3>Serving</h3><div class="num"><?= $serving ?></div></div>
                <div class="card"><h3>Completed</h3><div class="num"><?= $completed ?></div></div>
            </div>
            <div class="actions">
                <a class="btn" href="/Mediqueue/reception/register_patient.php">+ Register Patient</a>
                <a class="btn" href="/Mediqueue/reception/appointments.php">+ Book Appointment</a>
                <a class="btn green" href="/Mediqueue/reception/walkin.php">+ Walk-in Ticket</a>
            </div>
            <div class="panel">
                <h2>Current Queue</h2>
                <?php if (empty($queue)): ?>
                    <p style="color:#999;">No active queue tickets.</p>
                <?php else: ?>
                <table>
                    <tr>
                        <th>Queue No</th>
                        <th>Patient Name</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Arrival Time</th>
                    </tr>
                    <?php foreach ($queue as $q): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($q['queue_no']) ?></strong></td>
                        <td><?= htmlspecialchars($q['name']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($q['source'])) ?></td>
                        <td><span class="status <?= $q['status'] ?>"><?= ucfirst($q['status']) ?></span></td>
                        <td><?= date('H:i', strtotime($q['arrival_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>