<?php
require_once __DIR__ . '/config/config.php';

// The public portal has been removed. Redirect to login/dashboard.
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/dashboard.php");
} else {
    header("Location: " . BASE_URL . "/login.php");
}
exit;
