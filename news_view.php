<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'list';
$user = currentUser();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: " . BASE_URL . "/news_list.php");
    exit;
}

$stmt = $pdo->prepare("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.id = ?");
$stmt->execute([$id]);
$news = $stmt->fetch();

if (!$news) {
    die("Berita tidak ditemukan.");
}

// Tandai notifikasi terkait sebagai terbaca jika dilihat melalui tautan notifikasi
if (!empty($_GET['mark_read'])) {
    $notifId = (int)$_GET['mark_read'];
    markNotificationRead($notifId, $user['id']);
}

// Role restriction
if ($user['role'] !== 'A' && $news['status'] === 'draft') {
    die("Berita ini masih dalam bentuk draft.");
}

$images = $pdo->prepare("SELECT * FROM news_images WHERE news_id = ?");
$images->execute([$id]);
$gallery = $images->fetchAll();

$comments = $pdo->prepare("SELECT c.*, u.full_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.news_id = ? ORDER BY c.created_at ASC");
$comments->execute([$id]);
$commentsList = $comments->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($news['title']) ?> — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="top-navbar">
            <div class="top-navbar-left">
                <button class="hamburger-btn" title="Menu">☰</button>
                <div class="media-tabs">
                    <span class="media-tab-item active">👁️ Detail Berita</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <a href="<?= BASE_URL ?>/news_list.php" class="top-action-btn">← Kembali</a>
                <?php if ($user['role'] === 'A' && in_array($news['status'], ['draft','pending_b','revision_b','revision_c'])): ?>
                    <a href="<?= BASE_URL ?>/news_edit.php?id=<?= $id ?>" class="btn btn-primary btn-sm">✏️ Edit Berita</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-container">
            <div class="detail-grid">
                
                <!-- MAIN CONTENT -->
                <div class="detail-main">
                    <div class="detail-card">
                        <div class="detail-toolbar">
                            <div class="detail-toolbar-left">
                                <span class="badge <?= statusBadgeClass($news['status']) ?>"><?= statusLabel($news['status']) ?></span>
                                <span class="pill pill-<?= strtolower($news['sentiment'] ?? 'netral') ?>"><?= e($news['sentiment']) ?></span>
                                <span class="badge badge-gray">Prioritas: <?= e($news['priority']) ?></span>
                            </div>
                            <div class="detail-toolbar-right">
                                <button class="btn-share" onclick="window.print()" title="Cetak">🖨️</button>
                            </div>
                        </div>

                        <h1 class="detail-title"><?= e($news['title']) ?></h1>
                        
                        <div class="detail-meta">
                            <span>👤 <?= e($news['author_label'] ?? $news['author_name']) ?></span>
                            <span>🏛️ <?= e($news['wilayah'] ?: '-') ?></span>
                            <span>📰 <?= e($news['media'] ?: '-') ?></span>
                            <?php if ($news['published_at']): ?>
                                <span>📅 Terbit: <?= formatTanggal($news['published_at']) ?></span>
                            <?php else: ?>
                                <span>📅 Dibuat: <?= formatTanggal($news['created_at']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($news['image']): ?>
                            <img src="<?= UPLOAD_URL . e($news['image']) ?>" alt="Gambar Berita" class="detail-img">
                        <?php endif; ?>

                        <div class="detail-body">
                            <?= $news['content'] // Raw HTML permitted from editor ?>
                        </div>

                        <?php if (!empty($gallery)): ?>
                        <h4 class="info-section-title">Galeri Media</h4>
                        <div class="gallery-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
                            <?php foreach($gallery as $img): ?>
                                <div class="gallery-item">
                                    <img src="<?= UPLOAD_URL . e($img['image_path']) ?>" alt="Galeri">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="info-grid">
                            <div class="info-col">
                                <div class="info-row"><div class="info-label">Klasifikasi</div><div class="info-value"><?= e($news['classification']) ?></div></div>
                                <div class="info-row"><div class="info-label">Tempat</div><div class="info-value"><?= e($news['tempat'] ?: '-') ?></div></div>
                                <div class="info-row"><div class="info-label">Aktor</div><div class="info-value"><?= e($news['aktor'] ?: '-') ?></div></div>
                            </div>
                            <div class="info-col">
                                <div class="info-row"><div class="info-label">Tag</div>
                                    <div class="info-value">
                                        <?php $tags = array_filter(array_map('trim', explode(',', $news['tag'] ?? ''))); ?>
                                        <div class="tag-list">
                                            <?php if(empty($tags)): ?>-<?php endif; ?>
                                            <?php foreach($tags as $t): ?><span class="tag-chip"><span><?= e($t) ?></span></span><?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="info-row"><div class="info-label">Topik</div><div class="info-value"><?= e($news['topik'] ?: '-') ?></div></div>
                                <div class="info-row"><div class="info-label">Keyword</div><div class="info-value"><?= e($news['keyword'] ?: '-') ?></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- COMMENTS SECTION -->
                    <div class="comments-card">
                        <h3 class="comments-title">Diskusi &amp; Catatan Review (<?= count($commentsList) ?>)</h3>
                        <div class="comments-list">
                            <?php if(empty($commentsList)): ?>
                                <div class="empty-state">Belum ada catatan review.</div>
                            <?php else: ?>
                                <?php foreach($commentsList as $c): ?>
                                    <div class="comment-item">
                                        <div class="comment-avatar"><?= strtoupper(substr($c['full_name'],0,1)) ?></div>
                                        <div class="comment-body">
                                            <div class="comment-header">
                                                <span class="name"><?= e($c['full_name']) ?></span>
                                                <span class="time"><?= timeAgo($c['created_at']) ?></span>
                                                <?php if($c['is_correction']): ?><span class="tag-correction">Koreksi</span><?php endif; ?>
                                            </div>
                                            <div class="comment-text"><?= nl2br(e($c['comment'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form action="<?= BASE_URL ?>/comment_action.php" method="POST" style="margin-top:20px;border-top:1px solid #e2e6ea;padding-top:16px">
                            <input type="hidden" name="news_id" value="<?= $id ?>">
                            <div class="form-group">
                                <textarea name="comment" class="form-input" placeholder="Tulis catatan atau instruksi revisi..." required></textarea>
                            </div>
                            <?php if(in_array($user['role'], ['B','C'])): ?>
                            <label class="radio-label" style="margin-bottom:12px;display:inline-flex">
                                <input type="checkbox" name="is_correction" value="1"> Tandai sebagai instruksi koreksi/revisi
                            </label>
                            <?php endif; ?>
                            <div>
                                <button type="submit" class="btn btn-outline btn-sm">💬 Kirim Catatan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- RIGHT SIDEBAR (REVIEW ACTIONS) -->
                <div class="detail-sidebar">
                    <?php 
                    // Tampilkan kotak action jika user punya hak review
                    if (($user['role'] === 'B' && $news['status'] === 'pending_b') || 
                        ($user['role'] === 'C' && $news['status'] === 'pending_c')): 
                    ?>
                    <div class="review-card">
                        <h3>Tindakan Review</h3>
                        <p>Silakan tinjau berita ini. Jika sudah sesuai, setujui. Jika perlu perbaikan, kembalikan ke Reporter.</p>
                        <form action="<?= BASE_URL ?>/review_action.php" method="POST" style="display:flex;flex-direction:column;gap:8px">
                            <input type="hidden" name="news_id" value="<?= $id ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-block">✅ Setujui &amp; Lanjutkan</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-block" onclick="return confirm('Kembalikan ke Reporter untuk direvisi?')">❌ Minta Revisi</button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <?php if ($user['role'] === 'C' && $news['status'] === 'published'): ?>
                    <div class="review-card" style="border-color:var(--red);background:var(--red-bg)">
                        <h3 style="color:var(--red)">Tarik Berita</h3>
                        <p>Turunkan berita ini dari publikasi jika ada kesalahan fatal.</p>
                        <form action="<?= BASE_URL ?>/review_action.php" method="POST">
                            <input type="hidden" name="news_id" value="<?= $id ?>">
                            <button type="submit" name="action" value="unpublish" class="btn btn-outline btn-block" onclick="return confirm('Yakin ingin menurunkan berita ini?')" style="color:var(--red);border-color:var(--red)">⬇️ Unpublish</button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="accordion">
                        <div class="accordion-head">Riwayat Perubahan</div>
                        <div class="accordion-body">
                            <?php
                            $hist = $pdo->prepare("SELECT h.*, u.full_name FROM news_history h JOIN users u ON h.user_id = u.id WHERE h.news_id = ? ORDER BY h.created_at DESC LIMIT 5");
                            $hist->execute([$id]);
                            $histories = $hist->fetchAll();
                            foreach($histories as $h):
                            ?>
                            <div style="font-size:11.5px;margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:10px">
                                <strong><?= e($h['full_name']) ?></strong><br>
                                <span style="color:var(--text-sec)"><?= formatTanggal($h['created_at']) ?></span><br>
                                Mengubah status ke: <em><?= statusLabel($h['status_to']) ?></em>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>
