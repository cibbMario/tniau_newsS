<?php
require_once __DIR__ . '/config/config.php';
header("Location: " . BASE_URL . (isLoggedIn() ? "/dashboard.php" : "/login.php"));
exit;
