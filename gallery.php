<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();
$current = 'gallery';

// 1. Fetch main images from news table
$stmt = $pdo->query("SELECT id, title, image_path, created_at FROM news WHERE image_path IS NOT NULL AND status != 'draft' ORDER BY created_at DESC");
$mainImages = $stmt->fetchAll();

// 2. Fetch gallery images from news_images table
$stmt2 = $pdo->query("SELECT ni.image_path, n.id as news_id, n.title 
                      FROM news_images ni 
                      JOIN news n ON n.id = ni.news_id 
                      WHERE n.status != 'draft' 
                      ORDER BY ni.id DESC");
$galleryImages = $stmt2->fetchAll();

// Merge and sort by a virtual criteria (all together)
$allMedia = [];
foreach ($mainImages as $img) {
    $allMedia[] = [
        'news_id' => $img['id'],
        'title' => $img['title'],
        'image' => $img['image_path'],
        'type' => 'Gambar Utama'
    ];
}
foreach ($galleryImages as $img) {
    $allMedia[] = [
        'news_id' => $img['news_id'],
        'title' => $img['title'],
        'image' => $img['image_path'],
        'type' => 'Galeri Pendukung'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Galeri Foto - Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .gallery-layout-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .gallery-card {
            background: rgba(255, 255, 255, 0.80);
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: transform 0.2s;
        }
        .gallery-card:hover {
            transform: translateY(-4px);
        }
        .gallery-card-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-bottom: 1px solid var(--border);
        }
        .gallery-card-body {
            padding: 12px;
        }
        .gallery-card-title {
            font-size: 11.5px;
            font-weight: 600;
            color: #2c3e50;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 32px;
            margin-bottom: 6px;
        }
        .gallery-card-type {
            font-size: 10px;
            font-weight: 700;
            color: var(--blue);
            text-transform: uppercase;
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="top-navbar">
            <div class="top-navbar-left">
                <button class="hamburger-btn">Menu</button>
                <span style="font-weight:600; font-size:14px; color:var(--text);">Galeri Berita</span>
            </div>
            <div class="top-navbar-right">
                <button class="top-action-btn" style="font-weight:600;">User <?= e($user['full_name']) ?></button>
            </div>
        </div>

        <div class="page-container">
            <div style="margin-bottom: 20px;">
                <h2 style="font-size:18px; font-weight:600;">Media & Dokumentasi Berita</h2>
                <p style="color:var(--text-muted); font-size:12px;">Daftar seluruh gambar utama dan file pendukung dari berita yang terdaftar</p>
            </div>

            <?php if (empty($allMedia)): ?>
                <div class="card empty-state">Belum ada gambar yang diunggah di portal.</div>
            <?php else: ?>
                <div class="gallery-layout-grid">
                    <?php foreach ($allMedia as $media): ?>
                        <a href="<?= BASE_URL ?>/news_view.php?id=<?= $media['news_id'] ?>" class="gallery-card">
                            <img src="<?= UPLOAD_URL . e($media['image']) ?>" class="gallery-card-img" alt="Gallery image">
                            <div class="gallery-card-body">
                                <div class="gallery-card-title"><?= e($media['title']) ?></div>
                                <div class="gallery-card-type"><?= e($media['type']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
