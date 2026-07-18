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

    <main class="main-content" style="max-width:640px;">
        <div class="topbar">
            <h1>Notifikasi</h1>
            <a href="<?= BASE_URL ?>/notifications.php?mark_read=1" class="btn btn-outline btn-sm">Tandai semua dibaca</a>
        </div>

        <div class="card" style="padding:8px;">
            <?php if (empty($notifs)): ?>
                <p class="empty-state">Belum ada notifikasi.</p>
            <?php endif; ?>

            <?php foreach ($notifs as $n): ?>
                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $n['news_id'] ?>&mark_read=<?= $n['id'] ?>"
                   class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" style="display:flex;">
                    <span class="notif-dot" style="<?= $n['is_read'] ? 'background:#c7cdd6;' : '' ?>"></span>
                    <div>
                        <div style="font-size:13px;"><?= e($n['message']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= formatTanggal($n['created_at']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>
