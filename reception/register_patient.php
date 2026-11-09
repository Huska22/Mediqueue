<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['receptionist', 'admin']);
require_once __DIR__ . '/../config/db.php';

$u = current_user();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;

    if ($name && $phone) {
        try {
            $stmt = $pdo->prepare("INSERT INTO patients (name, phone, date_of_birth, gender) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $dob ?: null, $gender ?: null]);
            $success = "Patient registered successfully. Patient ID: " . $pdo->lastInsertId();
        } catch (Exception $e) {
            $error = "Failed to register: " . $e->getMessage();
        }
    } else {
        $error = "Name and phone are required.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Patient - MediQueue</title>
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
        .card { background: white; max-width: 600px; padding: 30px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
        button { background: #0d1b8f; color: white; padding: 12px 25px; margin-top: 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; }
        button:hover { background: #0a1466; }
        .alert { padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert.success { background: #e8f5e9; color: #2e7d32; }
        .alert.error { background: #ffebee; color: #c62828; }
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
            <a href="/Mediqueue/reception/register_patient.php" class="active">Register Patient</a>
            <a href="/Mediqueue/reception/patients.php">All Patients</a>
            <a href="/Mediqueue/reception/appointments.php">Appointments</a>
            <a href="/Mediqueue/reception/walkin.php">Walk-in Queue</a>
            <a href="/Mediqueue/reception/reports.php">Reports</a>
        </div>
        <div class="main">
            <div class="card">
                <h2>Patient Registration</h2>
                <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="POST">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>

                    <label>Phone Number *</label>
                    <input type="text" name="phone" required>

                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth">

                    <label>Gender</label>
                    <select name="gender">
                        <option value="">-- Select --</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>

                    <button type="submit">Save Patient</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>