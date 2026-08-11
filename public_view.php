<?php
require_once __DIR__ . '/config/config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: " . BASE_URL . "/");
    exit;
}

// Fetch only published news
$stmt = $pdo->prepare("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.id = ? AND n.status = 'published'");
$stmt->execute([$id]);
$news = $stmt->fetch();

if (!$news) {
    // Elegant public error state
    $title = "Berita Tidak Ditemukan";
} else {
    $title = $news['title'];
    
    // Fetch multi-image gallery
    $images = $pdo->prepare("SELECT * FROM news_images WHERE news_id = ?");
    $images->execute([$id]);
    $gallery = $images->fetchAll();
}

function getSentimentBadgeClass($sentiment) {
    switch ($sentiment) {
        case 'Positif': return 'badge-green';
        case 'Negatif': return 'badge-red';
        case 'Netral':
        default: return 'badge-blue';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — Portal Berita Resmi TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .public-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-sec);
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 24px;
            transition: color var(--transition);
        }
        .back-link:hover {
            color: var(--navy);
        }

        .article-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--border-light);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
            margin-bottom: 40px;
        }

        .article-header {
            margin-bottom: 24px;
        }
        .article-category-row {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .article-tag {
            background: var(--gold-light);
            color: var(--gold-dark);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .article-sentiment {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        .article-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--navy);
            line-height: 1.3;
            margin-bottom: 16px;
        }
        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 11.5px;
            color: var(--text-sec);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding-bottom: 16px;
        }

        .article-main-img {
            width: 100%;
            max-height: 480px;
            object-fit: cover;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
        }

        .article-body {
            font-size: 14px;
            line-height: 1.8;
            color: var(--text);
            margin-bottom: 40px;
        }
        .article-body p {
            margin-bottom: 20px;
        }

        /* Gallery Grid */
        .gallery-section {
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            padding-top: 30px;
            margin-bottom: 30px;
        }
        .gallery-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 16px;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        .gallery-item {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            height: 140px;
            border: 1px solid var(--border-light);
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition);
        }
        .gallery-item:hover img {
            transform: scale(1.05);
        }

        /* Tag Chips */
        .metadata-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: rgba(30, 111, 191, 0.04);
            border: 1px solid rgba(30, 111, 191, 0.08);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .meta-row {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 12px;
        }
        .meta-row-label {
            width: 100px;
            font-weight: 600;
            color: var(--text-sec);
        }
        .meta-row-value {
            color: var(--text);
        }
        .chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .chip {
            background: #fff;
            border: 1px solid rgba(30, 111, 191, 0.12);
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            color: var(--text-sec);
        }

        /* Error state styling */
        .error-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(217, 48, 37, 0.15);
            border-radius: 24px;
            padding: 48px;
            text-align: center;
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
            margin: 40px auto;
            max-width: 600px;
        }

        /* Unified public footer styling is in style.css */

        @media (max-width: 768px) {
            .article-card {
                padding: 20px;
            }
            .article-title {
                font-size: 22px;
            }
            .article-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column; background: linear-gradient(145deg, #deeeff 0%, #edf4ff 40%, #e8f0fc 100%);">

    <!-- NAVBAR -->
    <?php include __DIR__ . '/includes/public_navbar.php'; ?>

    <!-- CONTENT -->
    <main class="public-container">

        <a href="<?= BASE_URL ?>/" class="back-link">
            <span>←</span> Kembali ke Beranda
        </a>

        <?php if (!$news): ?>
            <div class="error-card">
                <div style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h2 style="font-size: 20px; font-weight: 700; color: var(--red); margin-bottom: 12px;">Halaman Tidak Ditemukan</h2>
                <p style="font-size: 13.5px; color: var(--text-sec); margin-bottom: 24px; line-height: 1.6;">
                    Maaf, berita yang Anda cari tidak ditemukan, belum dipublikasikan, atau telah ditarik dari publikasi oleh administrator.
                </p>
                <a href="<?= BASE_URL ?>/" class="btn btn-primary" style="padding: 10px 24px;">
                    Kembali ke Beranda
                </a>
            </div>
        <?php else: ?>
            <article class="article-card">
                
                <header class="article-header">
                    <div class="article-category-row">
                        <span class="article-tag">📰 <?= e($news['media']) ?></span>
                        <span class="badge <?= getSentimentBadgeClass($news['sentiment']) ?> article-sentiment"><?= e($news['sentiment']) ?></span>
                    </div>
                    <h1 class="article-title"><?= e($news['title']) ?></h1>
                    <div class="article-meta">
                        <span>📍 <strong>Wilayah:</strong> <?= e($news['wilayah'] ?: '-') ?></span>
                        <span>📅 <strong>Terbit:</strong> <?= formatTanggal($news['published_at'] ?: $news['created_at']) ?></span>
                        <span>✍️ <strong>Penulis:</strong> <?= e($news['author_label'] ?? $news['author_name']) ?></span>
                    </div>
                </header>

                <?php if ($news['image_path']): ?>
                    <img src="<?= UPLOAD_URL . e($news['image_path']) ?>" class="article-main-img" alt="Gambar Utama Berita">
                <?php endif; ?>

                <div class="article-body">
                    <?= $news['content'] // HTML content permitted from editor ?>
                </div>

                <!-- Related metadata / Classification chips -->
                <div class="metadata-section">
                    <div class="meta-row">
                        <span class="meta-row-label">Klasifikasi:</span>
                        <span class="meta-row-value"><strong><?= e($news['classification']) ?></strong></span>
                    </div>
                    <?php if (!empty($news['tempat'])): ?>
                        <div class="meta-row">
                            <span class="meta-row-label">Tempat:</span>
                            <span class="meta-row-value"><?= e($news['tempat']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($news['aktor'])): ?>
                        <div class="meta-row">
                            <span class="meta-row-label">Aktor Terkait:</span>
                            <div class="meta-row-value chip-list">
                                <?php foreach(array_filter(array_map('trim', explode(',', $news['aktor']))) as $actor): ?>
                                    <span class="chip"><?= e($actor) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($news['tag'])): ?>
                        <div class="meta-row">
                            <span class="meta-row-label">Tag / Kata Kunci:</span>
                            <div class="meta-row-value chip-list">
                                <?php foreach(array_filter(array_map('trim', explode(',', $news['tag']))) as $tagItem): ?>
                                    <span class="chip" style="border-color: var(--gold-dark); background: var(--gold-light);"><?= e($tagItem) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gallery Section if present -->
                <?php if (!empty($gallery)): ?>
                    <div class="gallery-section">
                        <h2 class="gallery-title">Galeri Foto Terkait</h2>
                        <div class="gallery-grid">
                            <?php foreach ($gallery as $img): ?>
                                <div class="gallery-item">
                                    <a href="<?= UPLOAD_URL . e($img['image_path']) ?>" target="_blank" title="Buka gambar penuh">
                                        <img src="<?= UPLOAD_URL . e($img['image_path']) ?>" alt="Foto Galeri Pendukung">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </article>
        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <?php include __DIR__ . '/includes/public_footer.php'; ?>

</body>
</html>
