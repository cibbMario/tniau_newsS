<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'list';
$user = currentUser();

// Helper: waktu relatif
function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return formatTanggal($datetime);
}

// Media filter
$mediaFilter = $_GET['media'] ?? 'Semua';
$validMedia = ['Wilayah', 'Media Online', 'Media Sosial', 'Semua'];
if (!in_array($mediaFilter, $validMedia)) $mediaFilter = 'Semua';

// Search
$search = trim($_GET['q'] ?? '');

// Query
$sql = "SELECT n.*, u.full_name AS author_name, n.created_by
        FROM news n JOIN users u ON n.created_by = u.id";
$where = [];
$params = [];

if ($user['role'] === 'A') {
    $where[] = "(n.created_by = ? OR n.status = 'published')";
    $params[] = $user['id'];
} else {
    $where[] = "n.status != 'draft'";
}
if ($mediaFilter !== 'Semua') {
    $where[] = "n.media = ?";
    $params[] = $mediaFilter;
}
if ($search) {
    $where[] = "(n.title LIKE ? OR n.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$newsList = $stmt->fetchAll();

// Sentiment counts
$total = count($newsList);
$positif = count(array_filter($newsList, fn($n) => ($n['sentiment'] ?? '') === 'Positif'));
$negatif = count(array_filter($newsList, fn($n) => ($n['sentiment'] ?? '') === 'Negatif'));
$netral  = count(array_filter($newsList, fn($n) => ($n['sentiment'] ?? '') === 'Netral'));
$pctP = $total ? round($positif / $total * 100) : 0;
$pctN = $total ? round($negatif / $total * 100) : 0;
$pctNe = $total ? round($netral / $total * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Berita — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- TOP NAVBAR -->
        <div class="top-navbar">
            <div class="top-navbar-left">
                <button class="hamburger-btn" title="Menu">Menu</button>
                <div class="media-tabs">
                    <?php
                    $tabs = [
                        'Semua'        => 'Semua Sumber',
                        'Wilayah'      => 'Berita Wilayah',
                        'Media Online' => 'Media Online',
                        'Media Sosial' => 'Media Sosial',
                    ];
                    foreach ($tabs as $key => $label): ?>
                        <a href="<?= BASE_URL ?>/news_list.php?media=<?= urlencode($key) ?>"
                           class="media-tab-item <?= $mediaFilter === $key ? 'active' : '' ?>">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                    <div class="nav-divider" style="width:1px;height:20px;background:#e2e8f0;margin:0 10px;"></div>
                    <a href="<?= BASE_URL ?>/statistics.php" class="media-tab-item">Statistik</a>
                    <a href="<?= BASE_URL ?>/gallery.php" class="media-tab-item">Galeri Media</a>
                </div>
            </div>
            <div class="top-navbar-right">
                <form method="GET" style="display:flex;gap:6px">
                    <input type="hidden" name="media" value="<?= e($mediaFilter) ?>">
                    <input type="text" name="q" class="form-input" placeholder="Cari berita..." value="<?= e($search) ?>" style="width:180px;height:32px;font-size:12px">
                    <button type="submit" class="top-action-btn">Cari</button>
                </form>
                <span class="top-action-btn">Tanggal <?= date('d M Y') ?></span>
            </div>
        </div>

        <!-- WORKSPACE TABS -->
        <div class="workspace-tabs-row">
            <div class="workspace-tab active">
                <span><?= $mediaFilter === 'Semua' ? 'Semua Sumber' : e($mediaFilter) ?></span>
                <span class="close-tab">×</span>
            </div>
        </div>

        <!-- PAGE CONTENT -->
        <div class="page-container">

            <!-- ACTION BAR -->
            <div class="action-bar">
                <div class="action-bar-left">
                    <?php if ($user['role'] === 'A'): ?>
                        <a href="<?= BASE_URL ?>/news_create.php" class="btn-entry-new">Buat Berita Baru</a>
                    <?php endif; ?>
                    <span class="pagination-info"><?= $total ?> berita ditemukan</span>
                </div>
                <div class="action-bar-right">
                    <a href="<?= BASE_URL ?>/news_list.php?media=<?= urlencode($mediaFilter) ?>" class="btn-refresh">Refresh</a>
                </div>
            </div>

            <?php if ($search): ?>
            <div class="filter-badge-row">
                <span class="filter-badge">
                    Pencarian: "<?= e($search) ?>"
                    <a href="<?= BASE_URL ?>/news_list.php?media=<?= urlencode($mediaFilter) ?>" class="close-badge">×</a>
                </span>
            </div>
            <?php endif; ?>

            <!-- SENTIMENT CARDS -->
            <div class="sentiment-grid">
                <div class="sentiment-card negatif">
                    <div class="card-label">Negatif</div>
                    <div class="card-row">
                        <span class="card-count"><?= $negatif ?></span>
                        <span class="card-pct"><?= $pctN ?>%</span>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= $pctN ?>%"></div></div>
                </div>
                <div class="sentiment-card netral">
                    <div class="card-label">Netral</div>
                    <div class="card-row">
                        <span class="card-count"><?= $netral ?></span>
                        <span class="card-pct"><?= $pctNe ?>%</span>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= $pctNe ?>%"></div></div>
                </div>
                <div class="sentiment-card positif">
                    <div class="card-label">Positif</div>
                    <div class="card-row">
                        <span class="card-count"><?= $positif ?></span>
                        <span class="card-pct"><?= $pctP ?>%</span>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= $pctP ?>%"></div></div>
                </div>
                <div class="sentiment-card total">
                    <div class="card-label">Total</div>
                    <div class="card-row">
                        <span class="card-count"><?= $total ?></span>
                        <span class="card-pct">100%</span>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:100%"></div></div>
                </div>
            </div>

            <!-- NEWS TABLE -->
            <div class="news-table-wrap">
                <table class="news-table">
                    <thead>
                        <tr>
                            <th style="width:30%">Subjek</th>
                            <th>Media</th>
                            <th>Author</th>
                            <th>Wilayah</th>
                            <th>Waktu Terbit</th>
                            <th>Status</th>
                            <th>Sentimen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($newsList)): ?>
                            <tr><td colspan="8" class="empty-state">Belum ada berita yang tersedia.</td></tr>
                        <?php else: ?>
                            <?php foreach ($newsList as $row): ?>
                            <tr>
                                <td class="col-subject">
                                    <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>">
                                        <?= e($row['title']) ?>
                                    </a>
                                </td>
                                <td class="col-muted"><?= e($row['media'] ?? '-') ?></td>
                                <td class="col-author"><?= e($row['author_label'] ?? $row['author_name']) ?></td>
                                <td class="col-muted"><?= e($row['wilayah'] ?? '-') ?></td>
                                <td class="col-time">
                                    <?php if (!empty($row['published_at'])): ?>
                                        <?= formatTanggal($row['published_at']) ?>
                                    <?php else: ?>
                                        <?= timeAgo($row['created_at']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= statusBadgeClass($row['status']) ?>"><?= statusLabel($row['status']) ?></span></td>
                                <td>
                                    <?php
                                    $s = strtolower($row['sentiment'] ?? 'netral');
                                    $cls = match($s) { 'positif'=>'pill-positif', 'negatif'=>'pill-negatif', default=>'pill-netral' };
                                    ?>
                                    <span class="pill <?= $cls ?>"><?= e($row['sentiment'] ?? 'Netral') ?></span>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'A' && $row['created_by'] === $user['id'] && in_array($row['status'], ['draft','pending_b','revision_b','revision_c'])): ?>
                                        <a href="<?= BASE_URL ?>/news_edit.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
