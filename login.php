<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    $u = currentUser();
    if (($u['role'] ?? '') === 'D') {
        header("Location: " . BASE_URL . "/user_d_dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/news_list.php");
    }
    exit;
}

$csrfToken   = generate_csrf_token();
$error       = '';
$lockSeconds = 0;
$postedUser  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['csrf_token'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $postedUser = $username;

    if (!verify_csrf_token($token)) {
        $error = 'Sesi login tidak valid. Silakan refresh halaman dan coba lagi.';
    } elseif (($lockSeconds = isLoginLocked()) > 0) {
        $error = 'Terlalu banyak percobaan login gagal. Akun sementara dikunci.';
    } elseif ($username && $password && login($username, $password)) {
        $u = currentUser();
        if (($u['role'] ?? '') === 'D') {
            header("Location: " . BASE_URL . "/user_d_dashboard.php");
        } else {
            header("Location: " . BASE_URL . "/news_list.php");
        }
        exit;
    } else {
        if (!$username || !$password) {
            $error = 'Username dan password wajib diisi.';
        } else {
            $lockSeconds = isLoginLocked();
            if ($lockSeconds > 0) {
                $error = 'Terlalu banyak percobaan login gagal. Akun sementara dikunci.';
            } else {
                $error = 'Username atau password salah.';
            }
        }
    }
} else {
    // Cek kunci saat halaman dibuka (bukan hanya setelah POST)
    $lockSeconds = isLoginLocked();
    if ($lockSeconds > 0) {
        $error = 'IP Anda sementara dikunci karena terlalu banyak percobaan login gagal.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="login-bg">
    <div class="login-box">
        <img src="<?= BASE_URL ?>/assets/img/logo-tniau-transparent.png" alt="TNI AU" class="login-logo">
        <h1>Portal Berita TNI AU</h1>
        <p class="sub">Sistem Monitoring &amp; Manajemen Berita<br>TNI Angkatan Udara <em>Swa Bhuwana Paksa</em></p>

        <?php if ($error): ?>
            <div class="error-msg" id="errorMsg"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($lockSeconds > 0): ?>
            <div class="error-msg" id="lockMsg" style="background:#fff3cd; color:#856404; border-color:#ffeeba;">
                Akun/IP Anda dikunci sementara. Silakan tunggu <span id="countdown"><?= $lockSeconds ?></span> detik.
            </div>
        <?php endif; ?>

        <form method="POST" style="text-align:left" id="loginForm" onsubmit="showLoginLoader()">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" autofocus required value="<?= e($postedUser) ?>" <?= $lockSeconds > 0 ? 'disabled' : '' ?>>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required <?= $lockSeconds > 0 ? 'disabled' : '' ?>>
            </div>
            <button type="submit" id="loginBtn" class="btn btn-primary btn-block" style="height:42px;font-size:14px;margin-top:8px" <?= $lockSeconds > 0 ? 'disabled' : '' ?>>
                <span id="loginBtnText">Masuk</span>
                <span id="loginBtnLoader" style="display:none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite">
                        <line x1="12" y1="2" x2="12" y2="6"></line>
                        <line x1="12" y1="18" x2="12" y2="22"></line>
                        <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                        <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                        <line x1="2" y1="12" x2="6" y2="12"></line>
                        <line x1="18" y1="12" x2="22" y2="12"></line>
                        <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                        <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                    </svg>
                    Memproses...
                </span>
            </button>
        </form>
        <style>
        @keyframes spin { 100% { transform: rotate(360deg); } }
        </style>
        <script>
        function showLoginLoader() {
            document.getElementById('loginBtnText').style.display = 'none';
            document.getElementById('loginBtnLoader').style.display = 'inline-flex';
            document.getElementById('loginBtn').disabled = true;
        }

        <?php if ($lockSeconds > 0): ?>
        (function() {
            var seconds = <?= $lockSeconds ?>;
            var countdownEl = document.getElementById('countdown');
            var timer = setInterval(function() {
                seconds--;
                if (seconds <= 0) {
                    clearInterval(timer);
                    var lockMsg = document.getElementById('lockMsg');
                    if (lockMsg) lockMsg.style.display = 'none';
                    var errorMsg = document.getElementById('errorMsg');
                    if (errorMsg) errorMsg.style.display = 'none';
                    document.getElementById('username').disabled = false;
                    document.getElementById('password').disabled = false;
                    document.getElementById('loginBtn').disabled = false;
                } else {
                    if (countdownEl) countdownEl.textContent = seconds;
                }
            }, 1000);
        })();
        <?php endif; ?>
        </script>

        <div class="credits">
            Created by Mario & Saksak &copy; <?= date('Y') ?> TNI Angkatan Udara
        </div>
    </div>
</div>
</body>
</html>
