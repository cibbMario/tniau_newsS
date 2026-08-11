<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();

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
