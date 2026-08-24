<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID Berita tidak valid.");
}

$stmt = $pdo->prepare("
    SELECT n.*, u.full_name as author_name, u.role as author_role, u.lanud as author_lanud
    FROM news n 
    JOIN users u ON n.created_by = u.id 
    WHERE n.id = ?
");
$stmt->execute([$id]);
$news = $stmt->fetch();

if (!$news) {
    die("Berita tidak ditemukan.");
}

$releaseDate = !empty($news['published_at']) ? strtotime($news['published_at']) : strtotime($news['created_at']);
$lanudLabel  = !empty($news['wilayah']) ? strtoupper($news['wilayah']) : 'MABES TNI AU';
$tempat      = !empty($news['tempat']) ? $news['tempat'] : 'Jakarta';
$nomorRilis  = "SP / " . date('m', $releaseDate) . " / " . date('Y', $releaseDate) . " / " . ($news['author_label'] ?? 'PEN ATS');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIARAN PERS: <?= e($news['title']) ?> TNI AU</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Times New Roman", Times, serif;
            background: #e2e8f0;
            margin: 0;
            padding: 30px 15px;
            color: #000;
        }
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px 60px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .kop-header {
            display: flex;
            align-items: center;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 24px;
            gap: 20px;
        }
        .kop-logo {
            width: 75px;
            height: auto;
        }
        .kop-text {
            flex: 1;
            text-align: center;
            line-height: 1.2;
        }
        .kop-text .instansi-1 { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
        .kop-text .instansi-2 { font-size: 18px; font-weight: bold; margin-top: 2px; }
        .kop-text .instansi-3 { font-size: 13px; font-style: italic; margin-top: 2px; }

        .release-meta-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.5;
        }
        .release-meta-table td {
            vertical-align: top;
        }

        .doc-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 20px 0 10px 0;
            letter-spacing: 0.5px;
        }
        .headline {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 24px;
            line-height: 1.4;
        }

        .lead-location {
            font-weight: bold;
            text-transform: uppercase;
        }
        .article-content {
            font-size: 14px;
            line-height: 1.7;
            text-align: justify;
            text-indent: 35px;
        }
        .article-content p {
            margin-bottom: 14px;
        }

        .featured-image-box {
            text-align: center;
            margin: 20px 0;
        }
        .featured-image-box img {
            max-width: 100%;
            max-height: 380px;
            object-fit: cover;
            border: 1px solid #ccc;
        }
        .featured-image-caption {
            font-size: 11px;
            font-style: italic;
            color: #444;
            margin-top: 4px;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
        }
        .signature-box {
            text-align: center;
            width: 250px;
            font-size: 13px;
            line-height: 1.3;
        }
        .signature-space {
            height: 70px;
        }

        .action-bar {
            max-width: 800px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            background: #1e3a8a;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-family: sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back {
            background: #475569;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
            .page-container {
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <a href="<?= BASE_URL ?>/news_view.php?id=<?= $news['id'] ?>" class="btn btn-back">
        &larr; Kembali ke Detail Berita
    </a>
    <button onclick="window.print()" class="btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>Cetak / Simpan PDF Siaran Pers
    </button>
</div>

<div class="page-container">
    <!-- KOP RESMI -->
    <div class="kop-header">
        <img src="<?= BASE_URL ?>/assets/img/logo-tniau-transparent.png" alt="Logo TNI AU" class="kop-logo">
        <div class="kop-text">
            <div class="instansi-1">TENTARA NASIONAL INDONESIA ANGKATAN UDARA</div>
            <div class="instansi-2">DINAS PENERANGAN - <?= $lanudLabel ?></div>
            <div class="instansi-3">Penerangan dan Hubungan Masyarakat TNI AU</div>
        </div>
    </div>

    <!-- META SIARAN PERS -->
    <table class="release-meta-table">
        <tr>
            <td style="width: 120px;"><strong>Nomor</strong></td>
            <td style="width: 15px;">:</td>
            <td><?= $nomorRilis ?></td>
            <td style="text-align: right;"><strong>Tempat, Tgl:</strong> <?= e($tempat) ?>, <?= date('d F Y', $releaseDate) ?></td>
        </tr>
        <tr>
            <td><strong>Klasifikasi</strong></td>
            <td>:</td>
            <td>BIASA / UNTUK DIPUBLIKASIKAN</td>
            <td style="text-align: right;"><strong>Media:</strong> <?= e($news['media'] ?? 'Wilayah') ?></td>
        </tr>
        <tr>
            <td><strong>Perihal</strong></td>
            <td>:</td>
            <td colspan="2"><strong>SIARAN PERS RESMI PENERANGAN TNI AU</strong></td>
        </tr>
    </table>

    <div class="doc-title">SIARAN PERS</div>
    <div class="headline"><?= e(strtoupper($news['title'])) ?></div>

    <?php if (!empty($news['image_path'])): ?>
        <div class="featured-image-box">
            <img src="<?= BASE_URL ?>/uploads/<?= e($news['image_path']) ?>" alt="Foto Dokumentasi">
            <div class="featured-image-caption">Dokumentasi Siaran Pers: <?= e($news['author_label'] ?? 'Penerangan TNI AU') ?></div>
        </div>
    <?php endif; ?>

    <!-- CONTENT -->
    <div class="article-content">
        <p>
            <span class="lead-location"><?= e($tempat) ?> (PEN AU)</span> <?= strip_tags($news['content'], '<p><br><strong><b><em><i><u><ul><ol><li><blockquote>') ?>
        </p>
    </div>

    <!-- PENGESAHAN / SIGNATURE -->
    <div class="signature-section">
        <div class="signature-box">
            <div>An. KEPALA DINAS PENERANGAN</div>
            <div style="font-weight: bold;"><?= $lanudLabel ?></div>
            <div class="signature-space"></div>
            <div style="font-weight: bold; text-decoration: underline;"><?= e($news['author_name']) ?></div>
            <div>Penanggung Jawab Siaran Pers</div>
        </div>
    </div>
</div>

</body>
</html>
