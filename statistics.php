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

    $trendLabelsJson   = json_encode(array_map(fn($d) => date('d M', strtotime($d)), $trendDates));
    $trendDatasetsJson = json_encode(array_map(fn($ds) => [
        'label'                => $ds['label'],
        'data'                 => $ds['data'],
        'borderColor'          => $ds['color'],
        'backgroundColor'      => $ds['color'] . '22',
        'pointBackgroundColor' => $ds['color'],
        'pointRadius'          => 3,
        'pointHoverRadius'     => 5,
        'tension'              => 0.4,
        'fill'                 => false,
        'borderWidth'          => 2,
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

// Titles per view
$pageTitles = [
    'berita' => 'Statistik Berita',
    'tren'   => 'Tren',
    'aktor'  => 'Top Aktor',
];
$pageTitle = $pageTitles[$view] ?? 'Statistik';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <?php if (in_array($view, ['tren'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    <style>
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

            <?php /* ====================================================
                   VIEW: STATISTIK BERITA
                   ==================================================== */ ?>
            <?php if ($view === 'berita'): ?>
            <?php
            $negatifPct = $totalSent > 0 ? round(($sentStats['Negatif'] / $totalSent) * 100) : 0;
            $netralPct  = $totalSent > 0 ? round(($sentStats['Netral']  / $totalSent) * 100) : 0;
            $positifPct = $totalSent > 0 ? round(($sentStats['Positif'] / $totalSent) * 100) : 0;
            ?>
            <div class="sentiment-grid">
                <div class="sentiment-card negatif">
                    <div class="card-label">Negatif</div>
                    <div class="card-row">
                        <span class="card-count"><?= $sentStats['Negatif'] ?></span>
                        <span class="card-pct"><?= $negatifPct ?>%</span>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= $negatifPct ?>%"></div></div>
                </div>
                <div class="sentiment-card netral">
                    <div class="card-label">Netral</div>
                    <div class="card-row">
                        <span class="card-count"><?= $sentStats['Netral'] ?></span>
                        <span class="card-pct"><?= $netralPct ?>%</span>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= $netralPct ?>%"></div></div>
                </div>
                <div class="sentiment-card positif">
                    <div class="card-label">Positif</div>
                    <div class="card-row">
                        <span class="card-count"><?= $sentStats['Positif'] ?></span>
                        <span class="card-pct"><?= $positifPct ?>%</span>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= $positifPct ?>%"></div></div>
                </div>
                <div class="sentiment-card total">
                    <div class="card-label">Total</div>
                    <div class="card-row">
                        <span class="card-count"><?= $totalSent ?></span>
                        <span class="card-pct">100%</span>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:100%"></div></div>
                </div>
            </div>

            <div class="stats-grid">
                <!-- Distribusi Sentimen -->
                <div class="stats-card">
                    <h3>Distribusi Sentimen Berita</h3>
                    <?php foreach ($sentStats as $label => $val):
                        $pct   = $totalSent > 0 ? round(($val / $totalSent) * 100) : 0;
                        $color = '#4b74e0';
                        if ($label === 'Positif') $color = '#47b275';
                        if ($label === 'Negatif') $color = '#e2583e';
                        if ($label === 'Netral')  $color = '#f1b72c';
                    ?>
                    <div class="bar-chart-row">
                        <div class="bar-chart-label">
                            <span><?= $label ?> (<?= $val ?> Berita)</span>
                            <strong><?= $pct ?>%</strong>
                        </div>
                        <div class="bar-chart-track">
                            <div class="bar-chart-fill" style="width:<?= $pct ?>%;background-color:<?= $color ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sumber Media -->
                <div class="stats-card">
                    <h3>Sumber Media Berita</h3>
                    <?php foreach ($mediaStats as $label => $val):
                        $pct   = $totalMedia > 0 ? round(($val / $totalMedia) * 100) : 0;
                        $color = '#4b74e0';
                        if ($label === 'Media Online')  $color = '#a55eea';
                        if ($label === 'Media Sosial')  $color = '#ff7675';
                    ?>
                    <div class="bar-chart-row">
                        <div class="bar-chart-label">
                            <span><?= $label ?> (<?= $val ?> Berita)</span>
                            <strong><?= $pct ?>%</strong>
                        </div>
                        <div class="bar-chart-track">
                            <div class="bar-chart-fill" style="width:<?= $pct ?>%;background-color:<?= $color ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>


            <?php /* ====================================================
                   VIEW: TREN
                   ==================================================== */ ?>
            <?php if ($view === 'tren'): ?>
            <div class="stats-card" style="margin-bottom:20px;">
                <h3>Tren Berita per Aktor (30 Hari Terakhir)</h3>
                <div style="position:relative;height:300px;">
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
                                labels: { boxWidth:10, font:{size:11}, padding:14 }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { font:{size:10}, maxRotation:45, autoSkip:true, maxTicksLimit:15 },
                                grid:  { color:'rgba(0,0,0,0.05)' }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize:1, font:{size:11} },
                                grid:  { color:'rgba(0,0,0,0.05)' }
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
                    echo '<p style="color:var(--text-muted);padding:12px 0;">Belum ada data aktor.</p>';
                    return;
                }
                $max = max(array_column($rows, 'total')) ?: 1;
                echo '<table class="aktor-table">';
                echo '<thead><tr>
                        <th style="width:36px;">No</th>
                        <th>Nama</th>
                        <th style="text-align:right;width:70px;">Jumlah</th>
                        <th style="width:180px;">Chart</th>
                      </tr></thead><tbody>';
                foreach ($rows as $i => $row) {
                    $pct   = round(($row['total'] / $max) * 100);
                    $color = $colors[$i % count($colors)];
                    $bg    = $i % 2 === 1 ? 'background:rgba(0,0,0,0.015);' : '';
                    echo "<tr style=\"$bg\">
                            <td style=\"color:var(--text-muted);\">" . ($i + 1) . "</td>
                            <td style=\"font-weight:500;\">" . htmlspecialchars($row['aktor_name'], ENT_QUOTES) . "</td>
                            <td style=\"text-align:right;font-weight:600;\">" . (int)$row['total'] . "</td>
                            <td>
                                <div style=\"background:#f1f3f5;height:12px;border-radius:3px;overflow:hidden;\">
                                    <div style=\"height:100%;width:{$pct}%;background:{$color};border-radius:3px;transition:width .6s ease;\"></div>
                                </div>
                            </td>
                          </tr>";
                }
                echo '</tbody></table>';
            }
            ?>

            <div class="stats-card" style="margin-bottom:16px;">
                <h3>Aktor</h3>
                <?php renderAktorTable($topAktor, $chartColors); ?>
            </div>

            <!-- ── Aktor Negatif (collapsible) ── -->
            <div>
                <div class="collapsible-header" onclick="toggleCollapse(this)">
                    <span>Aktor Negatif</span>
                    <svg class="chevron-rotate" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <div class="collapsible-body" style="display:none;">
                    <?php renderAktorTable($topAktorNegatif, $chartColors); ?>
                </div>
            </div>

            <!-- ── Aktor Netral (collapsible) ── -->
            <div style="margin-top:8px;">
                <div class="collapsible-header" onclick="toggleCollapse(this)">
                    <span>Aktor Netral</span>
                    <svg class="chevron-rotate" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <div class="collapsible-body" style="display:none;">
                    <?php renderAktorTable($topAktorNetral, $chartColors); ?>
                </div>
            </div>

            <script>
            function toggleCollapse(header) {
                var body = header.nextElementSibling;
                var open = body.style.display !== 'none';
                body.style.display = open ? 'none' : 'block';
                header.classList.toggle('open', !open);
            }
            </script>
            <?php endif; ?>

        </div><!-- /page-container -->
    </main>
</div>
</body>
</html>
