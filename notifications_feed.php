<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$user = currentUser();
$items = getNotifications($user['id']);
$latestItems = array_slice($items, 0, 8);

$output = [];
foreach ($latestItems as $item) {
    $output[] = [
        'id' => (int) $item['id'],
        'news_id' => (int) $item['news_id'],
        'message' => $item['message'],
        'is_read' => (int) $item['is_read'],
        'created_at' => $item['created_at'],
        'news_title' => $item['news_title'] ?? '',
    ];
}

echo json_encode([
    'unread_count' => countUnreadNotifications($user['id']),
    'items' => $output,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
