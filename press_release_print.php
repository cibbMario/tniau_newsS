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

$releaseTimestamp = !empty($news['published_at']) ? strtotime($news['published_at']) : strtotime($news['created_at']);
$releaseDateStr   = formatTanggalIndoFull($releaseTimestamp);

$lanudLabel  = !empty($news['wilayah']) ? strtoupper($news['wilayah']) : 'MABES TNI AU';
$tempat      = !empty($news['tempat']) ? $news['tempat'] : 'Jakarta';
$nomorRilis  = "SP / " . date('m', $releaseTimestamp) . " / " . date('Y', $releaseTimestamp) . " / " . ($news['author_label'] ?? 'PEN ATS');

// Persiapan konten paragraf pertama dengan lead lokasi
$rawContent  = strip_tags($news['content'] ?? '', '<p><br><strong><b><em><i><u><ul><ol><li><blockquote>');
$leadSpan    = '<span class="lead-location">' . e($tempat) . ' (PEN AU)</span> — ';

if (preg_match('/^\s*<p[^>]*>/i', $rawContent)) {
    $contentHtml = preg_replace('/^\s*<p[^>]*>/i', '<p>' . $leadSpan, $rawContent, 1);
} else {
    $contentHtml = '<p>' . $leadSpan . $rawContent . '</p>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIARAN PERS: <?= e($news['title']) ?> - TNI AU</title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            background: #0f172a;
            margin: 0;
            padding: 0;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* TOOLBAR ATAS (HANYA SCREEN) */
        .action-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .action-bar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .action-bar-title {
            color: #f8fafc;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-bar-title span {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .action-bar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .btn-print {
            background: #0284c7;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.4);
        }

        .btn-print:hover {
            background: #0369a1;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.6);
        }

        .print-tip {
            color: #94a3b8;
            font-size: 12px;
            margin-right: 6px;
            display: none;
        }

        @media (min-width: 768px) {
            .print-tip { display: inline-block; }
        }

        /* CONTAINER KERTAS A4 (SCREEN PREVIEW) */
        .page-wrapper {
            padding: 30px 15px 50px 15px;
            display: flex;
            justify-content: center;
        }

        .print-paper {
            width: 100%;
            max-width: 800px;
            background: #ffffff;
            padding: 50px 65px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.35), 0 0 1px rgba(0,0,0,0.2);
            border-radius: 4px;
            position: relative;
        }

        /* KOP SURAT RESMI TNI AU */
        .kop-header {
            display: flex;
            align-items: center;
            border-bottom: 3.5px double #000;
            padding-bottom: 14px;
            margin-bottom: 24px;
            gap: 20px;
        }

        .kop-logo {
            width: 80px;
            height: auto;
            flex-shrink: 0;
            display: block;
        }

        .kop-text {
            flex: 1;
            text-align: center;
            line-height: 1.25;
            color: #000;
        }

        .kop-text .instansi-1 {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .kop-text .instansi-2 {
            font-size: 17px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .kop-text .instansi-3 {
            font-size: 12px;
            font-style: italic;
            margin-top: 3px;
            color: #222;
        }

        /* META TABLE SIARAN PERS */
        .release-meta-table {
            width: 100%;
            margin-bottom: 24px;
            font-size: 13px;
            line-height: 1.6;
            border-collapse: collapse;
        }

        .release-meta-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .meta-label {
            font-weight: bold;
            width: 110px;
        }

        .meta-colon {
            width: 15px;
            text-align: center;
        }

        /* JUDUL & HEADLINE DOKUMEN */
        .doc-title {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 24px 0 10px 0;
            letter-spacing: 1.5px;
        }

        .headline {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 26px;
            line-height: 1.45;
            text-transform: uppercase;
            padding: 0 10px;
        }

        /* FOTO DOKUMENTASI */
        .featured-image-box {
            text-align: center;
            margin: 22px 0;
            page-break-inside: avoid;
        }

        .featured-image-box img {
            max-width: 100%;
            max-height: 380px;
            object-fit: cover;
            border: 1px solid #d1d5db;
            border-radius: 2px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .featured-image-caption {
            font-size: 11px;
            font-style: italic;
            color: #444;
            margin-top: 6px;
        }

        /* KONTEN ARTIKEL */
        .article-content {
            font-size: 13.5px;
            line-height: 1.7;
            text-align: justify;
            text-justify: inter-word;
            color: #000;
        }

        .article-content p {
            margin-bottom: 16px;
            text-indent: 36px;
        }

        .lead-location {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* BLOK TANDA TANGAN / PENGESAHAN */
        .signature-section {
            margin-top: 45px;
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            width: 280px;
            font-size: 13px;
            line-height: 1.4;
        }

        .signature-space {
            height: 75px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            font-size: 14px;
        }

        /* SETTING KHUSUS CETAK & SIMPAN PDF (@media print) */
        @media print {
            @page {
                size: A4 portrait;
                margin: 20mm 20mm 20mm 20mm;
            }

            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .action-bar {
                display: none !important;
            }

            .page-wrapper {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            .print-paper {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }

            .featured-image-box img {
                box-shadow: none !important;
                max-height: 350px !important;
            }
        }
    </style>
</head>
<body>

<!-- TOOLBAR AKSI (HANYA LAYAR) -->
<div class="action-bar">
    <div class="action-bar-left">
        <a href="<?= BASE_URL ?>/news_view.php?id=<?= $news['id'] ?>" class="btn btn-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke Detail Berita
        </a>
        <div class="action-bar-title">
            Format Siaran Pers <span>A4 REGULER</span>
        </div>
    </div>
    <div class="action-bar-right">
        <span class="print-tip">Tips: Pilih "Save as PDF" untuk menyimpan file PDF</span>
        <button onclick="window.print()" class="btn btn-print">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak / Simpan PDF Siaran Pers
        </button>
    </div>
</div>

<!-- CONTAINER DOKUMEN SIARAN PERS -->
<div class="page-wrapper">
    <div class="print-paper">
        
        <!-- KOP RESMI DINAS PENERANGAN TNI AU -->
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
                <td class="meta-label">Nomor</td>
                <td class="meta-colon">:</td>
                <td><?= e($nomorRilis) ?></td>
                <td style="text-align: right;"><strong>Tempat, Tgl:</strong> <?= e($tempat) ?>, <?= $releaseDateStr ?></td>
            </tr>
            <tr>
                <td class="meta-label">Klasifikasi</td>
                <td class="meta-colon">:</td>
                <td>BIASA / UNTUK DIPUBLIKASIKAN</td>
                <td style="text-align: right;"><strong>Media:</strong> <?= e($news['media'] ?? 'Wilayah') ?></td>
            </tr>
            <tr>
                <td class="meta-label">Perihal</td>
                <td class="meta-colon">:</td>
                <td colspan="2"><strong>SIARAN PERS RESMI PENERANGAN TNI AU</strong></td>
            </tr>
        </table>

        <!-- JUDUL & HEADLINE DOKUMEN -->
        <div class="doc-title">SIARAN PERS</div>
        <div class="headline"><?= e(strtoupper($news['title'])) ?></div>

        <!-- GAMBAR DOKUMENTASI UTAMA -->
        <?php if (!empty($news['image_path'])): ?>
            <div class="featured-image-box">
                <img src="<?= BASE_URL ?>/uploads/<?= e($news['image_path']) ?>" alt="Foto Dokumentasi">
                <div class="featured-image-caption">Dokumentasi Siaran Pers: <?= e($news['author_label'] ?? 'Penerangan TNI AU') ?></div>
            </div>
        <?php endif; ?>

        <!-- KONTEN BERITA -->
        <div class="article-content">
            <?= $contentHtml ?>
        </div>

        <!-- PENGESAHAN / TANDA TANGAN -->
        <div class="signature-section">
            <div class="signature-box">
                <div>An. KEPALA DINAS PENERANGAN</div>
                <div style="font-weight: bold; text-transform: uppercase;"><?= $lanudLabel ?></div>
                <div class="signature-space"></div>
                <div class="signature-name"><?= e($news['author_name']) ?></div>
                <div>Penanggung Jawab Siaran Pers</div>
            </div>
        </div>

    </div>
</div>

</body>
</html>
