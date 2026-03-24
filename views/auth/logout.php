<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Hancurkan session
$_SESSION = [];
session_unset();
session_destroy();

// Redirect ke halaman login
header('Location: ' . BASE_URL . '/views/auth/login.php');
exit;
