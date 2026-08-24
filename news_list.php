<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$statusFilter = $_GET['status'] ?? '';
$current = $statusFilter === 'draft' ? 'draft' : 'list';
$user = currentUser();

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

if ($statusFilter === 'draft' && in_array($user['role'], ['A','E'])) {
    if ($user['role'] === 'A') {
        $where[] = "n.created_by = ?";
        $params[] = $user['id'];
    }
    $where[] = "n.status = 'draft'";
} elseif ($user['role'] === 'A') {
    $where[] = "(n.created_by = ? OR n.status = 'published')";
    $params[] = $user['id'];
} elseif (in_array($user['role'], ['D','E'])) {
    // User D and E can view all news
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
    <title>Daftar Berita Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <!-- PAGE CONTENT -->
        <div class="page-container">

            <!-- ACTION BAR -->
            <div class="action-bar">
                <div class="action-bar-left">
                    <?php if ($statusFilter !== 'draft' && in_array($user['role'], ['A','E'])): ?>
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

            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div style="background:#eafaf1;color:#1e8449;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid rgba(39,174,96,.2);display:flex;align-items:center;gap:10px;font-size:13.5px;font-weight:500;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#27ae60;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?= e($_SESSION['flash_success']) ?>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <!-- NEWS TABLE -->
            <div class="news-table-wrap">
                <div class="table-responsive">
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
                            <?php $isPublished = $row['status'] === 'published'; ?>
                            <tr style="cursor:pointer;" onclick="window.location='<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>'">
                                <td class="col-subject <?= $isPublished ? 'subject-approved' : 'subject-unapproved' ?>">
                                    <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>" onclick="event.stopPropagation()">
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
                                <td onclick="event.stopPropagation()">
                                    <?php if (in_array($user['role'], ['A','B','C','D','E'])): ?>
                                        <div style="display:inline-flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                            <?php if (($user['role'] === 'A' && $row['created_by'] === $user['id']) || $user['role'] === 'E'): ?>
                                                <?php if (in_array($row['status'], ['draft','pending_b','pending_c','pending_d','revision_b','revision_c','revision_d','published'])): ?>
                                                    <a href="<?= BASE_URL ?>/news_edit.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                                <?php endif; ?>
                                                <?php if (in_array($row['status'], ['revision_b','revision_c','revision_d'])): ?>
                                                    <form method="POST" action="<?= BASE_URL ?>/resubmit_action.php" style="display:inline;margin:0;">
                                                        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                                        <input type="hidden" name="news_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" style="background:#27ae60;color:#fff;" title="Kirim ulang hasil revisi">Selesai Revisi</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" action="<?= BASE_URL ?>/news_delete.php" onsubmit="return confirm('Yakin ingin menghapus berita ini?');" style="display:inline;margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (($user['role'] === 'B' || $user['role'] === 'E') && $row['status'] === 'pending_b'): ?>
                                                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm" style="background:#27ae60;color:#fff;">Review Editor</a>
                                            <?php endif; ?>

                                            <?php if (($user['role'] === 'C' || $user['role'] === 'E') && $row['status'] === 'pending_c'): ?>
                                                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm" style="background:#27ae60;color:#fff;">Setujui</a>
                                                <form method="POST" action="<?= BASE_URL ?>/review_action.php" onsubmit="return confirm('Minta revisi dari Reporter?');" style="display:inline;margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                                    <input type="hidden" name="news_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-danger btn-sm">Tolak</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (($user['role'] === 'D' || $user['role'] === 'E') && in_array($row['status'], ['pending_d', 'pending_c'], true)): ?>
                                                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm" style="background:#27ae60;color:#fff;">Setujui</a>
                                            <?php endif; ?>
                                        </div>
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
