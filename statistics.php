<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user    = currentUser();
$current = 'statistics';
$view    = $_GET['view'] ?? 'berita';

$chartColors = [
    '#4b74e0','#e2583e','#47b275','#f1b72c','#a55eea',
    '#ff7675','#00b894','#fd79a8','#0984e3','#6c5ce7'
];

// ── VIEW: STATISTIK BERITA ────────────────────────────────────────
if ($view === 'berita') {
    $stmt = $pdo->query("SELECT sentiment, COUNT(*) as c FROM news GROUP BY sentiment");
    $sentimentData = $stmt->fetchAll();
    $sentStats  = ['Positif' => 0, 'Negatif' => 0, 'Netral' => 0];
    $totalSent  = 0;
    foreach ($sentimentData as $row) {
        if (isset($sentStats[$row['sentiment']])) {
            $sentStats[$row['sentiment']] = (int)$row['c'];
            $totalSent += (int)$row['c'];
        }
    }

    $stmt = $pdo->query("SELECT media, COUNT(*) as c FROM news GROUP BY media");
    $mediaData  = $stmt->fetchAll();
    $mediaStats = ['Wilayah' => 0, 'Media Online' => 0, 'Media Sosial' => 0];
    $totalMedia = 0;
    foreach ($mediaData as $row) {
        if (isset($mediaStats[$row['media']])) {
            $mediaStats[$row['media']] = (int)$row['c'];
            $totalMedia += (int)$row['c'];
        }
    }
}

// ── VIEW: TREN ────────────────────────────────────────────────────
if ($view === 'tren') {
    // Ambil top aktor untuk dijadikan dataset tren
    try {
        $stmtAktor = $pdo->query("
            SELECT TRIM(a.aktor_name) as aktor_name, COUNT(*) as total
            FROM news n
            JOIN JSON_TABLE(
                CONCAT('[', n.aktor, ']'),
                '\$[*]' COLUMNS (aktor_name VARCHAR(200) PATH '\$')
            ) a ON 1=1
            WHERE n.aktor IS NOT NULL AND n.aktor != ''
            GROUP BY TRIM(a.aktor_name)
            ORDER BY total DESC
            LIMIT 10
        ");
        $topAktor = $stmtAktor->fetchAll();
    } catch (Exception $e) {
        $topAktor = [];
    }
    if (empty($topAktor)) {
        $stmtAktor2 = $pdo->query("
            SELECT aktor as aktor_name, COUNT(*) as total
            FROM news WHERE aktor IS NOT NULL AND aktor != ''
            GROUP BY aktor ORDER BY total DESC LIMIT 10
        ");
        $topAktor = $stmtAktor2->fetchAll();
    }

    $topAktorNames = array_column($topAktor, 'aktor_name');

    // 30 hari terakhir
    $trendDates = [];
    for ($i = 29; $i >= 0; $i--) {
        $trendDates[] = date('Y-m-d', strtotime("-$i days"));
    }

    $trendDatasets = [];
    foreach ($topAktorNames as $idx => $aktorName) {
        $stmtTrend = $pdo->prepare("
            SELECT DATE(published_at) as tgl, COUNT(*) as total
            FROM news
            WHERE (aktor LIKE ? OR aktor = ?)
              AND published_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
            GROUP BY DATE(published_at)
            ORDER BY tgl ASC
        ");
        $stmtTrend->execute(['%' . $aktorName . '%', $aktorName]);
        $rows = $stmtTrend->fetchAll(PDO::FETCH_KEY_PAIR);

        $dataPoints = [];
        foreach ($trendDates as $d) {
            $dataPoints[] = (int)($rows[$d] ?? 0);
        }
        $trendDatasets[] = [
            'label'  => $aktorName,
            'data'   => $dataPoints,
            'color'  => $chartColors[$idx % count($chartColors)],
        ];
    }

    // Find peak activity day
    $peakTotal = 0;
    $peakDate = 'N/A';
    $dateTotals = [];
    foreach ($trendDates as $d) {
        $dateTotals[$d] = 0;
    }
    foreach ($trendDatasets as $ds) {
        foreach ($trendDates as $i => $d) {
            $dateTotals[$d] += $ds['data'][$i];
        }
    }
    if (!empty($dateTotals)) {
        $peakDate = array_search(max($dateTotals), $dateTotals);
        $peakTotal = max($dateTotals);
        $peakDateFormatted = date('d M Y', strtotime($peakDate));
    } else {
        $peakDateFormatted = '-';
    }

    $totalTrendNews = array_sum(array_column($topAktor, 'total'));
    $topAktorName = !empty($topAktorNames) ? $topAktorNames[0] : '-';
    $topAktorCount = !empty($topAktor) ? $topAktor[0]['total'] : 0;

    $trendLabelsJson   = json_encode(array_map(fn($d) => date('d M', strtotime($d)), $trendDates));
    $trendDatasetsJson = json_encode(array_map(fn($ds) => [
        'label'                => $ds['label'],
        'data'                 => $ds['data'],
        'borderColor'          => $ds['color'],
        'backgroundColor'      => $ds['color'] . '1a', // 10% opacity
        'pointBackgroundColor' => '#ffffff',
        'pointBorderColor'     => $ds['color'],
        'pointBorderWidth'     => 2,
        'pointRadius'          => 4,
        'pointHoverRadius'     => 6,
        'pointHoverBorderWidth'=> 3,
        'tension'              => 0.4,
        'fill'                 => true,
        'borderWidth'          => 3,
    ], $trendDatasets));
}

// ── VIEW: TOP AKTOR ───────────────────────────────────────────────
if ($view === 'aktor') {
    try {
        $stmtAll = $pdo->query("
            SELECT TRIM(a.aktor_name) as aktor_name, COUNT(*) as total
            FROM news n
            JOIN JSON_TABLE(
                CONCAT('[', n.aktor, ']'),
                '\$[*]' COLUMNS (aktor_name VARCHAR(200) PATH '\$')
            ) a ON 1=1
            WHERE n.aktor IS NOT NULL AND n.aktor != ''
            GROUP BY TRIM(a.aktor_name)
            ORDER BY total DESC
            LIMIT 10
        ");
        $topAktor = $stmtAll->fetchAll();
    } catch (Exception $e) {
        $topAktor = [];
    }
    if (empty($topAktor)) {
        $stmtAktor2 = $pdo->query("
            SELECT aktor as aktor_name, COUNT(*) as total
            FROM news WHERE aktor IS NOT NULL AND aktor != ''
            GROUP BY aktor ORDER BY total DESC LIMIT 10
        ");
        $topAktor = $stmtAktor2->fetchAll();
    }

    // Top Aktor Negatif
    try {
        $stmtNeg = $pdo->query("
            SELECT TRIM(a.aktor_name) as aktor_name, COUNT(*) as total
            FROM news n
            JOIN JSON_TABLE(
                CONCAT('[', n.aktor, ']'),
                '\$[*]' COLUMNS (aktor_name VARCHAR(200) PATH '\$')
            ) a ON 1=1
            WHERE n.aktor IS NOT NULL AND n.aktor != '' AND n.sentiment = 'Negatif'
            GROUP BY TRIM(a.aktor_name)
            ORDER BY total DESC
            LIMIT 10
        ");
        $topAktorNegatif = $stmtNeg->fetchAll();
    } catch (Exception $e) {
        $topAktorNegatif = [];
    }

    // Top Aktor Netral
    try {
        $stmtNet = $pdo->query("
            SELECT TRIM(a.aktor_name) as aktor_name, COUNT(*) as total
            FROM news n
            JOIN JSON_TABLE(
                CONCAT('[', n.aktor, ']'),
                '\$[*]' COLUMNS (aktor_name VARCHAR(200) PATH '\$')
            ) a ON 1=1
            WHERE n.aktor IS NOT NULL AND n.aktor != '' AND n.sentiment = 'Netral'
            GROUP BY TRIM(a.aktor_name)
            ORDER BY total DESC
            LIMIT 10
        ");
        $topAktorNetral = $stmtNet->fetchAll();
    } catch (Exception $e) {
        $topAktorNetral = [];
    }
}

// ── VIEW: PETA SEBARAN LANUD ──────────────────────────────────────
if ($view === 'peta') {
    $stmtWil = $pdo->query("
        SELECT wilayah, COUNT(*) as total_news,
        SUM(CASE WHEN sentiment='Positif' THEN 1 ELSE 0 END) as positif_count,
        SUM(CASE WHEN sentiment='Negatif' THEN 1 ELSE 0 END) as negatif_count,
        SUM(CASE WHEN sentiment='Netral' THEN 1 ELSE 0 END) as netral_count
        FROM news 
        WHERE wilayah IS NOT NULL AND wilayah != ''
        GROUP BY wilayah
    ");
    $wilData = $stmtWil->fetchAll();
    $lanudCounts = [];
    foreach ($wilData as $w) {
        $lanudCounts[$w['wilayah']] = $w;
    }
}

// Titles per view
$pageTitles = [
    'berita' => 'Statistik Berita',
    'tren'   => 'Tren',
    'aktor'  => 'Top Aktor',
    'peta'   => 'Peta Sebaran Berita Lanud',
];
$pageTitle = $pageTitles[$view] ?? 'Statistik';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        /* Custom Modern Statistics Dashboard Styles */
        .stats-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
        }
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.06);
        }
        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .kpi-info {
            display: flex;
            flex-direction: column;
        }
        .kpi-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kpi-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--navy);
            margin-top: 4px;
        }
        .kpi-desc {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .chart-container-large {
            position: relative;
            height: 320px;
            width: 100%;
        }

        /* Rank Badge */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 12px;
        }
        .rank-gold { background: linear-gradient(135deg, #ffd700, #ffa500); color: #fff; box-shadow: 0 2px 6px rgba(255,215,0,0.3); }
        .rank-silver { background: linear-gradient(135deg, #c0c0c0, #a9a9a9); color: #fff; box-shadow: 0 2px 6px rgba(192,192,192,0.3); }
        .rank-bronze { background: linear-gradient(135deg, #cd7f32, #8b5a2b); color: #fff; box-shadow: 0 2px 6px rgba(205,127,50,0.3); }
        .rank-normal { background: #f1f3f5; color: var(--text-muted); }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 16px;
        }
        .stats-card {
            background: rgba(255,255,255,0.78);
            border: 1px solid rgba(255,255,255,0.45);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 14px 40px rgba(0,0,0,0.05);
            backdrop-filter: blur(16px);
        }
        .stats-card h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }
        .bar-chart-row   { margin-bottom: 16px; }
        .bar-chart-label { display:flex; justify-content:space-between; font-size:12px; font-weight:500; margin-bottom:6px; }
        .bar-chart-track { background:#f1f3f5; height:16px; border-radius:4px; overflow:hidden; }
        .bar-chart-fill  { height:100%; border-radius:4px; transition:width .8s ease-in-out; }

        /* Aktor table */
        .aktor-table { width:100%; border-collapse:collapse; font-size:13px; }
        .aktor-table th { text-align:left; padding:8px 12px; font-weight:600; color:var(--text-muted); font-size:12px; border-bottom:1px solid var(--border); }
        .aktor-table td { padding:9px 12px; border-bottom:1px solid var(--border); }
        .aktor-table tr:last-child td { border-bottom:none; }
        .aktor-table tr:hover td { background:rgba(75,116,224,0.04); }

        /* Collapsible section */
        .collapsible-header {
            display:flex; align-items:center; justify-content:space-between;
            cursor:pointer; padding:14px 20px;
            background:rgba(255,255,255,0.78);
            border:1px solid rgba(255,255,255,0.45);
            border-radius:12px;
            font-size:14px; font-weight:600;
            box-shadow:0 4px 16px rgba(0,0,0,0.04);
            backdrop-filter:blur(12px);
            user-select:none;
            margin-bottom:4px;
        }
        .collapsible-header.open { border-radius:12px 12px 0 0; margin-bottom:0; }
        .collapsible-body {
            background:rgba(255,255,255,0.78);
            border:1px solid rgba(255,255,255,0.45);
            border-top:none;
            border-radius:0 0 12px 12px;
            padding:16px 20px;
            box-shadow:0 8px 20px rgba(0,0,0,0.04);
            backdrop-filter:blur(12px);
            margin-bottom:12px;
        }
        .chevron-rotate { transition:transform .25s ease; }
        .open .chevron-rotate { transform:rotate(180deg); }

        @media (max-width:768px) {
            .stats-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="page-container">

            <div style="margin-bottom:20px;">
                <h2 style="font-size:18px;font-weight:600;"><?= e($pageTitle) ?></h2>
                <p style="color:var(--text-muted);font-size:12px;">
                    <?php if ($view==='berita'): ?>Visualisasi distribusi sentimen &amp; sumber media berita
                    <?php elseif ($view==='tren'): ?>Tren jumlah berita per aktor dalam 30 hari terakhir
                    <?php else: ?>Peringkat aktor berdasarkan frekuensi kemunculan dalam berita
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($view === 'berita'): ?>
            <?php
            $negatifPct = $totalSent > 0 ? round(($sentStats['Negatif'] / $totalSent) * 100) : 0;
            $netralPct  = $totalSent > 0 ? round(($sentStats['Netral']  / $totalSent) * 100) : 0;
            $positifPct = $totalSent > 0 ? round(($sentStats['Positif'] / $totalSent) * 100) : 0;
            ?>
            
            <!-- Grid KPI -->
            <div class="stats-kpi-grid">
                <div class="kpi-card" style="border-left: 4px solid #ef4444;">
                    <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/></svg>
                    </div>
                    <div class="kpi-info">
                        <span class="kpi-label">Sentimen Negatif</span>
                        <span class="kpi-value"><?= $sentStats['Negatif'] ?></span>
                        <span class="kpi-desc"><?= $negatifPct ?>% dari sebaran</span>
                    </div>
                </div>
                <div class="kpi-card" style="border-left: 4px solid #3b82f6;">
                    <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="15" x2="16" y2="15"></line><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                    </div>
                    <div class="kpi-info">
                        <span class="kpi-label">Sentimen Netral</span>
                        <span class="kpi-value"><?= $sentStats['Netral'] ?></span>
                        <span class="kpi-desc"><?= $netralPct ?>% dari sebaran</span>
                    </div>
                </div>
                <div class="kpi-card" style="border-left: 4px solid #10b981;">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                    </div>
                    <div class="kpi-info">
                        <span class="kpi-label">Sentimen Positif</span>
                        <span class="kpi-value"><?= $sentStats['Positif'] ?></span>
                        <span class="kpi-desc"><?= $positifPct ?>% dari sebaran</span>
                    </div>
                </div>
                <div class="kpi-card" style="border-left: 4px solid #6c5ce7;">
                    <div class="kpi-icon" style="background: rgba(108, 92, 231, 0.1); color: #6c5ce7;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16l-2 2z"/><line x1="14" y1="8" x2="18" y2="8"/><line x1="14" y1="12" x2="18" y2="12"/><line x1="10" y1="16" x2="18" y2="16"/></svg></div>
                    <div class="kpi-info">
                        <span class="kpi-label">Total Publikasi</span>
                        <span class="kpi-value"><?= $totalSent ?></span>
                        <span class="kpi-desc">100% data termonitor</span>
                    </div>
                </div>
            </div>

            <!-- Grid Grafik -->
            <div class="stats-grid">
                <!-- Doughnut Chart Sentimen -->
                <div class="stats-card">
                    <h3 style="font-size:15px;color:var(--navy);font-weight:600;margin-bottom:15px;border-bottom:1px solid #f1f3f5;padding-bottom:10px;display:flex;align-items:center;gap:7px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--blue);"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg> Distribusi Sentimen Publik</h3>
                    <div class="chart-container-large" style="height: 240px;">
                        <canvas id="sentimentDoughnutChart"></canvas>
                    </div>
                </div>

                <!-- Doughnut Chart Sumber Media -->
                <div class="stats-card">
                    <h3 style="font-size:15px;color:var(--navy);font-weight:600;margin-bottom:15px;border-bottom:1px solid #f1f3f5;padding-bottom:10px;display:flex;align-items:center;gap:7px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--blue);"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg> Proporsi Sumber Media Berita</h3>
                    <div class="chart-container-large" style="height: 240px;">
                        <canvas id="mediaDoughnutChart"></canvas>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Chart 1: Sentiment Doughnut
                const sentimentCtx = document.getElementById('sentimentDoughnutChart').getContext('2d');
                new Chart(sentimentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Positif', 'Netral', 'Negatif'],
                        datasets: [{
                            data: [<?= $sentStats['Positif'] ?>, <?= $sentStats['Netral'] ?>, <?= $sentStats['Negatif'] ?>],
                            backgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { 
                                position: 'bottom', 
                                labels: { 
                                    font: { family: 'Poppins', size: 11, weight: '500' }, 
                                    boxWidth: 10, 
                                    padding: 12,
                                    color: '#4a5568'
                                } 
                            },
                            tooltip: { 
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                titleFont: { family: 'Poppins', size: 12 },
                                bodyFont: { family: 'Poppins', size: 12 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: { 
                                    label: function(context) { 
                                        return ' ' + context.label + ': ' + context.raw + ' Berita (' + Math.round(context.raw / <?= max(1, $totalSent) ?> * 100) + '%)'; 
                                    } 
                                } 
                            }
                        },
                        cutout: '70%'
                    }
                });

                // Chart 2: Media Source Doughnut
                const mediaCtx = document.getElementById('mediaDoughnutChart').getContext('2d');
                new Chart(mediaCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Berita Wilayah', 'Media Online', 'Media Sosial'],
                        datasets: [{
                            data: [<?= $mediaStats['Wilayah'] ?>, <?= $mediaStats['Media Online'] ?>, <?= $mediaStats['Media Sosial'] ?>],
                            backgroundColor: ['#4b74e0', '#a55eea', '#ff7675'],
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { 
                                position: 'bottom', 
                                labels: { 
                                    font: { family: 'Poppins', size: 11, weight: '500' }, 
                                    boxWidth: 10, 
                                    padding: 12,
                                    color: '#4a5568'
                                } 
                            },
                            tooltip: { 
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                titleFont: { family: 'Poppins', size: 12 },
                                bodyFont: { family: 'Poppins', size: 12 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: { 
                                    label: function(context) { 
                                        return ' ' + context.label + ': ' + context.raw + ' Berita (' + Math.round(context.raw / <?= max(1, $totalMedia) ?> * 100) + '%)'; 
                                    } 
                                } 
                            }
                        },
                        cutout: '70%'
                    }
                });
            });
            </script>
            <?php endif; ?>


            <?php /* ====================================================
                   VIEW: TREN
                   ==================================================== */ ?>
            <?php if ($view === 'tren'): ?>
            <!-- Grid KPI Aktor Tren -->
            <div class="stats-kpi-grid">
                <div class="kpi-card" style="border-left: 4px solid #4b74e0;">
                    <div class="kpi-icon" style="background: rgba(75, 116, 224, 0.1); color: #4b74e0;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
                    <div class="kpi-info">
                        <span class="kpi-label">Total Aktivitas Tren</span>
                        <span class="kpi-value"><?= $totalTrendNews ?></span>
                        <span class="kpi-desc">Kemunculan aktor top</span>
                    </div>
                </div>
                <div class="kpi-card" style="border-left: 4px solid #10b981;">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
                    <div class="kpi-info">
                        <span class="kpi-label">Aktor Terpopuler</span>
                        <span class="kpi-value" style="font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;display:block;" title="<?= e($topAktorName) ?>"><?= e($topAktorName) ?></span>
                        <span class="kpi-desc"><?= $topAktorCount ?> kali dalam berita</span>
                    </div>
                </div>
                <div class="kpi-card" style="border-left: 4px solid #f1b72c;">
                    <div class="kpi-icon" style="background: rgba(241, 183, 44, 0.1); color: #f1b72c;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                    <div class="kpi-info">
                        <span class="kpi-label">Puncak Publikasi</span>
                        <span class="kpi-value"><?= $peakDateFormatted ?></span>
                        <span class="kpi-desc"><?= $peakTotal ?> berita terbit</span>
                    </div>
                </div>
            </div>

            <div class="stats-card" style="margin-bottom:20px;">
                <h3 style="font-size:15px;color:var(--navy);font-weight:600;margin-bottom:15px;border-bottom:1px solid #f1f3f5;padding-bottom:10px;display:flex;align-items:center;gap:7px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--blue);"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg> Tren Berita per Aktor (30 Hari Terakhir)</h3>
                <div style="position:relative;height:350px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
            <script>
            (function(){
                var ctx = document.getElementById('trendChart');
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels:   <?= $trendLabelsJson ?>,
                        datasets: <?= $trendDatasetsJson ?>
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode:'index', intersect:false },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { 
                                    boxWidth:12, 
                                    font:{ family: 'Poppins', size:11, weight:'500' }, 
                                    padding:16,
                                    color: '#4a5568'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                titleFont: { family: 'Poppins', size: 12 },
                                bodyFont: { family: 'Poppins', size: 11 },
                                padding: 10,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            x: {
                                ticks: { font:{ family: 'Poppins', size:10 }, maxRotation:45, autoSkip:true, maxTicksLimit:15 },
                                grid:  { color:'rgba(0,0,0,0.04)' }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize:1, font:{ family: 'Poppins', size:11 } },
                                grid:  { color:'rgba(0,0,0,0.04)' }
                            }
                        }
                    }
                });
            })();
            </script>
            <?php endif; ?>


            <?php /* ====================================================
                   VIEW: TOP AKTOR
                   ==================================================== */ ?>
            <?php if ($view === 'aktor'): ?>

            <!-- ── Aktor (Semua Sentimen) ── -->
            <?php
            function renderAktorTable(array $rows, array $colors): void {
                if (empty($rows)) {
                    echo '<p style="color:var(--text-muted);padding:16px 0;text-align:center;font-size:13px;">Belum ada data aktor.</p>';
                    return;
                }
                $max = max(array_column($rows, 'total')) ?: 1;
                echo '<table class="aktor-table" style="width:100%;border-collapse:collapse;margin-top:8px;">';
                echo '<thead><tr style="border-bottom:2px solid #edf2f7;">
                        <th style="width:50px;padding:12px;font-weight:600;color:#718096;text-align:center;">Pos</th>
                        <th style="padding:12px;font-weight:600;color:#718096;text-align:left;">Nama Aktor</th>
                        <th style="width:100px;padding:12px;font-weight:600;color:#718096;text-align:center;">Frekuensi</th>
                        <th style="padding:12px;font-weight:600;color:#718096;text-align:left;">Sebaran Persentase</th>
                      </tr></thead><tbody>';
                foreach ($rows as $i => $row) {
                    $pct   = round(($row['total'] / $max) * 100);
                    $color = $colors[$i % count($colors)];
                    
                    // Rank Badge
                    $rankClass = 'rank-normal';
                    $rankText  = $i + 1;
                    if ($i === 0) { $rankClass = 'rank-gold'; $rankText = '1'; }
                    elseif ($i === 1) { $rankClass = 'rank-silver'; $rankText = '2'; }
                    elseif ($i === 2) { $rankClass = 'rank-bronze'; $rankText = '3'; }

                    echo "<tr style=\"border-bottom:1px solid #edf2f7;transition:background 0.2s ease;\">
                            <td style=\"text-align:center;padding:12px;\"><span class=\"rank-badge {$rankClass}\">{$rankText}</span></td>
                            <td style=\"padding:12px;font-weight:600;color:#2d3748;font-size:13.5px;\">" . htmlspecialchars($row['aktor_name'], ENT_QUOTES) . "</td>
                            <td style=\"text-align:center;padding:12px;font-weight:700;color:var(--navy);font-size:14px;\">" . (int)$row['total'] . "</td>
                            <td style=\"padding:12px;\">
                                <div style=\"display:flex;align-items:center;gap:12px;\">
                                    <div style=\"background:#e2e8f0;height:10px;border-radius:5px;flex:1;overflow:hidden;\">
                                        <div style=\"height:100%;width:{$pct}%;background:{$color};border-radius:5px;transition:width .8s ease-in-out;\"></div>
                                    </div>
                                    <span style=\"font-size:11px;font-weight:600;color:#718096;width:32px;\">{$pct}%</span>
                                </div>
                            </td>
                          </tr>";
                }
                echo '</tbody></table>';
            }
            ?>

            <div class="stats-card" style="margin-bottom:24px;">
                <h3 style="font-size:15px;color:var(--navy);font-weight:600;margin-bottom:15px;border-bottom:1px solid #f1f3f5;padding-bottom:10px;display:flex;align-items:center;gap:7px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--blue);"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Grafik Frekuensi Aktor Utama</h3>
                <div class="chart-container-large" style="height:320px;">
                    <canvas id="topAktorBarChart"></canvas>
                </div>
            </div>

            <div class="stats-card" style="margin-bottom:24px;">
                <h3 style="font-size:15px;color:var(--navy);font-weight:600;margin-bottom:15px;border-bottom:1px solid #f1f3f5;padding-bottom:10px;display:flex;align-items:center;gap:7px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--gold);"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Peringkat Aktor Utama (Semua Sentimen)</h3>
                <?php renderAktorTable($topAktor, $chartColors); ?>
            </div>

            <!-- Collapsible Section Aktor Negatif -->
            <div style="margin-bottom:12px;">
                <div class="collapsible-header" onclick="toggleCollapse(this)" style="display:flex;justify-content:space-between;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.02)">
                    <div style="display:flex;align-items:center;">
                        <span style="font-weight:700;color:#c53030;">Aktor Terkait Sentimen Negatif</span>
                        <span style="background:#e53e3e;color:#fff;padding:2px 8px;border-radius:20px;font-size:11px;margin-left:10px;font-weight:700;"><?= count($topAktorNegatif) ?> Aktor</span>
                    </div>
                    <svg class="chevron-rotate" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <div class="collapsible-body" style="display:none;background:#fff;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:20px;box-shadow:0 8px 24px rgba(0,0,0,0.04)">
                    <?php renderAktorTable($topAktorNegatif, $chartColors); ?>
                </div>
            </div>

            <!-- Collapsible Section Aktor Netral -->
            <div style="margin-bottom:24px;">
                <div class="collapsible-header" onclick="toggleCollapse(this)" style="display:flex;justify-content:space-between;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.02)">
                    <div style="display:flex;align-items:center;">
                        <span style="font-weight:700;color:#dd6b20;">Aktor Terkait Sentimen Netral</span>
                        <span style="background:#dd6b20;color:#fff;padding:2px 8px;border-radius:20px;font-size:11px;margin-left:10px;font-weight:700;"><?= count($topAktorNetral) ?> Aktor</span>
                    </div>
                    <svg class="chevron-rotate" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <div class="collapsible-body" style="display:none;background:#fff;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:20px;box-shadow:0 8px 24px rgba(0,0,0,0.04)">
                    <?php renderAktorTable($topAktorNetral, $chartColors); ?>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const actorCtx = document.getElementById('topAktorBarChart').getContext('2d');
                new Chart(actorCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($topAktor, 'aktor_name')) ?>,
                        datasets: [{
                            label: 'Kemunculan',
                            data: <?= json_encode(array_column($topAktor, 'total')) ?>,
                            backgroundColor: ['#4b74e0', '#3b82f6', '#10b981', '#f1b72c', '#a55eea', '#ff7675', '#00b894', '#fd79a8', '#0984e3', '#6c5ce7'],
                            borderRadius: 6,
                            borderWidth: 0,
                            barPercentage: 0.5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { 
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                titleFont: { family: 'Poppins', size: 12 },
                                bodyFont: { family: 'Poppins', size: 12 },
                                padding: 10,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            x: { 
                                ticks: { font: { family: 'Poppins', size: 10, weight: '500' }, maxRotation: 25 }, 
                                grid: { display: false } 
                            },
                            y: { 
                                beginAtZero: true, 
                                ticks: { stepSize: 1, font: { family: 'Poppins', size: 11 } }, 
                                grid: { color: 'rgba(0,0,0,0.04)' } 
                            }
                        }
                    }
                });
            });

            function toggleCollapse(header) {
                var body = header.nextElementSibling;
                var open = body.style.display !== 'none';
                body.style.display = open ? 'none' : 'block';
                header.classList.toggle('open', !open);
                if (!open) {
                    header.style.borderRadius = "12px 12px 0 0";
                } else {
                    header.style.borderRadius = "12px";
                }
            }
            </script>
            <?php endif; ?>

            <?php if ($view === 'peta'): ?>
            <?php
            $lanudsData = [
                ['name' => 'Lanud Halim Perdanakusuma', 'city' => 'Jakarta Timur', 'lat' => -6.2655, 'lng' => 106.8906, 'koopsud' => 'Koopsud I'],
                ['name' => 'Lanud Atang Sendjaja', 'city' => 'Bogor', 'lat' => -6.5414, 'lng' => 106.7515, 'koopsud' => 'Koopsud I'],
                ['name' => 'Lanud Roesmin Nurjadin', 'city' => 'Pekanbaru', 'lat' => 0.4611, 'lng' => 101.4444, 'koopsud' => 'Koopsud I'],
                ['name' => 'Lanud Supadio', 'city' => 'Pontianak', 'lat' => -0.1500, 'lng' => 109.4039, 'koopsud' => 'Koopsud I'],
                ['name' => 'Lanud Soewondo', 'city' => 'Medan', 'lat' => 3.5583, 'lng' => 98.6750, 'koopsud' => 'Koopsud I'],
                ['name' => 'Lanud Suryadarma', 'city' => 'Subang', 'lat' => -6.5392, 'lng' => 107.6719, 'koopsud' => 'Koopsud I'],
                ['name' => 'Lanud Iswahjudi', 'city' => 'Madiun', 'lat' => -7.6167, 'lng' => 111.4333, 'koopsud' => 'Koopsud II'],
                ['name' => 'Lanud Abdulrachman Saleh', 'city' => 'Malang', 'lat' => -7.9269, 'lng' => 112.7144, 'koopsud' => 'Koopsud II'],
                ['name' => 'Lanud Sultan Hasanuddin', 'city' => 'Makassar', 'lat' => -5.0614, 'lng' => 119.5542, 'koopsud' => 'Koopsud II'],
                ['name' => 'Lanud Sam Ratulangi', 'city' => 'Manado', 'lat' => 1.5494, 'lng' => 124.9264, 'koopsud' => 'Koopsud II'],
                ['name' => 'Lanud Dhomber', 'city' => 'Balikpapan', 'lat' => -1.2683, 'lng' => 116.8944, 'koopsud' => 'Koopsud II'],
                ['name' => 'Lanud El Tari', 'city' => 'Kupang', 'lat' => -10.1714, 'lng' => 123.6706, 'koopsud' => 'Koopsud II'],
                ['name' => 'Lanud Adisutjipto', 'city' => 'Yogyakarta', 'lat' => -7.7881, 'lng' => 110.4319, 'koopsud' => 'Kodiklatau'],
                ['name' => 'Lanud Adi Soemarmo', 'city' => 'Solo', 'lat' => -7.5161, 'lng' => 110.7567, 'koopsud' => 'Kodiklatau'],
                ['name' => 'Lanud Silas Papare', 'city' => 'Jayapura', 'lat' => -2.5769, 'lng' => 140.5161, 'koopsud' => 'Koopsud III'],
                ['name' => 'Lanud Manuhua', 'city' => 'Biak', 'lat' => -1.1903, 'lng' => 136.1083, 'koopsud' => 'Koopsud III'],
                ['name' => 'Lanud Pattimura', 'city' => 'Ambon', 'lat' => -3.7103, 'lng' => 128.0894, 'koopsud' => 'Koopsud III'],
                ['name' => 'Lanud Johanes Abraham Dimara', 'city' => 'Merauke', 'lat' => -8.5203, 'lng' => 140.4181, 'koopsud' => 'Koopsud III'],
            ];

            $geoJsonPoints = [];
            foreach ($lanudsData as $l) {
                $stat = $lanudCounts[$l['name']] ?? ['total_news' => 0, 'positif_count' => 0, 'negatif_count' => 0, 'netral_count' => 0];
                $geoJsonPoints[] = [
                    'name'    => $l['name'],
                    'city'    => $l['city'],
                    'lat'     => $l['lat'],
                    'lng'     => $l['lng'],
                    'koopsud' => $l['koopsud'],
                    'total'   => (int)$stat['total_news'],
                    'positif' => (int)$stat['positif_count'],
                    'negatif' => (int)$stat['negatif_count'],
                ];
            }
            ?>

            <div class="stats-card" style="margin-bottom:24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="margin:0; border:none; padding:0; font-size:16px; color:#1e293b;">Peta Sebaran Publikasi Berita Jajaran Lanud TNI AU</h3>
                    <div style="font-size:12px; color:#64748b;">Klik penanda pada peta untuk melihat detail pangkalan udara</div>
                </div>
                <div id="lanudMap" style="height: 520px; width: 100%; border-radius: 12px; z-index: 1;"></div>
            </div>

            <div class="card" style="padding:20px;">
                <h4 style="margin-top:0; margin-bottom:14px; font-size:15px; color:#1e293b;">Rekapitulasi Berita per Wilayah Lanud</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pangkalan Udara (Lanud)</th>
                                <th>Komando Operasi (Koopsud)</th>
                                <th>Total Publikasi</th>
                                <th>Sentimen Positif</th>
                                <th>Sentimen Negatif</th>
                                <th>Status Keaktifan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($geoJsonPoints as $pt): ?>
                                <tr>
                                    <td><strong><?= e($pt['name']) ?></strong> <span style="font-size:11px; color:#64748b;">(<?= e($pt['city']) ?>)</span></td>
                                    <td><span class="badge" style="background:#e0f2fe; color:#0369a1;"><?= e($pt['koopsud']) ?></span></td>
                                    <td><strong><?= $pt['total'] ?></strong> Berita</td>
                                    <td><span style="color:#16a34a; font-weight:600;"><?= $pt['positif'] ?></span></td>
                                    <td><span style="color:#dc2626; font-weight:600;"><?= $pt['negatif'] ?></span></td>
                                    <td>
                                        <?php if ($pt['total'] > 0): ?>
                                            <span style="display:inline-flex; align-items:center; gap:5px; color:#16a34a; font-size:12px; font-weight:600;">
                                                <span style="width:7px; height:7px; background:#16a34a; border-radius:50%;"></span> Aktif Rilis
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:12px;">Belum Ada Rilis</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var map = L.map('lanudMap').setView([-2.5489, 118.0149], 5);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '© OpenStreetMap contributors | Portal Berita TNI AU'
                }).addTo(map);

                var points = <?= json_encode($geoJsonPoints) ?>;
                points.forEach(function(pt) {
                    var color = pt.total > 0 ? '#10b981' : '#3b82f6';
                    var circle = L.circleMarker([pt.lat, pt.lng], {
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.8,
                        radius: pt.total > 0 ? 10 + Math.min(pt.total * 2, 16) : 7
                    }).addTo(map);

                    var popupHtml = '<div style="font-family:sans-serif; min-width:180px;">' +
                        '<strong style="font-size:13px; color:#1e293b;">' + pt.name + '</strong><br>' +
                        '<span style="font-size:11px; color:#64748b;">' + pt.koopsud + ' - ' + pt.city + '</span>' +
                        '<hr style="margin:8px 0; border:none; border-top:1px solid #e2e8f0;">' +
                        '<div style="font-size:12px;"><strong>Total Rilis:</strong> ' + pt.total + ' Berita</div>' +
                        '<div style="font-size:11px; color:#16a34a;">Positif: ' + pt.positif + '</div>' +
                        '<div style="font-size:11px; color:#dc2626;">Negatif: ' + pt.negatif + '</div>' +
                        '</div>';
                    circle.bindPopup(popupHtml);
                });
            });
            </script>
            <?php endif; ?>

        </div><!-- /page-container -->
    </main>
</div>
</body>
</html>
