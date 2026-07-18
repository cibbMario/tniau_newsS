<?php
/**
 * Konfigurasi umum aplikasi
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/tniau-news');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
