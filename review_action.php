<?php
require_once __DIR__ . '/config/config.php';
requireRole(['B', 'C']);
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$newsId         = (int)($_POST['news_id'] ?? 0);
$action         = $_POST['action'] ?? '';
$rejectionNote  = trim($_POST['rejection_note'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$newsId]);
$news = $stmt->fetch();

if (!$news || !in_array($action, ['approve', 'reject', 'unpublish'], true)) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

/* Helper: simpan catatan revisi ke tabel comments */
function saveRejectionComment($pdo, $newsId, $userId, $note) {
    if (empty(trim($note))) return;
    $stmt = $pdo->prepare(
        "INSERT INTO comments (news_id, user_id, comment, is_correction, created_at)
         VALUES (?, ?, ?, 1, NOW())"
    );
    $stmt->execute([$newsId, $userId, $note]);
}

/* =========================================================
   ALUR:
   - User B review berita status 'pending_b'
       oke      -> 'pending_c'  (diteruskan ke User C)
       tidak oke-> 'revision_b' (balik ke User A, notif A, simpan catatan)
   - User C review berita status 'pending_c'
       oke      -> 'published'  (tayang)
       tidak oke-> 'revision_c' (balik ke User A, notif A, simpan catatan)
   - User C unpublish berita status 'published'
       -> 'draft' (turunkan dari publik)
   ========================================================= */

if ($user['role'] === 'B' && $news['status'] === 'pending_b') {
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'pending_c', $user['id'], 'Disetujui Editor (B), diteruskan ke Petinggi (C)');
        $stmtC = $pdo->query("SELECT id FROM users WHERE role = 'C'");
        foreach ($stmtC->fetchAll() as $c) {
            sendNotification($newsId, $c['id'], "Berita \"{$news['title']}\" telah lolos Editor dan menunggu persetujuan Anda.");
        }
    } else {
        // Simpan catatan revisi sebagai comment (is_correction = 1)
        $note = $rejectionNote ?: 'Editor meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_b', $user['id'], 'Ditolak Editor (B): ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan Editor: $note");
    }
}
elseif ($user['role'] === 'C' && $news['status'] === 'pending_c') {
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'published', $user['id'], 'Disetujui Petinggi (C), berita dipublikasikan');
        sendNotification($newsId, $news['created_by'], "Selamat! Berita \"{$news['title']}\" telah disetujui dan dipublikasikan.");
    } else {
        $note = $rejectionNote ?: 'Petinggi meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_c', $user['id'], 'Ditolak Petinggi (C): ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan Petinggi: $note");
    }
}
elseif ($user['role'] === 'C' && $action === 'unpublish' && $news['status'] === 'published') {
    updateNewsStatus($newsId, 'draft', $user['id'], 'Diturunkan dari publik oleh Petinggi (C)');
    sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" telah diturunkan dari publikasi.");
}

header("Location: " . BASE_URL . "/news_view.php?id=$newsId");
exit;


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$newsId   = (int)($_POST['news_id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$newsId]);
$news = $stmt->fetch();

if (!$news || !in_array($action, ['approve', 'reject'], true)) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

/* =========================================================
   ALUR:
   - User B review berita status 'pending_b'
       oke      -> 'pending_c'  (diteruskan ke User C)
       tidak oke-> 'revision_b' (balik ke User A, notif A)
   - User C review berita status 'pending_c'
       oke      -> 'published'  (tayang)
       tidak oke-> 'revision_c' (balik ke User A, notif A)
   ========================================================= */

if ($user['role'] === 'B' && $news['status'] === 'pending_b') {
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'pending_c', $user['id'], 'Disetujui Editor (B), diteruskan ke Petinggi (C)');
        $stmtC = $pdo->query("SELECT id FROM users WHERE role = 'C'");
        foreach ($stmtC->fetchAll() as $c) {
            sendNotification($newsId, $c['id'], "Berita \"{$news['title']}\" telah lolos Editor dan menunggu persetujuan Anda.");
        }
    } else {
        updateNewsStatus($newsId, 'revision_b', $user['id'], 'Ditolak Editor (B), perlu revisi');
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi (catatan dari Editor).");
    }
}
elseif ($user['role'] === 'C' && $news['status'] === 'pending_c') {
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'published', $user['id'], 'Disetujui Petinggi (C), berita dipublikasikan');
        sendNotification($newsId, $news['created_by'], "Selamat! Berita \"{$news['title']}\" telah disetujui dan dipublikasikan.");
    } else {
        updateNewsStatus($newsId, 'revision_c', $user['id'], 'Ditolak Petinggi (C), perlu revisi');
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi (catatan dari Petinggi).");
    }
}

header("Location: " . BASE_URL . "/news_view.php?id=$newsId");
exit;
