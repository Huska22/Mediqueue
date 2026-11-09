<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['receptionist', 'admin']);
require_once __DIR__ . '/../config/db.php';

$u = current_user();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $service_doctor = trim($_POST['service_doctor'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($patient_id > 0 && $date && $time) {
        try {
            $scheduled_at = $date . ' ' . $time . ':00';
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, scheduled_at, status, created_by, service_doctor, notes) VALUES (?, ?, 'booked', ?, ?, ?)");
            $stmt->execute([$patient_id, $scheduled_at, $u['id'], $service_doctor, $notes]);
            $success = "Appointment booked successfully!";
        } catch (Exception $e) {
            $error = "Failed to book appointment: " . $e->getMessage();
        }
    } else {
        $error = "Please select a patient, date, and time.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $appt_id = (int)($_POST['appt_id_cancel'] ?? 0);
    if ($appt_id > 0) {
        $pdo->prepare("UPDATE appointments SET status='cancelled' WHERE appointment_id=?")->execute([$appt_id]);
        $success = "Appointment #$appt_id cancelled.";
    } else {
        $error = "Please enter a valid Appointment ID to cancel.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reschedule') {
    $appt_id = (int)($_POST['appt_id_resched'] ?? 0);
    $new_date = $_POST['new_date'] ?? '';
    $new_time = $_POST['new_time'] ?? '';
    
    if ($appt_id > 0 && $new_date && $new_time) {
        $new_scheduled = $new_date . ' ' . $new_time . ':00';
        $pdo->prepare("UPDATE appointments SET scheduled_at=?, status='rescheduled' WHERE appointment_id=?")->execute([$new_scheduled, $appt_id]);
        $success = "Appointment #$appt_id rescheduled.";
    } else {
        $error = "Please enter the Appointment ID, new date, and new time.";
    }
}

$patients = $pdo->query("SELECT patient_id, name, phone FROM patients ORDER BY name ASC")->fetchAll();
$appointments = $pdo->query("
    SELECT a.appointment_id, a.scheduled_at, a.status, p.name, p.phone 
    FROM appointments a
    JOIN patients p ON p.patient_id = a.patient_id
    WHERE a.status IN ('booked', 'rescheduled')
    ORDER BY a.scheduled_at ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Appointments - MediQueue</title>
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
        
        .main { flex: 1; padding: 25px; display: flex; justify-content: center; gap: 20px; align-items: flex-start; flex-wrap: wrap;}
        
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); width: 100%; max-width: 450px; }
        h2 { margin-top: 0; font-size: 20px; text-align: center; margin-bottom: 20px;}
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; font-size: 14px; margin-bottom: 5px; text-align: left; }
        select, input, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; font-family: Arial;}
        textarea { resize: vertical; height: 60px; }
        
        .btn-group { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
        button { width: 100%; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; text-align: center;}
        
        .btn-book { background: #0d1b8f; }
        .btn-book:hover { background: #0a1466; }
        .btn-resched { background: #999; }
        .btn-cancel { background: #dc3545; }
        
        .action-panel { display: none; background: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-top: 15px;}
        .action-panel input { margin-bottom: 10px; }

        .alert { padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; width: 100%; max-width: 500px;}
        .alert.success { background: #e8f5e9; color: #2e7d32; }
        .alert.error { background: #ffebee; color: #c62828; }

        .table-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); width: 100%; max-width: 600px;}
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; font-size: 12px; text-transform: uppercase; color: #666; }
        .status { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #e3f2fd; color: #1565c0; }
    </style>
    <script>
        function showPanel(id) {
            var panel = document.getElementById(id);
            if (panel.style.display === "block") {
                panel.style.display = "none";
            } else {
                panel.style.display = "block";
            }
        }
    </script>
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
            <a href="/Mediqueue/reception/appointments.php" class="active">Appointments</a>
            <a href="/Mediqueue/reception/walkin.php">Walk-in Queue</a>
            <a href="/Mediqueue/reception/reports.php">Reports</a>
        </div>
        <div class="main">
            
            <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <div class="card">
                <h2>Book Appointment</h2>
                <form method="POST" id="bookForm">
                    <input type="hidden" name="action" value="book">
                    <div class="form-group">
                        <label>Select Patient</label>
                        <select name="patient_id" required>
                            <option value="">-- Choose Patient --</option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['phone']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Date</label><input type="date" name="date" required min="<?= date('Y-m-d') ?>"></div>
                    <div class="form-group"><label>Time</label><input type="time" name="time" required></div>
                    <div class="form-group"><label>Service / Doctor</label><input type="text" name="service_doctor"></div>
                    <div class="form-group"><label>Notes</label><textarea name="notes"></textarea></div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn-book">Book Appointment</button>
                        <button type="button" class="btn-resched" onclick="showPanel('reschedulePanel')">Reschedule</button>
                        <button type="button" class="btn-cancel" onclick="showPanel('cancelPanel')">Cancel</button>
                    </div>
                </form>

                <div id="reschedulePanel" class="action-panel">
                    <form method="POST">
                        <input type="hidden" name="action" value="reschedule">
                        <label>Appointment ID (from table)</label>
                        <input type="number" name="appt_id_resched" required>
                        <label>New Date</label>
                        <input type="date" name="new_date" required>
                        <label>New Time</label>
                        <input type="time" name="new_time" required>
                        <button type="submit" class="btn-resched" style="margin-top:10px;">Confirm Reschedule</button>
                    </form>
                </div>

                <div id="cancelPanel" class="action-panel">
                    <form method="POST">
                        <input type="hidden" name="action" value="cancel">
                        <label>Appointment ID (from table)</label>
                        <input type="number" name="appt_id_cancel" required>
                        <button type="submit" class="btn-cancel" style="margin-top:10px;">Confirm Cancel</button>
                    </form>
                </div>

            </div>

            <div class="table-card">
                <h2>Upcoming Appointments</h2>
                <?php if (empty($appointments)): ?>
                    <p style="color:#999;">No appointments booked yet.</p>
                <?php else: ?>
                    <table>
                        <tr><th>ID</th><th>Patient</th><th>Date & Time</th><th>Status</th></tr>
                        <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><strong>#<?= $a['appointment_id'] ?></strong></td>
                            <td><?= htmlspecialchars($a['name']) ?></td>
                            <td><?= date('d M Y, H:i', strtotime($a['scheduled_at'])) ?></td>
                            <td><span class="status"><?= ucfirst($a['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>
</html>