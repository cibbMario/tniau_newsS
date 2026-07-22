<?php
require_once __DIR__ . '/config/config.php';

// Perform session cleanup
logout();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Clear any output buffers before header redirect
while (ob_get_level()) {
    ob_end_clean();
}

// Always redirect directly to login page — no conditional, no fallback
header("Location: " . BASE_URL . "/login.php");
exit;
