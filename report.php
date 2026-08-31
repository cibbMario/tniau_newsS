<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/lanud_list.php';
requireLogin();
$current = 'report';
$user = currentUser();

$view = $_GET['view'] ?? 'kontributor';
if (!in_array($view, ['kontributor', 'reviewer'], true)) {
    $view = 'kontributor';
}

$search      = trim($_GET['q'] ?? '');
$lanudFilter = trim($_GET['lanud'] ?? '');
$roleFilter  = trim($_GET['role'] ?? '');

// ── 1. KONTRIBUTOR INFORMASI VIEW ──────────────────────────────────────────
if ($view === 'kontributor') {
    $where = ["(u.role = 'A' OR (SELECT COUNT(*) FROM news WHERE created_by = u.id) > 0)"];
    $params = [];

    if ($lanudFilter !== '') {
        $where[] = "u.lanud = ?";
        $params[] = $lanudFilter;
    }
    if ($search !== '') {
        $where[] = "(u.full_name LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql = "SELECT u.id, u.username, u.full_name, u.role, u.lanud, u.is_active,
            COUNT(n.id) as total_news,
            SUM(CASE WHEN n.status = 'published' THEN 1 ELSE 0 END) as published_count,
            SUM(CASE WHEN n.status LIKE 'pending%' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN n.status LIKE 'revision%' THEN 1 ELSE 0 END) as revision_count,
            SUM(CASE WHEN n.status = 'draft' THEN 1 ELSE 0 END) as draft_count,
            MAX(n.created_at) as last_created_at
            FROM users u
            LEFT JOIN news n ON u.id = n.created_by
            WHERE " . implode(" AND ", $where) . "
            GROUP BY u.id
            ORDER BY total_news DESC, u.full_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contributors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // KPI Summary
    $kpiTotalContributors = count($contributors);
    $kpiTotalNews         = 0;
    $kpiTotalPublished    = 0;
    $kpiTotalPending      = 0;
    $kpiTotalDraft        = 0;

    foreach ($contributors as $c) {
        $kpiTotalNews      += (int)$c['total_news'];
        $kpiTotalPublished += (int)$c['published_count'];
        $kpiTotalPending   += (int)$c['pending_count'] + (int)$c['revision_count'];
        $kpiTotalDraft     += (int)$c['draft_count'];
    }

    // Recent news submitted by contributors
    $recentNews = $pdo->query("SELECT n.*, u.full_name as author_name, u.lanud as author_lanud 
        FROM news n 
        JOIN users u ON n.created_by = u.id 
        ORDER BY n.created_at DESC 
        LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
}

// ── 2. REVIEWER VIEW ───────────────────────────────────────────────────────
if ($view === 'reviewer') {
    $where = ["u.role IN ('B', 'C', 'D')"];
    $params = [];

    if ($roleFilter !== '' && in_array($roleFilter, ['B', 'C', 'D'], true)) {
        $where[] = "u.role = ?";
        $params[] = $roleFilter;
    }
    if ($lanudFilter !== '') {
        $where[] = "u.lanud = ?";
        $params[] = $lanudFilter;
    }
    if ($search !== '') {
        $where[] = "(u.full_name LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql = "SELECT u.id, u.username, u.full_name, u.role, u.lanud, u.is_active,
            COUNT(h.id) as total_actions,
            SUM(CASE WHEN h.status_to = 'published' OR h.status_to LIKE 'pending%' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN h.status_to LIKE 'revision%' THEN 1 ELSE 0 END) as revision_count,
            MAX(h.created_at) as last_action_at
            FROM users u
            LEFT JOIN news_history h ON u.id = h.user_id
            WHERE " . implode(" AND ", $where) . "
            GROUP BY u.id
            ORDER BY total_actions DESC, u.full_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviewers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // KPI Summary
    $kpiTotalReviewers = count($reviewers);
    $kpiTotalActions   = 0;
    $kpiTotalApproved  = 0;
    $kpiTotalRevisions = 0;

    foreach ($reviewers as $r) {
        $kpiTotalActions   += (int)$r['total_actions'];
        $kpiTotalApproved  += (int)$r['approved_count'];
        $kpiTotalRevisions += (int)$r['revision_count'];
    }

    // Recent reviewer history logs (Exclude Role E)
    $recentLogs = $pdo->query("SELECT h.*, u.full_name as reviewer_name, u.role as reviewer_role, n.title as news_title, n.id as news_id 
        FROM news_history h
        JOIN users u ON h.user_id = u.id
        JOIN news n ON h.news_id = n.id
        WHERE u.role IN ('B', 'C', 'D')
        ORDER BY h.created_at DESC 
        LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $view === 'kontributor' ? 'Laporan Kontributor Informasi' : 'Laporan Reviewer' ?> - Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .report-view-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
            background: rgba(255, 255, 255, 0.85);
            padding: 6px;
            border-radius: 12px;
            border: 1px solid rgba(30, 111, 191, 0.12);
            backdrop-filter: blur(12px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .report-tab-btn {
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-sec);
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .report-tab-btn:hover {
            background: rgba(30, 111, 191, 0.08);
            color: var(--navy);
        }
        .report-tab-btn.active {
            background: #ffffff;
            color: var(--navy);
            font-weight: 700;
            border-color: rgba(30, 111, 191, 0.16);
            border-bottom: 2.5px solid var(--gold);
            box-shadow: 0 4px 12px rgba(11, 37, 69, 0.06);
        }

        .report-header-banner {
            background: #ffffff;
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 20px;
            border: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow);
        }
        .report-header-banner h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--navy);
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .report-header-banner p {
            font-size: 13px;
            color: var(--text-sec);
            margin: 0;
        }

        .report-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 22px;
        }
        @media (max-width: 992px) {
            .report-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .report-kpi-grid { grid-template-columns: 1fr; }
        }

        .kpi-card {
            background: #ffffff;
            border-radius: var(--radius);
            padding: 18px 20px;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        }
        .kpi-card-info {
            display: flex;
            flex-direction: column;
        }
        .kpi-card-label {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-sec);
            margin-bottom: 4px;
        }
        .kpi-card-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--navy);
            line-height: 1.1;
        }
        .kpi-badge-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .icon-blue   { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
        .icon-green  { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .icon-yellow { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
        .icon-red    { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
        .icon-gold   { background: rgba(201, 162, 39, 0.15); color: #c9a227; }

        .report-filter-card {
            background: #ffffff;
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 22px;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }
        .report-filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }
        .report-filter-form input,
        .report-filter-form select {
            height: 38px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0 12px;
            font-size: 13px;
            color: var(--text);
            background: #fff;
            outline: none;
            transition: border-color 0.2s ease;
        }
        .report-filter-form input:focus,
        .report-filter-form select:focus {
            border-color: var(--blue);
        }
        .btn-filter-action {
            height: 38px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-primary-action {
            background: var(--navy);
            color: #ffffff;
        }
        .btn-primary-action:hover {
            background: #0d315d;
        }
        .btn-secondary-action {
            background: #f1f5f9;
            color: var(--text-sec);
            border: 1px solid var(--border);
        }
        .btn-secondary-action:hover {
            background: #e2e8f0;
            color: var(--text);
        }
        .btn-export-action {
            background: #10b981;
            color: #ffffff;
        }
        .btn-export-action:hover {
            background: #059669;
        }

        .report-table-card {
            background: #ffffff;
            border-radius: var(--radius);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .report-table-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fbfcfe;
        }
        .report-table-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        .report-table th {
            background: #f8fafc;
            color: var(--text-sec);
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            white-space: nowrap;
        }
        .report-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text);
            vertical-align: middle;
        }
        .report-table tr:last-child td {
            border-bottom: none;
        }
        .report-table tr:hover td {
            background: #f8fafc;
        }

        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(30, 111, 191, 0.12);
            color: var(--blue);
            font-weight: 700;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .user-name-title {
            font-weight: 600;
            color: var(--text);
            display: block;
        }
        .user-username-sub {
            font-size: 11.5px;
            color: var(--text-sec);
        }

        .progress-pill {
            background: #e2e8f0;
            border-radius: 10px;
            height: 8px;
            width: 80px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-right: 6px;
        }
        .progress-pill-fill {
            height: 100%;
            background: #10b981;
            border-radius: 10px;
        }

        .role-tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        .role-tag-a { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
        .role-tag-b { background: rgba(124, 58, 237, 0.12); color: #7c3aed; }
        .role-tag-c { background: rgba(201, 162, 39, 0.15); color: #c9a227; }
        .role-tag-d { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .role-tag-e { background: rgba(239, 68, 68, 0.12); color: #dc2626; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-container" style="background:var(--bg-body)">
            
            <!-- TOP VIEW SWITCHER TABS -->
            <div class="report-view-bar">
                <a href="<?= BASE_URL ?>/report.php?view=kontributor" class="report-tab-btn <?= $view==='kontributor' ? 'active':'' ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Kontributor Informasi
                </a>
                <a href="<?= BASE_URL ?>/report.php?view=reviewer" class="report-tab-btn <?= $view==='reviewer' ? 'active':'' ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    Reviewer & Peninjau
                </a>
            </div>

            <?php if ($view === 'kontributor'): ?>
                <!-- ════════════════════ KONTRIBUTOR INFORMASI VIEW ════════════════════ -->

                <!-- Header Banner -->
                <div class="report-header-banner">
                    <div>
                        <h2>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--navy)"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="11" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            Laporan Kontributor Informasi
                        </h2>
                        <p>Monitoring produktivitas dan kontribusi pembuatan berita dari setiap satuan/lanud jajaran TNI AU.</p>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>/export_csv.php?type=kontributor<?= $lanudFilter ? '&lanud='.urlencode($lanudFilter) : '' ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="btn-filter-action btn-export-action">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Unduh Laporan Kontributor (CSV)
                        </a>
                    </div>
                </div>

                <!-- KPI Cards Grid -->
                <div class="report-kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-info">
                            <span class="kpi-card-label">TOTAL KONTRIBUTOR</span>
                            <span class="kpi-card-value"><?= $kpiTotalContributors ?></span>
                        </div>
                        <div class="kpi-badge-icon icon-blue">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-info">
                            <span class="kpi-card-label">TOTAL BERITA DIBUAT</span>
                            <span class="kpi-card-value"><?= $kpiTotalNews ?></span>
                        </div>
                        <div class="kpi-badge-icon icon-gold">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 13a2 2 0 0 1-2-2V7m2 13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2m-4-3H9M7 16h6M7 8h6m-6 4h6"></path></svg>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-info">
                            <span class="kpi-card-label">BERITA TERBIT</span>
                            <span class="kpi-card-value"><?= $kpiTotalPublished ?></span>
                        </div>
                        <div class="kpi-badge-icon icon-green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-info">
                            <span class="kpi-card-label">DALAM PROSES / DRAFT</span>
                            <span class="kpi-card-value"><?= $kpiTotalPending + $kpiTotalDraft ?></span>
                        </div>
                        <div class="kpi-badge-icon icon-yellow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="report-filter-card">
                    <form action="<?= BASE_URL ?>/report.php" method="GET" class="report-filter-form">
                        <input type="hidden" name="view" value="kontributor">
                        <input type="text" name="q" placeholder="Cari nama kontributor..." value="<?= e($search) ?>" style="min-width: 220px;">
                        
                        <select name="lanud" style="max-width: 260px;">
                            <option value="">Semua Satuan / Lanud</option>
                            <?php foreach ($LANUD_OPTIONS as $lanudOpt): ?>
                                <option value="<?= e($lanudOpt) ?>" <?= $lanudFilter === $lanudOpt ? 'selected' : '' ?>><?= e($lanudOpt) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn-filter-action btn-primary-action">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            Filter
                        </button>
                        <?php if ($search !== '' || $lanudFilter !== ''): ?>
                            <a href="<?= BASE_URL ?>/report.php?view=kontributor" class="btn-filter-action btn-secondary-action">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Main Contributor Performance Table -->
                <div class="report-table-card">
                    <div class="report-table-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            Kinerja Kontributor Informasi (Reporter)
                        </h3>
                        <span style="font-size:12px;color:var(--text-sec)"><?= count($contributors) ?> Kontributor Terdaftar</span>
                    </div>
                    <div class="table-responsive">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">No</th>
                                    <th>Kontributor</th>
                                    <th>Satuan / Lanud</th>
                                    <th style="text-align: center;">Total Berita</th>
                                    <th style="text-align: center;">Terbit</th>
                                    <th style="text-align: center;">Pending</th>
                                    <th style="text-align: center;">Revisi</th>
                                    <th style="text-align: center;">Draft</th>
                                    <th>Rasio Terbit</th>
                                    <th>Berita Terakhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($contributors)): ?>
                                    <tr>
                                        <td colspan="10" style="text-align:center;padding:32px;color:var(--text-sec)">Tidak ada data kontributor yang cocok dengan filter pencarian.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($contributors as $c): 
                                        $tot = (int)$c['total_news'];
                                        $pub = (int)$c['published_count'];
                                        $pct = $tot > 0 ? round(($pub / $tot) * 100) : 0;
                                        $initials = strtoupper(substr($c['full_name'], 0, 1));
                                    ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: 600; color: var(--text-sec);"><?= $no++ ?></td>
                                        <td>
                                            <div class="user-info-cell">
                                                <div class="user-avatar-circle"><?= $initials ?></div>
                                                <div>
                                                    <span class="user-name-title"><?= e($c['full_name']) ?></span>
                                                    <span class="user-username-sub">@<?= e($c['username']) ?> &bull; <?= userDisplayName($c['role']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-weight: 500;"><?= e($c['lanud'] ?: 'Mabes TNI AU') ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <strong style="font-size: 14px; color: var(--navy);"><?= $tot ?></strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-green" style="font-weight: 600;"><?= $pub ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-blue"><?= (int)$c['pending_count'] ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-red"><?= (int)$c['revision_count'] ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-gray"><?= (int)$c['draft_count'] ?></span>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center;">
                                                <div class="progress-pill">
                                                    <div class="progress-pill-fill" style="width: <?= $pct ?>%;"></div>
                                                </div>
                                                <span style="font-size: 12px; font-weight: 600; color: var(--text-sec);"><?= $pct ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px; color: var(--text-sec);">
                                                <?= $c['last_created_at'] ? timeAgo($c['last_created_at']) : '-' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent News Table -->
                <div class="report-table-card">
                    <div class="report-table-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            Berita Terbaru oleh Kontributor
                        </h3>
                        <a href="<?= BASE_URL ?>/news_list.php" style="font-size:12px;color:var(--blue);text-decoration:none;font-weight:600">Lihat Semua Berita &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Judul Berita</th>
                                    <th>Penulis</th>
                                    <th>Wilayah</th>
                                    <th>Sentimen</th>
                                    <th>Status</th>
                                    <th>Waktu Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentNews as $rn): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>/news_view.php?id=<?= $rn['id'] ?>" style="font-weight:600;color:var(--navy);text-decoration:none;">
                                                <?= e(mb_strimwidth($rn['title'], 0, 70, '...')) ?>
                                            </a>
                                        </td>
                                        <td><?= e($rn['author_name']) ?></td>
                                        <td><?= e($rn['wilayah'] ?: $rn['author_lanud'] ?: '-') ?></td>
                                        <td>
                                            <?php 
                                            $sClass = 'badge-blue';
                                            if ($rn['sentiment'] === 'Positif') $sClass = 'badge-green';
                                            if ($rn['sentiment'] === 'Negatif') $sClass = 'badge-red';
                                            ?>
                                            <span class="badge <?= $sClass ?>"><?= e($rn['sentiment'] ?: 'Positif') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= statusBadgeClass($rn['status']) ?>"><?= statusLabel($rn['status']) ?></span>
                                        </td>
                                        <td><span style="font-size:12px;color:var(--text-sec)"><?= timeAgo($rn['created_at']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <!-- ════════════════════ REVIEWER VIEW ════════════════════ -->

                <!-- Header Banner -->
                <div class="report-header-banner">
                    <div>
                        <h2>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--navy)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            Laporan Kinerja Reviewer & Peninjau
                        </h2>
                        <p>Monitoring aktivitas verifikasi, peninjauan (Editor), dan persetujuan (Approver) berita sebelum publikasi.</p>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>/export_csv.php?type=reviewer<?= $roleFilter ? '&role='.urlencode($roleFilter) : '' ?><?= $lanudFilter ? '&lanud='.urlencode($lanudFilter) : '' ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="btn-filter-action btn-export-action">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Unduh Laporan Reviewer (CSV)
                        </a>
                    </div>
                </div>

                <!-- KPI Cards Grid -->
                <div class="report-kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-info">
                            <span class="kpi-card-label">TOTAL PERSONEL REVIEWER</span>
                            <span class="kpi-card-value"><?= $kpiTotalReviewers ?></span>
                        </div>
                        <div class="kpi-badge-icon icon-blue">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-info">
                            <span class="kpi-card-label">TOTAL AKSI PENINJAUAN</span>
                            <span class="kpi-card-value"><?= $kpiTotalActions ?></span>
                        </div>
                        <div class="kpi-badge-icon icon-gold">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-info">
                            <span class="kpi-card-label">DISETUJUI / DITERUSKAN</span>
                            <span class="kpi-card-value"><?= $kpiTotalApproved ?></span>
                        </div>
                        <div class="kpi-badge-icon icon-green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-info">
                            <span class="kpi-card-label">PERMINTAAN REVISI</span>
                            <span class="kpi-card-value"><?= $kpiTotalRevisions ?></span>
                        </div>
                        <div class="kpi-badge-icon icon-red">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6"></path><path d="M2.5 12a10 10 0 0 1 17.8-6.3L21.5 8"></path><path d="M2.5 22v-6h6"></path><path d="M21.5 12a10 10 0 0 1-17.8 6.3L2.5 16"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="report-filter-card">
                    <form action="<?= BASE_URL ?>/report.php" method="GET" class="report-filter-form">
                        <input type="hidden" name="view" value="reviewer">
                        <input type="text" name="q" placeholder="Cari nama reviewer..." value="<?= e($search) ?>" style="min-width: 200px;">

                        <select name="role" style="max-width: 200px;">
                            <option value="">Semua Posisi / Peran</option>
                            <option value="B" <?= $roleFilter === 'B' ? 'selected' : '' ?>>Editor (Role B)</option>
                            <option value="C" <?= $roleFilter === 'C' ? 'selected' : '' ?>>Approver Kadis (Role C)</option>
                            <option value="D" <?= $roleFilter === 'D' ? 'selected' : '' ?>>Approver Kejelasan (Role D)</option>
                        </select>

                        <select name="lanud" style="max-width: 240px;">
                            <option value="">Semua Satuan / Lanud</option>
                            <?php foreach ($LANUD_OPTIONS as $lanudOpt): ?>
                                <option value="<?= e($lanudOpt) ?>" <?= $lanudFilter === $lanudOpt ? 'selected' : '' ?>><?= e($lanudOpt) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn-filter-action btn-primary-action">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            Filter
                        </button>
                        <?php if ($search !== '' || $roleFilter !== '' || $lanudFilter !== ''): ?>
                            <a href="<?= BASE_URL ?>/report.php?view=reviewer" class="btn-filter-action btn-secondary-action">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Main Reviewer Performance Table -->
                <div class="report-table-card">
                    <div class="report-table-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="11" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            Kinerja Tim Reviewer & Penyetuju
                        </h3>
                        <span style="font-size:12px;color:var(--text-sec)"><?= count($reviewers) ?> Reviewer Terdaftar</span>
                    </div>
                    <div class="table-responsive">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">No</th>
                                    <th>Reviewer</th>
                                    <th>Peran / Posisi</th>
                                    <th>Satuan / Lanud</th>
                                    <th style="text-align: center;">Total Aksi</th>
                                    <th style="text-align: center;">Disetujui / Terbit</th>
                                    <th style="text-align: center;">Permintaan Revisi</th>
                                    <th>Aktivitas Terakhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reviewers)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center;padding:32px;color:var(--text-sec)">Tidak ada data reviewer yang cocok dengan filter pencarian.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($reviewers as $r): 
                                        $initials = strtoupper(substr($r['full_name'], 0, 1));
                                        $tagClass = 'role-tag-' . strtolower($r['role']);
                                    ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: 600; color: var(--text-sec);"><?= $no++ ?></td>
                                        <td>
                                            <div class="user-info-cell">
                                                <div class="user-avatar-circle"><?= $initials ?></div>
                                                <div>
                                                    <span class="user-name-title"><?= e($r['full_name']) ?></span>
                                                    <span class="user-username-sub">@<?= e($r['username']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="role-tag <?= $tagClass ?>"><?= userDisplayName($r['role']) ?></span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 500;"><?= e($r['lanud'] ?: 'Mabes TNI AU') ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <strong style="font-size: 14px; color: var(--navy);"><?= (int)$r['total_actions'] ?></strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-green" style="font-weight: 600;"><?= (int)$r['approved_count'] ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-red"><?= (int)$r['revision_count'] ?></span>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px; color: var(--text-sec);">
                                                <?= $r['last_action_at'] ? timeAgo($r['last_action_at']) : 'Belum ada aktivitas' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Review Logs Table -->
                <div class="report-table-card">
                    <div class="report-table-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            Log Riwayat Peninjauan Terakhir
                        </h3>
                        <a href="<?= BASE_URL ?>/news_history.php" style="font-size:12px;color:var(--blue);text-decoration:none;font-weight:600">Buka Riwayat Lengkap &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Berita</th>
                                    <th>Reviewer</th>
                                    <th>Perubahan Status</th>
                                    <th>Catatan Review</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLogs)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;padding:24px;color:var(--text-sec)">Belum ada riwayat aktivitas peninjauan.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $log['news_id'] ?>" style="font-weight:600;color:var(--navy);text-decoration:none;">
                                                    <?= e(mb_strimwidth($log['news_title'], 0, 60, '...')) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <strong><?= e($log['reviewer_name']) ?></strong>
                                                <span style="font-size:11px;color:var(--text-sec);display:block"><?= userDisplayName($log['reviewer_role']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge <?= statusBadgeClass($log['status_to']) ?>"><?= statusLabel($log['status_to']) ?></span>
                                            </td>
                                            <td>
                                                <span style="font-size:12px;color:var(--text-sec);font-style:italic">
                                                    <?= e($log['note'] ?: 'Tidak ada catatan khusus') ?>
                                                </span>
                                            </td>
                                            <td><span style="font-size:12px;color:var(--text-sec)"><?= timeAgo($log['created_at']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </main>
</div>
</body>
</html>
