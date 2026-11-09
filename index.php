<?php
require_once __DIR__ . '/includes/auth.php';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'clinician') {
        header('Location: /Mediqueue/clinician/dashboard.php');
    } else {
        header('Location: /Mediqueue/reception/dashboard.php');
    }
} else {
    header('Location: /Mediqueue/auth/login.php');
}
exit;
?>