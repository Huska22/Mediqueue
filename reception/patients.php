<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['receptionist', 'admin']);
require_once __DIR__ . '/../config/db.php';

$u = current_user();
$patients = $pdo->query("SELECT * FROM patients ORDER BY patient_id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Patients - MediQueue</title>
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
        .panel { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; font-size: 18px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center;}
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; font-size: 12px; text-transform: uppercase; color: #666; }
        .btn-edit { background: #f59e0b; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;}
        .btn-edit:hover { background: #d97706; }
        .btn-new { background: #0d1b8f; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;}
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
            <a href="/Mediqueue/reception/patients.php" class="active">All Patients</a>
            <a href="/Mediqueue/reception/appointments.php">Appointments</a>
            <a href="/Mediqueue/reception/walkin.php">Walk-in Queue</a>
            <a href="/Mediqueue/reception/reports.php">Reports</a>
        </div>
        <div class="main">
            <div class="panel">
                <h2>Patient Directory <a href="/Mediqueue/reception/register_patient.php" class="btn-new">+ New Patient</a></h2>
                <table>
                    <tr>
                        <th>ID</th><th>Name</th><th>Phone</th><th>DOB</th><th>Gender</th><th>Action</th>
                    </tr>
                    <?php foreach ($patients as $p): ?>
                    <tr>
                        <td>#<?= $p['patient_id'] ?></td>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td><?= htmlspecialchars($p['phone']) ?></td>
                        <td><?= htmlspecialchars($p['date_of_birth'] ?: '-') ?></td>
                        <td><?= ucfirst(htmlspecialchars($p['gender'] ?: '-')) ?></td>
                        <td><a href="/Mediqueue/reception/edit_patient.php?id=<?= $p['patient_id'] ?>" class="btn-edit">Edit</a></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>