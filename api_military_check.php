<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

$stmt = $pdo->query("SELECT id, term, expansion, category, correct_standard, is_outdated, notes FROM military_dictionary ORDER BY is_outdated DESC, LENGTH(term) DESC");
$terms = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $terms]);
