<?php
require_once __DIR__ . '/config/config.php';

// Only accept POST requests for logout to improve safety
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ' . BASE_URL . '/');
	exit;
}

// Verify CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!function_exists('verify_csrf_token') || !verify_csrf_token($token)) {
	// Invalid token — redirect back
	header('Location: ' . BASE_URL . '/profile.php');
	exit;
}

logout();
header("Location: " . BASE_URL . "/login.php");
exit;
