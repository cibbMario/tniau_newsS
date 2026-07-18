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
        <h2>Portal Berita TNI AU</h2>
        <p class="sub">Sistem Monitoring &amp; Manajemen Berita<br>TNI Angkatan Udara — <em>Swa Bhuwana Paksa</em></p>

        <?php if ($error): ?>
            <div class="error-msg"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" style="text-align:left">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" autofocus required value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="height:42px;font-size:14px;margin-top:8px">Masuk</button>
        </form>

        <div class="credits">
            Dibuat oleh <strong>onebox</strong> — &copy; <?= date('Y') ?> TNI Angkatan Udara
        </div>
    </div>
</div>
</body>
</html>
