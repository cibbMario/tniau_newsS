<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$user = currentUser();
$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    header("Location: " . BASE_URL . "/news_list.php");
    exit;
}

// Cari berita
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$id]);
$news = $stmt->fetch();

if (!$news) {
    die("Berita tidak ditemukan.");
}

// Proses hapus file utama
if (!empty($news['image_path'])) {
    $filePath = UPLOAD_DIR . $news['image_path'];
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
}

// Proses hapus gallery images
$gst = $pdo->prepare("SELECT image_path FROM news_images WHERE news_id = ?");
$gst->execute([$id]);
$gallery = $gst->fetchAll();
foreach ($gallery as $g) {
    $gPath = UPLOAD_DIR . $g['image_path'];
    if (file_exists($gPath)) {
        @unlink($gPath);
    }
}

// Hapus history, comment, gallery, baru berita
$pdo->prepare("DELETE FROM news_history WHERE news_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM comments WHERE news_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM news_images WHERE news_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM notifications WHERE news_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);

header("Location: " . BASE_URL . "/news_list.php?deleted=1");
exit;
