<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();
$current = 'search';

$q = trim($_GET['q'] ?? '');
$newsList = [];

if ($q !== '') {
    // Batasi hasil pencarian: A hanya lihat miliknya + published, B/C lihat semua kecuali draft
    if ($user['role'] === 'A') {
        $stmt = $pdo->prepare(
            "SELECT news.*, u.full_name AS author_name
             FROM news JOIN users u ON u.id = news.created_by
             WHERE (news.created_by = :uid OR news.status = 'published')
               AND (news.title LIKE :q OR news.content LIKE :q)
             ORDER BY news.updated_at DESC"
        );
        $stmt->execute(['uid' => $user['id'], 'q' => "%$q%"]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT news.*, u.full_name AS author_name
             FROM news JOIN users u ON u.id = news.created_by
             WHERE news.status != 'draft'
               AND (news.title LIKE :q OR news.content LIKE :q)
             ORDER BY news.updated_at DESC"
        );
        $stmt->execute(['q' => "%$q%"]);
    }
    $newsList = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cari Berita - Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <h1>Cari Berita</h1>
        </div>

        <form class="search-box" action="<?= BASE_URL ?>/search.php" method="GET" style="width:100%; max-width:480px; margin-bottom:20px;">
            <span>Cari</span>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Ketik judul atau isi berita..." autofocus>
        </form>

        <?php if ($q !== ''): ?>
            <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
                <?= count($newsList) ?> hasil untuk "<?= e($q) ?>"
            </p>
        <?php endif; ?>

        <?php if ($q !== '' && empty($newsList)): ?>
            <div class="card empty-state">Tidak ditemukan berita yang cocok.</div>
        <?php endif; ?>

        <?php foreach ($newsList as $n): ?>
            <a href="<?= BASE_URL ?>/news_view.php?id=<?= $n['id'] ?>" class="news-list-item">
                <img class="thumb" src="<?= $n['image_path'] ? UPLOAD_URL . e($n['image_path']) : 'https://placehold.co/72x56/e9edf2/a0a8b3?text=AU' ?>" alt="">
                <div class="info">
                    <p class="title"><?= e($n['title']) ?></p>
                    <div class="meta">oleh <?= e($n['author_name']) ?> &middot; <?= formatTanggal($n['updated_at']) ?></div>
                </div>
                <span class="badge <?= statusBadgeClass($n['status']) ?>"><?= statusLabel($n['status']) ?></span>
            </a>
        <?php endforeach; ?>
    </main>
</div>
</body>
</html>
