<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'news_history';
$user = currentUser();

// Get news ID from query string (optional — shows history for one article)
$newsId = isset($_GET['news_id']) ? (int)$_GET['news_id'] : null;

// Pagination
$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

if ($newsId) {
    // History for a single news article
    $newsStmt = $pdo->prepare("SELECT n.title, n.status, u.full_name AS author FROM news n JOIN users u ON n.created_by = u.id WHERE n.id = ?");
    $newsStmt->execute([$newsId]);
    $newsInfo = $newsStmt->fetch();
    if (!$newsInfo) { die("Berita tidak ditemukan."); }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM news_history WHERE news_id = ?");
    $countStmt->execute([$newsId]);
    $totalRows = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT h.*, u.full_name AS actor_name, u.role AS actor_role, n.title AS news_title, n.id AS news_id
        FROM news_history h
        JOIN users u ON h.user_id = u.id
        JOIN news n ON h.news_id = n.id
        WHERE h.news_id = ?
        ORDER BY h.created_at ASC
    ");
    $stmt->execute([$newsId]);
    $rows = $stmt->fetchAll();
} else {
    // All history (paginated)
    $totalRows = (int)$pdo->query("SELECT COUNT(*) FROM news_history")->fetchColumn();
    $totalPages = max(1, ceil($totalRows / $perPage));
    if ($page > $totalPages) $page = $totalPages;

    $stmt = $pdo->prepare("
        SELECT h.*, u.full_name AS actor_name, u.role AS actor_role, n.title AS news_title, n.id AS news_id
        FROM news_history h
        JOIN users u ON h.user_id = u.id
        JOIN news n ON h.news_id = n.id
        ORDER BY h.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $newsInfo = null;
}

$totalPages = max(1, ceil($totalRows / $perPage));

// Helper
function historyStatusIcon($status) {
    $icons = [
        'draft'      => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z"/><path d="M9 7h6M9 11h6M9 15h3"/></svg>',
        'pending_b'  => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'pending_c'  => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'pending_d'  => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'revision_b' => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6"/><path d="M2.5 12a10 10 0 0 1 17.8-6.3L21.5 8"/><path d="M2.5 22v-6h6"/><path d="M21.5 12a10 10 0 0 1-17.8 6.3L2.5 16"/></svg>',
        'revision_c' => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6"/><path d="M2.5 12a10 10 0 0 1 17.8-6.3L21.5 8"/><path d="M2.5 22v-6h6"/><path d="M21.5 12a10 10 0 0 1-17.8 6.3L2.5 16"/></svg>',
        'revision_d' => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6"/><path d="M2.5 12a10 10 0 0 1 17.8-6.3L21.5 8"/><path d="M2.5 22v-6h6"/><path d="M21.5 12a10 10 0 0 1-17.8 6.3L2.5 16"/></svg>',
        'published'  => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    ];
    return $icons[$status] ?? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
}

function historyArrow() {
    return '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Berita — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .nh-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .nh-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nh-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(124,58,237,0.25);
            flex-shrink: 0;
        }
        .nh-title h2 {
            font-size: 17px;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 2px 0;
        }
        .nh-title p {
            font-size: 12.5px;
            color: var(--text-sec);
            margin: 0;
        }

        /* News context banner (when filtering by single news) */
        .nh-context-banner {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            border: 1px solid #c4b5fd;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nh-context-banner svg { flex-shrink: 0; color: #7c3aed; }
        .nh-context-banner .info h3 { font-size: 13.5px; font-weight: 600; color: #4c1d95; margin: 0 0 2px 0; }
        .nh-context-banner .info p  { font-size: 12px; color: #6d28d9; margin: 0; }

        /* Timeline card */
        .nh-timeline {
            position: relative;
            padding-left: 32px;
        }
        .nh-timeline::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 8px;
            bottom: 8px;
            width: 2px;
            background: var(--border-light);
            border-radius: 2px;
        }
        .nh-timeline-item {
            position: relative;
            margin-bottom: 14px;
        }
        .nh-timeline-dot {
            position: absolute;
            left: -28px;
            top: 14px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2.5px solid #fff;
            box-shadow: 0 0 0 2px var(--border);
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nh-timeline-dot.published  { background: #22c55e; box-shadow: 0 0 0 2px #bbf7d0; }
        .nh-timeline-dot.pending    { background: #3b82f6; box-shadow: 0 0 0 2px #bfdbfe; }
        .nh-timeline-dot.revision   { background: #ef4444; box-shadow: 0 0 0 2px #fecaca; }
        .nh-timeline-dot.draft      { background: #9ca3af; box-shadow: 0 0 0 2px #e5e7eb; }

        /* Table view (for all-history page) */
        .nh-table-wrap {
            overflow-x: auto;
        }
        .nh-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .nh-table thead th {
            background: var(--bg-body);
            padding: 10px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-sec);
            border-bottom: 1px solid var(--border-light);
            white-space: nowrap;
        }
        .nh-table tbody tr {
            border-bottom: 1px solid var(--border-light);
            transition: background 0.15s;
        }
        .nh-table tbody tr:hover {
            background: rgba(0,0,0,0.02);
        }
        .nh-table tbody td {
            padding: 10px 14px;
            vertical-align: middle;
        }
        .nh-table tbody tr:last-child {
            border-bottom: none;
        }

        /* Status transition pill */
        .nh-transition {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .nh-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }
        .nh-status-chip.draft     { background: #f3f4f6; color: #6b7280; }
        .nh-status-chip.pending   { background: #dbeafe; color: #1d4ed8; }
        .nh-status-chip.revision  { background: #fee2e2; color: #dc2626; }
        .nh-status-chip.published { background: #dcfce7; color: #16a34a; }

        /* Note pill */
        .nh-note {
            font-size: 11.5px;
            color: var(--text-sec);
            font-style: italic;
            max-width: 240px;
        }

        /* Actor badge */
        .nh-actor {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .nh-actor-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--navy), #1a56db);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .nh-actor-name { font-size: 12.5px; font-weight: 500; color: var(--text); }
        .nh-actor-role { font-size: 10.5px; color: var(--text-sec); }

        /* Pagination */
        .nh-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .nh-page-btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg-card);
            font-size: 13px;
            color: var(--text);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .nh-page-btn:hover    { border-color: var(--blue); color: var(--blue); }
        .nh-page-btn.active   { background: var(--navy); color: #fff; border-color: var(--navy); }
        .nh-page-btn.disabled { opacity: 0.4; pointer-events: none; }

        /* Empty state */
        .nh-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-sec);
        }
        .nh-empty svg { margin-bottom: 14px; opacity: 0.35; }
        .nh-empty h3 { font-size: 15px; font-weight: 600; color: var(--text); margin: 0 0 6px 0; }
        .nh-empty p  { font-size: 13px; margin: 0; }

        .nh-news-link {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--blue);
            text-decoration: none;
            max-width: 220px;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .nh-news-link:hover { text-decoration: underline; }

        .nh-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f3e8ff;
            color: #7c3aed;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 11.5px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-container">

            <?php if ($newsInfo): ?>
            <!-- Context banner for single-news view -->
            <div class="nh-context-banner">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <div class="info">
                    <h3><?= e($newsInfo['title']) ?></h3>
                    <p>Riwayat perubahan status berita &mdash; oleh <strong><?= e($newsInfo['author']) ?></strong> &mdash; Status sekarang: <strong><?= e(statusLabel($newsInfo['status'])) ?></strong></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Header bar -->
            <div class="nh-header-bar">
                <div class="nh-header-left">
                    <div class="nh-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                    </div>
                    <div class="nh-title">
                        <h2>Riwayat Berita</h2>
                        <p>Log lengkap perubahan status setiap berita di sistem</p>
                    </div>
                </div>
                <div class="nh-count-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <?= number_format($totalRows) ?> entri riwayat
                </div>
            </div>

            <?php if ($newsId): ?>
            <!-- TIMELINE VIEW for single article -->
            <div class="card" style="padding:24px">
                <?php if (empty($rows)): ?>
                    <div class="nh-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        <h3>Belum Ada Riwayat</h3>
                        <p>Riwayat perubahan status belum tersedia untuk berita ini.</p>
                    </div>
                <?php else: ?>
                <div class="nh-timeline">
                    <?php foreach ($rows as $i => $row):
                        $isLast = $i === count($rows) - 1;
                        $dotClass = str_contains($row['status_to'], 'published') ? 'published'
                            : (str_contains($row['status_to'], 'pending') ? 'pending'
                            : (str_contains($row['status_to'], 'revision') ? 'revision' : 'draft'));
                    ?>
                    <div class="nh-timeline-item">
                        <div class="nh-timeline-dot <?= $dotClass ?>"></div>
                        <div style="background:var(--bg-card);border:1px solid var(--border-light);border-radius:10px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,0.04)">
                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:8px">
                                <div class="nh-transition">
                                    <?php if ($row['status_from'] && $row['status_from'] !== $row['status_to']): ?>
                                        <span class="nh-status-chip <?= str_contains($row['status_from'],'published')?'published':(str_contains($row['status_from'],'pending')?'pending':(str_contains($row['status_from'],'revision')?'revision':'draft')) ?>">
                                            <?= historyStatusIcon($row['status_from']) ?>
                                            <?= e(statusLabel($row['status_from'])) ?>
                                        </span>
                                        <span style="color:var(--text-sec)"><?= historyArrow() ?></span>
                                    <?php endif; ?>
                                    <span class="nh-status-chip <?= str_contains($row['status_to'],'published')?'published':(str_contains($row['status_to'],'pending')?'pending':(str_contains($row['status_to'],'revision')?'revision':'draft')) ?>">
                                        <?= historyStatusIcon($row['status_to']) ?>
                                        <?= e(statusLabel($row['status_to'])) ?>
                                    </span>
                                </div>
                                <span style="font-size:11.5px;color:var(--text-sec);">
                                    <?= formatTanggal($row['created_at']) ?>
                                </span>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="nh-actor">
                                    <div class="nh-actor-avatar"><?= strtoupper(substr($row['actor_name'],0,1)) ?></div>
                                    <div>
                                        <div class="nh-actor-name"><?= e($row['actor_name']) ?></div>
                                        <div class="nh-actor-role"><?= e(userDisplayName($row['actor_role'])) ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($row['note']): ?>
                                <div class="nh-note" style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border-light)">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    <?= e($row['note']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div style="margin-top:16px">
                <a href="<?= BASE_URL ?>/news_view.php?id=<?= $newsId ?>" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--text-sec);text-decoration:none">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Kembali ke Detail Berita
                </a>
                &nbsp;&nbsp;
                <a href="<?= BASE_URL ?>/news_history.php" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--text-sec);text-decoration:none">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Lihat Semua Riwayat
                </a>
            </div>

            <?php else: ?>
            <!-- TABLE VIEW for all history -->
            <div class="card" style="padding:0;overflow:hidden">
                <?php if (empty($rows)): ?>
                    <div class="nh-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        <h3>Belum Ada Riwayat</h3>
                        <p>Belum ada perubahan status berita yang tercatat.</p>
                    </div>
                <?php else: ?>
                <div class="nh-table-wrap">
                    <table class="nh-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Berita</th>
                                <th>Perubahan Status</th>
                                <th>Pelaku</th>
                                <th>Catatan</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $i => $row):
                                $fromClass = str_contains($row['status_from'] ?? '','published')?'published':(str_contains($row['status_from'] ?? '','pending')?'pending':(str_contains($row['status_from'] ?? '','revision')?'revision':'draft'));
                                $toClass   = str_contains($row['status_to'],'published')?'published':(str_contains($row['status_to'],'pending')?'pending':(str_contains($row['status_to'],'revision')?'revision':'draft'));
                            ?>
                            <tr>
                                <td style="color:var(--text-sec);font-size:12px"><?= $offset + $i + 1 ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/news_history.php?news_id=<?= $row['news_id'] ?>" class="nh-news-link" title="<?= e($row['news_title']) ?>">
                                        <?= e($row['news_title']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="nh-transition">
                                        <?php if ($row['status_from'] && $row['status_from'] !== $row['status_to']): ?>
                                            <span class="nh-status-chip <?= $fromClass ?>"><?= e(statusLabel($row['status_from'])) ?></span>
                                            <span style="color:var(--text-sec)"><?= historyArrow() ?></span>
                                        <?php endif; ?>
                                        <span class="nh-status-chip <?= $toClass ?>"><?= e(statusLabel($row['status_to'])) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="nh-actor">
                                        <div class="nh-actor-avatar"><?= strtoupper(substr($row['actor_name'],0,1)) ?></div>
                                        <div>
                                            <div class="nh-actor-name"><?= e($row['actor_name']) ?></div>
                                            <div class="nh-actor-role"><?= e(userDisplayName($row['actor_role'])) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['note']): ?>
                                        <span class="nh-note"><?= e($row['note']) ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--border);font-size:12px">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;font-size:12px;color:var(--text-sec)"><?= formatTanggal($row['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div style="padding:16px 14px;border-top:1px solid var(--border-light)">
                    <div class="nh-pagination">
                        <a href="?page=<?= max(1,$page-1) ?>" class="nh-page-btn <?= $page<=1?'disabled':'' ?>">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                            Sebelumnya
                        </a>
                        <?php
                        $start = max(1, $page - 2);
                        $end   = min($totalPages, $page + 2);
                        if ($start > 1): ?><a href="?page=1" class="nh-page-btn">1</a><?php if($start>2) echo '<span style="color:var(--text-sec)">...</span>'; endif;
                        for ($p = $start; $p <= $end; $p++): ?>
                            <a href="?page=<?= $p ?>" class="nh-page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
                        <?php endfor;
                        if ($end < $totalPages): if($end<$totalPages-1) echo '<span style="color:var(--text-sec)">...</span>'; ?>
                            <a href="?page=<?= $totalPages ?>" class="nh-page-btn"><?= $totalPages ?></a>
                        <?php endif; ?>
                        <a href="?page=<?= min($totalPages,$page+1) ?>" class="nh-page-btn <?= $page>=$totalPages?'disabled':'' ?>">
                            Berikutnya
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>
</body>
</html>
