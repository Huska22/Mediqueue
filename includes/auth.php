<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login($allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /Mediqueue/auth/login.php');
        exit;
    }
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        die("Access denied. You do not have permission to view this page.");
    }
}

function current_user() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
    ];
}
?>