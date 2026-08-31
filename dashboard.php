<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'dashboard';
$user = currentUser();

// Determine requested view
$view = $_GET['view'] ?? $_GET['media'] ?? 'semua';
$validViews = ['semua', 'dashboard', 'harian', 'negatif', 'inspiratif', 'konten', 'sentimen', 'wilayah'];
if (!in_array($view, $validViews)) {
    $view = 'semua';
}
if (in_array($view, ['semua', 'dashboard'], true)) {
    $view = 'harian';
}

// 1. Overall Stats
$stats = [
    'total'      => (int)$pdo->query("SELECT COUNT(*) FROM news")->fetchColumn(),
    'published'  => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetchColumn(),
    'pending'    => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status LIKE 'pending%'")->fetchColumn(),
    'users'      => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'wilayah'    => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE media='Wilayah'")->fetchColumn(),
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

// 5. Contributors Report Data
$contributorsData = $pdo->query("SELECT u.full_name, u.role, COUNT(n.id) as total_news, 
    SUM(CASE WHEN n.status='published' THEN 1 ELSE 0 END) as published_count,
    SUM(CASE WHEN n.status LIKE 'pending%' THEN 1 ELSE 0 END) as pending_count
    FROM users u 
    LEFT JOIN news n ON u.id = n.created_by 
    GROUP BY u.id 
    ORDER BY total_news DESC")->fetchAll();

// 8. View-specific datasets
$negativeNews = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.sentiment='Negatif' ORDER BY n.created_at DESC LIMIT 10")->fetchAll();
$inspiratifNews = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.sentiment='Positif' ORDER BY n.created_at DESC LIMIT 12")->fetchAll();
$kontenNews = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC LIMIT 10")->fetchAll();

// Konten per-media breakdown
$kontenWilayah     = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.media='Wilayah' ORDER BY n.created_at DESC LIMIT 6")->fetchAll();
$kontenOnline      = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.media='Media Online' ORDER BY n.created_at DESC LIMIT 6")->fetchAll();
$kontenSosial      = $pdo->query("SELECT n.*, u.full_name AS author_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.media='Media Sosial' ORDER BY n.created_at DESC LIMIT 6")->fetchAll();
$kontenMediaCounts = [
    'Wilayah'      => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE media='Wilayah'")->fetchColumn(),
    'Media Online' => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE media='Media Online'")->fetchColumn(),
    'Media Sosial' => (int)$pdo->query("SELECT COUNT(*) FROM news WHERE media='Media Sosial'")->fetchColumn(),
];

// Inspiratif stats
$inspiratifTotal   = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE sentiment='Positif'")->fetchColumn();
$inspiratifPubl    = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE sentiment='Positif' AND status='published'")->fetchColumn();
$inspiratifThisWk  = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE sentiment='Positif' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// 9. Wilayah-specific stats
if ($view === 'wilayah') {
    $wilayahTotal = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE media='Wilayah'")->fetchColumn();
    $wilayahSentData = $pdo->query("SELECT sentiment, COUNT(*) as c FROM news WHERE media='Wilayah' GROUP BY sentiment")->fetchAll();
    $wilayahSentStats = ['Positif' => 0, 'Negatif' => 0, 'Netral' => 0];
    $wilayahTotalSent = 0;
    foreach ($wilayahSentData as $row) {
        if (isset($wilayahSentStats[$row['sentiment']])) {
            $wilayahSentStats[$row['sentiment']] = (int)$row['c'];
            $wilayahTotalSent += (int)$row['c'];
        }
    }
    $wilayahPctP = $wilayahTotalSent ? round(($wilayahSentStats['Positif'] / $wilayahTotalSent) * 100) : 0;
    $wilayahPctNe = $wilayahTotalSent ? round(($wilayahSentStats['Netral'] / $wilayahTotalSent) * 100) : 0;
    $wilayahPctN = $wilayahTotalSent ? round(($wilayahSentStats['Negatif'] / $wilayahTotalSent) * 100) : 0;

    $wilayahDistData = $pdo->query("SELECT wilayah, COUNT(*) as c FROM news WHERE media='Wilayah' AND wilayah IS NOT NULL AND wilayah != '' GROUP BY wilayah ORDER BY c DESC LIMIT 6")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Multi-Tab Portal Berita TNI AU</title>
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
            border: 1px solid transparent;
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
            background: #ffffff;
            color: var(--navy);
            font-weight: 700;
            border-color: rgba(30, 111, 191, 0.14);
            border-bottom-color: var(--gold);
            box-shadow: 0 4px 12px rgba(11, 37, 69, 0.08);
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
                <button type="button" onclick="switchDashboardTab('harian')" class="view-tab-btn <?= $view==='harian' ? 'active':'' ?>" id="tabbtn-harian">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard Harian
                </button>
                <button type="button" onclick="switchDashboardTab('negatif')" class="view-tab-btn <?= $view==='negatif' ? 'active':'' ?>" id="tabbtn-negatif">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    Berita Negatif
                </button>
                <button type="button" onclick="switchDashboardTab('inspiratif')" class="view-tab-btn <?= $view==='inspiratif' ? 'active':'' ?>" id="tabbtn-inspiratif">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L14.39 8.26L21 9.27L16 14.14L17.18 21L12 17.77L6.82 21L8 14.14L3 9.27L9.61 8.26Z"></path></svg>
                    Inspiratif
                </button>
                <button type="button" onclick="switchDashboardTab('konten')" class="view-tab-btn <?= $view==='konten' ? 'active':'' ?>" id="tabbtn-konten">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v12H4z"></path><path d="M8 10h8"></path><path d="M8 14h5"></path></svg>
                    Konten
                </button>
                <button type="button" onclick="switchDashboardTab('sentimen')" class="view-tab-btn <?= $view==='sentimen' ? 'active':'' ?>" id="tabbtn-sentimen">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Sentimen
                </button>
                <button type="button" onclick="switchDashboardTab('wilayah')" class="view-tab-btn <?= $view==='wilayah' ? 'active':'' ?>" id="tabbtn-wilayah">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    Berita Wilayah
                </button>
            </div>

            <!-- TAB CONTENT CONTAINER -->
            <div class="tab-content-container">
                <div class="tab-pane <?= in_array($view, ['semua', 'harian']) ? 'active':'' ?>" id="pane-harian">
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

                    <div class="dash-grid-responsive">
                        <div class="dash-card smooth-card">
                            <div class="dash-card-info"><span class="dash-card-label">TOTAL BERITA</span><span class="dash-card-value"><?= $stats['total'] ?></span></div>
                            <div class="icon-box-badge icon-box-blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div>
                        </div>
                        <div class="dash-card smooth-card">
                            <div class="dash-card-info"><span class="dash-card-label">DIPUBLIKASIKAN</span><span class="dash-card-value"><?= $stats['published'] ?></span></div>
                            <div class="icon-box-badge icon-box-green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
                        </div>
                        <div class="dash-card smooth-card">
                            <div class="dash-card-info"><span class="dash-card-label">MENUNGGU REVIEW</span><span class="dash-card-value"><?= $stats['pending'] ?></span></div>
                            <div class="icon-box-badge icon-box-yellow"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                        </div>
                        <div class="dash-card smooth-card">
                            <div class="dash-card-info"><span class="dash-card-label">PENGGUNA AKTIF</span><span class="dash-card-value"><?= $stats['users'] ?></span></div>
                            <div class="icon-box-badge icon-box-gold"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                        </div>
                    </div>

                    <div class="sentiment-grid-responsive">
                        <div class="card smooth-card">
                            <h3 style="font-size:13.5px;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:12px;color:var(--navy);font-weight:600">Analisis Sentimen Publik</h3>
                            <div class="sentiment-bar-wrap">
                                <div class="sentiment-segment sentiment-seg-positive" style="width:<?= $pctP ?>%" title="Positif: <?= $pctP ?>%"></div>
                                <div class="sentiment-segment sentiment-seg-neutral" style="width:<?= $pctNe ?>%" title="Netral: <?= $pctNe ?>%"></div>
                                <div class="sentiment-segment sentiment-seg-negative" style="width:<?= $pctN ?>%" title="Negatif: <?= $pctN ?>%"></div>
                            </div>
                            <div class="sent-legend">
                                <div class="sent-legend-item"><span class="legend-dot" style="background:#10b981"></span><span>Positif (<?= $pctP ?>%)</span></div>
                                <div class="sent-legend-item"><span class="legend-dot" style="background:#3b82f6"></span><span>Netral (<?= $pctNe ?>%)</span></div>
                                <div class="sent-legend-item"><span class="legend-dot" style="background:#ef4444"></span><span>Negatif (<?= $pctN ?>%)</span></div>
                            </div>
                        </div>

                        <div class="card smooth-card">
                            <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:12px">
                                <h3 style="font-size:13.5px;margin:0;color:var(--navy);font-weight:600">Distribusi Berita Wilayah</h3>
                                <span style="font-size:11.5px;color:var(--text-sec)">Total: <?= $stats['wilayah'] ?> Berita</span>
                            </div>
                            <div style="display:flex;gap:12px;justify-content:center;text-align:center;padding:8px 0">
                                <div><div style="font-size:22px;font-weight:700;color:#2563eb"><?= $stats['wilayah'] ?></div><div style="font-size:11.5px;color:var(--text-sec)">Berita Wilayah</div></div>
                            </div>
                        </div>
                    </div>

                    <div class="card smooth-card">
                        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:12px">
                            <h3 style="font-size:13.5px;margin:0;color:var(--navy);font-weight:600">Berita & Publikasi Terbaru</h3>
                            <a href="<?= BASE_URL ?>/news_list.php" style="font-size:11.5px;color:var(--blue);font-weight:600;text-decoration:none">Lihat Semua →</a>
                        </div>
                        <div class="news-table-wrap"><div class="table-responsive"><table class="news-table"><thead><tr><th>Subjek Berita</th><th>Media</th><th>Sentimen</th><th>Status</th><th>Penulis</th><th>Waktu</th></tr></thead><tbody><?php foreach($recent as $r): ?><tr><td class="col-subject"><a href="<?= BASE_URL ?>/news_view.php?id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></td><td><span class="badge badge-gray"><?= e($r['media']) ?></span></td><td><?php $sColor = ($r['sentiment'] === 'Positif') ? 'badge-green' : (($r['sentiment'] === 'Negatif') ? 'badge-red' : 'badge-blue'); ?><span class="badge <?= $sColor ?>"><?= e($r['sentiment']) ?></span></td><td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= statusLabel($r['status']) ?></span></td><td style="font-size:11.5px;color:var(--text-sec)"><?= e($r['author_name']) ?></td><td class="col-time"><?= formatTanggal($r['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
                    </div>
                </div>

                <div class="tab-pane <?= $view==='negatif' ? 'active':'' ?>" id="pane-negatif">
                    <div class="card smooth-card mb-4">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                            <h3 style="font-size:15px;color:var(--navy);margin:0;font-weight:600">Berita Negatif</h3>
                            <span class="badge badge-red"><?= count($negativeNews) ?> Berita Ditemukan</span>
                        </div>
                        <div class="news-table-wrap"><div class="table-responsive"><table class="news-table"><thead><tr><th>Judul</th><th>Media</th><th>Status</th><th>Penulis</th><th>Waktu</th></tr></thead><tbody><?php if(empty($negativeNews)): ?><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-sec)">Belum ada berita negatif.</td></tr><?php else: ?><?php foreach($negativeNews as $row): ?><tr><td class="col-subject"><a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>"><?= e($row['title']) ?></a></td><td><span class="badge badge-gray"><?= e($row['media']) ?></span></td><td><span class="badge <?= statusBadgeClass($row['status']) ?>"><?= statusLabel($row['status']) ?></span></td><td style="font-size:11.5px"><?= e($row['author_name']) ?></td><td class="col-time"><?= formatTanggal($row['created_at']) ?></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
                    </div>
                </div>

                <div class="tab-pane <?= $view==='inspiratif' ? 'active':'' ?>" id="pane-inspiratif">
                    <!-- KPI strip -->
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;">
                        <div style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #6ee7b7;border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(16,185,129,0.15);display:flex;align-items:center;justify-content:center;color:#059669;flex-shrink:0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L14.39 8.26L21 9.27L16 14.14L17.18 21L12 17.77L6.82 21L8 14.14L3 9.27L9.61 8.26Z"/></svg>
                            </div>
                            <div>
                                <div style="font-size:22px;font-weight:800;color:#065f46;line-height:1"><?= $inspiratifTotal ?></div>
                                <div style="font-size:11px;color:#047857;font-weight:500;margin-top:2px">Total Konten Positif</div>
                            </div>
                        </div>
                        <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1px solid #fcd34d;border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(245,158,11,0.15);display:flex;align-items:center;justify-content:center;color:#d97706;flex-shrink:0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </div>
                            <div>
                                <div style="font-size:22px;font-weight:800;color:#92400e;line-height:1"><?= $inspiratifPubl ?></div>
                                <div style="font-size:11px;color:#b45309;font-weight:500;margin-top:2px">Sudah Dipublikasi</div>
                            </div>
                        </div>
                        <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #93c5fd;border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                            </div>
                            <div>
                                <div style="font-size:22px;font-weight:800;color:#1e3a8a;line-height:1"><?= $inspiratifThisWk ?></div>
                                <div style="font-size:11px;color:#1d4ed8;font-weight:500;margin-top:2px">Pekan Ini</div>
                            </div>
                        </div>
                    </div>

                    <!-- Header -->
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <div>
                            <h3 style="font-size:15px;color:var(--navy);margin:0;font-weight:700;">Konten Inspiratif &amp; Positif</h3>
                            <p style="font-size:12px;color:var(--text-sec);margin:3px 0 0;">Berita bersentimen positif yang menginspirasi</p>
                        </div>
                        <a href="<?= BASE_URL ?>/news_list.php?sentiment=Positif" style="font-size:11.5px;color:var(--blue);font-weight:600;text-decoration:none;">Lihat Semua →</a>
                    </div>

                    <!-- Magazine card grid -->
                    <?php if (empty($inspiratifNews)): ?>
                        <div style="text-align:center;padding:40px;color:var(--text-sec);font-size:13px;">Belum ada konten inspiratif.</div>
                    <?php else: ?>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;">
                        <?php foreach ($inspiratifNews as $i => $row):
                            $isFirst = ($i === 0);
                        ?>
                        <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>" style="text-decoration:none;display:block;background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.04);transition:transform 0.2s ease,box-shadow 0.2s ease;<?= $isFirst ? 'grid-column:span 2;' : '' ?>" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.09)'" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.04)'">
                            <!-- Colored accent strip -->
                            <div style="height:5px;background:linear-gradient(90deg,#10b981,#34d399);"></div>
                            <div style="padding:14px 16px;">
                                <!-- Tag row -->
                                <div style="display:flex;align-items:center;gap:7px;margin-bottom:10px;">
                                    <span style="background:#ecfdf5;color:#059669;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:0.5px;">POSITIF</span>
                                    <span style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;"><?= e($row['media']) ?></span>
                                </div>
                                <!-- Title -->
                                <p style="font-size:<?= $isFirst ? '14px' : '12.5px' ?>;font-weight:<?= $isFirst ? '700' : '600' ?>;color:var(--navy);margin:0 0 10px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:<?= $isFirst ? '3' : '2' ?>;-webkit-box-orient:vertical;overflow:hidden;"><?= e($row['title']) ?></p>
                                <!-- Footer -->
                                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #f1f5f9;padding-top:9px;">
                                    <span style="font-size:11px;color:#64748b;font-weight:500;">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <?= e($row['author_name']) ?>
                                    </span>
                                    <span style="font-size:10.5px;color:#94a3b8;"><?= formatTanggal($row['created_at']) ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane <?= $view==='konten' ? 'active':'' ?>" id="pane-konten">
                    <!-- Header -->
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                        <div>
                            <h3 style="font-size:15px;color:var(--navy);margin:0;font-weight:700;">Monitoring Konten Media</h3>
                            <p style="font-size:12px;color:var(--text-sec);margin:3px 0 0;">Sebaran publikasi berita berdasarkan platform media</p>
                        </div>
                        <a href="<?= BASE_URL ?>/news_list.php" style="font-size:11.5px;color:var(--blue);font-weight:600;text-decoration:none;">Lihat Semua →</a>
                    </div>

                    <!-- 3-column kanban -->
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;align-items:start;">

                        <!-- Column: Wilayah -->
                        <div style="background:#f8fafc;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#1e3a8a,#2563eb);padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    </div>
                                    <span style="color:#fff;font-weight:700;font-size:13px;">Berita Wilayah</span>
                                </div>
                                <span style="background:rgba(255,255,255,0.22);color:#fff;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;"><?= $kontenMediaCounts['Wilayah'] ?></span>
                            </div>
                            <div style="padding:10px 12px;display:flex;flex-direction:column;gap:8px;">
                                <?php if (empty($kontenWilayah)): ?>
                                    <p style="font-size:12px;color:var(--text-sec);text-align:center;padding:16px 0;">Belum ada data</p>
                                <?php else: ?>
                                <?php foreach ($kontenWilayah as $row): ?>
                                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>" style="text-decoration:none;display:block;background:#fff;border-radius:10px;padding:10px 12px;border:1px solid #e9eef6;transition:box-shadow 0.18s;" onmouseover="this.style.boxShadow='0 4px 14px rgba(37,99,235,0.1)'" onmouseout="this.style.boxShadow='none'">
                                    <p style="font-size:12px;font-weight:600;color:var(--navy);margin:0 0 6px;line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= e($row['title']) ?></p>
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <?php $sc = ($row['sentiment']==='Positif') ? '#10b981' : (($row['sentiment']==='Negatif') ? '#ef4444' : '#3b82f6'); ?>
                                        <span style="font-size:10px;font-weight:700;color:<?= $sc ?>;background:<?= $sc ?>18;padding:2px 7px;border-radius:20px;"><?= e($row['sentiment']) ?></span>
                                        <span style="font-size:10.5px;color:#94a3b8;"><?= formatTanggal($row['created_at']) ?></span>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Column: Media Online -->
                        <div style="background:#f8fafc;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#0e7490,#06b6d4);padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    </div>
                                    <span style="color:#fff;font-weight:700;font-size:13px;">Media Online</span>
                                </div>
                                <span style="background:rgba(255,255,255,0.22);color:#fff;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;"><?= $kontenMediaCounts['Media Online'] ?></span>
                            </div>
                            <div style="padding:10px 12px;display:flex;flex-direction:column;gap:8px;">
                                <?php if (empty($kontenOnline)): ?>
                                    <p style="font-size:12px;color:var(--text-sec);text-align:center;padding:16px 0;">Belum ada data</p>
                                <?php else: ?>
                                <?php foreach ($kontenOnline as $row): ?>
                                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>" style="text-decoration:none;display:block;background:#fff;border-radius:10px;padding:10px 12px;border:1px solid #e9eef6;transition:box-shadow 0.18s;" onmouseover="this.style.boxShadow='0 4px 14px rgba(6,182,212,0.12)'" onmouseout="this.style.boxShadow='none'">
                                    <p style="font-size:12px;font-weight:600;color:var(--navy);margin:0 0 6px;line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= e($row['title']) ?></p>
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <?php $sc = ($row['sentiment']==='Positif') ? '#10b981' : (($row['sentiment']==='Negatif') ? '#ef4444' : '#3b82f6'); ?>
                                        <span style="font-size:10px;font-weight:700;color:<?= $sc ?>;background:<?= $sc ?>18;padding:2px 7px;border-radius:20px;"><?= e($row['sentiment']) ?></span>
                                        <span style="font-size:10.5px;color:#94a3b8;"><?= formatTanggal($row['created_at']) ?></span>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Column: Media Sosial -->
                        <div style="background:#f8fafc;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#7c3aed,#a78bfa);padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                    </div>
                                    <span style="color:#fff;font-weight:700;font-size:13px;">Media Sosial</span>
                                </div>
                                <span style="background:rgba(255,255,255,0.22);color:#fff;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;"><?= $kontenMediaCounts['Media Sosial'] ?></span>
                            </div>
                            <div style="padding:10px 12px;display:flex;flex-direction:column;gap:8px;">
                                <?php if (empty($kontenSosial)): ?>
                                    <p style="font-size:12px;color:var(--text-sec);text-align:center;padding:16px 0;">Belum ada data</p>
                                <?php else: ?>
                                <?php foreach ($kontenSosial as $row): ?>
                                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>" style="text-decoration:none;display:block;background:#fff;border-radius:10px;padding:10px 12px;border:1px solid #e9eef6;transition:box-shadow 0.18s;" onmouseover="this.style.boxShadow='0 4px 14px rgba(124,58,237,0.1)'" onmouseout="this.style.boxShadow='none'">
                                    <p style="font-size:12px;font-weight:600;color:var(--navy);margin:0 0 6px;line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= e($row['title']) ?></p>
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <?php $sc = ($row['sentiment']==='Positif') ? '#10b981' : (($row['sentiment']==='Negatif') ? '#ef4444' : '#3b82f6'); ?>
                                        <span style="font-size:10px;font-weight:700;color:<?= $sc ?>;background:<?= $sc ?>18;padding:2px 7px;border-radius:20px;"><?= e($row['sentiment']) ?></span>
                                        <span style="font-size:10.5px;color:#94a3b8;"><?= formatTanggal($row['created_at']) ?></span>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="tab-pane <?= $view==='sentimen' ? 'active':'' ?>" id="pane-sentimen">
                    <div class="card smooth-card mb-4">
                        <h3 style="font-size:15px;color:var(--navy);margin-bottom:14px;font-weight:600">Sentimen</h3>
                        <div class="sentiment-grid-responsive">
                            <div class="card smooth-card">
                                <h3 style="font-size:13.5px;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:12px;color:var(--navy);font-weight:600">Analisis Sentimen Publik</h3>
                                <div class="sentiment-bar-wrap">
                                    <div class="sentiment-segment sentiment-seg-positive" style="width:<?= $pctP ?>%" title="Positif: <?= $pctP ?>%"></div>
                                    <div class="sentiment-segment sentiment-seg-neutral" style="width:<?= $pctNe ?>%" title="Netral: <?= $pctNe ?>%"></div>
                                    <div class="sentiment-segment sentiment-seg-negative" style="width:<?= $pctN ?>%" title="Negatif: <?= $pctN ?>%"></div>
                                </div>
                                <div class="sent-legend">
                                    <div class="sent-legend-item"><span class="legend-dot" style="background:#10b981"></span><span>Positif (<?= $pctP ?>%)</span></div>
                                    <div class="sent-legend-item"><span class="legend-dot" style="background:#3b82f6"></span><span>Netral (<?= $pctNe ?>%)</span></div>
                                    <div class="sent-legend-item"><span class="legend-dot" style="background:#ef4444"></span><span>Negatif (<?= $pctN ?>%)</span></div>
                                </div>
                            </div>
                            <div class="card smooth-card">
                                <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:12px">
                                    <h3 style="font-size:13.5px;margin:0;color:var(--navy);font-weight:600">Distribusi Sentimen</h3>
                                    <span style="font-size:11.5px;color:var(--text-sec)">Total: <?= $stats['total'] ?> Berita</span>
                                </div>
                                <div style="display:grid;gap:10px">
                                    <div><div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px"><span>Positif (<?= $sentStats['Positif'] ?>)</span><span><?= $pctP ?>%</span></div><div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden"><div style="height:100%;background:#10b981;width:<?= $pctP ?>%;transition:width 0.8s ease"></div></div></div>
                                    <div><div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px"><span>Netral (<?= $sentStats['Netral'] ?>)</span><span><?= $pctNe ?>%</span></div><div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden"><div style="height:100%;background:#3b82f6;width:<?= $pctNe ?>%;transition:width 0.8s ease"></div></div></div>
                                    <div><div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:4px"><span>Negatif (<?= $sentStats['Negatif'] ?>)</span><span><?= $pctN ?>%</span></div><div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden"><div style="height:100%;background:#ef4444;width:<?= $pctN ?>%;transition:width 0.8s ease"></div></div></div>
                                </div>
                             </div>
                         </div>
                     </div>
                 </div>

                <div class="tab-pane <?= $view==='wilayah' ? 'active':'' ?>" id="pane-wilayah">
                    <div class="card smooth-card mb-4" style="background: linear-gradient(135deg, rgba(30, 111, 191, 0.05), rgba(255, 255, 255, 0.9)); border: 1px solid rgba(30,111,191,0.12)">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:12px">
                            <div>
                                <h3 style="font-size:18px;color:var(--navy);margin:0;font-weight:700;display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--blue);flex-shrink:0;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Pemantauan Berita Wilayah & Lanud</h3>
                                <p style="color:var(--text-sec);font-size:12.5px;margin-top:4px">Analisis dan sebaran informasi dari satuan Lanud di seluruh Indonesia.</p>
                            </div>
                            <span class="badge badge-gold" style="font-size:12px;padding:6px 12px;font-weight:700">Total: <?= $wilayahTotal ?? 0 ?> Berita</span>
                        </div>

                        <!-- Grid Info Wilayah -->
                        <div class="sentiment-grid-responsive" style="margin-bottom: 24px;">
                            <!-- Sentiment khusus Wilayah -->
                            <div class="card smooth-card" style="border: 1px solid rgba(30,111,191,0.1); background:#ffffff;">
                                <h4 style="font-size:13px;color:var(--navy);font-weight:600;margin-bottom:12px;border-bottom:1px solid #eee;padding-bottom:8px">Sentimen Berita Wilayah</h4>
                                <div class="sentiment-bar-wrap" style="height:12px;margin:10px 0 14px">
                                    <div class="sentiment-segment sentiment-seg-positive" style="width:<?= $wilayahPctP ?? 0 ?>%" title="Positif: <?= $wilayahPctP ?? 0 ?>%"></div>
                                    <div class="sentiment-segment sentiment-seg-neutral" style="width:<?= $wilayahPctNe ?? 0 ?>%" title="Netral: <?= $wilayahPctNe ?? 0 ?>%"></div>
                                    <div class="sentiment-segment sentiment-seg-negative" style="width:<?= $wilayahPctN ?? 0 ?>%" title="Negatif: <?= $wilayahPctN ?? 0 ?>%"></div>
                                </div>
                                <div class="sent-legend" style="flex-wrap:wrap;row-gap:6px">
                                    <div class="sent-legend-item"><span class="legend-dot" style="background:#10b981"></span><span>Positif (<?= $wilayahPctP ?? 0 ?>%)</span></div>
                                    <div class="sent-legend-item"><span class="legend-dot" style="background:#3b82f6"></span><span>Netral (<?= $wilayahPctNe ?? 0 ?>%)</span></div>
                                    <div class="sent-legend-item"><span class="legend-dot" style="background:#ef4444"></span><span>Negatif (<?= $wilayahPctN ?? 0 ?>%)</span></div>
                                </div>
                            </div>

                            <!-- Distribusi per Lanud teraktif -->
                            <div class="card smooth-card" style="border: 1px solid rgba(30,111,191,0.1); background:#ffffff;">
                                <h4 style="font-size:13px;color:var(--navy);font-weight:600;margin-bottom:12px;border-bottom:1px solid #eee;padding-bottom:8px">Sebaran Wilayah Teraktif</h4>
                                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:10px;">
                                    <?php if (empty($wilayahDistData)): ?>
                                        <div style="grid-column: 1/-1; text-align:center; font-size:12px; color:var(--text-sec); padding:10px;">Belum ada sebaran wilayah</div>
                                    <?php else: ?>
                                        <?php foreach ($wilayahDistData as $dist): ?>
                                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;text-align:center;box-shadow:0 2px 4px rgba(0,0,0,0.02)">
                                                <div style="font-size:11px;color:var(--text-sec);text-transform:uppercase;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($dist['wilayah']) ?>"><?= e(getLanudInitials($dist['wilayah'])) ?></div>
                                                <div style="font-size:18px;font-weight:700;color:var(--navy);margin-top:2px"><?= $dist['c'] ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Daftar Berita Wilayah -->
                        <h4 style="font-size:14px;color:var(--navy);font-weight:600;margin-bottom:12px">Daftar Berita Wilayah Terbaru</h4>
                        <div class="news-table-wrap">
                            <div class="table-responsive">
                                <table class="news-table">
                                    <thead>
                                        <tr>
                                            <th>Subjek Berita</th>
                                            <th>Wilayah & Satuan</th>
                                            <th>Sentimen</th>
                                            <th>Status</th>
                                            <th>Penulis</th>
                                            <th>Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($regionalNews)): ?>
                                            <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-sec)">Belum ada berita wilayah.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($regionalNews as $row): ?>
                                                <tr>
                                                    <td class="col-subject"><a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>"><?= e($row['title']) ?></a></td>
                                                    <td><span class="badge badge-blue" style="background:#e1effe;color:#1e429f;font-weight:600;font-size:11px"><?= e($row['wilayah']) ?></span></td>
                                                    <td>
                                                        <?php $sColor = ($row['sentiment'] === 'Positif') ? 'badge-green' : (($row['sentiment'] === 'Negatif') ? 'badge-red' : 'badge-blue'); ?>
                                                        <span class="badge <?= $sColor ?>"><?= e($row['sentiment']) ?></span>
                                                    </td>
                                                    <td><span class="badge <?= statusBadgeClass($row['status']) ?>"><?= statusLabel($row['status']) ?></span></td>
                                                    <td style="font-size:11.5px;color:var(--text-sec)"><?= e($row['author_name']) ?></td>
                                                    <td class="col-time"><?= formatTanggal($row['created_at']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
    const rawTabId = String(tabId || '').trim().toLowerCase();
    const normalizedTabId = rawTabId.startsWith('dashboard-') ? rawTabId.replace('dashboard-', '') : rawTabId;
    const effectiveTabId = normalizedTabId === 'dashboard' || normalizedTabId === 'semua' ? 'harian' : normalizedTabId;

    const panes = document.querySelectorAll('.tab-pane');
    panes.forEach(p => p.classList.remove('active'));

    const targetPane = document.getElementById('pane-' + effectiveTabId);
    if (targetPane) {
        targetPane.classList.add('active');
    }

    const btns = document.querySelectorAll('.view-tab-btn');
    btns.forEach(b => b.classList.remove('active'));

    const targetBtn = document.getElementById('tabbtn-' + effectiveTabId);
    if (targetBtn) {
        targetBtn.classList.add('active');
    }

    if (window.history && window.history.pushState) {
        const newUrl = window.location.pathname + '?view=' + effectiveTabId;
        window.history.pushState({ view: effectiveTabId }, '', newUrl);
    }

    // Sync sidebar active state
    const sidebarDashLinks = document.querySelectorAll('.sidebar-accordion .accordion-content a[href*="dashboard.php"]');
    sidebarDashLinks.forEach(link => {
        if (link.getAttribute('href').includes('view=' + effectiveTabId)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

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
