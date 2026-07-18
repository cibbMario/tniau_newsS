<?php
/**
 * Kumpulan fungsi bantuan:
 * - Notifikasi
 * - Perubahan status berita + history
 * - Upload gambar
 * - Label status untuk tampilan
 */

/* =========================================================
   NOTIFIKASI
   ========================================================= */

function sendNotification($news_id, $user_id, $message) {
    global $pdo;
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, news_id, message) VALUES (?, ?, ?)"
    );
    $stmt->execute([$user_id, $news_id, $message]);
}

function getNotifications($user_id, $onlyUnread = false) {
    global $pdo;
    $sql = "SELECT n.*, news.title AS news_title
            FROM notifications n
            JOIN news ON news.id = n.news_id
            WHERE n.user_id = ?";
    if ($onlyUnread) $sql .= " AND n.is_read = 0";
    $sql .= " ORDER BY n.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function countUnreadNotifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

function markNotificationRead($notif_id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
}

function markAllNotificationsRead($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

/* =========================================================
   BERITA: perubahan status + history log
   ========================================================= */

function updateNewsStatus($news_id, $newStatus, $actor_user_id, $note = null) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT status FROM news WHERE id = ?");
    $stmt->execute([$news_id]);
    $oldStatus = $stmt->fetchColumn();

    $publishedAt = $newStatus === 'published' ? ", published_at = NOW()" : "";
    $stmt = $pdo->prepare("UPDATE news SET status = ? $publishedAt WHERE id = ?");
    $stmt->execute([$newStatus, $news_id]);

    $stmt = $pdo->prepare(
        "INSERT INTO news_history (news_id, user_id, status_from, status_to, note)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$news_id, $actor_user_id, $oldStatus, $newStatus, $note]);
}

/** Label status yang mudah dibaca */
function statusLabel($status) {
    $labels = [
        'draft'      => 'Draft',
        'pending_b'  => 'Menunggu Review Editor',
        'revision_b' => 'Perlu Revisi (dari Editor)',
        'pending_c'  => 'Menunggu Persetujuan Petinggi',
        'revision_c' => 'Perlu Revisi (dari Petinggi)',
        'published'  => 'Sudah Dipublikasikan',
    ];
    return $labels[$status] ?? $status;
}

function statusBadgeClass($status) {
    $classes = [
        'draft'      => 'badge-gray',
        'pending_b'  => 'badge-blue',
        'revision_b' => 'badge-red',
        'pending_c'  => 'badge-blue',
        'revision_c' => 'badge-red',
        'published'  => 'badge-green',
    ];
    return $classes[$status] ?? 'badge-gray';
}

/* =========================================================
   UPLOAD GAMBAR
   ========================================================= */

function uploadNewsImage($fileInputName) {
    if (empty($_FILES[$fileInputName]['name'])) return null;

    $file = $_FILES[$fileInputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload gagal (kode error: {$file['error']})");
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new Exception("Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.");
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception("Ukuran file maksimal 5MB.");
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = 'news_' . uniqid() . '_' . time() . '.' . $ext;
    $destination = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Gagal menyimpan file ke server.");
    }

    return $filename;
}

/* =========================================================
   UTIL
   ========================================================= */

function generateSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') . '-' . substr(uniqid(), -5);
}

function formatTanggal($datetime) {
    $bulan = [
        1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
        7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'
    ];
    $ts = strtotime($datetime);
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y H:i', $ts);
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
