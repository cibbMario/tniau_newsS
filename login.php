<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/news_list.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password && login($username, $password)) {
        header("Location: " . BASE_URL . "/news_list.php");
        exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="login-bg">
    <div class="login-box">
        <img src="<?= BASE_URL ?>/assets/img/logo-new.png" alt="TNI AU" class="login-logo" onerror="this.src='<?= BASE_URL ?>/assets/img/logo-tniau.png'">
        <h1>Portal Berita TNI AU</h1>
        <p class="sub">Sistem Monitoring &amp; Manajemen Berita<br>TNI Angkatan Udara — <em>Swa Bhuwana Paksa</em></p>

        <?php if ($error): ?>
            <div class="error-msg"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" style="text-align:left" id="loginForm" onsubmit="showLoginLoader()">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" autofocus required value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required>
            </div>
            <button type="submit" id="loginBtn" class="btn btn-primary btn-block" style="height:42px;font-size:14px;margin-top:8px">
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
        </script>

        <div class="credits">
            Dibuat oleh <strong>Dispen</strong> — &copy; <?= date('Y') ?> TNI Angkatan Udara
        </div>
    </div>
</div>
</body>
</html>
