<?php
require_once __DIR__ . '/config/config.php';
requireRole(['D','E']);
$current = 'user_d';
$user = currentUser();

// Filter
$filter = $_GET['filter'] ?? 'pending'; // 'pending', 'user_a', 'all', 'published'
$search = trim($_GET['q'] ?? '');

// Helper waktu relatif
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return 'Baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
        return formatTanggal($datetime);
    }
}

// Queries untuk Statistik
$statPendingStmt = $pdo->query("SELECT COUNT(*) FROM news WHERE status IN ('pending_d', 'pending_c', 'pending_b', 'draft', 'revision_b', 'revision_c', 'revision_d')");
$totalPending = (int)$statPendingStmt->fetchColumn();

$statPublishedStmt = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'published'");
$totalPublished = (int)$statPublishedStmt->fetchColumn();

$statReporterStmt = $pdo->query("SELECT COUNT(*) FROM news n JOIN users u ON n.created_by = u.id WHERE u.role = 'A'");
$totalUserANews = (int)$statReporterStmt->fetchColumn();

$statCommentsStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$statCommentsStmt->execute([$user['id']]);
$totalUserDComments = (int)$statCommentsStmt->fetchColumn();

// Base SQL query untuk daftar berita
$sql = "SELECT n.*, u.full_name AS author_name, u.role AS author_role
        FROM news n 
        JOIN users u ON n.created_by = u.id";
$where = [];
$params = [];

if ($filter === 'pending') {
    $where[] = "n.status IN ('pending_d', 'pending_c', 'pending_b', 'draft', 'revision_b', 'revision_c', 'revision_d')";
} elseif ($filter === 'user_a') {
    $where[] = "u.role = 'A'";
} elseif ($filter === 'published') {
    $where[] = "n.status = 'published'";
}

if ($search) {
    $where[] = "(n.title LIKE ? OR n.content LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY n.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$newsList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Pemantauan Status Berita (User D)</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .user-d-header-banner {
            background: linear-gradient(135deg, #0b2545 0%, #134074 60%, #1a5296 100%);
            color: #fff;
            padding: 24px 28px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(11, 37, 69, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .user-d-header-banner h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-d-header-banner p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            max-width: 650px;
            margin: 0;
        }
        .user-d-badge {
            background: rgba(201, 162, 39, 0.2);
            border: 1px solid #c9a227;
            color: #f0c84a;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .stats-grid-d {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card-d {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            padding: 18px;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(11, 37, 69, 0.06);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-card-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .icon-blue { background: rgba(30, 111, 191, 0.12); color: #1e6fbf; }
        .icon-green { background: rgba(15, 155, 110, 0.12); color: #0f9b6e; }
        .icon-gold { background: rgba(201, 162, 39, 0.15); color: #c9a227; }
        .icon-purple { background: rgba(111, 66, 193, 0.12); color: #6f42c1; }
        
        .stat-card-val { font-size: 22px; font-weight: 700; color: #0d1b2a; line-height: 1.2; }
        .stat-card-lbl { font-size: 12px; color: #3a5a7a; font-weight: 500; }
        
        .tab-filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            border-bottom: 2px solid rgba(30, 111, 191, 0.1);
            padding-bottom: 8px;
            overflow-x: auto;
        }
        .tab-filter-btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #3a5a7a;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .tab-filter-btn:hover {
            background: rgba(30, 111, 191, 0.08);
            color: #1e6fbf;
        }
        .tab-filter-btn.active {
            background: #0b2545;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(11, 37, 69, 0.2);
        }
        .action-group-d {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        /* Modal Style for Revision Note */
        .modal-revisi {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(11, 37, 69, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-revisi-box {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-container">

            <!-- HEADER BANNER -->
            <div class="user-d-header-banner">
                <div>
                    <h2>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        Halaman Pemantauan Status Berita
                    </h2>
                    <p>Selamat datang, <strong><?= e($user['full_name']) ?></strong>. Halaman ini berfungsi untuk memantau status berita yang telah disusun oleh Reporter, baik yang masih dalam tahap proses maupun yang sudah diterbitkan.</p>
                </div>
                <div class="user-d-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    Wewenang Approver Kejelasan
                </div>
            </div>

            <!-- STATS CARDS -->
            <div class="stats-grid-d">
                <div class="stat-card-d">
                    <div class="stat-card-icon icon-gold">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div>
                        <div class="stat-card-val"><?= $totalPending ?></div>
                        <div class="stat-card-lbl">Menunggu Persetujuan Kejelasan</div>
                    </div>
                </div>

                <div class="stat-card-d">
                    <div class="stat-card-icon icon-blue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </div>
                    <div>
                        <div class="stat-card-val"><?= $totalUserANews ?></div>
                        <div class="stat-card-lbl">Berita dari Reporter</div>
                    </div>
                </div>

                <div class="stat-card-d">
                    <div class="stat-card-icon icon-green">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div>
                        <div class="stat-card-val"><?= $totalPublished ?></div>
                        <div class="stat-card-lbl">Berita Dipublikasikan</div>
                    </div>
                </div>

                <div class="stat-card-d">
                    <div class="stat-card-icon icon-purple">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <div>
                        <div class="stat-card-val"><?= $totalUserDComments ?></div>
                        <div class="stat-card-lbl">Catatan Evaluasi Diberikan</div>
                    </div>
                </div>
            </div>

            <!-- FILTER TABS & SEARCH -->
            <div class="tab-filter-bar">
                <a href="<?= BASE_URL ?>/user_d_dashboard.php?filter=all" class="tab-filter-btn <?= $filter==='all'?'active':'' ?>">
                    Semua Berita
                </a>
                <a href="<?= BASE_URL ?>/user_d_dashboard.php?filter=pending" class="tab-filter-btn <?= $filter==='pending'?'active':'' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Menunggu Persetujuan Kejelasan (<?= $totalPending ?>)
                </a>
                <a href="<?= BASE_URL ?>/user_d_dashboard.php?filter=user_a" class="tab-filter-btn <?= $filter==='user_a'?'active':'' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Berita dari Reporter (<?= $totalUserANews ?>)
                </a>
                <a href="<?= BASE_URL ?>/user_d_dashboard.php?filter=published" class="tab-filter-btn <?= $filter==='published'?'active':'' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Berita Dipublikasikan (<?= $totalPublished ?>)
                </a>
            </div>

            <!-- NEWS TABLE FOR USER D -->
            <div class="news-table-wrap">
                <div class="table-responsive">
                    <table class="news-table">
                        <thead>
                            <tr>
                                <th style="width:28%">Judul / Subjek Berita</th>
                                <th>Reporter</th>
                                <th>Wilayah &amp; Media</th>
                                <th>Waktu Dibuat</th>
                                <th>Status</th>
                                <th>Sentimen</th>
                                <th style="width:22%; text-align:center;">Status Post</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($newsList)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        Tidak ada berita pada kategori filter ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($newsList as $row): ?>
                                <?php $isPublished = $row['status'] === 'published'; ?>
                                <tr>
                                    <td class="col-subject <?= $isPublished ? 'subject-approved' : 'subject-unapproved' ?>">
                                        <a href="<?= BASE_URL ?>/news_view.php?id=<?= $row['id'] ?>" style="font-weight:600;">
                                            <?= e($row['title']) ?>
                                        </a>
                                    </td>
                                    <td class="col-author">
                                        <strong><?= e($row['author_name']) ?></strong>
                                        <?php if ($row['author_role'] === 'A'): ?>
                                            <span style="font-size:10px; background:rgba(30,111,191,0.1); color:#1e6fbf; padding:2px 6px; border-radius:10px; font-weight:600;">Reporter</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-muted">
                                        <div><?= e($row['wilayah'] ?: '-') ?></div>
                                        <small style="color:var(--text-muted)"><?= e($row['media'] ?: '-') ?></small>
                                    </td>
                                    <td class="col-time">
                                        <?= timeAgo($row['created_at']) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= statusBadgeClass($row['status']) ?>"><?= statusLabel($row['status']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $s = strtolower($row['sentiment'] ?? 'netral');
                                        $cls = match($s) { 'positif'=>'pill-positif', 'negatif'=>'pill-negatif', default=>'pill-netral' };
                                        ?>
                                        <span class="pill <?= $cls ?>"><?= e($row['sentiment'] ?? 'Netral') ?></span>
                                    </td>
                                    <td style="text-align:center;" onclick="event.stopPropagation()">
                                        <div class="action-group-d" style="justify-content:center;">
                                            <?php if ($row['status'] !== 'published'): ?>
                                                <span style="font-size:11px; color:#e67e22; font-weight:700; padding:3px 12px; background:rgba(230,126,34,0.1); border-radius:12px; border:1px solid rgba(230,126,34,0.2);">
                                                    Dalam Proses
                                                </span>
                                            <?php else: ?>
                                                <span style="font-size:11px; color:#0f9b6e; font-weight:700; padding:3px 12px; background:rgba(15,155,110,0.1); border-radius:12px; border:1px solid rgba(15,155,110,0.2);">
                                                    Posted
                                                </span>
                                            <?php endif; ?>
                                        </div>
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

</body>
</html>
