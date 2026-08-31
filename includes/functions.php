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
                        JOIN users ON users.id = n.user_id
                        WHERE n.user_id = ?
                            AND (users.role <> 'B' OR news.wilayah = users.lanud)";
    if ($onlyUnread) $sql .= " AND n.is_read = 0";
    $sql .= " ORDER BY n.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function countUnreadNotifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*)
        FROM notifications n
        JOIN news ON news.id = n.news_id
        JOIN users ON users.id = n.user_id
        WHERE n.user_id = ? AND n.is_read = 0
          AND (users.role <> 'B' OR news.wilayah = users.lanud)");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

function countDraftsForUser($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE created_by = ? AND status = 'draft'");
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

    // Safety check to ensure table column supports longer status strings
    try {
        static $colAltered = false;
        if (!$colAltered && isset($pdo)) {
            $colAltered = true;
            $pdo->exec("ALTER TABLE news MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'draft'");
        }
    } catch (Exception $e) {}

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
        'pending_b'  => 'Menunggu Peninjauan Editor',
        'revision_b' => 'Perlu Revisi (dari Editor)',
        'pending_c'  => 'Menunggu Persetujuan (Approver)',
        'revision_c' => 'Perlu Revisi (dari Penyetuju)',
        'pending_d'  => 'Menunggu Peninjauan Kejelasan',
        'revision_d' => 'Perlu Revisi (dari Peninjau Kejelasan)',
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
        'pending_d'  => 'badge-yellow',
        'revision_d' => 'badge-red',
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

function timeAgo($datetime) {
    if (!$datetime) return '-';
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return formatTanggal($datetime);
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function userDisplayName($role) {
    $labels = [
        'A' => 'Reporter',
        'B' => 'Editor',
        'C' => 'Penyetuju (Approver)',
        'D' => 'Peninjau Kejelasan',
        'E' => 'Administrator',
    ];
    return $labels[$role] ?? 'Pengguna';
}

/* =========================================================
   CSRF Helpers
   ========================================================= */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate Lanud initials for author_label
 */
function getLanudInitials($lanudName) {
    return 'Pusat';
}

/* =========================================================
   AUDIT LOGGING
   ========================================================= */
function logAudit($action, $details = null, $userId = null) {
    global $pdo;
    try {
        if ($userId === null && function_exists('currentUser')) {
            $u = currentUser();
            $userId = $u['id'] ?? null;
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        // fail silently for audit
    }
}

