<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'report';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Monitoring — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .report-page-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 120px);
            padding: 40px 20px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .report-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        .report-header p {
            font-size: 13.5px;
            color: var(--text-sec);
        }

        .report-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 32px;
            width: 100%;
            max-width: 600px;
            border: 1px solid var(--border-light);
        }

        .report-filter-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        .report-filter-row label {
            width: 100px;
            font-size: 13px;
            color: var(--text);
            font-weight: 500;
        }
        .report-filter-row select {
            flex: 1;
            height: 36px;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0 12px;
            font-size: 13px;
            color: var(--text);
            outline: none;
            transition: border-color var(--transition);
        }
        .report-filter-row select:focus {
            border-color: var(--blue);
        }

        .report-alert {
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 12px;
            line-height: 1.5;
            color: var(--text-sec);
            margin-bottom: 24px;
            display: flex;
            gap: 10px;
        }
        .report-alert span.icon {
            font-size: 14px;
        }

        .btn-download-wide {
            display: block;
            width: 100%;
            background: #f8f9fa;
            border: 1px solid var(--border);
            color: var(--text);
            text-align: center;
            padding: 12px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
        }
        .btn-download-wide:hover {
            background: #eef2f5;
            border-color: #d1d5da;
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- TOP NAVBAR MATCHING SCREENSHOT -->
        <div class="top-navbar" style="height:56px">
            <div class="top-navbar-left">
                <button class="hamburger-btn">☰</button>
                <div class="media-tabs">
                    <span class="media-tab-item active" style="border:none">Ekspor Laporan</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <div class="user-dropdown-btn">
                    👤 <?= e($user['full_name']) ?> (<?= e(['A'=>'Reporter','B'=>'Editor','C'=>'Petinggi'][$user['role']]) ?>) ▼
                </div>
            </div>
        </div>

        <div class="page-container" style="background:var(--bg-body)">
            <div class="report-page-container">
                <div class="report-header">
                    <h2>Unduh Laporan Berita</h2>
                    <p>Saring data berita termonitor dan unduh dalam format CSV / Excel</p>
                </div>

                <div class="report-card">
                    <form action="<?= BASE_URL ?>/export_csv.php" method="GET">
                        <div class="report-filter-row">
                            <label>Filter Media</label>
                            <select name="media">
                                <option value="">Semua Sumber</option>
                                <option value="Wilayah">Berita Wilayah</option>
                                <option value="Media Online">Media Online</option>
                                <option value="Media Sosial">Media Sosial</option>
                            </select>
                        </div>
                        <div class="report-filter-row">
                            <label>Filter Sentimen</label>
                            <select name="sentiment">
                                <option value="">Semua Sentimen</option>
                                <option value="Positif">Positif</option>
                                <option value="Negatif">Negatif</option>
                                <option value="Netral">Netral</option>
                            </select>
                        </div>

                        <div class="report-alert">
                            <span class="icon">📌</span>
                            <div>Format ekspor berupa <strong>Comma Separated Values (.csv)</strong> dengan pengodean UTF-8 yang kompatibel dengan Microsoft Excel, Google Sheets, dan aplikasi spreadsheet lainnya.</div>
                        </div>

                        <button type="submit" class="btn-download-wide">
                            📥 Unduh Laporan (CSV)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
