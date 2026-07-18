<?php
require_once __DIR__ . '/config/config.php';
logout();
header("Location: " . BASE_URL . "/login.php");
exit;
