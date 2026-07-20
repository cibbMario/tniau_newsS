<?php
/**
 * JALANKAN SEKALI SAJA setelah import sql/schema.sql, lalu HAPUS file ini.
 * Membuat 3 akun contoh untuk masing-masing role.
 * Akses lewat browser: http://localhost/tniau-news/setup_users.php
 */
require_once __DIR__ . '/config/db.php';

$defaultPassword = 'password123'; // ganti setelah login pertama kali

$users = [
    ['username' => 'reporter1', 'full_name' => 'Mario',    'role' => 'A'],
    ['username' => 'editor1',   'full_name' => 'saksak',      'role' => 'B'],
    ['username' => 'approver1', 'full_name' => 'kadis', 'role' => 'C'],
];

$hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
$created = [];

foreach ($users as $u) {
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$u['username']]);
    if ($check->fetch()) {
        $created[] = $u['username'] . " (sudah ada, dilewati)";
        continue;
    }
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$u['username'], $hash, $u['full_name'], $u['role']]);
    $created[] = $u['username'] . " (berhasil dibuat)";
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Setup User</title></head>
<body style="font-family: sans-serif; padding: 40px;">
    <h2>Setup Akun Selesai</h2>
    <ul>
        <?php foreach ($created as $c): ?>
            <li><?= htmlspecialchars($c) ?></li>
        <?php endforeach; ?>
    </ul>
    <p><strong>Password default untuk semua akun:</strong> <?= htmlspecialchars($defaultPassword) ?></p>
    <p style="color:red;"><strong>PENTING:</strong> Hapus file <code>setup_users.php</code> ini dari server sekarang, dan segera ganti password.</p>
</body>
</html>
