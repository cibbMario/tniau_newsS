<?php
/**
 * Fungsi-fungsi autentikasi & proteksi akses per-role
 */

function login($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        return true;
    }
    return false;
}

function logout() {
    $_SESSION = [];
    session_destroy();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
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
}

/** Panggil untuk membatasi halaman hanya untuk role tertentu, mis: requireRole(['A']) */
function requireRole(array $roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}
