<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Permintaan tidak valid.']);
    exit;
}

$newsId = (int)($_POST['news_id'] ?? 0);
$label  = trim($_POST['label'] ?? '');

$allowed = ['Berita Utama', 'Mendesak', 'Terverifikasi', 'Untuk Ditelaah', 'Perlu Revisi Fakta', 'Siap Publikasi', 'Arsip', ''];
if (!in_array($label, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Label tidak dikenali.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM news WHERE id = ?");
$stmt->execute([$newsId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Berita tidak ditemukan.']);
    exit;
}

$upd = $pdo->prepare("UPDATE news SET label = ? WHERE id = ?");
$upd->execute([$label ?: null, $newsId]);

echo json_encode(['success' => true, 'label' => $label]);
