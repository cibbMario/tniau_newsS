<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();

$type = $_GET['type'] ?? 'news';

if ($type === 'kontributor') {
    // ── Export Kontributor Informasi ──
    $lanudFilter = trim($_GET['lanud'] ?? '');
    $search      = trim($_GET['q'] ?? '');

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

    $sql = "SELECT u.id, u.username, u.full_name, u.role, u.lanud,
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
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_kontributor_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'No', 'Nama Kontributor', 'Username', 'Role', 'Satuan / Lanud',
        'Total Berita', 'Berita Terbit', 'Menunggu Review', 'Perlu Revisi', 'Draft',
        'Persentase Terbit (%)', 'Waktu Berita Terakhir'
    ]);

    $no = 1;
    foreach ($rows as $r) {
        $total = (int)$r['total_news'];
        $pub   = (int)$r['published_count'];
        $pct   = $total > 0 ? round(($pub / $total) * 100) . '%' : '0%';

        fputcsv($output, [
            $no++,
            $r['full_name'],
            $r['username'],
            userDisplayName($r['role']),
            $r['lanud'] ?: 'Mabes TNI AU',
            $total,
            $pub,
            (int)$r['pending_count'],
            (int)$r['revision_count'],
            (int)$r['draft_count'],
            $pct,
            $r['last_created_at'] ? date('d/m/Y H:i', strtotime($r['last_created_at'])) : '-'
        ]);
    }
    fclose($output);
    exit;

} elseif ($type === 'reviewer') {
    // ── Export Reviewer ──
    $roleFilter  = trim($_GET['role'] ?? '');
    $lanudFilter = trim($_GET['lanud'] ?? '');
    $search      = trim($_GET['q'] ?? '');

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

    $sql = "SELECT u.id, u.username, u.full_name, u.role, u.lanud,
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
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_reviewer_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'No', 'Nama Reviewer', 'Username', 'Posisi / Role', 'Satuan / Lanud',
        'Total Aksi Peninjauan', 'Disetujui / Diteruskan', 'Permintaan Revisi',
        'Waktu Peninjauan Terakhir'
    ]);

    $no = 1;
    foreach ($rows as $r) {
        fputcsv($output, [
            $no++,
            $r['full_name'],
            $r['username'],
            userDisplayName($r['role']),
            $r['lanud'] ?: 'Mabes TNI AU',
            (int)$r['total_actions'],
            (int)$r['approved_count'],
            (int)$r['revision_count'],
            $r['last_action_at'] ? date('d/m/Y H:i', strtotime($r['last_action_at'])) : '-'
        ]);
    }
    fclose($output);
    exit;

} else {
    // ── Export Berita Umum (News) ──
    $media     = trim($_GET['media'] ?? '');
    $sentiment = trim($_GET['sentiment'] ?? '');

    $sql = "SELECT n.id, n.title, n.media, n.sentiment, n.priority, n.classification, n.wilayah, n.tempat, n.aktor, n.tag, n.topik, n.keyword, n.created_at, n.published_at, u.full_name AS author_name, n.status 
            FROM news n 
            LEFT JOIN users u ON n.created_by = u.id";

    $where = [];
    $params = [];

    if ($media !== '') {
        $where[] = "n.media = ?";
        $params[] = $media;
    }
    if ($sentiment !== '') {
        $where[] = "n.sentiment = ?";
        $params[] = $sentiment;
    }

    if ($user['role'] === 'A') {
        $where[] = "(n.created_by = ? OR n.status = 'published')";
        $params[] = $user['id'];
    } else {
        $where[] = "n.status != 'draft'";
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY n.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_berita_' . date('Ymd_His') . '.csv"');

    // UTF-8 BOM for Excel compliance
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'No', 'Judul', 'Sumber Media', 'Sentimen', 'Prioritas', 'Klasifikasi', 
        'Wilayah / Satuan', 'Tempat', 'Aktor', 'Tag', 'Topik', 'Keyword', 
        'Penulis', 'Status', 'Tanggal Dibuat', 'Tanggal Diterbitkan'
    ]);

    $no = 1;
    foreach ($news as $row) {
        fputcsv($output, [
            $no++,
            $row['title'],
            $row['media'],
            $row['sentiment'],
            $row['priority'],
            $row['classification'],
            $row['wilayah'],
            $row['tempat'],
            $row['aktor'],
            $row['tag'],
            $row['topik'],
            $row['keyword'],
            $row['author_name'],
            $row['status'],
            $row['created_at'],
            $row['published_at']
        ]);
    }
    fclose($output);
    exit;
}
