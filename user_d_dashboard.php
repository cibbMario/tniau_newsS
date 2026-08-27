<?php
require_once __DIR__ . '/config/config.php';
requireRole(['D','E']);
$current = 'user_d';
$user = currentUser();

// Filter
$filter = $_GET['filter'] ?? 'pending'; // 'pending', 'user_a', 'all', 'published'
$search = trim($_GET['q'] ?? '');

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
        .user-d-page { max-width: 1280px; margin: 0 auto; }
        .user-d-intro {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding: 4px 0 20px;
            border-bottom: 1px solid var(--border-cream);
        }
        .user-d-eyebrow {
            color: #486581;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .7px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .user-d-intro h1 { margin: 0; color: var(--navy); font-size: 24px; line-height: 1.2; }
        .user-d-intro p { margin: 7px 0 0; color: var(--text-sec); font-size: 13px; max-width: 720px; }
        .user-d-scope {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
            color: var(--navy);
            background: #fff;
            border: 1px solid var(--border-cream);
            border-radius: var(--radius);
            padding: 9px 12px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: var(--shadow-sm);
        }
        .stats-grid-d {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin: 20px 0;
        }
        .stat-card-d {
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid var(--border-cream);
            padding: 18px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
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
        .icon-blue { background: #e8eef4; color: #345b7d; }
        .icon-green { background: #e8f0ed; color: #3f6f60; }
        .icon-gold { background: #f1eee5; color: #806b3e; }
        .icon-slate { background: #edf0f2; color: #536572; }
        
        .stat-card-val { font-size: 22px; font-weight: 700; color: #0d1b2a; line-height: 1.2; }
        .stat-card-lbl { font-size: 12px; color: #3a5a7a; font-weight: 500; }
        
        .user-d-workspace {
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid var(--border-cream);
            border-radius: var(--radius);
            padding: 18px;
            box-shadow: var(--shadow-sm);
        }
        .user-d-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .tab-filter-bar {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding: 4px;
            background: #f5f8fb;
            border: 1px solid #e6edf3;
            border-radius: 10px;
        }
        .tab-filter-btn {
            padding: 8px 11px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-sec);
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
            background: #edf2f6;
            color: #345b7d;
        }
        .tab-filter-btn.active {
            background: #ffffff;
            color: var(--navy);
            box-shadow: var(--shadow-sm);
        }
        .user-d-search { position: relative; min-width: 230px; }
        .user-d-search input {
            width: 100%;
            border: 1px solid var(--border-cream);
            border-radius: 8px;
            padding: 9px 12px 9px 34px;
            color: var(--text-main);
            background: #fff;
            font-size: 12px;
        }
        .user-d-search svg { position: absolute; left: 11px; top: 10px; color: var(--text-muted); }
        .user-d-table-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .user-d-table-title h2 { margin: 0; font-size: 15px; color: var(--navy); }
        .user-d-result-count { color: var(--text-muted); font-size: 11px; }
        .user-d-table .news-table { min-width: 900px; }
        .user-d-table .news-table th { white-space: nowrap; }
        .user-d-table .col-subject a { display: block; max-width: 330px; line-height: 1.45; }
        .user-d-status-post { font-size: 11px; font-weight: 700; white-space: nowrap; }
        @media (max-width: 1024px) { .stats-grid-d { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) {
            .user-d-intro { flex-direction: column; }
            .user-d-intro h1 { font-size: 21px; }
            .stats-grid-d { grid-template-columns: 1fr; }
            .user-d-workspace { padding: 12px; }
            .user-d-toolbar { align-items: stretch; }
            .tab-filter-bar { width: 100%; }
            .user-d-search { width: 100%; }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-container user-d-page">

            <!-- PAGE INTRO -->
            <section class="user-d-intro">
                <div>
                    <div class="user-d-eyebrow">Monitoring & Review</div>
                    <h1>Pemantauan Berita</h1>
                    <p>Halo, <strong><?= e($user['full_name']) ?></strong>. Pantau alur berita Reporter dan status publikasinya dari satu tempat.</p>
                </div>
                <div class="user-d-scope">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.5 9.5 8 11 4.5-1.5 8-6 8-11V5l-8-3Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                    Approver Kejelasan
                </div>
            </section>

            <!-- STATS CARDS -->
            <div class="stats-grid-d">
                <div class="stat-card-d">
                    <div class="stat-card-icon icon-gold">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div>
                        <div class="stat-card-val"><?= $totalPending ?></div>
                        <div class="stat-card-lbl">Berita Dalam Proses</div>
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
                    <div class="stat-card-icon icon-slate">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <div>
                        <div class="stat-card-val"><?= $totalUserDComments ?></div>
                        <div class="stat-card-lbl">Catatan Evaluasi Diberikan</div>
                    </div>
                </div>
            </div>

            <section class="user-d-workspace">
                <div class="user-d-toolbar">
                <!-- FILTER TABS -->
                <div class="tab-filter-bar" role="tablist" aria-label="Filter berita">
                <a href="<?= BASE_URL ?>/user_d_dashboard.php?filter=all" class="tab-filter-btn <?= $filter==='all'?'active':'' ?>">
                    Semua Berita
                </a>
                <a href="<?= BASE_URL ?>/user_d_dashboard.php?filter=pending" class="tab-filter-btn <?= $filter==='pending'?'active':'' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Berita Dalam Proses (<?= $totalPending ?>)
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
                <form class="user-d-search" method="GET" action="<?= BASE_URL ?>/user_d_dashboard.php" role="search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari judul, isi, atau reporter..." aria-label="Cari berita">
                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                </form>
                </div>

                <div class="user-d-table-title">
                    <h2>Daftar Berita</h2>
                    <span class="user-d-result-count"><?= count($newsList) ?> hasil ditampilkan</span>
                </div>

            <!-- NEWS TABLE FOR USER D -->
            <div class="news-table-wrap user-d-table">
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
                                        <div style="text-align:center;">
                                            <?php if ($row['status'] !== 'published'): ?>
                                                <span class="user-d-status-post" style="color:#b45309; padding:4px 9px; background:rgba(245,158,11,0.1); border-radius:7px; border:1px solid rgba(245,158,11,0.2);">
                                                    Dalam Proses
                                                </span>
                                            <?php else: ?>
                                                <span class="user-d-status-post" style="color:#047857; padding:4px 9px; background:rgba(16,185,129,0.1); border-radius:7px; border:1px solid rgba(16,185,129,0.2);">
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

            </section>

        </div>
    </main>
</div>

</body>
</html>
