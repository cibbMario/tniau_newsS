<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();
$current = 'notif';

// Tandai semua terbaca saat halaman dibuka
if (($_GET['mark_read'] ?? '') === '1') {
    markAllNotificationsRead($user['id']);
    header("Location: " . BASE_URL . "/notifications.php");
    exit;
}

$notifs = getNotifications($user['id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi - Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content" style="background: radial-gradient(circle at top right, #e2e8f0, #f8f9fa); min-height: 100vh;">
        <div class="top-navbar">
            <div class="top-navbar-left">
                <button class="hamburger-btn" title="Toggle Menu">&#9776; Menu</button>
                <div class="media-tabs">
                    <span class="media-tab-item active">Notifikasi</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <a href="<?= BASE_URL ?>/notifications.php?mark_read=1" class="btn-outline btn-sm" style="border-radius:20px; padding:6px 12px;">Tandai semua dibaca</a>
            </div>
        </div>

        <div class="page-container" style="width: 100%; max-width: 1160px; margin: 10px auto; padding-top: 30px;">
            <div class="glass-panel" style="width: 100%;">
                <div class="glass-header">
                    <h2>Notifikasi Anda</h2>
                    <span class="glass-badge"><?= count(array_filter($notifs, fn($n) => !$n['is_read'])) ?> Baru</span>
                </div>

                <div class="glass-body">
                    <?php if (empty($notifs)): ?>
                        <div class="glass-empty">
                            <span class="empty-icon">Info</span>
                            <p>Belum ada notifikasi baru.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($notifs as $n): ?>
                        <a href="<?= BASE_URL ?>/news_view.php?id=<?= $n['news_id'] ?>&mark_read=<?= $n['id'] ?>" class="glass-notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                            <div class="notif-indicator">
                                <?php if (!$n['is_read']): ?>
                                    <div class="indicator-dot"></div>
                                <?php endif; ?>
                            </div>
                            <div class="notif-content">
                                <div class="notif-message"><?= e($n['message']) ?></div>
                                <div class="notif-time"><?= formatTanggal($n['created_at']) ?></div>
                            </div>
                            <div class="notif-action">
                                <span class="arrow-icon">›</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
