<?php
session_start();
session_destroy();
header('Location: /Mediqueue/auth/login.php');
exit;
?>