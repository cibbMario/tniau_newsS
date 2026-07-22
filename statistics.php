<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();
$current = 'statistics';

// Fetch stats for charts
// 1. Sentiment stats
$stmt = $pdo->query("SELECT sentiment, COUNT(*) as c FROM news GROUP BY sentiment");
$sentimentData = $stmt->fetchAll();
$sentStats = ['Positif' => 0, 'Negatif' => 0, 'Netral' => 0];
$totalSent = 0;
foreach ($sentimentData as $row) {
    if (isset($sentStats[$row['sentiment']])) {
        $sentStats[$row['sentiment']] = (int)$row['c'];
        $totalSent += (int)$row['c'];
    }
}

// 2. Media source stats
$stmt = $pdo->query("SELECT media, COUNT(*) as c FROM news GROUP BY media");
$mediaData = $stmt->fetchAll();
$mediaStats = ['Wilayah' => 0, 'Media Online' => 0, 'Media Sosial' => 0];
$totalMedia = 0;
foreach ($mediaData as $row) {
    if (isset($mediaStats[$row['media']])) {
        $mediaStats[$row['media']] = (int)$row['c'];
        $totalMedia += (int)$row['c'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Statistik Monitoring - Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 16px;
        }
        .stats-card {
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .stats-card h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }
        .bar-chart-row {
            margin-bottom: 16px;
        }
        .bar-chart-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .bar-chart-track {
            background: #f1f3f5;
            height: 16px;
            border-radius: 4px;
            overflow: hidden;
        }
        .bar-chart-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.8s ease-in-out;
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-container">
            <div style="margin-bottom: 20px;">
                <h2 style="font-size:18px; font-weight:600;">Statistik Ringkasan</h2>
                <p style="color:var(--text-muted); font-size:12px;">Visualisasi data dan sentimen berita termonitor</p>
            </div>

            <div class="stats-grid">
                <!-- Sentiment Statistics -->
                <div class="stats-card">
                    <h3>Distribusi Sentimen Berita</h3>
                    <?php foreach ($sentStats as $label => $val): 
                        $pct = $totalSent > 0 ? round(($val / $totalSent) * 100) : 0;
                        $color = 'var(--blue)';
                        if ($label === 'Positif') $color = '#47b275';
                        if ($label === 'Negatif') $color = '#e2583e';
                        if ($label === 'Netral') $color = '#f1b72c';
                    ?>
                        <div class="bar-chart-row">
                            <div class="bar-chart-label">
                                <span><?= $label ?> (<?= $val ?> Berita)</span>
                                <strong><?= $pct ?>%</strong>
                            </div>
                            <div class="bar-chart-track">
                                <div class="bar-chart-fill" style="width: <?= $pct ?>%; background-color: <?= $color ?>;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Media Source Statistics -->
                <div class="stats-card">
                    <h3>Sumber Media Berita</h3>
                    <?php foreach ($mediaStats as $label => $val): 
                        $pct = $totalMedia > 0 ? round(($val / $totalMedia) * 100) : 0;
                        $color = '#4b74e0';
                        if ($label === 'Media Online') $color = '#a55eea';
                        if ($label === 'Media Sosial') $color = '#ff7675';
                    ?>
                        <div class="bar-chart-row">
                            <div class="bar-chart-label">
                                <span><?= $label ?> (<?= $val ?> Berita)</span>
                                <strong><?= $pct ?>%</strong>
                            </div>
                            <div class="bar-chart-track">
                                <div class="bar-chart-fill" style="width: <?= $pct ?>%; background-color: <?= $color ?>;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
