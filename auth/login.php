<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'clinician') {
                header('Location: /Mediqueue/clinician/dashboard.php');
            } else {
                header('Location: /Mediqueue/reception/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please enter email and password.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>MediQueue - Login</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; margin: 0; }
        .header { background: #0d1b8f; color: white; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; }
        .card { background: white; max-width: 400px; margin: 60px auto; padding: 40px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        h2 { text-align: center; margin-top: 0; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; background: #0d1b8f; color: white; padding: 12px; margin-top: 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #0a1466; }
        .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-top: 15px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">MediQueue</div>
    <div class="card">
        <h2>Staff Login</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label>Email</label>
            <input type="email" name="email" required autofocus>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <div class="footer">Authorized staff only.</div>
    </div>
</body>
</html>