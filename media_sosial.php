<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$current = 'media_sosial';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Media Sosial - TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="top-navbar">
                <div class="top-navbar-left">
                    <button class="hamburger-btn">☰</button>
                    <div class="media-tabs">
                        <div class="media-tab-item active">Media Sosial</div>
                    </div>
                </div>
            </header>
            <div class="page-container">
                <h2>Media Sosial</h2>
                <p>Fitur ini dalam tahap pengembangan.</p>
            </div>
        </main>
    </div>
</body>
</html>
