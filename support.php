<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'support';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan & Dukungan — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .support-page-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 120px);
            padding: 40px 20px;
        }
        
        .support-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .support-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        .support-header p {
            font-size: 13.5px;
            color: var(--text-sec);
            max-width: 500px;
            margin: 0 auto;
        }

        .support-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 600px;
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .support-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--border-light);
            transition: background var(--transition);
            cursor: pointer;
        }
        .support-item:last-child {
            border-bottom: none;
        }
        .support-item:hover {
            background: #f9f9f9;
        }
        
        .support-icon {
            font-size: 24px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eaf2f8;
            border-radius: 12px;
            margin-right: 16px;
        }
        .support-content {
            flex: 1;
        }
        .support-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }
        .support-desc {
            font-size: 12px;
            color: var(--text-sec);
        }
        .support-arrow {
            font-size: 16px;
            color: #c7c7cc;
        }

        .btn-contact {
            display: block;
            width: 100%;
            background: var(--blue);
            color: #fff;
            text-align: center;
            padding: 12px;
            border: none;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
        }
        .btn-contact:hover {
            background: #005bb5;
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="top-navbar" style="height:56px">
            <div class="top-navbar-left">
                <button class="hamburger-btn" title="Toggle Menu">&#9776; Menu</button>
                <div class="media-tabs">
                    <span class="media-tab-item active" style="border:none">Bantuan & Dukungan</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <div class="user-dropdown-btn">
                    <?= e($user['full_name']) ?>
                </div>
            </div>
        </div>

        <div class="page-container" style="background:var(--bg-body)">
            <div class="support-page-container">
                
                <div class="support-header">
                    <h2>Pusat Bantuan</h2>
                    <p>Temukan panduan penggunaan sistem atau hubungi tim dukungan kami jika Anda mengalami masalah.</p>
                </div>

                <div class="support-card">
                    <div class="support-item">
                        <div class="support-icon">📖</div>
                        <div class="support-content">
                            <div class="support-title">Panduan Pengguna (Manual)</div>
                            <div class="support-desc">Pelajari cara membuat, mengedit, dan mempublikasikan berita.</div>
                        </div>
                        <div class="support-arrow">›</div>
                    </div>
                    
                    <div class="support-item">
                        <div class="support-icon">❓</div>
                        <div class="support-content">
                            <div class="support-title">Pertanyaan yang Sering Diajukan (FAQ)</div>
                            <div class="support-desc">Jawaban untuk pertanyaan umum terkait proses persetujuan dan peran.</div>
                        </div>
                        <div class="support-arrow">›</div>
                    </div>

                    <div class="support-item">
                        <div class="support-icon">⚙️</div>
                        <div class="support-content">
                            <div class="support-title">Pengaturan Akun & Keamanan</div>
                            <div class="support-desc">Informasi tentang pengelolaan kata sandi dan keamanan akun Anda.</div>
                        </div>
                        <div class="support-arrow">›</div>
                    </div>
                    
                    <button class="btn-contact" onclick="alert('Membuka form kontak tim dukungan teknis...')">
                        ✉️ Hubungi Dukungan Teknis
                    </button>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>
