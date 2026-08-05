<?php
require_once __DIR__ . '/config/config.php';
requireRole(['B', 'C', 'D']);
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
        updateNewsStatus($newsId, 'pending_c', $user['id'], 'Disetujui User B, diteruskan ke User C');
        $stmtC = $pdo->query("SELECT id FROM users WHERE role = 'C'");
        foreach ($stmtC->fetchAll() as $c) {
            sendNotification($newsId, $c['id'], "Berita \"{$news['title']}\" telah disetujui User B dan menunggu persetujuan Anda.");
        }
    } else {
        $note = $rejectionNote ?: 'User B meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_b', $user['id'], 'Ditolak User B: ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan User B: $note");
    }
}
elseif ($user['role'] === 'C' && $news['status'] === 'pending_c') {
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'pending_d', $user['id'], 'Disetujui User C, diteruskan ke User D');
        $stmtD = $pdo->query("SELECT id FROM users WHERE role = 'D'");
        foreach ($stmtD->fetchAll() as $d) {
            sendNotification($newsId, $d['id'], "Berita \"{$news['title']}\" telah disetujui User C dan menunggu persetujuan Anda.");
        }
    } elseif ($action === 'reject') {
        $note = $rejectionNote ?: 'User C meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_c', $user['id'], 'Ditolak User C: ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan User C: $note");
    }
}
elseif ($user['role'] === 'D' && in_array($news['status'], ['pending_d', 'pending_c'], true)) {
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'published', $user['id'], 'Disetujui oleh User D, berita dipublikasikan');
        sendNotification($newsId, $news['created_by'], "Selamat! Berita \"{$news['title']}\" telah disetujui oleh User D dan dipublikasikan.");
    } elseif ($action === 'reject') {
        $note = $rejectionNote ?: 'User D meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_d', $user['id'], 'Ditolak User D: ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan User D: $note");
    }
}
elseif ($user['role'] === 'D' && $action === 'unpublish' && $news['status'] === 'published') {
    updateNewsStatus($newsId, 'draft', $user['id'], 'Diturunkan dari publikasi oleh User D');
    sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" telah diturunkan dari publikasi.");
}

header("Location: " . $redirectUrl);
exit;
