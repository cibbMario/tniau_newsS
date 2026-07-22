<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'dashboard';
$user = currentUser();

// Determine requested view
$view = $_GET['view'] ?? $_GET['media'] ?? 'semua';
$validViews = ['semua', 'wilayah', 'online', 'sosial', 'statistics', 'report', 'gallery', 'harian', 'negatif', 'inspiratif', 'konten', 'sentimen'];
if (!in_array($view, $validViews)) {
    $view = 'semua';
}

// 1. Overall Stats
$stats = [
    'total'      => (int)$pdo->query("SELECT COUNT(*) FROM news")->fetchColumn(),
    'published'  => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetchColumn(),
    'pending'    => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status LIKE 'pending%'")->fetchColumn(),
    'users'      => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'wilayah'    => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE media='Wilayah'")->fetchColumn(),
    'online'     => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE media='Media Online'")->fetchColumn(),
    'sosial'     => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE media='Media Sosial'")->fetchColumn(),
];

// 2. Sentiment Counts
$stmt = $pdo->query("SELECT sentiment, COUNT(*) as c FROM news GROUP BY sentiment");
$sentData = $stmt->fetchAll();
$sentStats = ['Positif' => 0, 'Negatif' => 0, 'Netral' => 0];
$totalSent = 0;
foreach ($sentData as $row) {
    if (isset($sentStats[$row['sentiment']])) {
        $sentStats[$row['sentiment']] = (int)$row['c'];
        $totalSent += (int)$row['c'];
    }
}
$pctP = $totalSent ? round(($sentStats['Positif'] / $totalSent) * 100) : 0;
$pctNe = $totalSent ? round(($sentStats['Netral'] / $totalSent) * 100) : 0;
$pctN = $totalSent ? round(($sentStats['Negatif'] / $totalSent) * 100) : 0;

// 3. Recent News
$recent = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC LIMIT 10")->fetchAll();

// 4. Regional News
$regionalNews = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.media='Wilayah' ORDER BY n.created_at DESC LIMIT 10")->fetchAll();

// 5. Online Media News
$onlineNews = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.media='Media Online' ORDER BY n.created_at DESC LIMIT 10")->fetchAll();

// 6. Social Media News
$socialNews = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.media='Media Sosial' ORDER BY n.created_at DESC LIMIT 10")->fetchAll();

// 7. Contributors Report Data
$contributorsData = $pdo->query("SELECT u.full_name, u.role, COUNT(n.id) as total_news, 
    SUM(CASE WHEN n.status='published' THEN 1 ELSE 0 END) as published_count,
    SUM(CASE WHEN n.status LIKE 'pending%' THEN 1 ELSE 0 END) as pending_count
    FROM users u 
    LEFT JOIN news n ON u.id = n.created_by 
    GROUP BY u.id 
    ORDER BY total_news DESC")->fetchAll();

// 8. Gallery Media Images
$mainImages = $pdo->query("SELECT id, title, image_path, created_at, 'Gambar Utama' as img_type FROM news WHERE image_path IS NOT NULL AND status != 'draft' ORDER BY created_at DESC LIMIT 12")->fetchAll();
$galleryImages = $pdo->query("SELECT ni.image_path, n.id as news_id, n.title, 'Galeri Pendukung' as img_type FROM news_images ni JOIN news n ON n.id = ni.news_id WHERE n.status != 'draft' ORDER BY ni.id DESC LIMIT 12")->fetchAll();
$allGallery = array_merge($mainImages, $galleryImages);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Multi-Tab — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .view-switcher-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
            background: rgba(255, 255, 255, 0.75);
            padding: 5px;
            border-radius: 12px;
            border: 1px solid rgba(30, 111, 191, 0.12);
            overflow-x: auto;
            backdrop-filter: blur(12px);
        }
        .view-tab-btn {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-sec);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .view-tab-btn:hover {
            background: rgba(30, 111, 191, 0.08);
            color: var(--navy);
        }
        .view-tab-btn.active {
            background: var(--navy);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(11, 37, 69, 0.18);
        }

        /* Responsive Stat Grids */
        .dash-grid-responsive {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 1024px) {
            .dash-grid-responsive { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .dash-grid-responsive { grid-template-columns: 1fr; }
        }

        .sentiment-grid-responsive {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 992px) {
            .sentiment-grid-responsive { grid-template-columns: 1fr; }
        }

        .icon-box-badge {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .icon-box-blue { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
        .icon-box-green { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .icon-box-yellow { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
        .icon-box-gold { background: rgba(201, 162, 39, 0.15); color: #c9a227; }

        .sentiment-bar-wrap {
            display: flex;
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
            background: #e2e8f0;
            margin: 12px 0 16px;
        }
        .sentiment-segment {
            height: 100%;
            transition: width 0.6s ease;
        }
        .sentiment-seg-positive { background: linear-gradient(90deg, #10b981, #059669); }
        .sentiment-seg-neutral { background: linear-gradient(90deg, #3b82f6, #2563eb); }
        .sentiment-seg-negative { background: linear-gradient(90deg, #ef4444, #dc2626); }

        .sent-legend {
            display: flex;
            justify-content: space-around;
            gap: 8px;
            font-size: 11.5px;
        }
        .sent-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .gallery-grid-tab {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px;
            margin-top: 14px;
        }
        .gallery-item-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            cursor: pointer;
        }
        .gallery-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .gallery-thumb {
            width: 100%;
            height: 130px;
            object-fit: cover;
        }
        .gallery-caption {
            padding: 8px 10px;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--navy);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #lightboxModal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(8px);
            z-index: 100000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        #lightboxImg {
            max-width: 90vw;
            max-height: 85vh;
            border-radius: 10px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-container">
            
            <!-- VIEW SWITCHER BAR -->
            <div class="view-switcher-bar">
                <button type="button" onclick="switchDashboardTab('semua')" class="view-tab-btn <?= $view==='semua' ? 'active':'' ?>" id="tabbtn-semua">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Semua Sumber
                </button>
                <button type="button" onclick="switchDashboardTab('wilayah')" class="view-tab-btn <?= $view==='wilayah' ? 'active':'' ?>" id="tabbtn-wilayah">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    Berita Wilayah (<?= $stats['wilayah'] ?>)
                </button>
                <button type="button" onclick="switchDashboardTab('online')" class="view-tab-btn <?= $view==='online' ? 'active':'' ?>" id="tabbtn-online">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    Media Online (<?= $stats['online'] ?>)
                </button>
                <button type="button" onclick="switchDashboardTab('sosial')" class="view-tab-btn <?= $view==='sosial' ? 'active':'' ?>" id="tabbtn-sosial">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    Media Sosial (<?= $stats['sosial'] ?>)
                </button>
                <button type="button" onclick="switchDashboardTab('statistics')" class="view-tab-btn <?= $view==='statistics' ? 'active':'' ?>" id="tabbtn-statistics">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    Statistik
                </button>
                <button type="button" onclick="switchDashboardTab('report')" class="view-tab-btn <?= $view==='report' ? 'active':'' ?>" id="tabbtn-report">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    Report
                </button>
                <button type="button" onclick="switchDashboardTab('gallery')" class="view-tab-btn <?= $view==='gallery' ? 'active':'' ?>" id="tabbtn-gallery">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    Galeri Media
                </button>
            </div>

            <!-- TAB CONTENT CONTAINER -->
            <div class="tab-content-container">

                <!-- 1. SEMUA SUMBER / MAIN DASHBOARD -->
                <div class="tab-pane <?= $view==='semua' ? 'active':'' ?>" id="pane-semua">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
                        <div>
                            <h2 style="color:var(--navy);font-weight:700;margin:0;font-size:20px">Selamat datang, <?= e($user['full_name']) ?>!</h2>
                            <p style="color:var(--text-sec);font-size:12.5px;margin-top:2px">Ringkasan monitoring publikasi berita & media TNI Angkatan Udara.</p>
                        </div>
                        <?php if ($user['role'] === 'A'): ?>
                            <a href="<?= BASE_URL ?>/news_create.php" class="btn-entry-new">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                Buat Berita Baru
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- DASH STATS GRID -->
                    <div class="dash-grid-responsive">
                        <div class="dash-card smooth-card">
                            <div class="dash-card-info">
                                <span class="dash-card-label">TOTAL BERITA</span>
                                <span class="dash-card-value"><?= $stats['total'] ?></span>
                            </div>
                            <div class="icon-box-badge icon-box-blue">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            </div>
                        </div>
                        <div class="dash-card smooth-card">
                            <div class="dash-card-info">
                                <span class="dash-card-label">DIPUBLIKASIKAN</span>
                                <span class="dash-card-value"><?= $stats['published'] ?></span>
                            </div>
                            <div class="icon-box-badge icon-box-green">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                        </div>
                        <div class="dash-card smooth-card">
                            <div class="dash-card-info">
                                <span class="dash-card-label">MENUNGGU REVIEW</span>
                                <span class="dash-card-value"><?= $stats['pending'] ?></span>
                            </div>
                            <div class="icon-box-badge icon-box-yellow">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                        </div>
                        <div class="dash-card smooth-card">
                            <div class="dash-card-info">
                                <span class="dash-card-label">PENGGUNA AKTIF</span>
                                <span class="dash-card-value"><?= $stats['users'] ?></span>
                            </div>
                            <div class="icon-box-badge icon-box-gold">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                        </div>
                    </div>

                    <!-- SENTIMENT SUMMARY & OVERVIEW -->
                    <div class="sentiment-grid-responsive">
                        <div class="card smooth-card">
                            <h3 style="font-size:13.5px;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:12px;color:var(--navy);font-weight:600">Analisis Sentimen Publik</h3>
                            <div class="sentiment-bar-wrap">
                                <div class="sentiment-segment sentiment-seg-positive" style="width:<?= $pctP ?>%" title="Positif: <?= $pctP ?>%"></div>
                                <div class="sentiment-segment sentiment-seg-neutral" style="width:<?= $pctNe ?>%" title="Netral: <?= $pctNe ?>%"></div>
                                <div class="sentiment-segment sentiment-seg-negative" style="width:<?= $pctN ?>%" title="Negatif: <?= $pctN ?>%"></div>
                            </div>
                            <div class="sent-legend">
                                <div class="sent-legend-item">
                                    <span class="legend-dot" style="background:#10b981"></span>
                                    <span>Positif (<?= $pctP ?>%)</span>
                                </div>
                                <div class="sent-legend-item">
                                    <span class="legend-dot" style="background:#3b82f6"></span>
                                    <span>Netral (<?= $pctNe ?>%)</span>
                                </div>
                                <div class="sent-legend-item">
                                    <span class="legend-dot" style="background:#ef4444"></span>
                                    <span>Negatif (<?= $pctN ?>%)</span>
                                </div>
                            </div>
                        </div>

                        <div class="card smooth-card">
                            <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:12px">
                                <h3 style="font-size:13.5px;margin:0;color:var(--navy);font-weight:600">Distribution Media Overview</h3>
                                <span style="font-size:11.5px;color:var(--text-sec)">Total: <?= $stats['total'] ?> Berita</span>
                            </div>
                            <div style="display:flex;gap:12px;justify-content:space-around;text-align:center;padding:8px 0">
                                <div>
                                    <div style="font-size:22px;font-weight:700;color:#2563eb"><?= $stats['wilayah'] ?></div>
                                    <div style="font-size:11.5px;color:var(--text-sec)">Berita Wilayah</div>
                                </div>
                                <div style="border-left:1px solid #e2e8f0;height:36px"></div>
                                <div>
                                    <div style="font-size:22px;font-weight:700;color:#059669"><?= $stats['online'] ?></div>
                                    <div style="font-size:11.5px;color:var(--text-sec)">Media Online</div>
                                </div>
                                <div style="border-left:1px solid #e2e8f0;height:36px"></div>
                                <div>
                                    <div style="font-size:22px;font-weight:700;color:#d97706"><?= $stats['sosial'] ?></div>
                                    <div style="font-size:11.5px;color:var(--text-sec)">Media Sosial</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT NEWS TABLE -->
                    <div class="card smooth-card">
                        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:12px">
                            <h3 style="font-size:13.5px;margin:0;color:var(--navy);font-weight:600">Berita & Publikasi Terbaru</h3>
                            <a href="<?= BASE_URL ?>/news_list.php" style="font-size:11.5px;color:var(--blue);font-weight:600;text-decoration:none">Lihat Semua →</a>
                        </div>
                        <div class="news-table-wrap">
                            <div class="table-responsive">
                            <table class="news-table">
                                <thead>
                                    <tr>
                                        <th>Subjek Berita</th>
                                        <th>Media</th>
                                        <th>Sentimen</th>
                                        <th>Status</th>
                                        <th>Penulis</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent as $r): ?>
                                    <tr>
                                        <td class="col-subject"><a href="<?= BASE_URL ?>/news_view.php?id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></td>
                                        <td><span class="badge badge-gray"><?= e($r['media']) ?></span></td>
                                        <td>
                                            <?php 
                                            $sColor = ($r['sentiment'] === 'Positif') ? 'badge-green' : (($r['sentiment'] === 'Negatif') ? 'badge-red' : 'badge-blue');
                                            ?>
                                            <span class="badge <?= $sColor ?>"><?= e($r['sentiment']) ?></span>
                                        </td>
                                        <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= statusLabel($r['status']) ?></span></td>
                                        <td style="font-size:11.5px;color:var(--text-sec)"><?= e($r['author_name']) ?></td>
                                        <td class="col-time"><?= formatTanggal($r['created_at']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. BERITA WILAYAH PANE -->
                <div class="tab-pane <?= $view==='wilayah' ? 'active':'' ?>" id="pane-wilayah">
                    <div class="card smooth-card mb-4">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                            <h3 style="font-size:15px;color:var(--navy);margin:0;font-weight:600">Monitoring Berita Wilayah / Lanud / Satuan</h3>
                            <span class="badge badge-blue"><?= count($regionalNews) ?> Berita Ditemukan</span>
                        </div>
                        <div class="news-table-wrap">
                            <div class="table-responsive">
                            <table class="news-table">
                                <thead>
                                    <tr>
                                        <th>Judul Berita Wilayah</th>
                                        <th>Sentimen</th>
                                        <th>Status</th>
                                        <th>Kontributor</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($regionalNews)): ?>
                                        <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-sec)">Belum ada data berita wilayah.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($regionalNews as $rn): ?>
                                        <tr>
                                            <td class="col-subject"><a href="<?= BASE_URL ?>/news_view.php?id=<?= $rn['id'] ?>"><?= e($rn['title']) ?></a></td>
                                            <td><span class="badge <?= ($rn['sentiment']==='Positif')?'badge-green':(($rn['sentiment']==='Negatif')?'badge-red':'badge-blue') ?>"><?= e($rn['sentiment']) ?></span></td>
                                            <td><span class="badge <?= statusBadgeClass($rn['status']) ?>"><?= statusLabel($rn['status']) ?></span></td>
                                            <td style="font-size:11.5px"><?= e($rn['author_name']) ?></td>
                                            <td class="col-time"><?= formatTanggal($rn['created_at']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. MEDIA ONLINE PANE -->
                <div class="tab-pane <?= $view==='online' ? 'active':'' ?>" id="pane-online">
                    <div class="card smooth-card mb-4">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                            <h3 style="font-size:15px;color:var(--navy);margin:0;font-weight:600">Monitoring Portal Media Online Nasional & Lokal</h3>
                            <span class="badge badge-green"><?= count($onlineNews) ?> Artikel Media Online</span>
                        </div>
                        <div class="news-table-wrap">
                            <div class="table-responsive">
                            <table class="news-table">
                                <thead>
                                    <tr>
                                        <th>Artikel Media Online</th>
                                        <th>Sentimen</th>
                                        <th>Status</th>
                                        <th>Penulis</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($onlineNews)): ?>
                                        <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-sec)">Belum ada data berita media online.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($onlineNews as $on): ?>
                                        <tr>
                                            <td class="col-subject"><a href="<?= BASE_URL ?>/news_view.php?id=<?= $on['id'] ?>"><?= e($on['title']) ?></a></td>
                                            <td><span class="badge <?= ($on['sentiment']==='Positif')?'badge-green':(($on['sentiment']==='Negatif')?'badge-red':'badge-blue') ?>"><?= e($on['sentiment']) ?></span></td>
                                            <td><span class="badge <?= statusBadgeClass($on['status']) ?>"><?= statusLabel($on['status']) ?></span></td>
                                            <td style="font-size:11.5px"><?= e($on['author_name']) ?></td>
                                            <td class="col-time"><?= formatTanggal($on['created_at']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. MEDIA SOSIAL PANE -->
                <div class="tab-pane <?= $view==='sosial' ? 'active':'' ?>" id="pane-sosial">
                    <div class="card smooth-card mb-4">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                            <h3 style="font-size:15px;color:var(--navy);margin:0;font-weight:600">Monitoring & Analisis Media Sosial</h3>
                            <span class="badge badge-gold"><?= count($socialNews) ?> Postingan Medsos</span>
                        </div>
                        <div class="news-table-wrap">
                            <div class="table-responsive">
                            <table class="news-table">
                                <thead>
                                    <tr>
                                        <th>Konten Media Sosial</th>
                                        <th>Sentimen</th>
                                        <th>Status</th>
                                        <th>Penulis</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($socialNews)): ?>
                                        <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-sec)">Belum ada data media sosial.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($socialNews as $sn): ?>
                                        <tr>
                                            <td class="col-subject"><a href="<?= BASE_URL ?>/news_view.php?id=<?= $sn['id'] ?>"><?= e($sn['title']) ?></a></td>
                                            <td><span class="badge <?= ($sn['sentiment']==='Positif')?'badge-green':(($sn['sentiment']==='Negatif')?'badge-red':'badge-blue') ?>"><?= e($sn['sentiment']) ?></span></td>
                                            <td><span class="badge <?= statusBadgeClass($sn['status']) ?>"><?= statusLabel($sn['status']) ?></span></td>
                                            <td style="font-size:11.5px"><?= e($sn['author_name']) ?></td>
                                            <td class="col-time"><?= formatTanggal($sn['created_at']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. STATISTIK PANE -->
                <div class="tab-pane <?= $view==='statistics' ? 'active':'' ?>" id="pane-statistics">
                    <div class="card smooth-card mb-4">
                        <h3 style="font-size:15px;color:var(--navy);margin-bottom:14px;font-weight:600">Statistik Distribution & Analysis</h3>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                            <div style="background:#f8fafc;padding:16px;border-radius:10px;border:1px solid #e2e8f0">
                                <h4 style="font-size:13px;margin-bottom:12px;color:var(--navy)">Persentase Sentimen</h4>
                                <div style="margin-bottom:10px">
                                    <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px">
                                        <span>Positif (<?= $sentStats['Positif'] ?>)</span>
                                        <span><?= $pctP ?>%</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                                        <div style="height:100%;background:#10b981;width:<?= $pctP ?>%;transition:width 0.8s ease"></div>
                                    </div>
                                </div>
                                <div style="margin-bottom:10px">
                                    <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px">
                                        <span>Netral (<?= $sentStats['Netral'] ?>)</span>
                                        <span><?= $pctNe ?>%</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                                        <div style="height:100%;background:#3b82f6;width:<?= $pctNe ?>%;transition:width 0.8s ease"></div>
                                    </div>
                                </div>
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px">
                                        <span>Negatif (<?= $sentStats['Negatif'] ?>)</span>
                                        <span><?= $pctN ?>%</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                                        <div style="height:100%;background:#ef4444;width:<?= $pctN ?>%;transition:width 0.8s ease"></div>
                                    </div>
                                </div>
                            </div>

                            <div style="background:#f8fafc;padding:16px;border-radius:10px;border:1px solid #e2e8f0">
                                <h4 style="font-size:13px;margin-bottom:12px;color:var(--navy)">Distribusi Kanal Media</h4>
                                <?php 
                                $totalM = max(1, $stats['total']);
                                $pW = round(($stats['wilayah'] / $totalM) * 100);
                                $pO = round(($stats['online'] / $totalM) * 100);
                                $pS = round(($stats['sosial'] / $totalM) * 100);
                                ?>
                                <div style="margin-bottom:10px">
                                    <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px">
                                        <span>Berita Wilayah (<?= $stats['wilayah'] ?>)</span>
                                        <span><?= $pW ?>%</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                                        <div style="height:100%;background:#2563eb;width:<?= $pW ?>%;transition:width 0.8s ease"></div>
                                    </div>
                                </div>
                                <div style="margin-bottom:10px">
                                    <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px">
                                        <span>Media Online (<?= $stats['online'] ?>)</span>
                                        <span><?= $pO ?>%</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                                        <div style="height:100%;background:#059669;width:<?= $pO ?>%;transition:width 0.8s ease"></div>
                                    </div>
                                </div>
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px">
                                        <span>Media Sosial (<?= $stats['sosial'] ?>)</span>
                                        <span><?= $pS ?>%</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                                        <div style="height:100%;background:#d97706;width:<?= $pS ?>%;transition:width 0.8s ease"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. REPORT PANE -->
                <div class="tab-pane <?= $view==='report' ? 'active':'' ?>" id="pane-report">
                    <div class="card smooth-card mb-4">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                            <h3 style="font-size:15px;color:var(--navy);margin:0;font-weight:600">Report Monitoring Kontributor & Publikasi</h3>
                            <button onclick="window.print()" class="btn btn-outline btn-sm">Cetak Laporan</button>
                        </div>
                        <div class="news-table-wrap">
                            <div class="table-responsive">
                            <table class="news-table">
                                <thead>
                                    <tr>
                                        <th>Nama Kontributor</th>
                                        <th>Role / Peran</th>
                                        <th>Total Berita Dibuat</th>
                                        <th>Berita Dipublikasikan</th>
                                        <th>Menunggu Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($contributorsData as $cd): ?>
                                    <tr>
                                        <td style="font-weight:600;color:var(--navy)"><?= e($cd['full_name']) ?></td>
                                        <td><span class="badge badge-gray"><?= ['A'=>'Reporter','B'=>'Editor','C'=>'Petinggi'][$cd['role']] ?? $cd['role'] ?></span></td>
                                        <td style="font-weight:700"><?= (int)$cd['total_news'] ?></td>
                                        <td style="color:#059669;font-weight:700"><?= (int)$cd['published_count'] ?></td>
                                        <td style="color:#d97706;font-weight:700"><?= (int)$cd['pending_count'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7. GALERI MEDIA PANE -->
                <div class="tab-pane <?= $view==='gallery' ? 'active':'' ?>" id="pane-gallery">
                    <div class="card smooth-card mb-4">
                        <h3 style="font-size:15px;color:var(--navy);margin-bottom:14px;font-weight:600">Galeri Media Foto & Dokumentasi</h3>
                        <?php if(empty($allGallery)): ?>
                            <p style="color:var(--text-sec);text-align:center;padding:24px">Belum ada dokumentasi foto berita.</p>
                        <?php else: ?>
                            <div class="gallery-grid-tab">
                                <?php foreach($allGallery as $g): ?>
                                    <?php 
                                    $imgPath = str_starts_with($g['image_path'] ?? '', 'http') ? $g['image_path'] : BASE_URL . '/' . ltrim($g['image_path'] ?? '', '/');
                                    ?>
                                    <div class="gallery-item-card" onclick="openLightbox('<?= e($imgPath) ?>')">
                                        <img src="<?= e($imgPath) ?>" alt="<?= e($g['title']) ?>" class="gallery-thumb" onerror="this.src='<?= BASE_URL ?>/assets/img/logo-tniau.png'">
                                        <div class="gallery-caption"><?= e($g['title']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div> <!-- /tab-content-container -->

        </div>
    </main>
</div>

<!-- LIGHTBOX MODAL -->
<div id="lightboxModal" onclick="closeLightbox()">
    <img id="lightboxImg" src="" alt="Zoom Image">
</div>

<script>
function switchDashboardTab(tabId) {
    const panes = document.querySelectorAll('.tab-pane');
    panes.forEach(p => p.classList.remove('active'));

    const targetPane = document.getElementById('pane-' + tabId);
    if (targetPane) {
        targetPane.classList.add('active');
    }

    const btns = document.querySelectorAll('.view-tab-btn');
    btns.forEach(b => b.classList.remove('active'));

    const targetBtn = document.getElementById('tabbtn-' + tabId);
    if (targetBtn) {
        targetBtn.classList.add('active');
    }

    if (window.history && window.history.pushState) {
        const newUrl = window.location.pathname + '?view=' + tabId;
        window.history.pushState({ view: tabId }, '', newUrl);
    }

    if (window.WorkspaceTabs && window.WorkspaceTabs.render) {
        window.WorkspaceTabs.render();
    }
}

function openLightbox(src) {
    const modal = document.getElementById('lightboxModal');
    const img = document.getElementById('lightboxImg');
    if (modal && img) {
        img.src = src;
        modal.style.display = 'flex';
    }
}

function closeLightbox() {
    const modal = document.getElementById('lightboxModal');
    if (modal) modal.style.display = 'none';
}
</script>
</body>
</html>
