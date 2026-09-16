<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

$cat = trim($_GET['cat'] ?? '');
$q = trim($_GET['q'] ?? '');

$sql = "SELECT id, name, code_name, category, origin_country, unit_assignment, description, image_path, tags FROM alutsista_assets WHERE 1=1";
$params = [];

if ($cat && $cat !== 'Semua') {
    $sql .= " AND category = ?";
    $params[] = $cat;
}

if ($q) {
    $sql .= " AND (name LIKE ? OR code_name LIKE ? OR tags LIKE ? OR description LIKE ? OR unit_assignment LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY is_official DESC, category ASC, name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$results = [];
foreach ($rows as $r) {
    $imgSrc = str_starts_with($r['image_path'], 'http') ? $r['image_path'] : (str_starts_with($r['image_path'], 'assets/') ? BASE_URL . '/' . $r['image_path'] : UPLOAD_URL . $r['image_path']);
    $r['image_url'] = $imgSrc;
    $results[] = $r;
}

echo json_encode(['success' => true, 'data' => $results]);
