<?php
/**
 * Fungsi-fungsi autentikasi & proteksi akses per-role
 *
 * Brute-force protection: database-backed per IP+username.
 * Maks 5 percobaan gagal → kunci 5 menit.
 * Percobaan lama (> 15 menit) dibersihkan otomatis.
 */

// ---------------------------------------------------------------------------
// Brute-Force Helpers (DB-based)
// ---------------------------------------------------------------------------

/**
 * Pastikan tabel login_attempts ada (auto-create jika belum).
 */
function ensureLoginAttemptsTable(): void {
    global $pdo;
    static $checked = false;
    if ($checked) return;
    $checked = true;

    // Cek apakah tabel login_attempts masih menggunakan tipe lama (TIMESTAMP).
    // Jika iya, drop tabel agar di-recreate menggunakan tipe INT.
    try {
        $q = $pdo->query("SHOW COLUMNS FROM `login_attempts` LIKE 'attempted_at'");
        $col = $q->fetch();
        if ($col && strpos(strtolower($col['Type']), 'timestamp') !== false) {
            $pdo->exec("DROP TABLE IF EXISTS `login_attempts`");
        }
    } catch (Exception $e) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
        `id`         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `ip`         VARCHAR(45) NOT NULL,
        `username`   VARCHAR(100) NOT NULL,
        `attempted_at` INT NOT NULL,
        INDEX `idx_ip_user` (`ip`, `username`),
        INDEX `idx_time` (`attempted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * IP address pengunjung (handle proxy).
 */
function getClientIp(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * Bersihkan percobaan lama (> 15 menit).
 */
function cleanOldAttempts(): void {
    global $pdo;
    ensureLoginAttemptsTable();
    $fifteenMinutesAgo = time() - 900;
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
    $stmt->execute([$fifteenMinutesAgo]);
}

/**
 * Apakah IP ini sedang dikunci?
 * Kunci berlaku jika ada >= 5 percobaan gagal dalam 5 menit terakhir.
 * Mengembalikan sisa waktu kunci (detik), atau 0 jika tidak terkunci.
 */
function getLoginLockSeconds(string $ip, string $username = ''): int {
    global $pdo;
    ensureLoginAttemptsTable();

    // Hitung menggunakan Unix timestamp murni (detik) untuk memotong masalah timezone
    $fiveMinutesAgo = time() - 300;
    $stmt = $pdo->prepare(
        "SELECT COUNT(*), MIN(attempted_at) FROM login_attempts
         WHERE ip = ? AND attempted_at >= ?"
    );
    $stmt->execute([$ip, $fiveMinutesAgo]);
    [$count, $oldest] = $stmt->fetch(\PDO::FETCH_NUM);

    if ((int)$count >= 5 && $oldest) {
        $lockEnd = (int)$oldest + 300; // Kunci bertahan 5 menit sejak percobaan pertama dari rentang ini
        $remaining = $lockEnd - time();
        if ($remaining > 0) return (int)$remaining;
    }
    return 0;
}

/**
 * Catat percobaan login gagal.
 */
function recordFailedAttempt(string $ip, string $username): void {
    global $pdo;
    ensureLoginAttemptsTable();
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip, username, attempted_at) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $username, time()]);
}

/**
 * Hapus catatan percobaan gagal setelah login berhasil.
 */
function clearLoginAttempts(string $ip): void {
    global $pdo;
    ensureLoginAttemptsTable();
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
}

// ---------------------------------------------------------------------------
// Login / Logout
// ---------------------------------------------------------------------------

function login(string $username, string $password): bool {
    global $pdo;

    $ip = getClientIp();

    // Cek kunci DB-based
    if (getLoginLockSeconds($ip, $username) > 0) {
        return false;
    }

    // Cek kunci session-based (fallback lama, tetap dipertahankan)
    if (!empty($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > time()) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && isset($user['is_active']) && (int)$user['is_active'] === 0) {
        // Akun nonaktif
        return false;
    }

    if ($user && password_verify($password, $user['password_hash'])) {
        // Login berhasil — bersihkan semua catatan gagal
        clearLoginAttempts($ip);
        cleanOldAttempts();
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);

        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['lanud']         = $user['lanud'] ?? 'Lanud Atang Sendjaja';
        $_SESSION['login_time']    = time();
        $_SESSION['last_activity'] = time();

        if (function_exists('logAudit')) {
            logAudit('LOGIN', 'User ' . $user['username'] . ' (' . $user['full_name'] . ') berhasil login', $user['id']);
        }

        return true;
    }

    // Login gagal — catat ke DB
    recordFailedAttempt($ip, $username);
    if (function_exists('logAudit')) {
        logAudit('LOGIN_FAILED', 'Gagal login untuk username: ' . $username, null);
    }

    // Fallback session counter (untuk kompatibilitas)
    $attempts = (int)($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_attempts'] = $attempts;
    if ($attempts >= 5) {
        $_SESSION['login_locked_until'] = time() + 300;
    }

    return false;
}

function logout(): void {
    if (isset($_SESSION['user_id']) && function_exists('logAudit')) {
        logAudit('LOGOUT', 'User logout dari sesi', $_SESSION['user_id']);
    }
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

// ---------------------------------------------------------------------------
// Session Management
// ---------------------------------------------------------------------------

function refreshSessionActivity(): void {
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
    }
}

function sessionExpired(): bool {
    if (!isset($_SESSION['user_id'])) return true;

    $now             = time();
    $idleTimeout     = SESSION_IDLE_TIMEOUT     ?? 1800;
    $absoluteTimeout = SESSION_ABSOLUTE_TIMEOUT ?? 3600;

    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $idleTimeout) {
        logout(); return true;
    }
    if (isset($_SESSION['login_time']) && ($now - $_SESSION['login_time']) > $absoluteTimeout) {
        logout(); return true;
    }
    return false;
}

function isLoggedIn(): bool {
    return !sessionExpired() && isset($_SESSION['user_id']);
}

/**
 * Cek kunci dari dua sumber: session (lama) dan DB (baru).
 * Mengembalikan sisa detik kunci, atau 0 jika tidak dikunci.
 */
function isLoginLocked(): int {
    $ip = getClientIp();
    $dbLock = getLoginLockSeconds($ip);
    if ($dbLock > 0) return $dbLock;

    if (!empty($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > time()) {
        return (int)($_SESSION['login_locked_until'] - time());
    }
    return 0;
}

function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? null,
        'full_name' => userDisplayName($_SESSION['role'] ?? null),
        'role'      => $_SESSION['role']      ?? null,
    ];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
    refreshSessionActivity();
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}
