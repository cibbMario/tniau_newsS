<?php
require_once __DIR__ . "/config/config.php";
requireRole(["A", "E"]);
$user = currentUser();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/news_list.php");
    exit;
}

if (!verify_csrf_token($_POST["csrf_token"] ?? "")) {
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

$title = $news["title"];

if ($news["status"] === "revision_b") {
    updateNewsStatus($newsId, "pending_b", $user["id"], "Reporter telah menyelesaikan revisi dan mengirim ulang ke Editor");
    $targetStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'B' AND lanud = ?");
    $targetStmt->execute([$news['wilayah']]);
    $targetUsers = $targetStmt->fetchAll();
    foreach ($targetUsers as $uTarget) {
        sendNotification($newsId, $uTarget["id"], "Berita \"$title\" telah direvisi oleh Reporter dan memerlukan review ulang Editor.");
    }
} elseif ($news["status"] === "revision_c") {
    updateNewsStatus($newsId, "pending_c", $user["id"], "Reporter telah menyelesaikan revisi dan mengirim ulang ke Penyetuju");
    $targetUsers = $pdo->query("SELECT id FROM users WHERE role = 'C'")->fetchAll();
    foreach ($targetUsers as $uTarget) {
        sendNotification($newsId, $uTarget["id"], "Berita \"$title\" telah direvisi oleh Reporter dan memerlukan persetujuan ulang Penyetuju.");
    }
} elseif ($news["status"] === "revision_d") {
    updateNewsStatus($newsId, "pending_d", $user["id"], "Reporter telah menyelesaikan revisi dan mengirim ulang ke Peninjau Kejelasan");
    $targetUsers = $pdo->query("SELECT id FROM users WHERE role = 'D'")->fetchAll();
    foreach ($targetUsers as $uTarget) {
        sendNotification($newsId, $uTarget["id"], "Berita \"$title\" telah direvisi oleh Reporter dan memerlukan persetujuan ulang Peninjau Kejelasan.");
    }
}

header("Location: " . BASE_URL . "/news_view.php?id=$newsId");
exit;

