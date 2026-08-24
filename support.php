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
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eaf2f8;
            color: #1a73e8;
            border-radius: 12px;
            margin-right: 16px;
            flex-shrink: 0;
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
                <button class="hamburger-btn" title="Toggle Menu" aria-label="Toggle menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
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
                        <div class="support-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                        </div>
                        <div class="support-content">
                            <div class="support-title">Panduan Pengguna (Manual)</div>
                            <div class="support-desc">Pelajari cara membuat, mengedit, dan mempublikasikan berita.</div>
                        </div>
                        <div class="support-arrow">›</div>
                    </div>
                    
                    <div class="support-item">
                        <div class="support-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </div>
                        <div class="support-content">
                            <div class="support-title">Pertanyaan yang Sering Diajukan (FAQ)</div>
                            <div class="support-desc">Jawaban untuk pertanyaan umum terkait proses persetujuan dan peran.</div>
                        </div>
                        <div class="support-arrow">›</div>
                    </div>

                    <div class="support-item">
                        <div class="support-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        </div>
                        <div class="support-content">
                            <div class="support-title">Pengaturan Akun & Keamanan</div>
                            <div class="support-desc">Informasi tentang pengelolaan kata sandi dan keamanan akun Anda.</div>
                        </div>
                        <div class="support-arrow">›</div>
                    </div>
                    
                    <button class="btn-contact" onclick="alert('Membuka form kontak tim dukungan teknis...')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg> Hubungi Dukungan Teknis
                    </button>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>
