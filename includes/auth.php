<?php
/**
 * Fungsi-fungsi autentikasi & proteksi akses per-role
 */

function login($username, $password) {
    global $pdo;

    if (!empty($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > time()) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        return true;
    }

    $attempts = (int)($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_attempts'] = $attempts;

    if ($attempts >= 5) {
        $_SESSION['login_locked_until'] = time() + 300;
    }

    return false;
}

function logout() {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function refreshSessionActivity() {
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
    }
}

function sessionExpired() {
    if (!isset($_SESSION['user_id'])) {
        return true;
    }

    $now = time();
    $idleTimeout = SESSION_IDLE_TIMEOUT ?? 1800;
    $absoluteTimeout = SESSION_ABSOLUTE_TIMEOUT ?? 3600;

    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $idleTimeout) {
        logout();
        return true;
    }

    if (isset($_SESSION['login_time']) && ($now - $_SESSION['login_time']) > $absoluteTimeout) {
        logout();
        return true;
    }

    return false;
}

function isLoggedIn() {
    return !sessionExpired() && isset($_SESSION['user_id']);
}

function isLoginLocked() {
    return !empty($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > time();
}

function currentUser() {
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role'      => $_SESSION['role']      ?? null,
    ];
}

/** Panggil di atas setiap halaman yang butuh login */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }

    refreshSessionActivity();
}

/** Panggil untuk membatasi halaman hanya untuk role tertentu, mis: requireRole(['A']) */
function requireRole(array $roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}
