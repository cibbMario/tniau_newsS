<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$newsId  = (int)($_POST['news_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$isCorrection = !empty($_POST['is_correction']);

$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$newsId]);
$news = $stmt->fetch();

if (!$news || $message === '') {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$type = $isCorrection ? 'correction' : 'comment';

$stmt = $pdo->prepare("INSERT INTO comments (news_id, user_id, type, message) VALUES (?, ?, ?, ?)");
$stmt->execute([$newsId, $user['id'], $type, $message]);

// Notifikasi ke pemilik berita jika yang komentar bukan pemiliknya sendiri
if ($news['created_by'] != $user['id']) {
    $label = $isCorrection ? 'koreksi' : 'komentar';
    sendNotification($newsId, $news['created_by'],
        "{$user['full_name']} memberi $label pada berita \"{$news['title']}\".");
}

header("Location: " . BASE_URL . "/news_view.php?id=$newsId");
exit;
