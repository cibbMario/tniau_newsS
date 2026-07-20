<?php
require_once __DIR__ . "/config/config.php";
requireRole(["A"]);
$user = currentUser();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/news_list.php");
    exit;
}

$newsId = (int)($_POST["news_id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ? AND created_by = ?");
$stmt->execute([$newsId, $user["id"]]);
$news = $stmt->fetch();

if (!$news) {
    header("Location: " . BASE_URL . "/news_list.php");
    exit;
}

if ($news["status"] === "revision_b") {
    updateNewsStatus($newsId, "pending_b", $user["id"], "Reporter (A) telah menyelesaikan revisi dan mengirim ulang ke Editor");
    $editors = $pdo->query("SELECT id FROM users WHERE role = 'B'")->fetchAll();
    foreach ($editors as $ed) {
        $title = $news["title"];
        sendNotification($newsId, $ed["id"], "Berita \"$title\" telah direvisi oleh Reporter dan memerlukan review ulang Anda.");
    }
    header("Location: " . BASE_URL . "/news_view.php?id=$newsId");
    exit;
} elseif ($news["status"] === "revision_c") {
    updateNewsStatus($newsId, "pending_c", $user["id"], "Reporter (A) telah menyelesaikan revisi dan mengirim ulang ke Petinggi");
    $seniors = $pdo->query("SELECT id FROM users WHERE role = 'C'")->fetchAll();
    foreach ($seniors as $sr) {
        $title = $news["title"];
        sendNotification($newsId, $sr["id"], "Berita \"$title\" telah direvisi oleh Reporter dan memerlukan persetujuan ulang Anda.");
    }
    header("Location: " . BASE_URL . "/news_view.php?id=$newsId");
    exit;
} else {
    header("Location: " . BASE_URL . "/news_view.php?id=$newsId");
    exit;
}
