<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'dashboard';
$user = currentUser();

// Stats
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn(),
    'published' => $pdo->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM news WHERE status LIKE 'pending%'")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
];

// Recent news
$recent = $pdo->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="top-navbar">
            <div class="top-navbar-left">
                <button class="hamburger-btn" title="Toggle Menu">☰ Menu</button>
                <div class="media-tabs">
                    <span class="media-tab-item active">🏠 Dashboard Utama</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <span class="top-action-btn">📅 <?= date('d M Y') ?></span>
                <span class="top-action-btn">👤 <?= e($user['full_name']) ?></span>
            </div>
        </div>

        <div class="page-container">
            <h2 style="margin-bottom:20px;color:var(--navy);font-weight:700">Selamat datang, <?= e($user['full_name']) ?>!</h2>
            
            <div class="dash-grid">
                <div class="dash-card">
                    <div class="dash-card-info">
                        <span class="dash-card-label">Total Berita</span>
                        <span class="dash-card-value"><?= $stats['total'] ?></span>
                    </div>
                    <div class="dash-card-icon" style="color:var(--blue)">Dokumen</div>
                </div>
                <div class="dash-card">
                    <div class="dash-card-info">
                        <span class="dash-card-label">Dipublikasikan</span>
                        <span class="dash-card-value"><?= $stats['published'] ?></span>
                    </div>
                    <div class="dash-card-icon" style="color:var(--green)">Selesai</div>
                </div>
                <div class="dash-card">
                    <div class="dash-card-info">
                        <span class="dash-card-label">Menunggu Review</span>
                        <span class="dash-card-value"><?= $stats['pending'] ?></span>
                    </div>
                    <div class="dash-card-icon" style="color:var(--yellow)">⏳</div>
                </div>
                <div class="dash-card">
                    <div class="dash-card-info">
                        <span class="dash-card-label">Pengguna Aktif</span>
                        <span class="dash-card-value"><?= $stats['users'] ?></span>
                    </div>
                    <div class="dash-card-icon" style="color:var(--gold-dark)">👥</div>
                </div>
            </div>

            <div class="card">
                <h3 style="font-size:14px;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:14px;color:var(--text)">Berita Terbaru</h3>
                <div class="news-table-wrap">
                    <table class="news-table">
                        <thead>
                            <tr>
                                <th>Subjek</th>
                                <th>Status</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent as $r): ?>
                            <tr>
                                <td class="col-subject"><a href="<?= BASE_URL ?>/news_view.php?id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></td>
                                <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= statusLabel($r['status']) ?></span></td>
                                <td class="col-time"><?= formatTanggal($r['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:16px;text-align:right">
                    <a href="<?= BASE_URL ?>/news_list.php" class="btn btn-outline btn-sm">Lihat Semua Berita →</a>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
