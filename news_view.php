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

// Helper waktu relatif (lokal, aman jika belum ada di functions.php)
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)     return 'Baru saja';
        if ($diff < 3600)   return floor($diff / 60) . ' menit lalu';
        if ($diff < 86400)  return floor($diff / 3600) . ' jam lalu';
        if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
        return formatTanggal($datetime);
    }
}
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
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-container">
            <div class="detail-grid">
                
                <!-- MAIN CONTENT -->
                <div class="detail-main">
                    <div class="detail-card">
                        <div class="detail-toolbar" style="border:none; margin-bottom:8px;">
                            <div class="detail-toolbar-left">
                                <span class="btn-tool" style="border-radius:4px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Ubah Ke Berita Utama
                                </span>
                                <?php if ($user['role'] === 'A' && in_array($news['status'], ['draft','pending_b','revision_b','revision_c'])): ?>
                                <a href="<?= BASE_URL ?>/news_edit.php?id=<?= $id ?>" class="btn-tool" style="border-radius:4px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                                <?php endif; ?>
                                <span class="btn-tool" style="border-radius:4px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Review
                                </span>
                                <span class="btn-tool" style="border-radius:4px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                    Ditandai Sebagai
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </span>
                                <span class="btn-tool active" style="border-radius:4px;background:var(--navy);color:#fff;border-color:var(--navy)">Riwayat Catatan</span>
                            </div>
                            <div class="detail-toolbar-right">
                                <button class="btn-tool" style="border-radius:4px;color:var(--blue);background:rgba(30,111,191,0.08);border-color:transparent;" title="Salin tautan">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="detail-toolbar-left" style="margin-bottom: 12px; gap:8px;">
                            <span class="badge" style="border-radius:20px; border:1px solid #ced4da; background:#fff; color:#333; font-weight:600; padding:2px 10px; font-size:10px;">Medium</span>
                            <span class="badge" style="border-radius:20px; border:1px solid #27ae60; background:rgba(39,174,96,0.1); color:#27ae60; font-weight:700; padding:2px 10px; font-size:10px;">POSITIF</span>
                            <span class="badge" style="border-radius:20px; background:#4A89DC; color:#fff; font-weight:700; padding:2px 10px; font-size:10px;">Publish</span>
                        </div>

                        <h1 class="detail-title"><?= e($news['title']) ?></h1>
                        
                        <div class="detail-meta">
                            <span><?= e($news['author_label'] ?? $news['author_name']) ?></span>
                            <span><?= e($news['wilayah'] ?: '-') ?></span>
                            <span><?= e($news['media'] ?: '-') ?></span>
                            <?php if ($news['published_at']): ?>
                                <span>Terbit: <?= formatTanggal($news['published_at']) ?></span>
                            <?php else: ?>
                                <span>Dibuat: <?= formatTanggal($news['created_at']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($news['image'] ?? '')): ?>
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
                                <div class="info-row"><div class="info-label">Tempat</div><div class="info-value"><?= e($news['tempat'] ?? '-') ?></div></div>
                                <div class="info-row"><div class="info-label">Aktor</div><div class="info-value"><?= e($news['aktor'] ?? '-') ?></div></div>
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
                                <div class="info-row"><div class="info-label">Topik</div><div class="info-value"><?= e($news['topik'] ?? '-') ?></div></div>
                                <div class="info-row"><div class="info-label">Keyword</div><div class="info-value"><?= e($news['keyword'] ?? '-') ?></div></div>
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
                                <button type="submit" class="btn btn-outline btn-sm">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    Kirim Catatan
                                </button>
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
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-block">Setujui &amp; Lanjutkan</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-block" onclick="return confirm('Kembalikan ke Reporter untuk direvisi?')">Minta Revisi</button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <?php if ($user['role'] === 'C' && $news['status'] === 'published'): ?>
                    <div class="review-card" style="border-color:var(--red);background:var(--red-bg)">
                        <h3 style="color:var(--red)">Tarik Berita</h3>
                        <p>Turunkan berita ini dari publikasi jika ada kesalahan fatal.</p>
                        <form action="<?= BASE_URL ?>/review_action.php" method="POST">
                            <input type="hidden" name="news_id" value="<?= $id ?>">
                            <button type="submit" name="action" value="unpublish" class="btn btn-outline btn-block" onclick="return confirm('Yakin ingin menurunkan berita ini?')" style="color:var(--red);border-color:var(--red)">Unpublish</button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="relevan-section">
                        <div class="relevan-header">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Informasi Relevan
                        </div>

                        <!-- Subjek terkait -->
                        <div class="relevan-card">
                            <div class="relevan-card-title">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                                Subjek yang terkait
                            </div>
                            <?php if (!empty($news['topik'])): ?>
                                <div class="relevan-tags">
                                    <?php foreach(array_filter(array_map('trim', explode(',', $news['topik']))) as $t): ?>
                                        <span class="relevan-tag"><?= e($t) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="relevan-empty">Tidak ada subjek terkait</span>
                            <?php endif; ?>
                        </div>

                        <!-- Aktor yang sama -->
                        <div class="relevan-card">
                            <div class="relevan-card-title">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Aktor yang sama
                            </div>
                            <?php if (!empty($news['aktor'])): ?>
                                <div class="relevan-tags">
                                    <?php foreach(array_filter(array_map('trim', explode(',', $news['aktor']))) as $a): ?>
                                        <span class="relevan-tag"><?= e($a) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="relevan-empty">Tidak ada aktor terkait</span>
                            <?php endif; ?>
                        </div>

                        <!-- Tag yang sama -->
                        <div class="relevan-card">
                            <div class="relevan-card-title">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                Tag yang sama
                            </div>
                            <?php
                                $tags = array_filter(array_map('trim', explode(',', $news['tag'] ?? '')));
                            ?>
                            <?php if (!empty($tags)): ?>
                                <div class="relevan-tags">
                                    <?php foreach($tags as $t): ?>
                                        <span class="relevan-tag relevan-tag-gold"><?= e($t) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="relevan-empty">Tidak ada tag terkait</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>
