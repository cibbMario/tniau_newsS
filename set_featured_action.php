<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Permintaan tidak valid.']);
    exit;
}

$newsId = (int)($_POST['news_id'] ?? 0);

$stmt = $pdo->prepare("SELECT is_featured FROM news WHERE id = ?");
$stmt->execute([$newsId]);
$news = $stmt->fetch();

if (!$news) {
    echo json_encode(['success' => false, 'error' => 'Berita tidak ditemukan.']);
    exit;
}

$newVal = $news['is_featured'] ? 0 : 1;
$upd = $pdo->prepare("UPDATE news SET is_featured = ? WHERE id = ?");
$upd->execute([$newVal, $newsId]);

echo json_encode(['success' => true, 'is_featured' => (bool)$newVal]);
