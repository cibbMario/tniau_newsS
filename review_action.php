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
        updateNewsStatus($newsId, 'pending_c', $user['id'], 'Disetujui Editor (B), diteruskan ke Approver (C/D)');
        $stmtC = $pdo->query("SELECT id FROM users WHERE role IN ('C', 'D')");
        foreach ($stmtC->fetchAll() as $c) {
            sendNotification($newsId, $c['id'], "Berita \"{$news['title']}\" telah lolos Editor dan menunggu persetujuan Anda.");
        }
    } else {
        $note = $rejectionNote ?: 'Editor meminta revisi pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_b', $user['id'], 'Ditolak Editor (B): ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi. Catatan Editor: $note");
    }
}
elseif (in_array($user['role'], ['C', 'D'])) {
    $roleTitle = ($user['role'] === 'D') ? 'Approver Kejelasan (User D)' : 'Petinggi (User C)';
    if ($action === 'approve') {
        updateNewsStatus($newsId, 'published', $user['id'], 'Disetujui kejelasan berita oleh ' . $roleTitle . ', berita dipublikasikan');
        sendNotification($newsId, $news['created_by'], "Selamat! Kejelasan berita \"{$news['title']}\" telah disetujui oleh {$user['full_name']} dan dipublikasikan.");
    } elseif ($action === 'reject') {
        $note = $rejectionNote ?: 'Minta revisi kejelasan pada berita ini.';
        saveRejectionComment($pdo, $newsId, $user['id'], $note);
        updateNewsStatus($newsId, 'revision_c', $user['id'], 'Ditolak (' . $roleTitle . '): ' . $note);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" perlu direvisi kejelasannya. Catatan: $note");
    } elseif ($action === 'unpublish' && $news['status'] === 'published') {
        updateNewsStatus($newsId, 'draft', $user['id'], 'Diturunkan dari publikasi oleh ' . $roleTitle);
        sendNotification($newsId, $news['created_by'], "Berita \"{$news['title']}\" telah diturunkan dari publikasi.");
    }
}

header("Location: " . $redirectUrl);
exit;
