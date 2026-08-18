<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();
$current = 'media_monitoring';

$error = '';
$success = '';

// Handle add monitoring
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_monitoring'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        header("Location: " . BASE_URL . "/media_monitoring.php");
        exit;
    }

    $portal_name    = trim($_POST['portal_name'] ?? '');
    $article_title  = trim($_POST['article_title'] ?? '');
    $article_url    = trim($_POST['article_url'] ?? '');
    $published_date = !empty($_POST['published_date']) ? $_POST['published_date'] : date('Y-m-d');
    $sentiment      = $_POST['sentiment'] ?? 'Positif';
    $reach_estimate = (int)($_POST['reach_estimate'] ?? 0);
    $news_id        = !empty($_POST['news_id']) ? (int)$_POST['news_id'] : null;
    $notes          = trim($_POST['notes'] ?? '');

    if (!$portal_name || !$article_title || !$article_url) {
        $error = 'Nama portal, judul artikel, dan URL wajib diisi.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO media_monitoring (portal_name, article_title, article_url, published_date, sentiment, reach_estimate, news_id, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$portal_name, $article_title, $article_url, $published_date, $sentiment, $reach_estimate, $news_id, $notes, $user['id']]);
        logAudit('ADD_MONITORING', "Pencatatan media monitoring: $portal_name - $article_title", $user['id']);
        $success = "Data pemantauan media luar berhasil disimpan.";
    }
}

// Handle delete monitoring
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_monitoring'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $monId = (int)($_POST['mon_id'] ?? 0);
        $pdo->prepare("DELETE FROM media_monitoring WHERE id = ?")->execute([$monId]);
        logAudit('DELETE_MONITORING', "Menghapus data media monitoring ID #$monId", $user['id']);
        $success = "Data pemantauan media berhasil dihapus.";
    }
}

// Analytics metrics
$totalClippings = (int)$pdo->query("SELECT COUNT(*) FROM media_monitoring")->fetchColumn();
$totalReach     = (int)$pdo->query("SELECT SUM(reach_estimate) FROM media_monitoring")->fetchColumn();
$posCount       = (int)$pdo->query("SELECT COUNT(*) FROM media_monitoring WHERE sentiment='Positif'")->fetchColumn();
$negCount       = (int)$pdo->query("SELECT COUNT(*) FROM media_monitoring WHERE sentiment='Negatif'")->fetchColumn();
$netCount       = (int)$pdo->query("SELECT COUNT(*) FROM media_monitoring WHERE sentiment='Netral'")->fetchColumn();

// Fetch items
$filterSentiment = $_GET['sentiment'] ?? '';
$searchQuery     = trim($_GET['q'] ?? '');

$sql = "SELECT m.*, n.title as internal_news_title, u.full_name as recorder_name 
        FROM media_monitoring m
        LEFT JOIN news n ON m.news_id = n.id
        LEFT JOIN users u ON m.created_by = u.id
        WHERE 1=1";
$params = [];

if ($filterSentiment) {
    $sql .= " AND m.sentiment = ?";
    $params[] = $filterSentiment;
}
if ($searchQuery) {
    $sql .= " AND (m.portal_name LIKE ? OR m.article_title LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY m.published_date DESC, m.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$monitoringList = $stmt->fetchAll();

// List internal published news for dropdown link
$publishedNews = $pdo->query("SELECT id, title FROM news WHERE status='published' ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Monitoring & Clipping Tracker — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .kpi-value { font-size: 24px; font-weight: 700; color: #1e293b; margin: 4px 0 0; }
        .kpi-label { font-size: 12px; color: #64748b; font-weight: 500; }
        .badge-pos { background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-neg { background: #fee2e2; color: #b91c1c; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-net { background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    </style>
</head>
<body class="dashboard-body">
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-content">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
                <div>
                    <h1 class="page-title" style="margin:0; font-size:22px; color:#1a202c;">Media Monitoring & E-Clipping Tracker</h1>
                    <p style="margin:4px 0 0; color:#718096; font-size:13px;">Pelacakan liputan dan persepsi media massa nasional/daerah terhadap siaran berita TNI AU.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('addMonitoringModal').style.display='flex'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Catat Liputan Media
                    </button>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom:16px;"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom:16px;"><?= $success ?></div>
            <?php endif; ?>

            <!-- KPI Row -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-label">Total E-Clipping Tercatat</div>
                    <div class="kpi-value"><?= number_format($totalClippings) ?> Artikel</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Estimasi Total Reach / Pembaca</div>
                    <div class="kpi-value"><?= number_format($totalReach) ?> Pembaca</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Sentimen Positif Media Luar</div>
                    <div class="kpi-value" style="color:#16a34a;"><?= number_format($posCount) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Sentimen Netral & Negatif</div>
                    <div class="kpi-value" style="color:#475569;"><?= $netCount ?> Netral / <span style="color:#dc2626;"><?= $negCount ?> Negatif</span></div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card" style="padding:16px; margin-bottom:20px;">
                <form method="GET" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 120px; gap:12px; align-items:end;">
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Cari Portal / Judul Liputan</label>
                        <input type="text" name="q" class="form-input" placeholder="Contoh: Kompas, Antara, Latma..." value="<?= e($searchQuery) ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Sentimen Liputan</label>
                        <select name="sentiment" class="form-input">
                            <option value="">-- Semua Sentimen --</option>
                            <option value="Positif" <?= $filterSentiment==='Positif'?'selected':'' ?>>Positif</option>
                            <option value="Netral" <?= $filterSentiment==='Netral'?'selected':'' ?>>Netral</option>
                            <option value="Negatif" <?= $filterSentiment==='Negatif'?'selected':'' ?>>Negatif</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Table Monitoring -->
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Portal Media</th>
                                <th>Judul Artikel & Link Luar</th>
                                <th>Terkait Berita Internal</th>
                                <th>Tanggal Tayang</th>
                                <th>Sentimen</th>
                                <th>Est. Reach</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monitoringList)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:30px; color:#a0aec0;">Belum ada data monitoring media luar. Klik tombol <strong>Catat Liputan Media</strong> untuk menambah data.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monitoringList as $m): ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#1e293b; font-size:14px;"><?= e($m['portal_name']) ?></strong>
                                            <?php if ($m['notes']): ?>
                                                <div style="font-size:11px; color:#64748b;"><?= e($m['notes']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight:500; color:#2563eb;"><?= e($m['article_title']) ?></div>
                                            <a href="<?= e($m['article_url']) ?>" target="_blank" rel="noopener noreferrer" style="font-size:11px; color:#64748b; display:inline-flex; align-items:center; gap:4px; text-decoration:underline;">
                                                Buka Tautan Luar ↗
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($m['internal_news_title']): ?>
                                                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $m['news_id'] ?>" style="font-size:12px; color:#334155; font-weight:500;">
                                                    <?= e(mb_strimwidth($m['internal_news_title'], 0, 45, '...')) ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size:11px; color:#94a3b8;">Umum / Rilis Terpisah</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="white-space:nowrap; font-size:12px; color:#64748b;">
                                            <?= date('d M Y', strtotime($m['published_date'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($m['sentiment'] === 'Positif'): ?>
                                                <span class="badge-pos">Positif</span>
                                            <?php elseif ($m['sentiment'] === 'Negatif'): ?>
                                                <span class="badge-neg">Negatif</span>
                                            <?php else: ?>
                                                <span class="badge-net">Netral</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:12px; font-weight:600; color:#334155;">
                                            <?= number_format($m['reach_estimate']) ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus catatan monitoring ini?')">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="delete_monitoring" value="1">
                                                <input type="hidden" name="mon_id" value="<?= $m['id'] ?>">
                                                <button type="submit" class="btn btn-outline" style="padding:4px 8px; font-size:11px; color:#e53e3e;">
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- MODAL ADD MONITORING -->
<div id="addMonitoringModal" class="modal-form" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-card" style="background:#fff; border-radius:8px; max-width:520px; width:90%; padding:24px;">
        <h3 style="margin-top:0; margin-bottom:16px;">Catat Liputan Media Luar</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="add_monitoring" value="1">
            
            <div class="form-group">
                <label>Nama Portal Media</label>
                <input type="text" name="portal_name" class="form-input" required placeholder="Contoh: Kompas.com, Detik.com, LKBN Antara, TVRI">
            </div>

            <div class="form-group">
                <label>Judul Artikel di Media Luar</label>
                <input type="text" name="article_title" class="form-input" required placeholder="Judul berita yang ditayangkan portal tersebut">
            </div>

            <div class="form-group">
                <label>URL / Tautan Berita Online</label>
                <input type="url" name="article_url" class="form-input" required placeholder="https://www.detik.com/...">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label>Tanggal Tayang</label>
                    <input type="date" name="published_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Sentimen Pemberitaan</label>
                    <select name="sentiment" class="form-input" required>
                        <option value="Positif">Positif</option>
                        <option value="Netral">Netral</option>
                        <option value="Negatif">Negatif</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Estimasi Jumlah Pembaca / Reach</label>
                <input type="number" name="reach_estimate" class="form-input" placeholder="Contoh: 15000" min="0" value="0">
            </div>

            <div class="form-group">
                <label>Tautkan dengan Siaran Berita Internal (Opsional)</label>
                <select name="news_id" class="form-input">
                    <option value="">-- Pilih Berita Terkait --</option>
                    <?php foreach ($publishedNews as $pn): ?>
                        <option value="<?= $pn['id'] ?>"><?= e(mb_strimwidth($pn['title'], 0, 60, '...')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Catatan / Kutipan Utama</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Catatan sudut pandang wartawan atau kutipan narasumber..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addMonitoringModal').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Liputan</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
