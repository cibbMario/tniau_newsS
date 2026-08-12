<?php
require_once __DIR__ . '/config/config.php';
requireRole(['B', 'C', 'D', 'E']);
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
$redirectUrl    = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (BASE_URL . "/news_view.php?id=$newsId");

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
        "INSERT INTO comments (news_id, user_id, type, message, created_at)
         VALUES (?, ?, 'correction', ?, NOW())"
    );
    $stmt->execute([$newsId, $userId, $note]);
}

if ($user['role'] === 'B' && $news['status'] === 'pending_b') {
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'pending_c', $user['id'], 'Disetujui Editor, diteruskan ke Penyetuju');
        $stmtC = $pdo->query("SELECT id FROM users WHERE role = 'C'");
        foreach ($stmtC->fetchAll() as $c) {
            sendNotification($newsId, $c['id'], "Berita \"{$news['title']}\" telah disetujui Editor dan menunggu persetujuan Anda.");
        }
    } else {
        $note = $rejectionNote ?: 'Editor meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_b', $user['id'], 'Ditolak Editor: ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan Editor: $note");
    }
}
elseif ($user['role'] === 'C' && $news['status'] === 'pending_c') {
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'published', $user['id'], 'Disetujui Penyetuju, berita dipublikasikan');
        sendNotification($newsId, $news['created_by'], "Selamat! Berita \"{$news['title']}\" telah disetujui oleh Penyetuju dan dipublikasikan.");
    } elseif ($action === 'reject') {
        $note = $rejectionNote ?: 'Penyetuju meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_c', $user['id'], 'Ditolak Penyetuju: ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan Penyetuju: $note");
    }
}
elseif ($user['role'] === 'D' && in_array($news['status'], ['pending_d', 'pending_c'], true)) {
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'published', $user['id'], 'Disetujui oleh Peninjau Kejelasan, berita dipublikasikan');
        sendNotification($newsId, $news['created_by'], "Selamat! Berita \"{$news['title']}\" telah disetujui oleh Peninjau Kejelasan dan dipublikasikan.");
    } elseif ($action === 'reject') {
        $note = $rejectionNote ?: 'Peninjau Kejelasan meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_d', $user['id'], 'Ditolak Peninjau Kejelasan: ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan Peninjau Kejelasan: $note");
    }
}
elseif ($user['role'] === 'E' && in_array($news['status'], ['pending_b', 'pending_c', 'pending_d'], true)) {
    if ($action === 'approve') {
        if ($news['status'] === 'pending_b') {
            updateNewsStatus($newsId, 'pending_c', $user['id'], 'Disetujui oleh Administrator sebagai pengganti Editor');
            $stmtC = $pdo->query("SELECT id FROM users WHERE role = 'C'");
            foreach ($stmtC->fetchAll() as $c) {
                sendNotification($newsId, $c['id'], "Berita \"{$news['title']}\" telah disetujui Administrator dan menunggu persetujuan Anda.");
            }
        } elseif ($news['status'] === 'pending_c') {
            updateNewsStatus($newsId, 'published', $user['id'], 'Disetujui oleh Administrator (pengganti Penyetuju), berita dipublikasikan');
            sendNotification($newsId, $news['created_by'], "Selamat! Berita \"{$news['title']}\" telah disetujui oleh Administrator dan dipublikasikan.");
        } else {
            updateNewsStatus($newsId, 'published', $user['id'], 'Disetujui oleh Administrator, berita dipublikasikan');
            sendNotification($newsId, $news['created_by'], "Selamat! Berita \"{$news['title']}\" telah disetujui oleh Administrator dan dipublikasikan.");
        }
    } elseif ($action === 'reject') {
        $note = $rejectionNote ?: 'Administrator meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        $revisionStatus = $news['status'] === 'pending_b' ? 'revision_b' : ($news['status'] === 'pending_c' ? 'revision_c' : 'revision_d');
        updateNewsStatus($newsId, $revisionStatus, $user['id'], 'Ditolak Administrator: ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan Administrator: $note");
    }
}
elseif (in_array($user['role'], ['D','E']) && $action === 'unpublish' && $news['status'] === 'published') {
    updateNewsStatus($newsId, 'draft', $user['id'], 'Diturunkan dari publikasi oleh Administrator/Peninjau Kejelasan');
    sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" telah diturunkan dari publikasi.");
}

header("Location: " . $redirectUrl);
exit;
