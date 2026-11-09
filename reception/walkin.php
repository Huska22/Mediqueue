<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['receptionist', 'admin']);
require_once __DIR__ . '/../config/db.php';

$u = current_user();
$success = '';
$error = '';
$lastTicket = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);

    if ($patient_id > 0) {
        try {
            $last = $pdo->query("SELECT MAX(CAST(SUBSTRING(queue_no, 3) AS UNSIGNED)) AS maxno FROM queue_tickets WHERE queue_no LIKE 'A-%'")->fetch();
            $next = ($last['maxno'] ?? 0) + 1;
            $queue_no = 'A-' . str_pad($next, 3, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO queue_tickets (patient_id, queue_no, source, status) VALUES (?, ?, 'walkin', 'waiting')");
            $stmt->execute([$patient_id, $queue_no]);

            $ticket_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO audit_events (user_id, ticket_id, event_type) VALUES (?, ?, 'created_ticket')")
                ->execute([$u['id'], $ticket_id]);

            $lastTicket = ['queue_no' => $queue_no, 'ticket_id' => $ticket_id];
            $success = "Walk-in ticket created successfully.";
        } catch (Exception $e) {
            $error = "Failed: " . $e->getMessage();
        }
    } else {
        $error = "Please select a patient.";
    }
}

$patients = $pdo->query("SELECT patient_id, name, phone FROM patients ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Walk-in Queue - MediQueue</title>
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
        .main { flex: 1; padding: 25px; display: flex; justify-content: center; align-items: flex-start;}
        
        .card { background: white; width: 100%; max-width: 500px; padding: 40px 30px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); text-align: center; }
        h2 { margin-top: 0; margin-bottom: 25px; font-size: 24px; color: #000; }
        
        label { display: block; font-weight: bold; font-size: 16px; margin-bottom: 10px; text-align: center;}
        select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; margin-bottom: 30px; }
        
        .ticket-result { margin-bottom: 30px; }
        .ticket-result p.label { font-weight: bold; font-size: 16px; margin: 0 0 10px 0; }
        .qno { font-size: 90px; font-weight: bold; color: #000; line-height: 1; margin-bottom: 15px; }
        .status-text { font-weight: bold; font-size: 16px; margin: 0; }

        .btn-group { display: flex; gap: 10px; justify-content: center; }
        button { color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; width: 50%; }
        .btn-generate { background: #0d1b8f; }
        .btn-generate:hover { background: #0a1466; }
        .btn-print { background: #999; }
        .btn-print:hover { background: #777; }

        .alert { padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert.error { background: #ffebee; color: #c62828; }
        
        @media print {
            body * { visibility: hidden; }
            .card, .card * { visibility: visible; }
            .card { position: absolute; left: 0; top: 0; box-shadow: none; border: none; }
            .btn-group, select, label { display: none; }
            h2 { display: block; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MediQueue | Receptionist | <?= htmlspecialchars($u['name']) ?></h1>
        <a href="/Mediqueue/auth/logout.php">Logout</a>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="/Mediqueue/reception/dashboard.php">Dashboard</a>
            <a href="/Mediqueue/reception/register_patient.php">Register Patient</a>
            <a href="/Mediqueue/reception/patients.php">All Patients</a>
            <a href="/Mediqueue/reception/appointments.php">Appointments</a>
            <a href="/Mediqueue/reception/walkin.php" class="active">Walk-in Queue</a>
            <a href="/Mediqueue/reception/reports.php">Reports</a>
        </div>
        <div class="main">
            <div class="card">
                <h2>Walk-in Queue Ticket</h2>
                
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <form method="POST">
                    <label>Select Patient</label>
                    <select name="patient_id" required>
                        <option value="">-- Choose a patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>">
                                <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['phone']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if ($lastTicket): ?>
                        <div class="ticket-result">
                            <p class="label">Your Queue Number:</p>
                            <div class="qno"><?= htmlspecialchars($lastTicket['queue_no']) ?></div>
                            <p class="status-text">Status: Waiting</p>
                        </div>
                    <?php endif; ?>

                    <div class="btn-group">
                        <button type="submit" class="btn-generate">Generate Ticket</button>
                        <?php if ($lastTicket): ?>
                            <button type="button" class="btn-print" onclick="window.print()">Print Ticket</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>